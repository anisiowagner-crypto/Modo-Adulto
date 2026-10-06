<?php
session_start();
require 'conexao.php';

// Segurança: Garante autenticação prévia (RN2)
if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        // Exclui o conteúdo (as Chaves Estrangeiras com ON DELETE CASCADE eliminam os registros vinculados)
        $stmt = $pdo->prepare("DELETE FROM conteudo WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("Erro ao remover conteúdo: " . $e->getMessage());
    }
}

// Redireciona de volta para o painel principal
header("Location: painel_curador.php");
exit;
?>