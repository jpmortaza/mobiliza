<?php
$titulo_pagina = 'Configurações';
require_once 'header.php';

// Buscar configurações existentes
try {
    $pdo = conectar_db();
    
    // Buscar configurações da tabela configuracoes (se existir)
    $configs = [];
    try {
        $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
        $configs = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        // Tabela pode não existir ainda
        error_log("Tabela configuracoes não encontrada: " . $e->getMessage());
    }
    
    // Buscar informações do usuário atual
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Erro ao buscar configurações: " . $e->getMessage());
    $configs = [];
    $usuario = [];
}

// Mensagens de feedback
$msg = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="margin: 0;">Configurações do Sistema</h2>
        <p class="text-secondary">Gerencie as configurações gerais e integrações</p>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo htmlspecialchars($erro); ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<!-- Tabs de Configuração -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#geral">
            <i class="fas fa-cog"></i> Geral
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#integracao">
            <i class="fas fa-plug"></i> Integrações
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#email">
            <i class="fas fa-envelope"></i> E-mail
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#seguranca">
            <i class="fas fa-shield-alt"></i> Segurança
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#backup">
            <i class="fas fa-database"></i> Backup
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">
    <!-- Configurações Gerais -->
    <div class="tab-pane fade show active" id="geral">
        <form id="formGeral" method="POST" action="salvar_configuracoes.php" enctype="multipart/form-data">
            <input type="hidden" name="tipo" value="geral">
            
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">🌐 Informações do Site</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nome_site">Nome do Site *</label>
                                <input type="text" class="form-control" id="nome_site" name="nome_site" 
                                       value="<?php echo htmlspecialchars($configs['nome_site'] ?? 'Mobiliza+'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="slogan">Slogan</label>
                                <input type="text" class="form-control" id="slogan" name="slogan" 
                                       value="<?php echo htmlspecialchars($configs['slogan'] ?? 'Sistema de Mobilização Digital'); ?>"
                                       placeholder="Frase de efeito do site">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao_site">Descrição do Site</label>
                        <textarea class="form-control" id="descricao_site" name="descricao_site" rows="3"
                                  placeholder="Descrição para SEO"><?php echo htmlspecialchars($configs['descricao_site'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Esta descrição será usada para SEO e redes sociais</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_contato">E-mail de Contato</label>
                                <input type="email" class="form-control" id="email_contato" name="email_contato" 
                                       value="<?php echo htmlspecialchars($configs['email_contato'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefone_contato">Telefone de Contato</label>
                                <input type="text" class="form-control" id="telefone_contato" name="telefone_contato" 
                                       value="<?php echo htmlspecialchars($configs['telefone_contato'] ?? ''); ?>"
                                       placeholder="(00) 00000-0000">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="logo">Logo do Site</label>
                                <input type="file" class="form-control-file" id="logo" name="logo" accept="image/*">
                                <?php if (!empty($configs['logo'])): ?>
                                    <div class="mt-2">
                                        <img src="../<?php echo htmlspecialchars($configs['logo']); ?>" 
                                             alt="Logo atual" style="max-height: 60px;">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="favicon">Favicon</label>
                                <input type="file" class="form-control-file" id="favicon" name="favicon" accept="image/x-icon,image/png">
                                <small class="form-text text-muted">Ícone que aparece na aba do navegador (.ico ou .png)</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 style="margin: 0;">📱 Redes Sociais</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="facebook">Facebook</label>
                                <input type="url" class="form-control" id="facebook" name="facebook" 
                                       value="<?php echo htmlspecialchars($configs['facebook'] ?? ''); ?>"
                                       placeholder="https://facebook.com/suapagina">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="instagram">Instagram</label>
                                <input type="url" class="form-control" id="instagram" name="instagram" 
                                       value="<?php echo htmlspecialchars($configs['instagram'] ?? ''); ?>"
                                       placeholder="https://instagram.com/seuperfil">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="twitter">Twitter/X</label>
                                <input type="url" class="form-control" id="twitter" name="twitter" 
                                       value="<?php echo htmlspecialchars($configs['twitter'] ?? ''); ?>"
                                       placeholder="https://twitter.com/seuperfil">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="youtube">YouTube</label>
                                <input type="url" class="form-control" id="youtube" name="youtube" 
                                       value="<?php echo htmlspecialchars($configs['youtube'] ?? ''); ?>"
                                       placeholder="https://youtube.com/seucanal">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="linkedin">LinkedIn</label>
                                <input type="url" class="form-control" id="linkedin" name="linkedin" 
                                       value="<?php echo htmlspecialchars($configs['linkedin'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/in/seuperfil">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tiktok">TikTok</label>
                                <input type="url" class="form-control" id="tiktok" name="tiktok" 
                                       value="<?php echo htmlspecialchars($configs['tiktok'] ?? ''); ?>"
                                       placeholder="https://tiktok.com/@seuperfil">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações Gerais
                </button>
            </div>
        </form>
    </div>
    
    <!-- Integrações -->
    <div class="tab-pane fade" id="integracao">
        <form id="formIntegracao" method="POST" action="salvar_configuracoes.php">
            <input type="hidden" name="tipo" value="integracao">
            
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📱 WhatsApp Business API</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="whatsapp_api_url">URL da API</label>
                        <input type="url" class="form-control" id="whatsapp_api_url" name="whatsapp_api_url" 
                               value="<?php echo htmlspecialchars($configs['whatsapp_api_url'] ?? ''); ?>"
                               placeholder="https://api.whatsapp.com/...">
                    </div>
                    <div class="form-group">
                        <label for="whatsapp_api_token">Token de Acesso</label>
                        <input type="text" class="form-control" id="whatsapp_api_token" name="whatsapp_api_token" 
                               value="<?php echo htmlspecialchars($configs['whatsapp_api_token'] ?? ''); ?>"
                               placeholder="Seu token de acesso">
                    </div>
                    <div class="form-group">
                        <label for="whatsapp_numero">Número do WhatsApp Business</label>
                        <input type="text" class="form-control" id="whatsapp_numero" name="whatsapp_numero" 
                               value="<?php echo htmlspecialchars($configs['whatsapp_numero'] ?? ''); ?>"
                               placeholder="+55 11 99999-9999">
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 style="margin: 0;">📊 Google Analytics</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="google_analytics_id">ID de Acompanhamento</label>
                        <input type="text" class="form-control" id="google_analytics_id" name="google_analytics_id" 
                               value="<?php echo htmlspecialchars($configs['google_analytics_id'] ?? ''); ?>"
                               placeholder="UA-XXXXXXXXX-X ou G-XXXXXXXXXX">
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 style="margin: 0;">🔍 Google Search Console</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="google_site_verification">Meta Tag de Verificação</label>
                        <input type="text" class="form-control" id="google_site_verification" name="google_site_verification" 
                               value="<?php echo htmlspecialchars($configs['google_site_verification'] ?? ''); ?>"
                               placeholder="Código de verificação do Google">
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 style="margin: 0;">📍 Google Maps</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="google_maps_api_key">API Key</label>
                        <input type="text" class="form-control" id="google_maps_api_key" name="google_maps_api_key" 
                               value="<?php echo htmlspecialchars($configs['google_maps_api_key'] ?? ''); ?>"
                               placeholder="Sua chave de API do Google Maps">
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Integrações
                </button>
            </div>
        </form>
    </div>
    
    <!-- Configurações de E-mail -->
    <div class="tab-pane fade" id="email">
        <form id="formEmail" method="POST" action="salvar_configuracoes.php">
            <input type="hidden" name="tipo" value="email">
            
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">📧 Configurações de E-mail (SMTP)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="smtp_host">Servidor SMTP</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                       value="<?php echo htmlspecialchars($configs['smtp_host'] ?? ''); ?>"
                                       placeholder="smtp.gmail.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="smtp_porta">Porta SMTP</label>
                                <input type="number" class="form-control" id="smtp_porta" name="smtp_porta" 
                                       value="<?php echo htmlspecialchars($configs['smtp_porta'] ?? '587'); ?>"
                                       placeholder="587">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="smtp_usuario">Usuário SMTP</label>
                                <input type="email" class="form-control" id="smtp_usuario" name="smtp_usuario" 
                                       value="<?php echo htmlspecialchars($configs['smtp_usuario'] ?? ''); ?>"
                                       placeholder="seu@email.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="smtp_senha">Senha SMTP</label>
                                <input type="password" class="form-control" id="smtp_senha" name="smtp_senha" 
                                       value="<?php echo htmlspecialchars($configs['smtp_senha'] ?? ''); ?>"
                                       placeholder="Sua senha SMTP">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="smtp_seguranca">Segurança</label>
                                <select class="form-control" id="smtp_seguranca" name="smtp_seguranca">
                                    <option value="tls" <?php echo ($configs['smtp_seguranca'] ?? 'tls') == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo ($configs['smtp_seguranca'] ?? '') == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="" <?php echo ($configs['smtp_seguranca'] ?? '') == '' ? 'selected' : ''; ?>>Nenhuma</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_remetente">E-mail Remetente</label>
                                <input type="email" class="form-control" id="email_remetente" name="email_remetente" 
                                       value="<?php echo htmlspecialchars($configs['email_remetente'] ?? ''); ?>"
                                       placeholder="noreply@seusite.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome_remetente">Nome do Remetente</label>
                        <input type="text" class="form-control" id="nome_remetente" name="nome_remetente" 
                               value="<?php echo htmlspecialchars($configs['nome_remetente'] ?? 'Mobiliza+'); ?>"
                               placeholder="Nome que aparecerá nos e-mails">
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="testarEmail()">
                        <i class="fas fa-paper-plane"></i> Testar Configurações
                    </button>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações de E-mail
                </button>
            </div>
        </form>
    </div>
    
    <!-- Segurança -->
    <div class="tab-pane fade" id="seguranca">
        <form id="formSeguranca" method="POST" action="salvar_configuracoes.php">
            <input type="hidden" name="tipo" value="seguranca">
            
            <div class="card">
                <div class="card-header">
                    <h3 style="margin: 0;">🔐 Configurações de Segurança</h3>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="forcar_https" name="forcar_https" 
                               <?php echo ($configs['forcar_https'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="forcar_https">
                            Forçar HTTPS em todo o site
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="manutencao" name="manutencao" 
                               <?php echo ($configs['manutencao'] ?? false) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="manutencao">
                            Modo de Manutenção (apenas administradores podem acessar)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="mensagem_manutencao">Mensagem de Manutenção</label>
                        <textarea class="form-control" id="mensagem_manutencao" name="mensagem_manutencao" rows="3"><?php echo htmlspecialchars($configs['mensagem_manutencao'] ?? 'Site em manutenção. Voltaremos em breve!'); ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <h4>Alterar Senha do Administrador</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="senha_atual">Senha Atual</label>
                                <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nova_senha">Nova Senha</label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="confirmar_senha">Confirmar Nova Senha</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações de Segurança
                </button>
            </div>
        </form>
    </div>
    
    <!-- Backup -->
    <div class="tab-pane fade" id="backup">
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">💾 Backup do Sistema</h3>
            </div>
            <div class="card-body">
                <p>Faça backup completo do banco de dados e arquivos do sistema.</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-database fa-3x mb-3 text-primary"></i>
                                <h4>Backup do Banco de Dados</h4>
                                <p>Exportar todas as tabelas e dados</p>
                                <button type="button" class="btn btn-primary" onclick="fazerBackupDB()">
                                    <i class="fas fa-download"></i> Baixar Backup SQL
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-file-archive fa-3x mb-3 text-success"></i>
                                <h4>Backup de Arquivos</h4>
                                <p>Exportar imagens e uploads</p>
                                <button type="button" class="btn btn-success" onclick="fazerBackupFiles()">
                                    <i class="fas fa-download"></i> Baixar Arquivos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h4>Backups Automáticos</h4>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="backup_automatico" 
                           <?php echo ($configs['backup_automatico'] ?? false) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="backup_automatico">
                        Ativar backup automático diário
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="backup_email">E-mail para receber backups</label>
                    <input type="email" class="form-control" id="backup_email" name="backup_email" 
                           value="<?php echo htmlspecialchars($configs['backup_email'] ?? ''); ?>"
                           placeholder="backup@seusite.com">
                </div>
                
                <button type="button" class="btn btn-primary" onclick="salvarConfigBackup()">
                    <i class="fas fa-save"></i> Salvar Configurações de Backup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Salvar aba ativa
document.querySelectorAll('a[data-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function (e) {
        localStorage.setItem('activeTab', e.target.getAttribute('href'));
    });
});

// Restaurar aba ativa
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
        const tab = document.querySelector(`a[href="${activeTab}"]`);
        if (tab) {
            tab.click();
        }
    }
});

