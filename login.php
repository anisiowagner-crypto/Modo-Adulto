<?php
session_start();
require 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $senha = trim($_POST['senha']);

    if (empty($login) || empty($senha)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $sql = "SELECT id, senha FROM curador WHERE login = :login OR email = :login";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        
        $curador = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validação da senha (assumindo uso de password_hash no cadastro)
        if ($curador && password_verify($senha, $curador['senha'])) {
            $_SESSION['curador_id'] = $curador['id'];
            // Inicia sessão administrativa segura (Classe SessaoUsuario/SessaoCurador)
            header("Location: painel_curador.php"); 
            exit;
        } else {
            $erro = "Login ou senha inválidos. Tente novamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login Curador - Modo Adulto</title>
    <style>
        /* Reutilizando as variáveis da temática */
        body { background-color: #e6f0fa; color: #333; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-top: 4px solid #0056b3; width: 300px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;}
        button { width: 100%; padding: 10px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;}
        .erro { color: red; font-size: 0.9rem; margin-bottom: 10px;}
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Acesso do Curador</h2>
        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="text" name="login" placeholder="Email ou Usuário" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <button type="submit">Entrar</button>
        </form>
        <a href="index.html" style="display:block; margin-top:15px; color:#0056b3; text-decoration:none;">Voltar ao Site</a>
    </div>
</body>
</html>