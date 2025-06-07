<?php
require 'db.php';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = ? AND criado_por = ?");
    $stmt->execute([(int)$_GET['id'], $_SESSION['usuario_id']]);
}

header('Location: index.php');
exit;
