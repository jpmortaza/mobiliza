<?php
$peticao_id = $_GET['id'] ?? 0;
$titulo_pagina = $peticao_id ? 'Editar Petição' : 'Nova Petição';
require_once '../header.php';

$peticao = null;
$protagonistas = [];

if ($peticao_id) {
    try {
        $pdo = conectar_db();
        
        // Buscar petição
        $stmt = $pdo->prepare("SELECT * FROM peticoes WHERE id = ? AND ativo = 1");
        $stmt->execute([$peticao_id]);
        $peticao = $stmt->fetch();
        
        if (!$peticao) {
            header("Location: index.php");
            exit();
        }
        
        // Buscar protagonistas
        $stmt = $pdo->prepare("
            SELECT * FROM protagonistas_peticao 
            WHERE peticao_id = ? 
            ORDER BY ordem ASC
        ");
        $stmt->execute([$peticao_id]);
        $protagonistas = $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Erro ao buscar petição: " . $e->getMessage());
        header("Location: index.php");
        exit();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;"><?php echo $titulo_pagina; ?></h2>
        <p class="text-secondary">
            <?php echo $peticao_id ? 'Edite as informações da petição' : 'Preencha as informações da nova petição'; ?>
        </p>
    </div>
    <a href="index.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<form id="peticaoForm" enctype="multipart/form-data">
    <input type="hidden" name="action" value="salvar">
    <input type="hidden" name="id" value="<?php echo $peticao_id; ?>">
    
    <div class="row">
        <!-- Coluna Principal -->
        <div class="col" style="flex: 2;">
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📝 Informações Básicas</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="titulo" class="form-label">Título da Petição *</label>
                        <input type="text" 
                               id="titulo" 
                               name="titulo" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($peticao['titulo'] ?? ''); ?>"
                               required 
                               placeholder="Ex: Contra o Aumento dos Impostos Municipais">
                        <div class="form-text">Este será o título principal da campanha</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug" class="form-label">Slug (URL) *</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">/apoie/</span>
                            </div>
                            <input type="text" 
                                   id="slug" 
                                   name="slug" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($peticao['slug'] ?? ''); ?>"
                                   pattern="[a-z0-9\-]+"
                                   placeholder="contra-aumento-impostos">
                        </div>
                        <div class="form-text">URL amigável. Use apenas letras minúsculas, números e hífens</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao" class="form-label">Descrição da Causa</label>
                        <textarea id="descricao" 
                                  name="descricao" 
                                  class="form-control" 
                                  rows="6"
                                  placeholder="Descreva a causa, o problema e por que as pessoas devem apoiar..."><?php echo htmlspecialchars($peticao['descricao'] ?? ''); ?></textarea>
                        <div class="form-text">Explique claramente a causa e motive as pessoas a assinar</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_assinaturas" class="form-label">Meta de Assinaturas</label>
                        <input type="number" 
                               id="meta_assinaturas" 
                               name="meta_assinaturas" 
                               class="form-control"
                               value="<?php echo $peticao['meta_assinaturas'] ?? ''; ?>"
                               min="0"
                               placeholder="Ex: 1000">
                        <div class="form-text">Meta de assinaturas para a campanha (opcional)</div>
                    </div>
                </div>
            </div>
            
            <!-- Textos do Formulário -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📝 Textos do Formulário</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="form_titulo" class="form-label">Título do Formulário</label>
                        <input type="text" 
                               id="form_titulo" 
                               name="form_titulo" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($peticao['form_titulo'] ?? 'Assine este abaixo-assinado'); ?>"
                               placeholder="Ex: Assine para apoiar esta causa">
                    </div>
                    
                    <div class="form-group">
                        <label for="form_botao_texto" class="form-label">Texto do Botão</label>
                        <input type="text" 
                               id="form_botao_texto" 
                               name="form_botao_texto" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($peticao['form_botao_texto'] ?? 'Eu apoio'); ?>"
                               placeholder="Ex: Eu apoio, Assinar Agora">
                    </div>
                </div>
            </div>
            
            <!-- Protagonistas/Criadores -->
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">👥 Protagonistas/Criadores</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="criador_titulo" class="form-label">Título da Seção</label>
                        <input type="text" 
                               id="criador_titulo" 
                               name="criador_titulo" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($peticao['criador_titulo'] ?? 'Criadores'); ?>"
                               placeholder="Ex: Criadores da Moção, Organizadores">
                    </div>
                    
                    <div id="protagonistas-container">
                        <!-- Protagonistas serão adicionados aqui via JavaScript -->
                    </div>
                    
                    <button type="button" id="add-protagonista" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Adicionar Protagonista
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Coluna Lateral -->
        <div class="col" style="flex: 1;">
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">🖼️ Imagem de Cabeçalho</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="imagem_cabecalho" class="form-label">Imagem de Cabeçalho</label>
                        <input type="file" 
                               id="imagem_cabecalho" 
                               name="imagem_cabecalho" 
                               class="form-control"
                               accept="image/*">
                        <div class="form-text">Recomendado: 1200x600px, máximo 5MB</div>
                        <input type="hidden" name="imagem_cabecalho_atual" value="<?php echo htmlspecialchars($peticao['imagem_cabecalho'] ?? ''); ?>">
                        
                        <?php if (!empty($peticao['imagem_cabecalho'])): ?>
                            <div class="image-preview mt-3">
                                <img src="../../<?php echo htmlspecialchars($peticao['imagem_cabecalho']); ?>" 
                                     style="max-width: 100%; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($peticao_id): ?>
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📊 Estatísticas</h3>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as total_assinaturas
                            FROM assinaturas_peticoes 
                            WHERE peticao_id = ?
                        ");
                        $stmt->execute([$peticao_id]);
                        $stats = $stmt->fetch();
                    } catch (Exception $e) {
                        $stats = ['total_assinaturas' => 0];
                    }
                    
                    $percentual = 0;
                    if ($peticao['meta_assinaturas'] > 0) {
                        $percentual = min(100, round(($stats['total_assinaturas'] / $peticao['meta_assinaturas']) * 100));
                    }
                    ?>
                    
                    <div class="stat-item">
                        <div class="stat-value text-primary"><?php echo number_format($stats['total_assinaturas']); ?></div>
                        <div class="stat-label">Assinaturas</div>
                    </div>
                    
                    <?php if ($peticao['meta_assinaturas'] > 0): ?>
                        <div class="stat-item">
                            <div class="stat-value text-success"><?php echo $percentual; ?>%</div>
                            <div class="stat-label">da Meta</div>
                        </div>
                        
                        <div class="progress-bar-container mt-3">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentual; ?>%;"></div>
                            </div>
                            <small class="text-secondary">
                                Meta: <?php echo number_format($peticao['meta_assinaturas']); ?> assinaturas
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="assinaturas.php?peticao_id=<?php echo $peticao_id; ?>" class="btn btn-info btn-block">
                            <i class="fas fa-list"></i> Ver Assinaturas
                        </a>
                        <a href="../../apoie/?slug=<?php echo $peticao['slug']; ?>" target="_blank" class="btn btn-success btn-block">
                            <i class="fas fa-external-link-alt"></i> Ver Página Pública
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">💾 Ações</h3>
                </div>
                <div class="card-body">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Salvar Petição
                    </button>
                    
                    <?php if ($peticao_id): ?>
                        <button type="button" onclick="excluirPeticao()" class="btn btn-danger btn-block">
                            <i class="fas fa-trash"></i> Excluir Petição
                        </button>
                    <?php endif; ?>
                    
                    <div class="form-text mt-2 text-center">
                        <small>💡 Use Ctrl+S para salvar rapidamente</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Template para Protagonista -->
<template id="protagonista-template">
    <div class="protagonista-item border p-3 mb-3" style="border-radius: 8px; background: #f8f9fa;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 style="margin: 0;">Protagonista</h5>
            <button type="button" class="btn btn-sm btn-danger remove-protagonista">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="form-group">
            <label class="form-label">Nome *</label>
            <input type="text" name="protagonistas[][nome]" class="form-control protagonista-nome" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Descrição</label>
            <input type="text" name="protagonistas[][descricao]" class="form-control" placeholder="Ex: Vereador, Ativista, Cidadão">
        </div>
        
        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="url" name="protagonistas[][instagram]" class="form-control" placeholder="https://instagram.com/usuario">
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" name="protagonistas[][linkedin]" class="form-control" placeholder="https://linkedin.com/in/usuario">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Foto</label>
            <input type="file" name="protagonistas[][foto]" class="form-control protagonista-foto" accept="image/*">
            <input type="hidden" name="protagonistas[][foto_atual]" class="foto-atual">
            <div class="image-preview mt-2"></div>
        </div>
    </div>
</template>

<script>
let protagonistaCounter = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Gerar slug automaticamente baseado no título
    document.getElementById('titulo').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
        document.getElementById('slug').value = slug;
    });
    
    // Adicionar protagonistas existentes
    const protagonistasExistentes = <?php echo json_encode($protagonistas); ?>;
    protagonistasExistentes.forEach(protagonista => {
        adicionarProtagonista(protagonista);
    });
    
    // Event listener para adicionar protagonista
    document.getElementById('add-protagonista').addEventListener('click', function() {
        adicionarProtagonista();
    });
    
    // Submit do formulário
    document.getElementById('peticaoForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        
        try {
            const formData = new FormData(this);
            
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                clearAutoSave('peticaoForm');
                
                // Redirecionar após sucesso
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1500);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('Erro ao salvar petição', 'danger');
            console.error('Erro:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Auto-save a cada 30 segundos
    setInterval(() => {
        autoSave('peticaoForm');
    }, 30000);
    
    // Restaurar auto-save se existir
    restoreAutoSave('peticaoForm');
});

