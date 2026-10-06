<?php
session_start();
require 'conexao.php';

// Proteção da página: Garante acesso apenas para curadores autenticados
if (!isset($_SESSION['curador_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';
$erro = '';

// Identifica o ID do conteúdo
$id_conteudo = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id_conteudo) {
    die("ID do conteúdo não especificado.");
}

// Busca todos os Nichos e Temas para montagem das opções
$nichos = $pdo->query("SELECT * FROM nicho ORDER BY id_nicho")->fetchAll(PDO::FETCH_ASSOC);
$temas = $pdo->query("SELECT * FROM tema ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 1. PROCESSAR A ATUALIZAÇÃO (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $tipo_midia = $_POST['tipo_midia'];
    $id_nicho = $_POST['id_nicho'] ?? null;
    $temas_selecionados = $_POST['temas'] ?? [];

    if (empty($titulo) || empty($descricao) || empty($id_nicho)) {
        $erro = "Título, síntese e nicho são obrigatórios.";
    } elseif (count($temas_selecionados) < 1) {
        $erro = "É obrigatório selecionar pelo menos um Tema (RN1).";
    } else {
        try {
            $pdo->beginTransaction();

            // 1.1. Atualiza dados principais na tabela 'conteudo' (incluindo o Nicho)
            $sqlConteudo = "UPDATE conteudo SET titulo = :titulo, descricao = :descricao, id_nicho = :id_nicho WHERE id = :id";
            $stmt = $pdo->prepare($sqlConteudo);
            $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao,
                ':id_nicho' => $id_nicho,
                ':id' => $id_conteudo
            ]);

            // 1.2. Atualiza os Temas na tabela associativa 'conteudo_tema' (Remove antigos e insere os novos)
            $stmtDelTemas = $pdo->prepare("DELETE FROM conteudo_tema WHERE id_conteudo = ?");
            $stmtDelTemas->execute([$id_conteudo]);

            $stmtInsTema = $pdo->prepare("INSERT INTO conteudo_tema (id_conteudo, id_tema) VALUES (:id_conteudo, :id_tema)");
            foreach ($temas_selecionados as $id_tema) {
                $stmtInsTema->execute([':id_conteudo' => $id_conteudo, ':id_tema' => $id_tema]);
            }

            // 1.3. Atualiza dados específicos por tipo de mídia
            if ($tipo_midia == 'video') {
                $link_video = trim($_POST['link_video']);
                $stmtMedia = $pdo->prepare("UPDATE video SET link_ou_arquivo_do_video = ? WHERE id_conteudo = ?");
                $stmtMedia->execute([$link_video, $id_conteudo]);

            } elseif ($tipo_midia == 'podcast') {
                $link_podcast = trim($_POST['link_podcast']);
                $stmtMedia = $pdo->prepare("UPDATE podcast SET link_ou_arquivo_de_audio = ? WHERE id_conteudo = ?");
                $stmtMedia->execute([$link_podcast, $id_conteudo]);

            } elseif ($tipo_midia == 'artigo') {
                $subtitulo = trim($_POST['subtitulo_artigo']);
                $corpo = trim($_POST['corpo_artigo']);
                $stmtMedia = $pdo->prepare("UPDATE artigo SET subtitulo = ?, corpo_do_texto = ? WHERE id_conteudo = ?");
                $stmtMedia->execute([$subtitulo, $corpo, $id_conteudo]);

            } elseif ($tipo_midia == 'flashcard') {
                $pergunta = trim($_POST['pergunta_flashcard']);
                $resposta = trim($_POST['resposta_flashcard']);
                $stmtMedia = $pdo->prepare("UPDATE flashcards SET pergunta_frente = ?, resposta_verso = ? WHERE id_conteudo = ?");
                $stmtMedia->execute([$pergunta, $resposta, $id_conteudo]);
            }

            $pdo->commit();
            $mensagem = "Conteúdo atualizado com sucesso!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao atualizar conteúdo: " . $e->getMessage();
        }
    }
}

