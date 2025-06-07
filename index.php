<?php
require 'db.php';

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Redirecionar se não estiver logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Atualizar tarefas concluídas sem data
$pdo->prepare("UPDATE tarefas SET data_conclusao = data_criacao WHERE concluida = 1 AND data_conclusao IS NULL")->execute();

// Lógica de concluir tarefa
if (isset($_GET['concluir_id'])) {
    $tarefa_id = (int)$_GET['concluir_id'];
    $stmt = $pdo->prepare("UPDATE tarefas SET concluida = 1, concluido_por = ?, data_conclusao = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id'], $tarefa_id]);
    header('Location: index.php');
    exit;
}

// Lógica de editar tarefa
if (isset($_POST['editar_id'])) {
    $id = (int) $_POST['editar_id'];
    $descricao = $_POST['descricao'];
    $prazo = $_POST['prazo'];
    $prioridade = $_POST['prioridade'];

    $stmt = $pdo->prepare("UPDATE tarefas SET descricao = ?, prazo = ?, prioridade = ? WHERE id = ?");
    $stmt->execute([$descricao, $prazo, $prioridade, $id]);
    header('Location: index.php');
    exit;
}

// Filtros de tarefas
$condicoes = ['t.concluida = 0'];
$params = [];

if (isset($_GET['status']) && $_GET['status'] === 'pendentes') {
    $condicoes[] = 't.prazo < CURDATE()';
}

$query = "SELECT t.*, u.nome AS criador, u2.nome AS finalizador
          FROM tarefas t
          LEFT JOIN usuarios u ON t.criado_por = u.id
          LEFT JOIN usuarios u2 ON t.concluido_por = u2.id
          WHERE " . implode(' AND ', $condicoes) . " 
          ORDER BY t.prazo ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Criar nova tarefa
if (isset($_POST['descricao']) && !isset($_POST['editar_id'])) {
    $descricao = $_POST['descricao'];
    $prazo = $_POST['prazo'];
    $prioridade = $_POST['prioridade'];

    $dataAtual = date('Y-m-d');
    if ($prazo < $dataAtual) {
        $erro = "Erro: Não é possível criar uma tarefa com prazo no passado.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tarefas (descricao, prazo, prioridade, criado_por) VALUES (?, ?, ?, ?)");
        $stmt->execute([$descricao, $prazo, $prioridade, $_SESSION['usuario_id']]);
        header('Location: index.php');
        exit;
    }
}
?>

<!-- Estilos -->
<link rel="stylesheet" href="CSS/stylee.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<div class="container">
    <div class="header-top">
        <h1>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h1>
        <a href="logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    </div>

    <h2>Nova Tarefa</h2>

    <?php if (isset($erro)): ?>
        <p class="erro"><?php echo $erro; ?></p>
    <?php endif; ?>

    <form action="index.php" method="POST" class="form-tarefa">
        <input type="text" name="descricao" placeholder="Descrição" required>
        <input type="date" name="prazo" required>
        <select name="prioridade" required>
            <option value="Alta">Alta</option>
            <option value="Média" selected>Média</option>
            <option value="Baixa">Baixa</option>
        </select>
        <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i> Adicionar</button>
    </form>

    <h2>Todas as Tarefas</h2>
    <div class="filtros">
        <a href="index.php" class="btn-filtro"><i class="fa-solid fa-list"></i> Todas</a> 
        <a href="index.php?status=pendentes" class="btn-filtro"><i class="fa-solid fa-hourglass-end"></i> Pendentes</a> 
        <a href="historico.php" class="btn-historico"><i class="fa-solid fa-clock-rotate-left"></i> Histórico</a>
    </div>

    <table class="tabela-tarefas">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Prazo</th>
                <th>Prioridade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tarefas as $tarefa): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                    <td>
                        <?php
                        $dataAtual = date('Y-m-d');
                        if (!$tarefa['concluida'] && $tarefa['prazo'] < $dataAtual) {
                            echo '<span style="color: red; font-weight: bold;">Pendente</span>';
                        } else {
                            echo htmlspecialchars($tarefa['prazo']);
                        }
                        ?>
                    </td>
                    <td style="color: <?php echo ($tarefa['prioridade'] === 'Alta' ? 'red' : ($tarefa['prioridade'] === 'Média' ? 'orange' : 'green')); ?>">
                        <?php echo htmlspecialchars($tarefa['prioridade']); ?>
                    </td>
                    <td class="action-buttons">
                        <?php if (!$tarefa['concluida']): ?>
                            <a class="btn concluir" href="javascript:void(0);" onclick="confirmarConclusao(<?php echo $tarefa['id']; ?>)">
                                <i class="fa-solid fa-check"></i> Concluir
                            </a>
                            <?php if ($tarefa['criado_por'] == $_SESSION['usuario_id']): ?>
                                <a class="btn editar" href="javascript:void(0);" onclick='abrirModalEdicao(<?php echo json_encode($tarefa); ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </a>
                                <a class="btn excluir" href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $tarefa['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i> Excluir
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge-concluida"><i class="fa-solid fa-check-double"></i> Concluída</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal de confirmação -->
<div id="modal" class="modal">
  <div class="modal-content">
    <p id="modal-text">Você tem certeza?</p>
    <button id="confirm-btn" class="btn-confirm"><i class="fa-solid fa-circle-check"></i> Confirmar</button>
    <button onclick="closeModal()" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancelar</button>
  </div>
</div>

<!-- Modal de edição -->
<div id="modal-editar" class="modal">
  <div class="modal-content">
    <h2>Editar Tarefa</h2>
    <form method="POST" action="index.php" class="form-editar">
        <input type="hidden" id="editar_id" name="editar_id">
        <input type="text" id="editar_descricao" name="descricao" placeholder="Descrição" required>
        <input type="date" id="editar_prazo" name="prazo" required>
        <select id="editar_prioridade" name="prioridade" required>
            <option value="Alta">Alta</option>
            <option value="Média">Média</option>
            <option value="Baixa">Baixa</option>
        </select>
        <div class="botoes-modal">
            <button type="submit" class="btn-confirm"><i class="fa-solid fa-floppy-disk"></i> Salvar</button>
            <button type="button" onclick="closeModalEdicao()" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancelar</button>
        </div>
    </form>
  </div>
</div>

<script>
let actionUrl = '';

function openModal(url, message) {
    actionUrl = url;
    document.getElementById('modal-text').innerText = message;
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
}

document.getElementById('confirm-btn').onclick = () => window.location.href = actionUrl;

function confirmarConclusao(id) {
    openModal('index.php?concluir_id=' + id, 'Deseja concluir esta tarefa?');
}

function confirmarExclusao(id) {
    openModal('excluir.php?id=' + id, 'Deseja excluir esta tarefa?');
}

function abrirModalEdicao(tarefa) {
    document.getElementById('editar_id').value = tarefa.id;
    document.getElementById('editar_descricao').value = tarefa.descricao;
    document.getElementById('editar_prazo').value = tarefa.prazo;
    document.getElementById('editar_prioridade').value = tarefa.prioridade;
    document.getElementById('modal-editar').style.display = 'block';
}

function closeModalEdicao() {
    document.getElementById('modal-editar').style.display = 'none';
}
</script>