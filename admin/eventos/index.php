<?php
/**
 * ADMIN: Gestão de Eventos
 * Arquivo: admin/eventos/index.php
 * Estrutura real baseada no MySQL fornecido
 */

$titulo_pagina = 'Gerenciar Eventos';
require_once '../header.php';

// Conexão com PDO
$pdo = conectar_db();

// Buscar todos os eventos
$sql = "SELECT e.id, e.titulo, e.slug, e.data_evento, e.ativo, 
               COUNT(i.id) as total_inscricoes,
               SUM(CASE WHEN i.checkin = 1 THEN 1 ELSE 0 END) as total_checkins
        FROM eventos e 
        LEFT JOIN inscricoes_eventos i ON e.id = i.evento_id 
        GROUP BY e.id 
        ORDER BY e.data_criacao DESC";

$eventos = $pdo->query($sql)->fetchAll();

$msg = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';
?>

<div class="admin-section-header">
    <h2>Gerenciar Eventos</h2>
    <a href="form.php" class="btn-principal" style="text-decoration: none; padding: 10px 20px;">
        <i class="fas fa-plus"></i> Criar Novo Evento
    </a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
<?php endif; ?>

<div class="admin-table" style="margin-top: 2rem;">
    <table>
        <thead>
            <tr>
                <th>Título do Evento</th>
                <th>Data</th>
                <th>Status</th>
                <th>Inscrições</th>
                <th>Check-ins</th>
                <th>Slug (URL)</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($eventos)): ?>
                <?php foreach($eventos as $evento): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                    </td>
                    <td>
                        <?php if ($evento['data_evento']): ?>
                            <?php echo formatar_data_br($evento['data_evento']); ?>
                        <?php else: ?>
                            <span style="color: #999;">Não definida</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $evento['ativo'] ? 'ativo' : 'inativo'; ?>">
                            <?php echo $evento['ativo'] ? 'Ativo' : 'Inativo'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="stat-number"><?php echo $evento['total_inscricoes']; ?></span>
                        <small>inscrições</small>
                    </td>
                    <td>
                        <span class="stat-number"><?php echo $evento['total_checkins']; ?></span>
                        <small>check-ins</small>
                        <?php if ($evento['total_inscricoes'] > 0): ?>
                            <br><small style="color: #666;">
                                (<?php echo round(($evento['total_checkins'] / $evento['total_inscricoes']) * 100, 1); ?>%)
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code><?php echo htmlspecialchars($evento['slug']); ?></code>
                    </td>
                    <td class="action-links">
                        <a href="form.php?id=<?php echo $evento['id']; ?>" title="Editar">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        
                        <a href="inscricoes.php?evento_id=<?php echo $evento['id']; ?>" title="Ver Inscrições">
                            <i class="fas fa-users"></i> Inscrições
                        </a>
                        
                        <a href="../../eventos/?slug=<?php echo htmlspecialchars($evento['slug']); ?>" target="_blank" title="Ver Página Pública">
                            <i class="fas fa-external-link-alt"></i> Ver
                        </a>
                        
                        <a href="#" onclick="confirmarExclusao(<?php echo $evento['id']; ?>, '<?php echo addslashes($evento['titulo']); ?>')" class="delete" title="Excluir">
                            <i class="fas fa-trash"></i> Excluir
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px;">
                        <div class="empty-state">
                            <i class="fas fa-calendar-plus" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                            <h3 style="color: #666;">Nenhum evento encontrado</h3>
                            <p style="color: #999;">Comece criando seu primeiro evento!</p>
                            <a href="form.php" class="btn-principal" style="margin-top: 15px; text-decoration: none; padding: 12px 25px;">
                                <i class="fas fa-plus"></i> Criar Primeiro Evento
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de confirmação de exclusão -->
<div id="modal-exclusao" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; max-width: 400px; text-align: center;">
        <h3 style="color: var(--cor-perigo); margin-top: 0;">
            <i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão
        </h3>
        <p>Tem certeza que deseja excluir o evento <strong id="nome-evento-exclusao"></strong>?</p>
        <p style="color: #666; font-size: 0.9rem;">
            <i class="fas fa-warning"></i> Esta ação excluirá também todas as inscrições e participantes associados.
        </p>
        <div style="margin-top: 25px; display: flex; gap: 10px; justify-content: center;">
            <button onclick="fecharModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button id="btn-confirmar-exclusao" onclick="excluirEvento()" style="padding: 10px 20px; background: var(--cor-perigo); color: white; border: none; border-radius: 6px; cursor: pointer;">
                <i class="fas fa-trash"></i> Excluir
            </button>
        </div>
    </div>
