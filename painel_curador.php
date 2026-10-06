<?php
session_start();
require 'conexao.php';

// Segurança: Garante que apenas curadores autenticados acedem à página (RN2)
if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}

// Procura os conteúdos com os respetivos nichos cadastrados
$sql = "
    SELECT c.id, c.titulo, c.tipo_midia, c.data_publicacao, n.nome AS nicho_nome 
    FROM conteudo c
    INNER JOIN nicho n ON c.id_nicho = n.id_nicho
    ORDER BY c.data_publicacao DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$conteudos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Curador - Modo Adulto</title>
    <style>
        :root {
            --primary-blue: #0056b3;
            --light-blue: #e6f0fa;
            --white: #ffffff;
            --danger-red: #d9534f;
            --warning-orange: #f0ad4e;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--light-blue); margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: var(--white); padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-top: 5px solid var(--primary-blue); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        h1 { color: var(--primary-blue); margin: 0; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: var(--primary-blue); color: var(--white); }
        .btn-warning { background-color: var(--warning-orange); color: var(--white); }
        .btn-danger { background-color: var(--danger-red); color: var(--white); }
        .btn-secondary { background-color: #6c757d; color: var(--white); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: var(--primary-blue); color: var(--white); }
        tr:hover { background-color: #f1f7ff; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; background-color: var(--light-blue); color: var(--primary-blue); font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestão do Acervo - Modo Adulto</h1>
            <div>
                <a href="cadastrar_conteudo.php" class="btn btn-primary">+ Cadastrar Conteúdo</a>
                <a href="logout.php" class="btn btn-secondary">Sair</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tipo</th>
                    <th>Nicho</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($conteudos)): ?>
                    <tr><td colspan="5">Nenhum conteúdo cadastrado até ao momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($conteudos as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['titulo']) ?></td>
                            <td><span class="badge"><?= strtoupper($item['tipo_midia']) ?></span></td>
                            <td><?= htmlspecialchars($item['nicho_nome']) ?></td>
                            <td><?= date('d/m/Y', strtotime($item['data_publicacao'])) ?></td>
                            <td>
                                <!-- UC06: Editar -->
                                <a href="editar_conteudo.php?id=<?= $item['id'] ?>" class="btn btn-warning" style="padding: 5px 10px; font-size: 0.8rem;">Editar</a>
                                <!-- UC05: Excluir -->
                                <a href="excluir_conteudo.php?id=<?= $item['id'] ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;" onclick="return confirm('Deseja realmente remover este conteúdo?');">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>