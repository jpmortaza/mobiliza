<?php
$titulo_pagina = 'Inscrições de Eventos';
require_once '../header.php';

try {
    $pdo = conectar_db();
    
    // Filtros
    $evento_id = $_GET['evento_id'] ?? '';
    $search = $_GET['search'] ?? '';
    $checkin_status = $_GET['checkin'] ?? '';
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    // Buscar eventos para o filtro
    $stmt = $pdo->query("SELECT id, titulo FROM eventos WHERE ativo = 1 ORDER BY data_evento DESC");
    $eventos = $stmt->fetchAll();
    
    // Construir query principal
    $where = [];
    $params = [];
    
    if ($evento_id) {
        $where[] = "i.evento_id = ?";
        $params[] = $evento_id;
    }
    
    if ($search) {
        $where[] = "(i.nome LIKE ? OR i.whatsapp LIKE ? OR i.cidade LIKE ? OR i.email LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if ($checkin_status !== '') {
        $where[] = "i.checkin = ?";
        $params[] = (int)$checkin_status;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Contar total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM inscricoes_eventos i 
        JOIN eventos e ON i.evento_id = e.id 
        $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Buscar inscrições
    $stmt = $pdo->prepare("
        SELECT i.*, e.titulo as evento_titulo, e.data_evento, e.slug as evento_slug
        FROM inscricoes_eventos i 
        JOIN eventos e ON i.evento_id = e.id 
        $whereClause 
        ORDER BY i.data_inscricao DESC 
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute($params);
    $inscricoes = $stmt->fetchAll();
    
    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_inscricoes,
            COUNT(CASE WHEN i.checkin = 1 THEN 1 END) as total_checkins,
            COUNT(DISTINCT i.evento_id) as eventos_com_inscricoes,
            COUNT(DISTINCT i.whatsapp) as contatos_unicos
        FROM inscricoes_eventos i 
        JOIN eventos e ON i.evento_id = e.id 
        $whereClause
    ");
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Erro ao buscar inscrições: " . $e->getMessage());
    $inscricoes = [];
    $eventos = [];
    $stats = ['total_inscricoes' => 0, 'total_checkins' => 0, 'eventos_com_inscricoes' => 0, 'contatos_unicos' => 0];
    $total = 0;
}

$total_pages = ceil($total / $limit);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;">Inscrições de Eventos</h2>
        <p class="text-secondary">Gerencie todas as inscrições realizadas nos eventos</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <?php if (!empty($inscricoes)): ?>
            <a href="api.php?action=exportar_csv&<?php echo http_build_query($_GET); ?>" 
               class="btn btn-success">
                <i class="fas fa-download"></i> Exportar CSV
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Estatísticas -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_inscricoes']); ?></div>
        <div class="stat-label">Total de Inscrições</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_checkins']); ?></div>
        <div class="stat-label">Check-ins Realizados</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">
            <?php 
            $percentual = $stats['total_inscricoes'] > 0 ? 
                round(($stats['total_checkins'] / $stats['total_inscricoes']) * 100) : 0;
            echo $percentual . '%';
            ?>
        </div>
        <div class="stat-label">Taxa de Presença</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['contatos_unicos']); ?></div>
        <div class="stat-label">Contatos Únicos</div>
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
                <label for="evento_id" class="form-label">Evento</label>
                <select name="evento_id" id="evento_id" class="form-control">
                    <option value="">Todos os eventos</option>
                    <?php foreach ($eventos as $evento): ?>
                        <option value="<?php echo $evento['id']; ?>" 
                                <?php echo ($evento_id == $evento['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($evento['titulo']); ?>
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
                <label for="checkin" class="form-label">Check-in</label>
                <select name="checkin" id="checkin" class="form-control">
                    <option value="">Todos</option>
                    <option value="1" <?php echo ($checkin_status === '1') ? 'selected' : ''; ?>>Presentes</option>
                    <option value="0" <?php echo ($checkin_status === '0') ? 'selected' : ''; ?>>Ausentes</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="inscricoes.php" class="btn btn-outline">Limpar</a>
            </div>
        </form>
    </div>
</div>

<?php if (empty($inscricoes)): ?>
    <div class="card text-center">
        <div class="card-body" style="padding: 4rem;">
            <i class="fas fa-user-friends" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
            <h3>Nenhuma inscrição encontrada</h3>
            <p class="text-secondary">
                <?php if ($search || $evento_id || $checkin_status !== ''): ?>
                    Tente ajustar os filtros para encontrar as inscrições desejadas.
                <?php else: ?>
                    As inscrições aparecerão aqui conforme as pessoas se cadastrarem nos eventos.
                <?php endif; ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- Lista de Inscrições -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 style="margin: 0;">
                📋 Inscrições 
                <span class="badge badge-primary"><?php echo number_format($total); ?></span>
            </h3>
            <div>
                <button onclick="marcarTodosPresentes()" class="btn btn-sm btn-success">
                    <i class="fas fa-check-double"></i> Marcar Todos Presentes
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
                        <th>Participante</th>
                        <th>Evento</th>
                        <th>Check-in</th>
                        <th>Inscrição</th>
                        <th>Referência</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscricoes as $inscricao): ?>
                        <tr class="<?php echo $inscricao['checkin'] ? 'table-success' : ''; ?>">
                            <td>
                                <input type="checkbox" class="inscricao-checkbox" value="<?php echo $inscricao['id']; ?>">
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($inscricao['nome']); ?></strong>
                                    <br>
                                    <small class="text-secondary">
                                        <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($inscricao['whatsapp']); ?>
                                    </small>
                                    <?php if ($inscricao['email']): ?>
                                        <br>
                                        <small class="text-secondary">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($inscricao['email']); ?>
                                        </small>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-secondary">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($inscricao['cidade']); ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($inscricao['evento_titulo']); ?></strong>
                                    <?php if ($inscricao['data_evento']): ?>
                                        <br>
                                        <small class="text-secondary">
                                            <?php echo formatar_data_br($inscricao['data_evento']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <button onclick="toggleCheckin(<?php echo $inscricao['id']; ?>, this)" 
                                        class="btn btn-sm <?php echo $inscricao['checkin'] ? 'btn-success' : 'btn-danger'; ?>">
                                    <i class="fas <?php echo $inscricao['checkin'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                    <?php echo $inscricao['checkin'] ? 'Presente' : 'Ausente'; ?>
                                </button>
                                <?php if ($inscricao['checkin'] && $inscricao['data_checkin']): ?>
                                    <br>
                                    <small class="text-secondary">
                                        <?php echo formatar_data_br($inscricao['data_checkin']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-secondary">
                                    <?php echo formatar_data_br($inscricao['data_inscricao']); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($inscricao['referencia']): ?>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($inscricao['referencia']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="../../eventos/?slug=<?php echo $inscricao['evento_slug']; ?>" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline" 
                                       title="Ver evento">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button onclick="excluirInscricao(<?php echo $inscricao['id']; ?>, '<?php echo htmlspecialchars($inscricao['nome']); ?>')" 
                                            class="btn btn-sm btn-danger" 
                                            title="Excluir inscrição">
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
// Toggle check-in individual
async function toggleCheckin(id, button) {
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=toggle_checkin&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            const isCheckedIn = result.checkin;
            const row = button.closest('tr');
            
            button.className = `btn btn-sm ${isCheckedIn ? 'btn-success' : 'btn-danger'}`;
            button.innerHTML = `<i class="fas ${isCheckedIn ? 'fa-check' : 'fa-times'}"></i> ${isCheckedIn ? 'Presente' : 'Ausente'}`;
            
            if (isCheckedIn) {
                row.classList.add('table-success');
            } else {
                row.classList.remove('table-success');
            }
            
            showToast(result.message, 'success');
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao alterar check-in', 'danger');
        console.error('Erro:', error);
    } finally {
        button.disabled = false;
        if (button.innerHTML.includes('spinner')) {
            button.innerHTML = originalText;
        }
    }
}

// Selecionar todos
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.inscricao-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Marcar todos como presentes
async function marcarTodosPresentes() {
    const checkboxes = document.querySelectorAll('.inscricao-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showToast('Selecione pelo menos uma inscrição', 'warning');
        return;
    }
    
    if (!confirm(`Marcar ${checkboxes.length} pessoa(s) como presente(s)?`)) {
        return;
    }
    
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    try {
        const promises = ids.map(id => 
            fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=marcar_presente&id=${id}`
            })
        );
        
        await Promise.all(promises);
        showToast('Check-ins atualizados com sucesso!', 'success');
        setTimeout(() => location.reload(), 1000);
        
    } catch (error) {
        showToast('Erro ao atualizar check-ins', 'danger');
        console.error('Erro:', error);
    }
}

// Excluir inscrição
async function excluirInscricao(id, nome) {
    if (!confirm(`Tem certeza que deseja excluir a inscrição de "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
        return;
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=excluir_inscricao&id=${id}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao excluir inscrição', 'danger');
        console.error('Erro:', error);
    }
}

// Auto-refresh a cada 30 segundos para atualizar dados em tempo real
setInterval(() => {
    // Apenas se não há checkboxes selecionados para evitar perder seleções
    const hasSelection = document.querySelectorAll('.inscricao-checkbox:checked').length > 0;
    if (!hasSelection) {
        location.reload();
    }
}, 30000);
</script>

<style>
.table-success {
    background-color: rgba(40, 167, 69, 0.1);
}

.pagination {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0.25rem;
}

.page-item {
    display: block;
}

.page-link {
    display: block;
    padding: 0.5rem 0.75rem;
    color: var(--cor-primaria);
    text-decoration: none;
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    transition: all 0.2s ease;
}

.page-link:hover {
    background-color: #f8f9fa;
    color: var(--cor-primaria);
    text-decoration: none;
}

.page-item.active .page-link {
    background-color: var(--cor-primaria);
    color: white;
    border-color: var(--cor-primaria);
}

.inscricao-checkbox {
    cursor: pointer;
}

.btn-group {
    display: flex;
    gap: 0.25rem;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .table-container {
        font-size: 0.9rem;
    }
    
    .d-flex.gap-1 {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php require_once '../footer.php'; ?>