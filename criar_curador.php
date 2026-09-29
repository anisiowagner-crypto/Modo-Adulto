<?php
require 'conexao.php'; // Inclui a ligação ao banco de dados

// Defina aqui os dados do curador que deseja criar
$login = 'juananisio';
$email = 'juananisio@modoadulto.com';
$senha = '123'; // Escolha a palavra-passe desejada

// A função password_hash cria a criptografia segura exigida pelo login.php
$senhaCriptografada = password_hash($senha, PASSWORD_DEFAULT);

try {
    // Insere os dados na tabela curador
    $sql = "INSERT INTO curador (login, email, senha) VALUES (:login, :email, :senha)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':login', $login);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senhaCriptografada);
    
    $stmt->execute();
    
    echo "<h1>Curador criado com sucesso!</h1>";
    echo "<p>Login: " . htmlspecialchars($login) . "</p>";
    echo "<p>Pode agora tentar fazer o login.</p>";
    
} catch (PDOException $e) {
    echo "Erro ao criar curador (pode já existir no banco de dados): " . $e->getMessage();
}
?>