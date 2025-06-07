<?php
require 'db.php';
date_default_timezone_set('America/Sao_Paulo');

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int) $_POST['id'];
    $descricao = $_POST['descricao'];
    $prazo = $_POST['prazo'];
    $concluida = isset($_POST['concluida']) ? 1 : 0;
    $concluido_por = $concluida ? $_SESSION['usuario_id'] : NULL;
    $data_conclusao = $concluida ? date('Y-m-d H:i:s') : NULL;

    $stmt = $pdo->prepare("UPDATE tarefas SET descricao = ?, prazo = ?, concluida = ?, concluido_por = ?, data_conclusao = ? WHERE id = ?");
    $stmt->execute([$descricao, $prazo, $concluida, $concluido_por, $data_conclusao, $id]);

    header('Location: index.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>
