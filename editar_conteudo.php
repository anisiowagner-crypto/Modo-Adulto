<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: painel_curador.php");
    exit;
}

// Procura os dados do conteúdo base
$stmt = $pdo->prepare("SELECT * FROM conteudo WHERE id = ?");
$stmt->execute([$id]);
$conteudo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conteudo) {
    die("Conteúdo não encontrado.");
}

$nichos = $pdo->query("SELECT * FROM nicho ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$temas = $pdo->query("SELECT * FROM tema ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Procura os temas selecionados atualmente
$stmtTemasAtual = $pdo->prepare("SELECT id_tema FROM conteudo_tema WHERE id_conteudo = ?");
$stmtTemasAtual->execute([$id]);
$temasAtual = $stmtTemasAtual->fetchAll(PDO::FETCH_COLUMN);

// Processa a edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $id_nicho = $_POST['id_nicho'];
    $temas_selecionados = $_POST['temas'] ?? [];

    if (empty($titulo) || empty($descricao) || empty($id_nicho) || count($temas_selecionados) < 1) {
        $erro = "Preencha todos os campos e selecione ao menos um tema.";
    } else {
        try {
            $pdo->beginTransaction();

            // Atualiza tabela base
            $stmtUpdate = $pdo->prepare("UPDATE conteudo SET titulo = ?, descricao = ?, id_nicho = ? WHERE id = ?");
            $stmtUpdate->execute([$titulo, $descricao, $id_nicho, $id]);

            // Atualiza relacionamento de temas
            $pdo->prepare("DELETE FROM conteudo_tema WHERE id_conteudo = ?")->execute([$id]);
            $stmtTema = $pdo->prepare("INSERT INTO conteudo_tema (id_conteudo, id_tema) VALUES (?, ?)");
            foreach ($temas_selecionados as $id_tema) {
                $stmtTema->execute([$id, $id_tema]);
            }

            $pdo->commit();
            header("Location: painel_curador.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Conteúdo - Modo Adulto</title>
    <style>
        body { font-family: sans-serif; background-color: #e6f0fa; padding: 20px; }
        .form-box { background: #fff; padding: 25px; max-width: 600px; margin: 0 auto; border-radius: 8px; border-top: 5px solid #0056b3; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; color: #0056b3; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 10px 20px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Editar Conteúdo</h2>
        <form method="POST">
            <div class="form-group">
                <label>Título:</label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($conteudo['titulo']) ?>" required>
            </div>
            <div class="form-group">
                <label>Síntese/Descrição:</label>
                <textarea name="descricao" rows="4" required><?= htmlspecialchars($conteudo['descricao']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Nicho:</label>
                <select name="id_nicho" required>
                    <?php foreach ($nichos as $n): ?>
                        <option value="<?= $n['id_nicho'] ?>" <?= $n['id_nicho'] == $conteudo['id_nicho'] ? 'selected' : '' ?>><?= $n['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Temas:</label>
                <?php foreach ($temas as $t): ?>
                    <label style="font-weight:normal;">
                        <input type="checkbox" name="temas[]" value="<?= $t['id_tema'] ?>" <?= in_array($t['id_tema'], $temasAtual) ? 'checked' : '' ?>> <?= $t['nome'] ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn">Atualizar Conteúdo</button>
            <a href="painel_curador.php" style="margin-left:10px;">Cancelar</a>
        </form>
    </div>
</body>
</html>