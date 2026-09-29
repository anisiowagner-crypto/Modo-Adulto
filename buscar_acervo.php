<?php
require 'conexao.php';
header('Content-Type: application/json');

$query = isset($_GET['q']) ? $_GET['q'] : '';

// Busca os conteúdos associando aos nichos (A busca procura no título, descrição e nome do nicho)
$sql = "
    SELECT c.id, c.titulo, c.descricao, c.tipo_midia, n.nome AS nicho_nome 
    FROM conteudo c
    INNER JOIN nicho n ON c.id_nicho = n.id_nicho
    WHERE c.titulo LIKE :busca 
       OR c.descricao LIKE :busca 
       OR n.nome LIKE :busca
    ORDER BY c.data_publicacao DESC
";

$stmt = $pdo->prepare($sql);
$buscaParam = "%" . $query . "%";
$stmt->bindParam(':busca', $buscaParam);
$stmt->execute();

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($resultados);
?>