// ==========================================
// 2. CARREGAR DADOS ATUAIS PARA EXIBIÇÃO
// ==========================================
$stmt = $pdo->prepare("SELECT * FROM conteudo WHERE id = ?");
$stmt->execute([$id_conteudo]);
$conteudo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conteudo) {
    die("Conteúdo não encontrado.");
}

$tipo_midia = $conteudo['tipo_midia'];

// Busca os IDs dos Temas vinculados atualmente a este conteúdo
$stmtTemasAtuais = $pdo->prepare("SELECT id_tema FROM conteudo_tema WHERE id_conteudo = ?");
$stmtTemasAtuais->execute([$id_conteudo]);
$temas_atuais = $stmtTemasAtuais->fetchAll(PDO::FETCH_COLUMN);

// Busca dados específicos da mídia
$midia = [];
if ($tipo_midia == 'video') {
    $stmt = $pdo->prepare("SELECT link_ou_arquivo_do_video AS link FROM video WHERE id_conteudo = ?");
    $stmt->execute([$id_conteudo]);
    $midia = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($tipo_midia == 'podcast') {
    $stmt = $pdo->prepare("SELECT link_ou_arquivo_de_audio AS link FROM podcast WHERE id_conteudo = ?");
    $stmt->execute([$id_conteudo]);
    $midia = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($tipo_midia == 'artigo') {
    $stmt = $pdo->prepare("SELECT subtitulo, corpo_do_texto FROM artigo WHERE id_conteudo = ?");
    $stmt->execute([$id_conteudo]);
    $midia = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($tipo_midia == 'flashcard') {
    $stmt = $pdo->prepare("SELECT pergunta_frente, resposta_verso FROM flashcards WHERE id_conteudo = ?");
    $stmt->execute([$id_conteudo]);
    $midia = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Conteúdo - Modo Adulto</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #e6f0fa; padding: 20px; }
        .form-box { background: #fff; padding: 25px; max-width: 700px; margin: 0 auto; border-radius: 8px; border-top: 5px solid #0056b3; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 6px; color: #0056b3; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; background: #f0f7ff; padding: 15px; border-radius: 4px; border: 1px solid #cce0ff; }
        .item-tema { display: none; margin-right: 15px; }
        .item-tema label { font-weight: normal; color: #333; cursor: pointer; }
        .badge { background-color: #e6f0fa; color: #0056b3; padding: 5px 10px; border-radius: 12px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; display: inline-block; margin-bottom: 15px; }
        .btn { padding: 12px 20px; background-color: #0056b3; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .btn:hover { background-color: #003d80; }
        .midia-fields { padding: 15px; background: #eef5fc; border-radius: 4px; border-left: 3px solid #0056b3; }
        .alert-success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="form-box">
        <h2>Editar Conteúdo</h2>
        <a href="painel_curador.php" style="color: #0056b3; text-decoration: none; font-weight: bold;">← Voltar ao Painel</a><br><br>

        <?php if ($mensagem): ?><div class="alert-success"><?= $mensagem ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="alert-danger"><?= $erro ?></div><?php endif; ?>

        <form method="POST" action="editar_conteudo.php">
            <input type="hidden" name="id" value="<?= $id_conteudo ?>">
            <input type="hidden" name="tipo_midia" value="<?= $tipo_midia ?>">

            <div class="badge">Formato: <?= htmlspecialchars($tipo_midia) ?></div>

            <div class="form-group">
                <label>Título do Conteúdo:</label>
                <input type="text" name="titulo" required value="<?= htmlspecialchars($conteudo['titulo']) ?>">
            </div>

            <div class="form-group">
                <label>Síntese/Descrição:</label>
                <textarea name="descricao" rows="3" required><?= htmlspecialchars($conteudo['descricao']) ?></textarea>
            </div>

            <!-- Seleção e Edição do Nicho -->
            <div class="form-group">
                <label>Nicho Principal (RN1):</label>
                <select name="id_nicho" id="select_nicho" onchange="filtrarTemasPorNicho(true)" required>
                    <option value="">-- Selecione um Nicho --</option>
                    <?php foreach ($nichos as $n): ?>
                        <option value="<?= $n['id_nicho'] ?>" <?= ($n['id_nicho'] == $conteudo['id_nicho']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($n['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Seleção e Edição dos Temas -->
            <div class="form-group">
                <label>Temas Relacionados (Selecione pelo menos um):</label>
                <div class="checkbox-group" id="container_temas">
                    <?php foreach ($temas as $t): ?>
                        <div class="item-tema tema-nicho-<?= $t['id_nicho'] ?>">
                            <label>
                                <input type="checkbox" name="temas[]" value="<?= $t['id_tema'] ?>" <?= in_array($t['id_tema'], $temas_atuais) ? 'checked' : '' ?>> 
                                <?= htmlspecialchars($t['nome']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Campos Específicos por Mídia -->
            <div class="midia-fields">
                <?php if ($tipo_midia == 'video'): ?>
                    <label>Link/URL do Vídeo:</label>
                    <input type="text" name="link_video" value="<?= htmlspecialchars($midia['link'] ?? '') ?>" placeholder="https://youtube.com/...">

                <?php elseif ($tipo_midia == 'podcast'): ?>
                    <label>Link/URL do Áudio do Podcast:</label>
                    <input type="text" name="link_podcast" value="<?= htmlspecialchars($midia['link'] ?? '') ?>" placeholder="https://spotify.com/...">

                <?php elseif ($tipo_midia == 'artigo'): ?>
                    <label>Subtítulo do Artigo:</label>
                    <input type="text" name="subtitulo_artigo" value="<?= htmlspecialchars($midia['subtitulo'] ?? '') ?>">
                    <label style="margin-top:10px;">Corpo do Texto:</label>
                    <textarea name="corpo_artigo" rows="8"><?= htmlspecialchars($midia['corpo_do_texto'] ?? '') ?></textarea>

                <?php elseif ($tipo_midia == 'flashcard'): ?>
                    <label>Pergunta (Frente):</label>
                    <textarea name="pergunta_flashcard" rows="2"><?= htmlspecialchars($midia['pergunta_frente'] ?? '') ?></textarea>
                    <label style="margin-top:10px;">Resposta (Verso):</label>
                    <textarea name="resposta_flashcard" rows="3"><?= htmlspecialchars($midia['resposta_verso'] ?? '') ?></textarea>
                <?php endif; ?>
            </div>

            <br>
            <button type="submit" class="btn">Salvar Alterações</button>
        </form>
    </div>

    <script>
        // Função para filtrar temas de acordo com o nicho selecionado
        function filtrarTemasPorNicho(limparSelecoes = false) {
            const nichoId = document.getElementById('select_nicho').value;
            const containerTemas = document.getElementById('container_temas');
            const todosOsTemas = document.querySelectorAll('.item-tema');

            todosOsTemas.forEach(el => {
                el.style.display = 'none';
                // Limpa as caixas de seleção apenas se o usuário trocar manualmente o nicho
                if (limparSelecoes) {
                    const checkbox = el.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = false;
                }
            });

            if (nichoId) {
                containerTemas.style.display = 'flex';
                const temasDoNicho = document.querySelectorAll('.tema-nicho-' + nichoId);
                temasDoNicho.forEach(el => el.style.display = 'block');
            } else {
                containerTemas.style.display = 'none';
            }
        }

        // Executa ao carregar a página para exibir os temas do nicho atual mantendo as marcações existentes
        window.onload = function() {
            filtrarTemasPorNicho(false);
        };
    </script>
</body>
</html>