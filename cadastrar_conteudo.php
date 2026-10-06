<?php
session_start();
require 'conexao.php';

// Segurança: Garante acesso apenas para curadores autenticados (RN2)
if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';
$erro = '';

// Procura Nichos e Temas (buscando id_nicho do tema para vincular no frontend)
$nichos = $pdo->query("SELECT * FROM nicho ORDER BY id_nicho")->fetchAll(PDO::FETCH_ASSOC);
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

            // 2. Inserir os Temas associados (Relacionamento N:M na tabela conteudo_tema)
            $sqlTema = "INSERT INTO conteudo_tema (id_conteudo, id_tema) VALUES (:id_conteudo, :id_tema)";
            $stmtTema = $pdo->prepare($sqlTema);
            foreach ($temas_selecionados as $id_tema) {
                $stmtTema->execute([':id_conteudo' => $id_conteudo, ':id_tema' => $id_tema]);
            }

            // 3. Inserir nas tabelas específicas de acordo com o tipo de mídia
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e6f0fa; padding: 20px; }
        .form-box { background: #fff; padding: 25px; max-width: 700px; margin: 0 auto; border-radius: 8px; border-top: 5px solid #0056b3; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 6px; color: #0056b3; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; background: #f0f7ff; padding: 15px; border-radius: 4px; border: 1px solid #cce0ff; }
        .item-tema { display: none; margin-right: 15px; } /* Oculto por padrão até selecionar o nicho */
        .item-tema label { font-weight: normal; color: #333; cursor: pointer; }
        .btn { padding: 12px 20px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .btn:hover { background-color: #003d80; }
        .midia-fields { display: none; margin-top: 15px; padding: 15px; background: #eef5fc; border-radius: 4px; border-left: 3px solid #0056b3; }
        .alert-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Cadastrar Novo Conteúdo</h2>
        <a href="painel_curador.php" style="color: #0056b3; text-decoration: none; font-weight: bold;">← Voltar ao Painel</a><br><br>

        <?php if ($mensagem): ?><div class="alert-success"><?= $mensagem ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="alert-danger"><?= $erro ?></div><?php endif; ?>

        <form method="POST" action="cadastrar_conteudo.php">
            <div class="form-group">
                <label>Título do Conteúdo:</label>
                <input type="text" name="titulo" required placeholder="Ex: Como fazer arroz soltinho">
            </div>

            <div class="form-group">
                <label>Síntese/Descrição:</label>
                <textarea name="descricao" rows="3" required placeholder="Escreva uma breve explicação em linguagem simples..."></textarea>
            </div>

            <!-- Seleção do Nicho -->
            <div class="form-group">
                <label>Nicho Principal (RN1):</label>
                <select name="id_nicho" id="select_nicho" onchange="filtrarTemasPorNicho()" required>
                    <option value="">-- Selecione um Nicho --</option>
                    <?php foreach ($nichos as $n): ?>
                        <option value="<?= $n['id_nicho'] ?>"><?= htmlspecialchars($n['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Seleção Dinâmica dos Temas vinculados ao Nicho -->
            <div class="form-group">
                <label>Temas Relacionados (Selecione pelo menos um):</label>
                <p id="msg_selecione_nicho" style="font-size: 0.9rem; color: #666; margin-top: 0;">
                    <em>Por favor, selecione um Nicho acima para ver os temas disponíveis.</em>
                </p>
                <div class="checkbox-group" id="container_temas" style="display: none;">
                    <?php foreach ($temas as $t): ?>
                        <div class="item-tema tema-nicho-<?= $t['id_nicho'] ?>">
                            <label>
                                <input type="checkbox" name="temas[]" value="<?= $t['id_tema'] ?>"> 
                                <?= htmlspecialchars($t['nome']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Tipo de Mídia:</label>
                <select name="tipo_midia" id="tipo_midia" onchange="mostrarCamposMidia()" required>
                    <option value="">-- Selecione o formato --</option>
                    <option value="video">Vídeo</option>
                    <option value="artigo">Artigo</option>
                    <option value="podcast">Podcast</option>
                    <option value="flashcard">Flashcard</option>
                </select>
            </div>

            <!-- Campos Específicos por Tipo de Mídia -->
            <div id="field_video" class="midia-fields">
                <label>Link/URL do Vídeo:</label>
                <input type="text" name="link_video" placeholder="https://youtube.com/...">
            </div>

            <div id="field_artigo" class="midia-fields">
                <label>Subtítulo do Artigo:</label>
                <input type="text" name="subtitulo_artigo">
                <label style="margin-top:10px;">Corpo do Texto:</label>
                <textarea name="corpo_artigo" rows="6"></textarea>
            </div>

            <div id="field_podcast" class="midia-fields">
                <label>Link/URL do Áudio do Podcast:</label>
                <input type="text" name="link_podcast" placeholder="https://spotify.com/...">
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
        // Função para mostrar somente os temas vinculados ao nicho selecionado
        function filtrarTemasPorNicho() {
            const nichoId = document.getElementById('select_nicho').value;
            const containerTemas = document.getElementById('container_temas');
            const msgNicho = document.getElementById('msg_selecione_nicho');
            const todosOsTemas = document.querySelectorAll('.item-tema');

            // Limpa as escolhas anteriores e oculta todos os temas
            todosOsTemas.forEach(el => {
                el.style.display = 'none';
                const checkbox = el.querySelector('input[type="checkbox"]');
                if (checkbox) checkbox.checked = false;
            });

            if (nichoId) {
                msgNicho.style.display = 'none';
                containerTemas.style.display = 'flex';
                
                // Exibe apenas os temas pertencentes ao id_nicho selecionado
                const temasDoNicho = document.querySelectorAll('.tema-nicho-' + nichoId);
                temasDoNicho.forEach(el => el.style.display = 'block');
            } else {
                msgNicho.style.display = 'block';
                containerTemas.style.display = 'none';
            }
        }

        // Função para exibir os campos específicos da mídia escolhida
        function mostrarCamposMidia() {
            document.querySelectorAll('.midia-fields').forEach(el => el.style.display = 'none');
            const tipo = document.getElementById('tipo_midia').value;
            if (tipo) {
                const target = document.getElementById('field_' + tipo);
                if (target) target.style.display = 'block';
            }
        }
    </script>
</body>
</html>