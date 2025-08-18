<?php
// O header agora é o único lugar que precisa incluir o arquivo de configuração.
require_once __DIR__ . '/../config.php';

// Verificar se está logado. A função verificar_login() já está definida em config.php.
if (!verificar_login()) {
    header("Location: /admin/index.php");
    exit();
}

// Pegar informações do usuário da sessão
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
$usuario_tipo = $_SESSION['usuario_tipo'] ?? 'admin';

// Definir página e pasta atual para destacar o menu
$current_url = $_SERVER['REQUEST_URI'];
$current_path = parse_url($current_url, PHP_URL_PATH);

// Remover /admin/ do início para facilitar comparações
$relative_path = str_replace('/admin/', '', $current_path);

// Verificar se é usuário de check-in (não deve acessar o admin geral)
if ($usuario_tipo === 'checkin') {
    header("Location: /eventos/checkin/");
    exit();
}

/**
 * Função auxiliar para destacar o item de menu ativo.
 */
function menu_ativo($link) {
    global $relative_path;
    
    // Remove trailing slash para comparação
    $link = rtrim($link, '/');
    $current = rtrim($relative_path, '/');
    
    // Para o dashboard, comparação exata
    if ($link === 'dashboard.php' && basename($current) === 'dashboard.php') {
        return 'active';
    }
    
    // Para pastas (eventos/, apoie/, etc)
    if (strpos($link, '/') !== false) {
        // Remove index.php se existir
        $link_folder = str_replace('/index.php', '', $link);
        $current_folder = str_replace('/index.php', '', $current);
        
        // Verifica se a pasta atual começa com a pasta do link
        if (strpos($current_folder, $link_folder) === 0) {
            return 'active';
        }
    }
    
    return '';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina ?? 'Painel Admin'; ?> - Mobiliza+</title>
    <link rel="stylesheet" href="/estilo_global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS do Admin -->
    <link rel="stylesheet" href="/admin/admin-styles.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Menu Mobile -->
        <button class="mobile-menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1 class="sidebar-title">Mobiliza+</h1>
                <p class="sidebar-subtitle">Painel Administrativo</p>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <div class="nav-item">
                        <a href="/admin/dashboard.php" class="nav-link <?php echo menu_ativo('dashboard.php'); ?>">
                            <i class="fas fa-chart-line nav-icon"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Módulos</div>
                    <div class="nav-item">
                        <a href="/admin/eventos/" class="nav-link <?php echo menu_ativo('eventos/'); ?>">
                            <i class="fas fa-calendar-alt nav-icon"></i>
                            <span>Eventos</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/admin/apoie/" class="nav-link <?php echo menu_ativo('apoie/'); ?>">
                            <i class="fas fa-hand-holding-heart nav-icon"></i>
                            <span>Apoie</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/admin/grupos/" class="nav-link <?php echo menu_ativo('grupos/'); ?>">
                            <i class="fas fa-users nav-icon"></i>
                            <span>Grupos</span>
                        </a>
                    </div>
                </div>
                
                <?php if ($usuario_tipo === 'admin'): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Administração</div>
                    <div class="nav-item">
                        <a href="/admin/usuarios/" class="nav-link <?php echo menu_ativo('usuarios/'); ?>">
                            <i class="fas fa-user-cog nav-icon"></i>
                            <span>Usuários</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="/admin/configuracoes.php" class="nav-link <?php echo menu_ativo('configuracoes.php'); ?>">
                            <i class="fas fa-cog nav-icon"></i>
                            <span>Configurações</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </nav>
        </aside>
        
        <!-- Conteúdo Principal -->
        <main class="main-content">
            <div class="top-bar">
                <h1 class="page-title"><?php echo $titulo_pagina ?? 'Dashboard'; ?></h1>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($usuario_nome); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($usuario_tipo); ?></span>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($usuario_nome, 0, 1)); ?>
                    </div>
                    <a href="/admin/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </div>
            
            <div class="content-area">