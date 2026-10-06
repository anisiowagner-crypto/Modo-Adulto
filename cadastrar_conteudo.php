<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';
$erro = '';

// Procura Nichos e Temas para popular as opções do formulário
$nichos = $pdo->query("SELECT * FROM nicho ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$temas = $pdo->query("SELECT * FROM tema ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']); // Síntese
    $tipo_midia = $_POST['tipo_midia'];
    $id_nicho = $_POST['id_nicho'];
    $temas_selecionados = $_POST['temas'] ?? [];

    // Validação de Regras de Negócio (RN1 e RN3)
    if (empty($titulo) || empty($descricao) || empty($tipo_midia) || empty($id_nicho)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (count($temas_selecionados) < 1) {
        $erro = "É obrigatório selecionar pelo menos um Tema para publicar o conteúdo (RN1).";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Inserir na tabela base CONTEUDO
            $sqlConteudo = "INSERT INTO conteudo (titulo, descricao, data_publicacao, tipo_midia, id_nicho, id_curador) 
                            VALUES (:titulo, :descricao, CURDATE(), :tipo_midia, :id_nicho, :id_curador)";
            $stmt = $pdo->prepare($sqlConteudo);
            $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':tipo_midia' => $tipo_midia,
                ':id_nicho' => $id_nicho,
                ':id_curador' => $_SESSION['curador_id']
            ]);
            $id_conteudo = $pdo->lastInsertId();

            // 2. Inserir os Temas associados (Relacionamento N:M)
            $sqlTema = "INSERT INTO conteudo_tema (id_conteudo, id_tema) VALUES (:id_conteudo, :id_tema)";
            $stmtTema = $pdo->prepare($sqlTema);
            foreach ($temas_selecionados as $id_tema) {
                $stmtTema->execute([':id_conteudo' => $id_conteudo, ':id_tema' => $id_tema]);
            }

            // 3. Inserir nas tabelas de especialização conforme a mídia
            if ($tipo_midia == 'video') {
                $stmtMedia = $pdo->prepare("INSERT INTO video (id_conteudo, link_ou_arquivo_do_video) VALUES (?, ?)");
                $stmtMedia->execute([$id_conteudo, $_POST['link_video']]);
            } elseif ($tipo_midia == 'artigo') {
                $stmtMedia = $pdo->prepare("INSERT INTO artigo (id_conteudo, subtitulo, corpo_do_texto) VALUES (?, ?, ?)");
                $stmtMedia->execute([$id_conteudo, $_POST['subtitulo_artigo'], $_POST['corpo_artigo']]);
            } elseif ($tipo_midia == 'podcast') {
                $stmtMedia = $pdo->prepare("INSERT INTO podcast (id_conteudo, link_ou_arquivo_de_audio) VALUES (?, ?)");
                $stmtMedia->execute([$id_conteudo, $_POST['link_podcast']]);
            } elseif ($tipo_midia == 'flashcard') {
                $stmtMedia = $pdo->prepare("INSERT INTO flashcards (id_conteudo, pergunta_frente, resposta_verso) VALUES (?, ?, ?)");
                $stmtMedia->execute([$id_conteudo, $_POST['pergunta_flashcard'], $_POST['resposta_flashcard']]);
            }

            $pdo->commit();
            $mensagem = "Conteúdo cadastrado com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao cadastrar conteúdo: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Conteúdo - Modo Adulto</title>
    <style>
        body { font-family: sans-serif; background-color: #e6f0fa; padding: 20px; }
        .form-box { background: #fff; padding: 25px; max-width: 700px; margin: 0 auto; border-radius: 8px; border-top: 5px solid #0056b3; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; color: #0056b3; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; background: #f8f9fa; padding: 10px; border-radius: 4px; }
        .checkbox-group label { font-weight: normal; color: #333; }
        .btn { padding: 10px 20px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .midia-fields { display: none; margin-top: 15px; padding: 15px; background: #f0f7ff; border-radius: 4px; }
        .alert-success { color: green; font-weight: bold; margin-bottom: 15px; }
        .alert-danger { color: red; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Cadastrar Novo Conteúdo</h2>
        <a href="painel_curador.php">← Voltar ao Painel</a><br><br>

        <?php if ($mensagem): ?><div class="alert-success"><?= $mensagem ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="alert-danger"><?= $erro ?></div><?php endif; ?>

        <form method="POST" action="cadastrar_conteudo.php">
            <div class="form-group">
                <label>Título:</label>
                <input type="text" name="titulo" required>
            </div>

            <div class="form-group">
                <label>Síntese/Descrição:</label>
                <textarea name="descricao" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label>Nicho Principal (RN1):</label>
                <select name="id_nicho" required>
                    <option value="">Selecione um Nicho</option>
                    <?php foreach ($nichos as $n): ?>
                        <option value="<?= $n['id_nicho'] ?>"><?= $n['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Temas Relacionados (Selecione pelo menos um):</label>
                <div class="checkbox-group">
                    <?php foreach ($temas as $t): ?>
                        <label>
                            <input type="checkbox" name="temas[]" value="<?= $t['id_tema'] ?>"> <?= $t['nome'] ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Tipo de Mídia:</label>
                <select name="tipo_midia" id="tipo_midia" onchange="mostrarCamposMidia()" required>
                    <option value="">Selecione o formato</option>
                    <option value="video">Vídeo</option>
                    <option value="artigo">Artigo</option>
                    <option value="podcast">Podcast</option>
                    <option value="flashcard">Flashcard</option>
                </select>
            </div>

            <!-- Campos Específicos Dinâmicos -->
            <div id="field_video" class="midia-fields">
                <label>Link/URL do Vídeo:</label>
                <input type="text" name="link_video">
            </div>

            <div id="field_artigo" class="midia-fields">
                <label>Subtítulo do Artigo:</label>
                <input type="text" name="subtitulo_artigo">
                <label style="margin-top:10px;">Corpo do Texto:</label>
                <textarea name="corpo_artigo" rows="6"></textarea>
            </div>

            <div id="field_podcast" class="midia-fields">
                <label>Link/URL do Áudio do Podcast:</label>
                <input type="text" name="link_podcast">
            </div>

            <div id="field_flashcard" class="midia-fields">
                <label>Pergunta (Frente):</label>
                <textarea name="pergunta_flashcard" rows="2"></textarea>
                <label style="margin-top:10px;">Resposta (Verso):</label>
                <textarea name="resposta_flashcard" rows="2"></textarea>
            </div>

            <br>
            <button type="submit" class="btn">Salvar Conteúdo</button>
        </form>
    </div>

    <script>
        function mostrarCamposMidia() {
            // Oculta todos os campos específicos
            document.querySelectorAll('.midia-fields').forEach(el => el.style.display = 'none');
            
            // Exibe apenas o campo relativo à mídia selecionada
            const tipo = document.getElementById('tipo_midia').value;
            if (tipo) {
                const target = document.getElementById('field_' + tipo);
                if (target) target.style.display = 'block';
            }
        }
    </script>
</body>
</html>