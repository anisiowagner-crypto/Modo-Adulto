<?php
require 'conexao.php';
header('Content-Type: application/json; charset=utf-8');

$query = isset($_GET['q']) ? $_GET['q'] : '';

// Consulta completa trazendo os campos específicos das mídias
$sql = "
    SELECT 
        c.id, 
        c.titulo, 
        c.descricao, 
        c.tipo_midia, 
        c.data_publicacao,
        n.nome AS nicho_nome,
        v.link_ou_arquivo_do_video,
        a.subtitulo, 
        a.corpo_do_texto,
        p.link_ou_arquivo_de_audio,
        f.pergunta_frente, 
        f.resposta_verso
    FROM conteudo c
    INNER JOIN nicho n ON c.id_nicho = n.id_nicho
    LEFT JOIN video v ON c.id = v.id_conteudo
    LEFT JOIN artigo a ON c.id = a.id_conteudo
    LEFT JOIN podcast p ON c.id = p.id_conteudo
    LEFT JOIN flashcards f ON c.id = f.id_conteudo
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