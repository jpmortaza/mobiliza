formUsuario<?php
require_once '../header.php';

// Verificar se é admin
if ($_SESSION['usuario_tipo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit();
}

try {
    $pdo = conectar_db();
    
    // Buscar todos os usuários da tabela correta
    $stmt = $pdo->query("
        SELECT 
            u.*,
            (SELECT COUNT(*) FROM logs_sistema WHERE usuario_id = u.id) as total_acoes
        FROM usuarios_sistema u
        ORDER BY u.data_criacao DESC
    ");
    $usuarios = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erro ao buscar usuários: " . $e->getMessage());
    $usuarios = [];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;">Gerenciar Usuários</h2>
        <p class="text-secondary">Gerencie os usuários com acesso ao sistema</p>
    </div>
    <button onclick="novoUsuario()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo Usuário
    </button>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($usuarios)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h3 class="text-muted">Nenhum usuário cadastrado</h3>
                <p class="text-muted">Clique em "Novo Usuário" para adicionar o primeiro usuário.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Último Acesso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                    <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                        <span class="badge badge-info">Você</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php 
                                    switch($usuario['tipo']) {
                                        case 'admin':
                                            echo '<span class="badge badge-danger">Administrador</span>';
                                            break;
                                        case 'checkin':
                                            echo '<span class="badge badge-info">Check-in</span>';
                                            break;
                                        case 'organizador':
                                            echo '<span class="badge badge-warning">Organizador</span>';
                                            break;
                                        default:
                                            echo '<span class="badge badge-secondary">Usuário</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ativo']): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_login']): ?>
                                        <small>
                                            <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca acessou</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-links">
                                        <a href="#" onclick="editarUsuario(<?php echo $usuario['id']; ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <?php if ($usuario['ativo']): ?>
                                                <a href="#" onclick="alterarStatus(<?php echo $usuario['id']; ?>, 0)" 
                                                   title="Desativar" class="text-warning">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="#" onclick="alterarStatus(<?php echo $usuario['id']; ?>, 1)" 
                                                   title="Ativar" class="text-success">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="#" onclick="excluirUsuario(<?php echo $usuario['id']; ?>)" 
                                               title="Excluir" class="delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Usuário -->
<div id="modalUsuario" class="modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitulo">Novo Usuário</h5>
                <button type="button" class="close" onclick="fecharModal()">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formUsuario">
                <input type="hidden" id="usuario_id" name="id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" id="usuario_nome" name="nome" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" id="usuario_email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Senha *</label>
                        <input type="password" id="usuario_senha" name="senha" class="form-control">
                        <small class="form-text">Deixe em branco para manter a senha atual (apenas edição)</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tipo de Usuário *</label>
                        <select id="usuario_tipo" name="tipo" class="form-control" required>
                            <option value="admin">Administrador</option>
                            <option value="checkin">Check-in</option>
                            <option value="organizador">Organizador</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="evento_permitido_group" style="display: none;">
                        <label class="form-label">Evento Permitido</label>
                        <select id="usuario_evento_permitido" name="evento_permitido_id" class="form-control">
                            <option value="">Todos os eventos</option>
                            <?php
                            // Buscar eventos para o select
                            $stmt = $pdo->query("SELECT id, titulo FROM eventos WHERE ativo = 1 ORDER BY titulo");
                            $eventos = $stmt->fetchAll();
                            foreach ($eventos as $evento):
                            ?>
                                <option value="<?php echo $evento['id']; ?>">
                                    <?php echo htmlspecialchars($evento['titulo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Selecione o evento que este usuário pode gerenciar</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" id="usuario_ativo" name="ativo" value="1" checked>
                            Usuário ativo
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar campo de evento permitido baseado no tipo
document.getElementById('usuario_tipo').addEventListener('change', function() {
    const eventoGroup = document.getElementById('evento_permitido_group');
    if (this.value === 'checkin' || this.value === 'organizador') {
        eventoGroup.style.display = 'block';
    } else {
        eventoGroup.style.display = 'none';
        document.getElementById('usuario_evento_permitido').value = '';
    }
});

// Função para mostrar mensagens toast
function showToast(message, type = 'info') {
    // Criar elemento de notificação
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = message;
    
    document.body.appendChild(toast);
    
    // Remover após 5 segundos
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function novoUsuario() {
    document.getElementById('modalUsuarioTitulo').textContent = 'Novo Usuário';
    document.getElementById('formUsuario').reset();
    document.getElementById('usuario_id').value = '';
    document.getElementById('usuario_senha').required = true;
    document.getElementById('evento_permitido_group').style.display = 'none';
    document.getElementById('modalUsuario').style.display = 'block';
}

function editarUsuario(id) {
    fetch(`api.php?action=buscar&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuário';
                document.getElementById('usuario_id').value = data.usuario.id;
                document.getElementById('usuario_nome').value = data.usuario.nome;
                document.getElementById('usuario_email').value = data.usuario.email;
                document.getElementById('usuario_tipo').value = data.usuario.tipo;
                document.getElementById('usuario_ativo').checked = data.usuario.ativo == 1;
                document.getElementById('usuario_senha').required = false;
                document.getElementById('usuario_senha').value = '';
                
                // Configurar evento permitido
                if (data.usuario.tipo === 'checkin' || data.usuario.tipo === 'organizador') {
                    document.getElementById('evento_permitido_group').style.display = 'block';
                    document.getElementById('usuario_evento_permitido').value = data.usuario.evento_permitido_id || '';
                } else {
                    document.getElementById('evento_permitido_group').style.display = 'none';
                }
                
                document.getElementById('modalUsuario').style.display = 'block';
            }
        })
        .catch(error => {
            showToast('Erro ao buscar usuário', 'danger');
            console.error('Erro:', error);
        });
}

function fecharModal() {
    document.getElementById('modalUsuario').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalUsuario');
    if (event.target == modal) {
        fecharModal();
    }
}

// Submit do formulário
// Submit do formulário
document.getElementById('formUsuario').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'salvar');
    
    // Se não marcou ativo, enviar como 0
    if (!document.getElementById('usuario_ativo').checked) {
        formData.set('ativo', '0');
    }
    
    // Debug - mostrar o que está sendo enviado
    console.log('Dados sendo enviados:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        // Primeiro, vamos ver o texto da resposta
        const responseText = await response.text();
        console.log('Resposta do servidor:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Erro ao fazer parse do JSON:', parseError);
            console.error('Resposta recebida:', responseText);
            showToast('Erro no servidor: resposta inválida', 'danger');
            return;
        }
        
        if (result.success) {
            showToast(result.message, 'success');
            fecharModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            console.error('Erro retornado pelo servidor:', result);
            showToast(result.message || 'Erro ao salvar usuário', 'danger');
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        showToast('Erro ao salvar usuário: ' + error.message, 'danger');
    }
});

async function alterarStatus(id, status) {
    const acao = status ? 'ativar' : 'desativar';
    
    if (!confirm(`Deseja realmente ${acao} este usuário?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'alterar_status');
        formData.append('id', id);
        formData.append('ativo', status);
        
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao alterar status', 'danger');
        console.error('Erro:', error);
    }
}

async function excluirUsuario(id) {
    if (!confirm('Tem certeza que deseja excluir este usuário?\n\nEsta ação não pode ser desfeita.')) {
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'danger');
        }
    } catch (error) {
        showToast('Erro ao excluir usuário', 'danger');
        console.error('Erro:', error);
    }
}
</script>

<style>
.modal {
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
}

.modal-dialog {
    position: relative;
    width: auto;
    margin: 1.75rem auto;
    max-width: 500px;
}

.modal-content {
    position: relative;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.5);
}

.modal-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 1rem;
}

.modal-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<?php require_once '../footer.php'; ?>