</div>

<style>
.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}
.status-ativo {
    background-color: #d4edda;
    color: #155724;
}
.status-inativo {
    background-color: #f8d7da;
    color: #721c24;
}
.stat-number {
    font-weight: 700;
    color: var(--cor-primaria);
}
.action-links {
    white-space: nowrap;
}
.action-links a {
    margin-right: 15px;
    color: var(--cor-secundaria);
    text-decoration: none;
    font-size: 0.9rem;
}
.action-links a:hover {
    color: var(--cor-primaria);
}
.action-links a.delete {
    color: var(--cor-perigo);
}
.action-links a.delete:hover {
    color: #c82333;
}
.empty-state {
    text-align: center;
}
.empty-state h3 {
    margin: 0;
}
.empty-state p {
    margin: 5px 0 0 0;
}
/* Estilos para a tabela de eventos */
.admin-table {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.admin-table table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    background: #fafafa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: #666;
    border-bottom: 2px solid #e0e0e0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    vertical-align: middle;
}

.admin-table tbody tr:hover {
    background: #fafafa;
}

.admin-table tbody tr:last-child td {
    border-bottom: none;
}

/* Status badges */
.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-ativo {
    background: #d4edda;
    color: #155724;
}

.status-inativo {
    background: #f8d7da;
    color: #721c24;
}

/* Números de estatísticas */
.stat-number {
    font-size: 18px;
    font-weight: 700;
    color: #27ae60;
    display: block;
}

.admin-table small {
    font-size: 12px;
    color: #999;
    display: block;
}

/* Links de ação */
.action-links {
    white-space: nowrap;
}

.action-links a {
    display: inline-block;
    margin-right: 10px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.2s;
}

.action-links a:hover {
    color: #27ae60;
}

.action-links a.delete {
    color: #e74c3c;
}

.action-links a.delete:hover {
    color: #c0392b;
}

.action-links a i {
    margin-right: 3px;
}

/* Cabeçalho da seção */
.admin-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.admin-section-header h2 {
    margin: 0;
    color: #333;
    font-size: 24px;
}

.btn-principal {
    background: #27ae60;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.btn-principal:hover {
    background: #219a52;
    color: white;
    text-decoration: none;
}

/* Código inline */
code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 13px;
    color: #e74c3c;
    font-family: 'Courier New', monospace;
}

/* Responsivo */
@media (max-width: 1200px) {
    .admin-table {
        overflow-x: auto;
    }
    
    .admin-table table {
        min-width: 800px;
    }
}

@media (max-width: 768px) {
    .admin-section-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .action-links a {
        display: block;
        margin-bottom: 5px;
    }
}
</style>

<script>
let eventoParaExcluir = null;

function confirmarExclusao(id, nome) {
    eventoParaExcluir = id;
    document.getElementById('nome-evento-exclusao').textContent = nome;
    document.getElementById('modal-exclusao').style.display = 'block';
}

function fecharModal() {
    document.getElementById('modal-exclusao').style.display = 'none';
    eventoParaExcluir = null;
}

async function excluirEvento() {
    if (!eventoParaExcluir) return;
    
    const btnConfirmar = document.getElementById('btn-confirmar-exclusao');
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Excluindo...';
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_evento&id=${eventoParaExcluir}`
        });
        
        const result = await response.json();
        
        if (result.status === 'sucesso') {
            window.location.href = 'index.php?msg=' + encodeURIComponent(result.msg);
        } else {
            alert('Erro: ' + result.msg);
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-trash"></i> Excluir';
        }
    } catch (error) {
        alert('Erro de conexão ao excluir evento.');
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="fas fa-trash"></i> Excluir';
    }
    
    fecharModal();
}

// Fechar modal clicando fora dele
document.getElementById('modal-exclusao').addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModal();
    }
});

// Atalhos de teclado
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModal();
    }
});
</script>

<?php
require_once '../footer.php';
?>