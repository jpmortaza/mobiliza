<?php
$titulo_pagina = 'Assinaturas de Petições';
require_once '../header.php';

try {
    $pdo = conectar_db();
    
    // Filtros
    $peticao_id = $_GET['peticao_id'] ?? '';
    $search = $_GET['search'] ?? '';
    $cidade_filter = $_GET['cidade'] ?? '';
    $referencia_filter = $_GET['referencia'] ?? '';
    $periodo = $_GET['periodo'] ?? '';
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Buscar petições para o filtro
    $stmt = $pdo->query("SELECT id, titulo FROM peticoes WHERE ativo = 1 ORDER BY data_criacao DESC");
    $peticoes = $stmt->fetchAll();
    
    // Construir query principal
    $where = [];
    $params = [];
    
    if ($peticao_id) {
        $where[] = "a.peticao_id = ?";
        $params[] = $peticao_id;
    }
    
    if ($search) {
        $where[] = "(a.nome LIKE ? OR a.whatsapp LIKE ? OR a.cidade LIKE ? OR a.email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($cidade_filter) {
        $where[] = "a.cidade = ?";
        $params[] = $cidade_filter;
    }
    
    if ($referencia_filter) {
        $where[] = "a.referencia = ?";
        $params[] = $referencia_filter;
    }
    
    if ($periodo) {
        switch ($periodo) {
            case 'hoje':
                $where[] = "DATE(a.data_assinatura) = CURDATE()";
                break;
            case 'semana':
                $where[] = "a.data_assinatura >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'mes':
                $where[] = "a.data_assinatura >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM assinaturas_peticoes a 
        JOIN peticoes p ON a.peticao_id = p.id 
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Buscar assinaturas
    $stmt = $pdo->prepare("
        SELECT a.*, p.titulo as peticao_titulo, p.slug as peticao_slug
        FROM assinaturas_peticoes a 
        JOIN peticoes p ON a.peticao_id = p.id 
        $whereClause 
        ORDER BY a.data_assinatura DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $assinaturas = $stmt->fetchAll();
    
    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_assinaturas,
            COUNT(DISTINCT a.peticao_id) as peticoes_com_assinaturas,
            COUNT(DISTINCT a.whatsapp) as contatos_unicos,
            COUNT(DISTINCT a.cidade) as cidades_diferentes,
            COUNT(CASE WHEN a.referencia IS NOT NULL AND a.referencia != '' THEN 1 END) as via_referencia
        FROM assinaturas_peticoes a 
        JOIN peticoes p ON a.peticao_id = p.id 
        $whereClause
    ");
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
    // Buscar cidades para filtro
    $stmt = $pdo->query("
        SELECT cidade, COUNT(*) as total
        FROM assinaturas_peticoes 
        WHERE cidade != ''
        GROUP BY cidade 
        ORDER BY total DESC, cidade ASC
        LIMIT 15
    ");
    $cidades = $stmt->fetchAll();
    
    // Buscar referências para filtro
    $stmt = $pdo->query("
        SELECT referencia, COUNT(*) as total
        FROM assinaturas_peticoes 
        WHERE referencia IS NOT NULL AND referencia != ''
        GROUP BY referencia 
        ORDER BY total DESC
        LIMIT 15
    ");
    $referencias = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erro ao buscar assinaturas: " . $e->getMessage());
    $assinaturas = [];
    $peticoes = [];
    $cidades = [];
    $referencias = [];
    $stats = ['total_assinaturas' => 0, 'peticoes_com_assinaturas' => 0, 'contatos_unicos' => 0, 'cidades_diferentes' => 0, 'via_referencia' => 0];
    $total = 0;
}

$total_pages = ceil($total / $limit);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;">Assinaturas de Petições</h2>
        <p class="text-secondary">Gerencie todas as assinaturas coletadas nas campanhas</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <?php if (!empty($assinaturas)): ?>
            <a href="api.php?action=exportar_assinaturas_csv&<?php echo http_build_query($_GET); ?>" 
               class="btn btn-success">
                <i class="fas fa-download"></i> Exportar CSV
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_assinaturas']); ?></div>
        <div class="stat-label">Total de Assinaturas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['contatos_unicos']); ?></div>
        <div class="stat-label">Contatos Únicos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['cidades_diferentes']); ?></div>
        <div class="stat-label">Cidades Alcançadas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">
            <?php 
            $percentual = $stats['total_assinaturas'] > 0 ? 
                round(($stats['via_referencia'] / $stats['total_assinaturas']) * 100) : 0;
            echo $percentual . '%';
            ?>
        </div>
        <div class="stat-label">Via Referência</div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h3 style="margin: 0;">🔍 Filtros</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col">
                <label for="peticao_id" class="form-label">Petição</label>
                <select name="peticao_id" id="peticao_id" class="form-control">
                    <option value="">Todas as petições</option>
                    <?php foreach ($peticoes as $peticao): ?>
                        <option value="<?php echo $peticao['id']; ?>" 
                                <?php echo ($peticao_id == $peticao['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($peticao['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <label for="search" class="form-label">Buscar</label>
                <input type="text" name="search" id="search" class="form-control" 
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Nome, WhatsApp, cidade ou email">
            </div>
            <div class="col">
                <label for="cidade" class="form-label">Cidade</label>
                <select name="cidade" id="cidade" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($cidades as $cidade): ?>
                        <option value="<?php echo htmlspecialchars($cidade['cidade']); ?>" 
                                <?php echo ($cidade_filter === $cidade['cidade']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cidade['cidade']); ?> (<?php echo $cidade['total']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <label for="referencia" class="form-label">Referência</label>
                <select name="referencia" id="referencia" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($referencias as $ref): ?>
                        <option value="<?php echo htmlspecialchars($ref['referencia']); ?>" 
                                <?php echo ($referencia_filter === $ref['referencia']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ref['referencia']); ?> (<?php echo $ref['total']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <label for="periodo" class="form-label">Período</label>
                <select name="periodo" id="periodo" class="form-control">
                    <option value="">Todos</option>
                    <option value="hoje" <?php echo ($periodo === 'hoje') ? 'selected' : ''; ?>>Hoje</option>
                    <option value="semana" <?php echo ($periodo === 'semana') ? 'selected' : ''; ?>>Última semana</option>
                    <option value="mes" <?php echo ($periodo === 'mes') ? 'selected' : ''; ?>>Último mês</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="assinaturas.php" class="btn btn-outline">Limpar</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($assinaturas)): ?>
    <div class="card text-center">
        <div class="card-body" style="padding: 4rem;">
            <i class="fas fa-signature" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
            <h3>Nenhuma assinatura encontrada</h3>
            <p class="text-secondary">
                <?php if ($search || $peticao_id || $cidade_filter || $referencia_filter || $periodo): ?>
                    Tente ajustar os filtros para encontrar as assinaturas desejadas.
                <?php else: ?>
                    As assinaturas aparecerão aqui conforme as pessoas apoiarem as petições.
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- Lista de Assinaturas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 style="margin: 0;">
                ✍️ Assinaturas 
                <span class="badge badge-primary"><?php echo number_format($total); ?></span>
            </h3>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline dropdown-toggle" 
                            type="button" 
                            data-toggle="dropdown">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="api.php?action=exportar_assinaturas_csv&<?php echo http_build_query($_GET); ?>">
                            <i class="fas fa-file-csv"></i> CSV Completo
                        </a>
                        <a class="dropdown-item" href="#" onclick="exportarEmails()">
                            <i class="fas fa-envelope"></i> Lista de Emails
                        </a>
                        <a class="dropdown-item" href="#" onclick="exportarWhatsApp()">
                            <i class="fab fa-whatsapp"></i> Lista WhatsApp
                        </a>
                    </div>
                </div>
                <button onclick="selecionarTodos()" class="btn btn-sm btn-info">
                    <i class="fas fa-check-square"></i> Selecionar Todos
                </button>
            </div>
        </div>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </th>
                        <th>Apoiador</th>
                        <th>Petição</th>
                        <th>Data</th>
                        <th>Referência</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assinaturas as $assinatura): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="assinatura-checkbox" value="<?php echo $assinatura['id']; ?>">
                            </td>
                            <td>
                                <div class="apoiador-info">
                                    <div class="apoiador-avatar">
                                        <?php echo strtoupper(substr($assinatura['nome'], 0, 1)); ?>
                                    </div>
                                    <div class="apoiador-detalhes">
                                        <strong><?php echo htmlspecialchars($assinatura['nome']); ?></strong>
                                        <br>
                                        <small class="text-secondary">
                                            <i class="fab fa-whatsapp"></i> 
                                            <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $assinatura['whatsapp']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($assinatura['whatsapp']); ?>
                                            </a>
                                        </small>
                                        <?php if ($assinatura['email']): ?>
                                            <br>
                                            <small class="text-secondary">
                                                <i class="fas fa-envelope"></i> 
                                                <a href="mailto:<?php echo htmlspecialchars($assinatura['email']); ?>">
                                                    <?php echo htmlspecialchars($assinatura['email']); ?>
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-secondary">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($assinatura['cidade']); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($assinatura['peticao_titulo']); ?></strong>
                                    <br>
                                    <small class="text-secondary">
                                        <a href="../../apoie/?slug=<?php echo $assinatura['peticao_slug']; ?>" target="_blank">
                                            Ver petição <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <small class="text-secondary">
                                    <?php echo formatar_data_br($assinatura['data_assinatura']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($assinatura['referencia']): ?>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($assinatura['referencia']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $assinatura['whatsapp']); ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-success" 
                                       title="WhatsApp">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <?php if ($assinatura['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($assinatura['email']); ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="Email">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="excluirAssinatura(<?php echo $assinatura['id']; ?>, '<?php echo htmlspecialchars($assinatura['nome']); ?>')" 
                                            class="btn btn-sm btn-danger" 
                                            title="Excluir assinatura">
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
    
    <!-- Ações em Lote -->
    <div class="card mt-4" id="acoes-lote" style="display: none;">
        <div class="card-header">
            <h3 style="margin: 0;">🔧 Ações em Lote</h3>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2 align-items-center">
                <span id="contador-selecionados" class="badge badge-primary">0 selecionados</span>
                <button onclick="exportarSelecionados()" class="btn btn-success">
                    <i class="fas fa-download"></i> Exportar Selecionados
                </button>
                <button onclick="enviarWhatsAppLote()" class="btn btn-success">
                    <i class="fab fa-whatsapp"></i> WhatsApp em Lote
                </button>
                <button onclick="criarListaEmail()" class="btn btn-info">
                    <i class="fas fa-envelope"></i> Lista de Email
                </button>
                <button onclick="limparSelecao()" class="btn btn-outline">
                    <i class="fas fa-times"></i> Limpar Seleção
                </button>
            </div>
        </div>
    </div>
    
    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                « Anterior
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                Próxima »
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        
        <div class="text-center text-secondary mt-2">
            Página <?php echo $page; ?> de <?php echo $total_pages; ?> 
            (<?php echo number_format($total); ?> registros)
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
let assinaturasSelecionadas = [];

// Gerenciar seleção
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('assinatura-checkbox')) {
        const id = e.target.value;
        
        if (e.target.checked) {
            if (!assinaturasSelecionadas.includes(id)) {
                assinaturasSelecionadas.push(id);
            }
        } else {
            assinaturasSelecionadas = assinaturasSelecionadas.filter(i => i !== id);
        }
        
        atualizarContadorSelecao();
    }
});

function atualizarContadorSelecao() {
    const contador = document.getElementById('contador-selecionados');
    const acoesLote = document.getElementById('acoes-lote');
    
    contador.textContent = `${assinaturasSelecionadas.length} selecionados`;
    
    if (assinaturasSelecionadas.length > 0) {
        acoesLote.style.display = 'block';
    } else {
        acoesLote.style.display = 'none';
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.assinatura-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
        
        const id = checkbox.value;
        if (selectAll.checked) {
            if (!assinaturasSelecionadas.includes(id)) {
                assinaturasSelecionadas.push(id);
            }
        } else {
            assinaturasSelecionadas = assinaturasSelecionadas.filter(i => i !== id);
        }
    });
    
    atualizarContadorSelecao();
}

function selecionarTodos() {
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = !selectAll.checked;
    toggleSelectAll();
}

function limparSelecao() {
    document.querySelectorAll('.assinatura-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    assinaturasSelecionadas = [];
    atualizarContadorSelecao();
}

// Exportar emails
function exportarEmails() {
    const emails = [];
    document.querySelectorAll('tbody tr').forEach(row => {
        const emailElement = row.querySelector('.apoiador-detalhes a[href^="mailto:"]');
        if (emailElement) {
            emails.push(emailElement.textContent.trim());
        }
    });
    
    if (emails.length === 0) {
        showToast('Nenhum email encontrado na lista atual', 'warning');
        return;
    }
    
    navigator.clipboard.writeText(emails.join('; ')).then(() => {
        showToast(`${emails.length} emails copiados para a área de transferência!`, 'success');
    });
}

// Exportar WhatsApp
function exportarWhatsApp() {
    const whatsapps = [];
    document.querySelectorAll('tbody tr').forEach(row => {
        const whatsappElement = row.querySelector('.apoiador-detalhes a[href^="https://wa.me/"]');
        if (whatsappElement) {
            const numero = whatsappElement.textContent.trim();
            whatsapps.push(numero);
        }
    });
    
    if (whatsapps.length === 0) {
        showToast('Nenhum WhatsApp encontrado na lista atual', 'warning');
        return;
    }
    
    navigator.clipboard.writeText(whatsapps.join('\n')).then(() => {
        showToast(`${whatsapps.length} números WhatsApp copiados para a área de transferência!`, 'success');
    });
}

// Exportar selecionados
function exportarSelecionados() {
    if (assinaturasSelecionadas.length === 0) {
        showToast('Selecione pelo menos uma assinatura', 'warning');
        return;
    }
    
    const params = new URLSearchParams(window.location.search);
    params.set('action', 'exportar_assinaturas_csv');
    params.set('ids', assinaturasSelecionadas.join(','));
    
    window.open(`api.php?${params.toString()}`, '_blank');
    showToast(`Exportando ${assinaturasSelecionadas.length} assinaturas...`, 'success');
}

// WhatsApp em lote
function enviarWhatsAppLote() {
    if (assinaturasSelecionadas.length === 0) {
        showToast('Selecione pelo menos uma assinatura', 'warning');
        return;
    }
    
    if (assinaturasSelecionadas.length > 10) {
        showToast('Máximo 10 contatos por vez no WhatsApp', 'warning');
        return;
    }
    
    const mensagem = prompt('Digite a mensagem para enviar:');
    if (!mensagem) return;
    
    assinaturasSelecionadas.forEach((id, index) => {
        const row = document.querySelector(`input[value="${id}"]`).closest('tr');
        const whatsappLink = row.querySelector('a[href^="https://wa.me/"]');
        
        if (whatsappLink) {
            setTimeout(() => {
                const url = whatsappLink.href + `&text=${encodeURIComponent(mensagem)}`;
                window.open(url, '_blank');
            }, index * 1000);
        }
    });
    
    showToast(`Abrindo WhatsApp para ${assinaturasSelecionadas.length} contatos...`, 'success');
}

// Criar lista de emails selecionados
function criarListaEmail() {
    if (assinaturasSelecionadas.length === 0) {
        showToast('Selecione pelo menos uma assinatura', 'warning');
        return;
    }
    
    const emails = [];
    assinaturasSelecionadas.forEach(id => {
        const row = document.querySelector(`input[value="${id}"]`).closest('tr');
        const emailElement = row.querySelector('a[href^="mailto:"]');
        if (emailElement) {
            emails.push(emailElement.textContent.trim());
        }
    });
    
    if (emails.length === 0) {
        showToast('Nenhum dos selecionados possui email', 'warning');
        return;
    }
    
    navigator.clipboard.writeText(emails.join('; ')).then(() => {
        showToast(`${emails.length} emails copiados para a área de transferência!`, 'success');
    });
}

// Excluir assinatura
async function excluirAssinatura(id, nome) {
    if (!confirm(`Tem certeza que deseja excluir a assinatura de "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=excluir_assinatura&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao excluir assinatura', 'danger');
        console.error('Erro:', error);
    }
}

// Auto-refresh a cada minuto para assinaturas novas
setInterval(() => {
    if (assinaturasSelecionadas.length === 0) {
        location.reload();
    }
}, 60000);
</script>

<style>
.apoiador-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.apoiador-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--cor-primaria);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
    flex-shrink: 0;
}

.apoiador-detalhes {
    flex: 1;
    min-width: 0;
}

.apoiador-detalhes a {
    color: inherit;
    text-decoration: none;
}

.apoiador-detalhes a:hover {
    text-decoration: underline;
}

.table td {
    vertical-align: middle;
}

.assinatura-checkbox {
    transform: scale(1.2);
    cursor: pointer;
}

.dropdown-menu {
    min-width: 200px;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-container {
        font-size: 0.9rem;
    }
    
    .apoiador-info {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .d-flex.gap-1 {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<?php require_once '../footer.php'; ?>