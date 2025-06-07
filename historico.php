<?php
require 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$condicoes = [];
$params = [];

// Filtrando pela descrição da tarefa
if (!empty($_GET['pesquisa'])) {
    $condicoes[] = 't.descricao LIKE ?';
    $params[] = '%' . $_GET['pesquisa'] . '%';
}

// Filtrando pelo nome do criador
if (!empty($_GET['criador'])) {
    $condicoes[] = 'u.nome LIKE ?';
    $params[] = '%' . $_GET['criador'] . '%';
}

// Filtrando pelo nome do finalizador
if (!empty($_GET['concluidor'])) {
    $condicoes[] = 'u2.nome LIKE ?';
    $params[] = '%' . $_GET['concluidor'] . '%';
}

// Filtrando pela data de criação
if (!empty($_GET['data_criacao'])) {
    $condicoes[] = 'DATE(t.data_criacao) = ?';
    $params[] = $_GET['data_criacao'];
}

// Filtrando pela prioridade
if (!empty($_GET['prioridade'])) {
    $condicoes[] = 't.prioridade = ?';
    $params[] = $_GET['prioridade'];
}

// Filtrando apenas tarefas concluídas
$condicoes[] = 't.concluida = 1';  // Exibir apenas tarefas concluídas

$where = '';
if (!empty($condicoes)) {
    $where = 'WHERE ' . implode(' AND ', $condicoes);
}

$stmt = $pdo->prepare("SELECT t.*, u.nome AS criador, u2.nome AS finalizador
                      FROM tarefas t
                      LEFT JOIN usuarios u ON t.criado_por = u.id
                      LEFT JOIN usuarios u2 ON t.concluido_por = u2.id
                      $where
                      ORDER BY t.data_criacao DESC");
$stmt->execute($params);
$tarefas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Tarefas</title>
    <link rel="stylesheet" href="CSS/style.css"> 
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <h1>Histórico de Tarefas</h1>
    </header>

    <div class="container">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Voltar</a>

        <h2>Filtros de Pesquisa</h2>
        <form method="GET" action="historico.php">
            <input type="text" name="pesquisa" placeholder="Descrição da tarefa" value="<?php echo isset($_GET['pesquisa']) ? htmlspecialchars($_GET['pesquisa']) : ''; ?>">
            <input type="text" name="criador" placeholder="Nome do criador" value="<?php echo isset($_GET['criador']) ? htmlspecialchars($_GET['criador']) : ''; ?>">
            <input type="text" name="concluidor" placeholder="Nome de quem concluiu" value="<?php echo isset($_GET['concluidor']) ? htmlspecialchars($_GET['concluidor']) : ''; ?>">
            <input type="date" name="data_criacao" value="<?php echo isset($_GET['data_criacao']) ? htmlspecialchars($_GET['data_criacao']) : ''; ?>">

            <select name="prioridade">
                <option value="">Todas Prioridades</option>
                <option value="Alta" <?php echo (isset($_GET['prioridade']) && $_GET['prioridade'] == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                <option value="Média" <?php echo (isset($_GET['prioridade']) && $_GET['prioridade'] == 'Média') ? 'selected' : ''; ?>>Média</option>
                <option value="Baixa" <?php echo (isset($_GET['prioridade']) && $_GET['prioridade'] == 'Baixa') ? 'selected' : ''; ?>>Baixa</option>
            </select>

            <button type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="historico.php"><i class="fas fa-times-circle"></i> Limpar filtros</a>
        </form>

        <h2>Resultados</h2>

        <?php if (count($tarefas) > 0): ?>
        <ul>
            <?php foreach($tarefas as $tarefa): ?>
                <li>
                    [<?php echo $tarefa['data_criacao']; ?>] 
                    Criado por <?php echo htmlspecialchars($tarefa['criador']); ?>: 
                    <?php echo htmlspecialchars($tarefa['descricao']); ?>
                    (Prazo: <?php echo $tarefa['prazo']; ?> | Prioridade: <span class="prioridade"><?php echo $tarefa['prioridade']; ?></span>)
                    <?php if ($tarefa['concluida']): ?>
                        - <span class="status concluida">Concluída por <?php echo htmlspecialchars($tarefa['finalizador']); ?>
                        <?php if (!empty($tarefa['data_conclusao'])): ?>
                            em <?php echo date('d/m/Y H:i', strtotime($tarefa['data_conclusao'])); ?>
                        <?php else: ?>
                            (data de conclusão não disponível)
                        <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <p><strong>❗ Nenhuma tarefa encontrada com os filtros informados.</strong></p>
        <?php endif; ?>
    </div>
</body>
</html>
