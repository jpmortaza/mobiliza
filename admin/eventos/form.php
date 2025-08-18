<?php
require_once '../../config.php';
if (!verificar_login()) { header('Location: ../index.php'); exit; }

// carrega evento (edição) se tiver id
$pdo = conectar_db();
$evento = null;
$participantes = [];
if (!empty($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $evento = $stmt->fetch();

    if ($evento) {
        $stmt = $pdo->prepare("SELECT * FROM participantes_evento WHERE evento_id = ? ORDER BY ordem ASC");
        $stmt->execute([(int)$_GET['id']]);
        $participantes = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $evento ? 'Editar evento' : 'Novo evento'; ?> · Admin</title>

<!-- ESTILOS DO SISTEMA -->
<link rel="stylesheet" href="/estilo_global.css">
<link rel="stylesheet" href="/admin-styles.css">

<style>
/* ajustes leves específicos da página (se precisar) */
.participantes-wrap .participante-item {
    border: 1px solid #ddd;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: #fafafa;
}
.participantes-wrap .participante-item .cols {
    display:flex; 
    gap:16px; 
    flex-wrap:wrap;
}
.participantes-wrap .participante-item .col-50 { flex:1 1 320px; }
.participantes-wrap .participante-item .col-33 { flex:1 1 240px; }
.participantes-wrap .participante-item .col-100 { flex:1 1 100%; }
.image-preview { margin: 10px 0; }
.image-preview img { max-height:120px; display:block; border-radius: 4px; border:1px solid #e0e0e0; }
.btn-remover-participante { margin-top: 15px; }
</style>
</head>
<body>
<div class="admin-layout">
    <?php if (file_exists(__DIR__.'/../header.php')) include __DIR__.'/../header.php'; ?>

    <div class="main-content">
        <div class="top-bar">
            <h1 class="page-title"><?php echo $evento ? 'Editar evento' : 'Criar evento'; ?></h1>
            <div class="user-menu">
                <a href="./index.php" class="btn btn-outline">Voltar</a>
            </div>
        </div>

        <div class="content-area">
            <div id="message-container"></div>

            <div class="card">
                <div class="card-header">
                    <h3>Dados do evento</h3>
                </div>
                <div class="card-body">
                    <form id="form-evento" class="form" method="post" action="api.php" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="salvar">
                        <?php if ($evento): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$evento['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" class="form-control" required
                                   value="<?php echo htmlspecialchars($evento['titulo'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control"
                                   placeholder="deixe em branco para gerar automaticamente"
                                   value="<?php echo htmlspecialchars($evento['slug'] ?? ''); ?>">
                            <small class="form-text">Somente letras minúsculas, números e hífens.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="6"><?php
                                echo htmlspecialchars($evento['descricao'] ?? '');
                            ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label class="form-label">Data do evento</label>
                                    <input type="datetime-local" name="data_evento" class="form-control"
                                           value="<?php
                                           if (!empty($evento['data_evento'])) {
                                               $dt = new DateTime($evento['data_evento']);
                                               echo $dt->format('Y-m-d\TH:i');
                                           }
                                           ?>">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label class="form-label">Local</label>
                                    <input type="text" name="local_evento" class="form-control"
                                           value="<?php echo htmlspecialchars($evento['local_evento'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Link do WhatsApp</label>
                            <input type="url" name="link_whatsapp" class="form-control"
                                   placeholder="https://chat.whatsapp.com/..."
                                   value="<?php echo htmlspecialchars($evento['link_whatsapp'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Imagem de cabeçalho</label>
                            <?php $imgAtual = $evento['imagem_cabecalho'] ?? ''; ?>
                            <?php if ($imgAtual): ?>
                                <div class="image-preview">
                                    <img src="<?php echo htmlspecialchars($imgAtual); ?>" alt="Imagem atual">
                                    <small class="text-secondary">Imagem atual</small>
                                </div>
                            <?php endif; ?>
                            <input type="hidden" name="imagem_cabecalho_atual" value="<?php echo htmlspecialchars($imgAtual); ?>">
                            <input type="file" name="imagem_cabecalho" class="form-control" accept="image/*">
                            <small class="form-text">Formatos: JPG, PNG, WEBP. Tamanho recomendado 1200x630. Max: 5MB</small>
                        </div>

                        <hr class="mb-3">

                        <div class="form-group">
                            <label class="form-label">Título da seção de participantes</label>
                            <input type="text" name="titulo_participantes" class="form-control"
                                   value="<?php echo htmlspecialchars($evento['titulo_participantes'] ?? 'Palestrantes'); ?>">
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h3>Participantes / Palestrantes</h3>
                            </div>
                            <div class="card-body">
                                <div class="participantes-wrap" id="participantes-wrap">
                                    <!-- itens existentes -->
                                    <?php
                                    $lista = $participantes ?: [];
                                    if (empty($lista)) {
                                        // Criar um item vazio se não houver participantes
                                        $lista = [['id' => 0, 'nome'=>'','descricao'=>'','instagram_url'=>'','linkedin_url'=>'','foto_url'=>'']];
                                    }
                                    foreach ($lista as $idx => $p):
                                    ?>
                                    <div class="participante-item" data-index="<?php echo $idx; ?>">
                                        <?php if (!empty($p['id']) && $p['id'] > 0): ?>
                                            <input type="hidden" name="participantes[<?php echo $idx; ?>][id]" value="<?php echo $p['id']; ?>">
                                        <?php endif; ?>
                                        <div class="cols">
                                            <div class="col-50">
                                                <div class="form-group">
                                                    <label class="form-label">Nome *</label>
                                                    <input type="text" name="participantes[<?php echo $idx; ?>][nome]" class="form-control" required
                                                           value="<?php echo htmlspecialchars($p['nome'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="col-50">
                                                <div class="form-group">
                                                    <label class="form-label">Instagram</label>
                                                    <input type="url" name="participantes[<?php echo $idx; ?>][instagram]" class="form-control"
                                                           placeholder="https://instagram.com/..."
                                                           value="<?php echo htmlspecialchars($p['instagram_url'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="col-50">
                                                <div class="form-group">
                                                    <label class="form-label">LinkedIn</label>
                                                    <input type="url" name="participantes[<?php echo $idx; ?>][linkedin]" class="form-control"
                                                           placeholder="https://www.linkedin.com/in/..."
                                                           value="<?php echo htmlspecialchars($p['linkedin_url'] ?? ''); ?>">
                                                </div>
                                            </div>
                                            <div class="col-50">
                                                <div class="form-group">
                                                    <label class="form-label">Foto</label>
                                                    <?php $foto = $p['foto_url'] ?? ''; ?>
                                                    <?php if ($foto): ?>
                                                        <div class="image-preview">
                                                            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto atual">
                                                            <small class="text-secondary">Foto atual</small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="hidden" name="participantes[<?php echo $idx; ?>][foto_atual]" value="<?php echo htmlspecialchars($foto); ?>">
                                                    <!-- IMPORTANTE: o name abaixo precisa ser participantes[IDX][foto] para a API ler -->
                                                    <input type="file" name="participantes[<?php echo $idx; ?>][foto]" class="form-control" accept="image/*">
                                                    <small class="form-text">JPG, PNG, WEBP. Max: 2MB</small>
                                                </div>
                                            </div>
                                            <div class="col-100">
                                                <div class="form-group">
                                                    <label class="form-label">Descrição</label>
                                                    <textarea name="participantes[<?php echo $idx; ?>][descricao]" class="form-control" rows="3"><?php
                                                        echo htmlspecialchars($p['descricao'] ?? '');
                                                    ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm btn-remover-participante" onclick="removerParticipante(this)">Remover</button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-secondary" id="btn-add-participante">Adicionar palestrante</button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="./index.php" class="btn btn-outline">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar evento</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div><!-- /.main-content -->
</div><!-- /.admin-layout -->

<script>
(function(){
    const wrap = document.getElementById('participantes-wrap');
    const addBtn = document.getElementById('btn-add-participante');
    let participanteIndex = <?php echo count($lista); ?>;

    function templateParticipante(i){
        // IMPORTANTE: o input de foto deve usar participantes[i][foto]
        return `
        <div class="participante-item" data-index="\${i}">
            <div class="cols">
                <div class="col-50">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="participantes[\${i}][nome]" class="form-control" required>
                    </div>
                </div>
                <div class="col-50">
                    <div class="form-group">
                        <label class="form-label">Instagram</label>
                        <input type="url" name="participantes[\${i}][instagram]" class="form-control" placeholder="https://instagram.com/...">
                    </div>
                </div>
                <div class="col-50">
                    <div class="form-group">
                        <label class="form-label">LinkedIn</label>
                        <input type="url" name="participantes[\${i}][linkedin]" class="form-control" placeholder="https://www.linkedin.com/in/...">
                    </div>
                </div>
                <div class="col-50">
                    <div class="form-group">
                        <label class="form-label">Foto</label>
                        <input type="file" name="participantes[\${i}][foto]" class="form-control" accept="image/*">
                        <small class="form-text">JPG, PNG, WEBP. Max: 2MB</small>
                    </div>
                </div>
                <div class="col-100">
                    <div class="form-group">
                        <label class="form-label">Descrição</label>
                        <textarea name="participantes[\${i}][descricao]" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm btn-remover-participante" onclick="removerParticipante(this)">Remover</button>
        </div>`;
    }

    window.removerParticipante = function(btn){
        const item = btn.closest('.participante-item');
        if (item) {
            const totalItems = wrap.querySelectorAll('.participante-item').length;
            if (totalItems <= 1) {
                alert('Deve haver pelo menos um participante');
                return;
            }
            item.remove();
        }
    }

    if (addBtn){
        addBtn.addEventListener('click', function(){
            const newParticipante = document.createElement('div');
            newParticipante.innerHTML = templateParticipante(participanteIndex);
            wrap.appendChild(newParticipante.firstElementChild);
            participanteIndex++;
        });
    }

    // envio com feedback
    document.getElementById('form-evento').addEventListener('submit', async function(e){
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Salvando...';
        
        const fd = new FormData(form);

        try {
            const res = await fetch('api.php', { method: 'POST', body: fd });
            const text = await res.text();
            let json;
            try { json = JSON.parse(text); } 
            catch (er) { throw new Error('Resposta inválida do servidor'); }

            const box = document.getElementById('message-container');
            box.innerHTML = '';
            const alert = document.createElement('div');
            alert.className = 'alert ' + (json.success ? 'alert-success' : 'alert-danger');
            alert.textContent = json.message || (json.success ? 'Salvo com sucesso!' : 'Erro ao salvar.');
            box.appendChild(alert);

            if (json.success && json.data && json.data.id) {
                setTimeout(()=> { window.location.href = 'form.php?id=' + json.data.id; }, 1200);
            }
        } catch (error) {
            const box = document.getElementById('message-container');
            box.innerHTML = '<div class="alert alert-danger">Erro ao salvar: ' + error.message + '</div>';
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
})();
</script>
</body>
</html>