// Testar configurações de e-mail
async function testarEmail() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';
    
    try {
        const formData = new FormData(document.getElementById('formEmail'));
        formData.append('action', 'testar_email');
        
        const response = await fetch('salvar_configuracoes.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('E-mail de teste enviado com sucesso!', 'success');
        } else {
            showToast(result.message || 'Erro ao enviar e-mail de teste', 'danger');
        }
    } catch (error) {
        showToast('Erro ao testar e-mail', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Fazer backup do banco
async function fazerBackupDB() {
    window.location.href = 'backup.php?type=database';
}

// Fazer backup de arquivos
async function fazerBackupFiles() {
    window.location.href = 'backup.php?type=files';
}

// Salvar configurações de backup
async function salvarConfigBackup() {
    const formData = new FormData();
    formData.append('tipo', 'backup');
    formData.append('backup_automatico', document.getElementById('backup_automatico').checked ? '1' : '0');
    formData.append('backup_email', document.getElementById('backup_email').value);
    
    try {
        const response = await fetch('salvar_configuracoes.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Configurações de backup salvas!', 'success');
        } else {
            showToast(result.message || 'Erro ao salvar', 'danger');
        }
    } catch (error) {
        showToast('Erro ao salvar configurações', 'danger');
    }
}

// Função para mostrar toast
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 150);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999;';
    document.body.appendChild(container);
    return container;
}

// Máscara para telefone
document.getElementById('telefone_contato').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        if (value.length === 11) {
            value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
        } else if (value.length === 10) {
            value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
        }
        e.target.value = value;
    }
});
</script>

<style>
.nav-tabs .nav-link {
    color: #666;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: var(--cor-primaria);
    border-color: #dee2e6 #dee2e6 #fff;
}

.nav-tabs .nav-link i {
    margin-right: 5px;
}

.card {
    margin-bottom: 1.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 1.25rem;
}

.card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
}

.form-check-input {
    cursor: pointer;
}

.form-check-label {
    cursor: pointer;
    user-select: none;
}

.bg-light {
    background-color: #f8f9fa !important;
}

/* Toast container */
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

#toast-container .alert {
    min-width: 250px;
    margin-bottom: 10px;
}
</style>

<?php require_once 'footer.php'; ?>