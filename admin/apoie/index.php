<?php
$titulo_pagina = 'Apoie - Petições';
require_once '../header.php';

try {
    $pdo = conectar_db();
    
    // Buscar petições com estatísticas
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(a.id) as total_assinaturas,
               u.nome as criador_nome
        FROM peticoes p 
        LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id 
        LEFT JOIN usuarios_sistema u ON p.criado_por = u.id
        WHERE p.ativo = 1 
        GROUP BY p.id 
        ORDER BY p.data_criacao DESC
    ");
    $stmt->execute();
    $peticoes = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erro ao buscar petições: " . $e->getMessage());
    $peticoes = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;">Gerenciar Petições e Abaixo-Assinados</h2>
        <p class="text-secondary">Crie e gerencie campanhas de mobilização e apoio</p>
    </div>
    <a href="form.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nova Petição
    </a>
</div>

<?php if (empty($peticoes)): ?>
    <div class="card text-center">
        <div class="card-body" style="padding: 4rem;">
            <i class="fas fa-hand-holding-heart" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
            <h3>Nenhuma petição criada ainda</h3>
            <p class="text-secondary">Comece criando sua primeira campanha de mobilização.</p>
            <a href="form.php" class="btn btn-primary btn-lg">
                <i class="fas fa-plus"></i> Criar Primeira Petição
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Petição</th>
                        <th>Assinaturas</th>
                        <th>Meta</th>
                        <th>Progresso</th>
                        <th>Status</th>
                        <th>Criado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($peticoes as $peticao): ?>
                        <?php 
                        $percentual = 0;
                        if ($peticao['meta_assinaturas'] > 0) {
                            $percentual = min(100, round(($peticao['total_assinaturas'] / $peticao['meta_assinaturas']) * 100));
                        }
                        $cor_progresso = $percentual >= 100 ? 'success' : ($percentual >= 75 ? 'warning' : 'primary');
                        ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($peticao['titulo']); ?></strong>
                                    <br>
                                    <small class="text-secondary">
                                        <i class="fas fa-link"></i> /apoie/<?php echo htmlspecialchars($peticao['slug']); ?>
                                    </small>
                                    <?php if ($peticao['descricao']): ?>
                                        <br>
                                        <small class="text-secondary">
                                            <?php echo htmlspecialchars(substr($peticao['descricao'], 0, 100)); ?>
                                            <?php if (strlen($peticao['descricao']) > 100): ?>...<?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-center">
                                    <div class="assinaturas-count">
                                        <?php echo number_format($peticao['total_assinaturas']); ?>
                                    </div>
                                    <?php if ($peticao['total_assinaturas'] > 0): ?>
                                        <small>
                                            <a href="assinaturas.php?peticao_id=<?php echo $peticao['id']; ?>" class="text-secondary">
                                                Ver assinaturas
                                            </a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($peticao['meta_assinaturas'] > 0): ?>
                                    <span class="badge badge-info">
                                        <?php echo number_format($peticao['meta_assinaturas']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary">Sem meta</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($peticao['meta_assinaturas'] > 0): ?>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-fill bg-<?php echo $cor_progresso; ?>" 
                                                 style="width: <?php echo $percentual; ?>%;"></div>
                                        </div>
                                        <small class="text-<?php echo $cor_progresso; ?>">
                                            <?php echo $percentual; ?>%
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($peticao['ativo']): ?>
                                    <span class="badge badge-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-secondary">
                                    <?php echo formatar_data_br($peticao['data_criacao'], false); ?>
                                    <?php if ($peticao['criador_nome']): ?>
                                        <br>por <?php echo htmlspecialchars($peticao['criador_nome']); ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="form.php?id=<?php echo $peticao['id']; ?>" 
                                       class="btn btn-sm btn-outline" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../apoie/?slug=<?php echo $peticao['slug']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-info" 
                                       title="Visualizar">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button onclick="compartilhar('<?php echo $peticao['slug']; ?>', '<?php echo htmlspecialchars($peticao['titulo']); ?>')" 
                                            class="btn btn-sm btn-success" 
                                            title="Compartilhar">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                    <button onclick="excluirPeticao(<?php echo $peticao['id']; ?>, '<?php echo htmlspecialchars($peticao['titulo']); ?>')" 
                                            class="btn btn-sm btn-danger" 
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Estatísticas Resumidas -->
    <div class="row mt-4">
        <div class="col">
            <div class="stats-grid">
                <?php
                $total_peticoes = count($peticoes);
                $total_assinaturas = array_sum(array_column($peticoes, 'total_assinaturas'));
                $peticoes_com_meta = array_filter($peticoes, function($p) { return $p['meta_assinaturas'] > 0; });
                $metas_atingidas = array_filter($peticoes_com_meta, function($p) { 
                    return $p['total_assinaturas'] >= $p['meta_assinaturas']; 
                });
                ?>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_peticoes; ?></div>
                    <div class="stat-label">Total de Petições</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_assinaturas; ?></div>
                    <div class="stat-label">Total de Assinaturas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($metas_atingidas); ?></div>
                    <div class="stat-label">Metas Atingidas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $media = $total_peticoes > 0 ? round($total_assinaturas / $total_peticoes) : 0;
                        echo $media;
                        ?>
                    </div>
                    <div class="stat-label">Média por Petição</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Excluir petição
async function excluirPeticao(id, titulo) {
    if (!confirm(`Tem certeza que deseja excluir a petição "${titulo}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'excluir');
        formData.append('id', id);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao excluir petição', 'danger');
        console.error('Erro:', error);
    }
}

// Compartilhar no WhatsApp
function compartilhar(slug, titulo) {
    const url = `${window.location.origin}/apoie/${slug}`;
    const texto = `Apoie esta causa: ${titulo} - ${url}`;
    const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(texto)}`;
    window.open(whatsappUrl, '_blank');
}

// Copiar link da petição
function copiarLink(slug) {
    const url = `${window.location.origin}/apoie/${slug}`;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Link copiado para a área de transferência!', 'success');
    });
}

// Auto-refresh da página a cada 30 segundos para atualizar estatísticas
setInterval(() => {
    // Verificar se não há modals abertos ou formulários sendo editados
    if (!document.querySelector('.modal.show') && !document.querySelector('form[data-changed="true"]')) {
        location.reload();
    }
}, 30000);
</script>

<style>
.assinaturas-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--cor-primaria);
    margin-bottom: 0.25rem;
}

.progress-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.progress-bar {
    flex: 1;
    height: 8px;
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.bg-primary { background-color: var(--cor-primaria); }
.bg-warning { background-color: #ffc107; }
.bg-success { background-color: #28a745; }

.text-primary { color: var(--cor-primaria); }
.text-warning { color: #ffc107; }
.text-success { color: #28a745; }

.stat-card {
    border-left-color: var(--cor-primaria);
}

.badge {
    font-size: 0.75rem;
}

.table td {
    vertical-align: middle;
}

@media (max-width: 768px) {
    .table-container {
        font-size: 0.9rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
    }
    
    .progress-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .progress-bar {
        width: 100%;
    }
}
</style>

<?php require_once '../footer.php'; ?>