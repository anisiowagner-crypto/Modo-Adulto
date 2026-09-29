<?php
$host = 'localhost';
$dbname = 'modo_adulto';
$user = 'root'; // Altere para seu usuário MySQL
$pass = ''; // Altere para sua senha MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
?>