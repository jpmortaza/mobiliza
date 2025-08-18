<?php
require_once '../../config.php';

// Inicia a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
$usuario_logado = isset($_SESSION['checkin_logado']) && $_SESSION['checkin_logado'];
$evento_titulo = $_SESSION['checkin_evento_titulo'] ?? '';
$evento_id = $_SESSION['checkin_evento_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in do Evento</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../estilo_eventos.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .checkin-app {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .login-section {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: var(--sombra);
            text-align: center;
            max-width: 400px;
            margin: 10vh auto;
        }
        
        .app-section {
            background: white;
            border-radius: 16px;
            box-shadow: var(--sombra);
            overflow: hidden;
        }
        
        .app-header {
            background: linear-gradient(135deg, var(--cor-primaria) 0%, var(--cor-secundaria) 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .app-header h1 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
            font-weight: 800;
        }
        
        .app-header .evento-nome {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            padding: 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--cor-primaria);
            display: block;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        
        .controls-section {
            padding: 25px;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 25px;
        }
        
        .search-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .search-input:focus {
            border-color: var(--cor-primaria);
            box-shadow: 0 0 0 3px rgba(20, 123, 11, 0.1);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
        }
        
        .inscritos-container {
            max-height: 60vh;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }
        
        .inscrito-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }
        
        .inscrito-item:last-child {
            border-bottom: none;
        }
        
        .inscrito-item:hover {
            background-color: #f8f9fa;
        }
        
        .inscrito-item.checked-in {
            background-color: #f0fff4;
            border-left: 4px solid var(--cor-sucesso);
        }
        
        .inscrito-info h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
            color: #333;
        }
        
        .inscrito-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }
        
        .checkin-button {
            padding: 8px 16px;
            border: none;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 80px;
            transition: all 0.3s ease;
        }
        
        .checkin-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .checkin-button.nao {
            background-color: var(--cor-perigo);
        }
        
        .checkin-button.nao:hover:not(:disabled) {
            background-color: #c82333;
        }
        
        .checkin-button.sim {
            background-color: var(--cor-sucesso);
        }
        
        .checkin-button.sim:hover:not(:disabled) {
            background-color: #218838;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }
        
        .logout-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .logout-button:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="checkin-app">
        <?php if (!$usuario_logado): ?>
        <!-- Seção de Login -->
        <div class="login-section">
            <h1><i class="fas fa-clipboard-check"></i> Check-in</h1>
            <p style="margin-bottom: 30px; color: #666;">Faça login para acessar o sistema de check-in</p>
            
            <div id="login-alert" style="display: none;"></div>
            
            <form id="login-form">
                <div class="input-group">
                    <input type="text" id="usuario" name="usuario" placeholder="Usuário" required>
                </div>
                <div class="input-group">
                    <input type="password" id="senha" name="senha" placeholder="Senha" required>
                </div>
                <button type="submit" class="btn-principal" id="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>
        </div>
        
        <?php else: ?>
        <!-- Aplicação Principal -->
        <div class="app-section">
            <div class="app-header">
                <button class="logout-button" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
                <h1><i class="fas fa-clipboard-check"></i> Check-in</h1>
                <div class="evento-nome"><?php echo htmlspecialchars($evento_titulo); ?></div>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <span class="stat-number" id="total-inscritos">-</span>
                    <div class="stat-label">Total Inscritos</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="total-checkins">-</span>
                    <div class="stat-label">Check-ins</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="total-pendentes">-</span>
                    <div class="stat-label">Pendentes</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="percentual-checkin">-</span>
                    <div class="stat-label">% Confirmados</div>
                </div>
            </div>
            
            <div class="controls-section">
                <div class="search-container">
                    <input type="text" id="search-input" class="search-input" 
                           placeholder="Buscar por nome ou WhatsApp...">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <div id="inscritos-container" class="inscritos-container">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Carregando inscritos...</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
    class CheckinApp {
        constructor() {
            this.inscritos = [];
            this.inscritosFiltrados = [];
            
            <?php if ($usuario_logado): ?>
            this.initApp();
            <?php else: ?>
            this.initLogin();
            <?php endif; ?>
        }
        
        initLogin() {
            document.getElementById('login-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.fazerLogin();
            });
        }
        
        async fazerLogin() {
            const usuario = document.getElementById('usuario').value;
            const senha = document.getElementById('senha').value;
            const loginBtn = document.getElementById('login-btn');
            const alertDiv = document.getElementById('login-alert');
            
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=login&usuario=${encodeURIComponent(usuario)}&senha=${encodeURIComponent(senha)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alertDiv.innerHTML = '<div class="alert alert-success">Login realizado! Redirecionando...</div>';
                    alertDiv.style.display = 'block';
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alertDiv.innerHTML = `<div class="alert alert-error">${result.message}</div>`;
                    alertDiv.style.display = 'block';
                }
            } catch (error) {
                alertDiv.innerHTML = '<div class="alert alert-error">Erro de conexão</div>';
                alertDiv.style.display = 'block';
            }
            
            loginBtn.disabled = false;
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Entrar';
        }
        
        initApp() {
            this.carregarInscritos();
            this.carregarEstatisticas();
            
            // Configurar busca
            const searchInput = document.getElementById('search-input');
            searchInput.addEventListener('input', () => this.filtrarInscritos());
            
            // Atualizar dados a cada 30 segundos
            setInterval(() => {
                this.carregarInscritos();
                this.carregarEstatisticas();
            }, 30000);
        }
        
        async carregarInscritos() {
            try {
                const response = await fetch('api.php?action=get_inscritos');
                const result = await response.json();
                
                if (result.success) {
                    this.inscritos = result.data;
                    this.filtrarInscritos();
                } else {
                    this.mostrarErro(result.message);
                }
            } catch (error) {
                this.mostrarErro('Erro ao carregar inscritos');
            }
        }
        
        async carregarEstatisticas() {
            try {
                const response = await fetch('api.php?action=get_stats');
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.data;
                    document.getElementById('total-inscritos').textContent = stats.total_inscritos;
                    document.getElementById('total-checkins').textContent = stats.total_checkins;
                    document.getElementById('total-pendentes').textContent = stats.pendentes;
                    document.getElementById('percentual-checkin').textContent = stats.percentual_checkin + '%';
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
        }
        
        filtrarInscritos() {
            const termo = document.getElementById('search-input').value.toLowerCase();
            
            if (!termo) {
                this.inscritosFiltrados = this.inscritos;
            } else {
                this.inscritosFiltrados = this.inscritos.filter(inscrito => 
                    inscrito.nome.toLowerCase().includes(termo) || 
                    inscrito.whatsapp.includes(termo)
                );
            }
            
            this.renderizarInscritos();
        }
        
        renderizarInscritos() {
            const container = document.getElementById('inscritos-container');
            
            if (this.inscritosFiltrados.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <p>Nenhum inscrito encontrado</p>
                    </div>
                `;
                return;
            }
            
            const html = this.inscritosFiltrados.map(inscrito => `
                <div class="inscrito-item ${inscrito.checkin ? 'checked-in' : ''}" id="inscrito-${inscrito.id}">
                    <div class="inscrito-info">
                        <h4>${this.escapeHtml(inscrito.nome)}</h4>
                        <p><strong>WhatsApp:</strong> ${inscrito.whatsapp}</p>
                        <p><strong>Cidade:</strong> ${this.escapeHtml(inscrito.cidade)}</p>
                    </div>
                    <button class="checkin-button ${inscrito.checkin ? 'sim' : 'nao'}" 
                            onclick="app.toggleCheckin(${inscrito.id}, ${inscrito.checkin})">
                        ${inscrito.checkin ? 'Sim' : 'Não'}
                    </button>
                </div>
            `).join('');
            
            container.innerHTML = html;
        }
        
        async toggleCheckin(inscritoId, statusAtual) {
            const button = document.querySelector(`#inscrito-${inscritoId} .checkin-button`);
            const originalText = button.textContent;
            
            button.disabled = true;
            button.textContent = '...';
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_checkin&inscrito_id=${inscritoId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Atualizar dados localmente
                    const inscrito = this.inscritos.find(i => i.id === inscritoId);
                    if (inscrito) {
                        inscrito.checkin = result.data.novo_status;
                    }
                    
                    this.filtrarInscritos();
                    this.carregarEstatisticas();
                } else {
                    this.mostrarErro(result.message);
                    button.disabled = false;
                    button.textContent = originalText;
                }
            } catch (error) {
                this.mostrarErro('Erro de conexão');
                button.disabled = false;
                button.textContent = originalText;
            }
        }
        
        async logout() {
            try {
                await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=logout'
                });
                
                location.reload();
            } catch (error) {
                console.error('Erro ao fazer logout:', error);
                location.reload();
            }
        }
        
        mostrarErro(mensagem) {
            console.error(mensagem);
            // Aqui você pode implementar um toast ou notification
        }
        
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
    
    const app = new CheckinApp();
    
    function logout() {
        app.logout();
    }
    </script>
</body>
</html>