function adicionarProtagonista(data = {}) {
    const template = document.getElementById('protagonista-template');
    const clone = template.content.cloneNode(true);
    const container = document.getElementById('protagonistas-container');
    
    // Configurar campos com dados existentes
    if (data.nome) clone.querySelector('.protagonista-nome').value = data.nome;
    if (data.descricao) clone.querySelector('input[name="protagonistas[][descricao]"]').value = data.descricao;
    if (data.instagram_url) clone.querySelector('input[name="protagonistas[][instagram]"]').value = data.instagram_url;
    if (data.linkedin_url) clone.querySelector('input[name="protagonistas[][linkedin]"]').value = data.linkedin_url;
    if (data.foto_url) {
        clone.querySelector('.foto-atual').value = data.foto_url;
        clone.querySelector('.image-preview').innerHTML = `
            <img src="../../${data.foto_url}" style="max-width: 150px; border-radius: 8px;">
        `;
    }
    
    // Event listener para remover
    clone.querySelector('.remove-protagonista').addEventListener('click', function() {
        this.closest('.protagonista-item').remove();
    });
    
    // Preview de imagem
    const fotoInput = clone.querySelector('.protagonista-foto');
    fotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = fotoInput.closest('.protagonista-item').querySelector('.image-preview');
                preview.innerHTML = `<img src="${e.target.result}" style="max-width: 150px; border-radius: 8px;">`;
            };
            reader.readAsDataURL(file);
        }
    });
    
    container.appendChild(clone);
    protagonistaCounter++;
}

<?php if ($peticao_id): ?>
async function excluirPeticao() {
    if (!confirm('Tem certeza que deseja excluir esta petição?\n\nTodas as assinaturas serão perdidas. Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', <?php echo $peticao_id; ?>);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao excluir petição', 'danger');
        console.error('Erro:', error);
    }
}
<?php endif; ?>
</script>

<style>
.input-group-text {
    background: #f8f9fa;
    border-color: #e9ecef;
    color: #6c757d;
    font-size: 0.9rem;
}

.stat-item {
    text-align: center;
    padding: 1rem 0;
    border-bottom: 1px solid #eee;
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.protagonista-item {
    position: relative;
    transition: all 0.3s ease;
}

.protagonista-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.remove-protagonista {
    opacity: 0.7;
}

.remove-protagonista:hover {
    opacity: 1;
}

.progress-bar-container {
    text-align: center;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--cor-primaria) 0%, var(--cor-secundaria) 100%);
    transition: width 0.3s ease;
}
</style>

<?php require_once '../footer.php'; ?>