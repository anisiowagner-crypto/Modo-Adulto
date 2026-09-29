<?php
session_start();
// Verifica se o curador está logado. Se não, manda de volta para o login.
if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head><title>Painel Curador</title></head>
<body>
    <h1>Bem-vindo ao Painel Administrativo!</h1>
    <p>Login efetuado com sucesso.</p>
    <a href="logout.php">Sair</a>
</body>
</html>