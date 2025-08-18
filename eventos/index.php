<?php
require_once '../config.php';

// Capturar slug da URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    die("Evento não especificado.");
}

try {
    $pdo = conectar_db();
    
    // Buscar evento
    $stmt = $pdo->prepare("SELECT * FROM eventos WHERE slug = ? AND ativo = 1");
    $stmt->execute([$slug]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        http_response_code(404);
        die("Evento não encontrado.");
    }
    
    // Buscar participantes
    $stmt = $pdo->prepare("
        SELECT * FROM participantes_evento 
        WHERE evento_id = ? 
        ORDER BY ordem ASC
    ");
    $stmt->execute([$evento['id']]);
    $participantes = $stmt->fetchAll();
    
    // Capturar referência se houver
    $referencia = $_GET['ref'] ?? '';
    
} catch (Exception $e) {
    error_log("Erro ao buscar evento: " . $e->getMessage());
    http_response_code(500);
    die("Erro interno do servidor.");
}

// URL atual para compartilhamento
$url_atual = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$texto_whatsapp = "Participe do evento: " . urlencode($evento['titulo']) . " - " . $url_atual;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($evento['titulo']); ?> - Mobiliza+</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($evento['descricao'] ?? ''), 0, 160)); ?>">
    <link rel="canonical" href="<?php echo $url_atual; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="event">
    <meta property="og:title" content="<?php echo htmlspecialchars($evento['titulo']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($evento['descricao'] ?? ''), 0, 200)); ?>">
    <meta property="og:url" content="<?php echo $url_atual; ?>">
    <?php if ($evento['imagem_cabecalho']): ?>
    <meta property="og:image" content="<?php echo SITE_URL . '/' . $evento['imagem_cabecalho']; ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($evento['titulo']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($evento['descricao'] ?? ''), 0, 200)); ?>">
    
    <!-- Event Schema Markup -->
    <?php if ($evento['data_evento']): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Event",
        "name": "<?php echo htmlspecialchars($evento['titulo']); ?>",
        "description": "<?php echo htmlspecialchars($evento['descricao'] ?? ''); ?>",
        "startDate": "<?php echo date('c', strtotime($evento['data_evento'])); ?>",
        <?php if ($evento['local_evento']): ?>
        "location": {
            "@type": "Place",
            "name": "<?php echo htmlspecialchars($evento['local_evento']); ?>"
        },
        <?php endif; ?>
        <?php if ($evento['imagem_cabecalho']): ?>
        "image": "<?php echo SITE_URL . '/' . $evento['imagem_cabecalho']; ?>",
        <?php endif; ?>
        "organizer": {
            "@type": "Organization",
            "name": "Mobiliza+"
        }
    }
    </script>
    <?php endif; ?>
    
    <link rel="stylesheet" href="../estilo_global.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --neutral-900: #1a1a1a;
            --neutral-800: #2d2d2d;
            --neutral-700: #404040;
            --neutral-600: #525252;
            --neutral-500: #737373;
            --neutral-400: #a3a3a3;
            --neutral-300: #d4d4d4;
            --neutral-200: #e5e5e5;
            --neutral-100: #f5f5f5;
            --neutral-50: #fafafa;
            --white: #ffffff;
            
            --primary-color: #1a1a1a;
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--neutral-50);
            color: var(--neutral-900);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .event-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0;
        }
        
        .event-header {
            background: var(--white);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .event-header-image {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            display: block;
        }
        
        .event-header-content {
            padding: 2rem;
            background: var(--white);
        }
        
        .event-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--neutral-600);
        }
        
        .meta-icon {
            width: 20px;
            height: 20px;
            color: var(--neutral-500);
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin: 2rem auto;
            padding: 0 1rem;
            align-items: start;
        }
        
        .content-section {
            background: var(--white);
            border-radius: var(--border-radius-xl);
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--neutral-900);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--neutral-100);
        }
        
        .event-description {
            color: var(--neutral-700);
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        .detail-grid {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--neutral-50);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--neutral-200);
            transition: all 0.2s ease;
        }
        
        .detail-item:hover {
            border-color: var(--neutral-300);
            box-shadow: var(--shadow-sm);
        }
        
        .detail-icon {
            width: 44px;
            height: 44px;
            background: var(--neutral-900);
            color: var(--white);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .detail-content h4 {
            margin: 0 0 0.25rem 0;
            color: var(--neutral-900);
            font-size: 1rem;
            font-weight: 600;
        }
        
        .detail-content p {
            margin: 0;
            color: var(--neutral-600);
            font-size: 0.95rem;
        }
        
        .participants-grid {
            display: grid;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .participant-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.25rem;
            background: var(--neutral-50);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--neutral-200);
            transition: all 0.2s ease;
        }
        
        .participant-card:hover {
            border-color: var(--neutral-300);
            box-shadow: var(--shadow-sm);
        }
        
        .participant-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: var(--shadow);
            flex-shrink: 0;
        }
        
        .participant-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--neutral-900);
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .participant-info p {
            margin: 0 0 0.5rem 0;
            color: var(--neutral-600);
            font-size: 0.9rem;
        }
        
        .participant-social {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .participant-social a {
            color: var(--neutral-500);
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }
        
        .participant-social a:hover {
            color: var(--neutral-900);
        }
        
        .form-section {
            background: var(--white);
            border-radius: var(--border-radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 1rem;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--neutral-900);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--neutral-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            color: var(--neutral-900);
            background: var(--white);
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--neutral-400);
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05);
        }
        
        .form-input::placeholder {
            color: var(--neutral-400);
        }
        
        .submit-btn {
            width: 100%;
            padding: 1rem 2rem;
            background: var(--neutral-900);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover {
            background: var(--neutral-800);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .submit-btn:active {
            transform: translateY(0);
            box-shadow: var(--shadow-sm);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .share-section {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--neutral-200);
        }
        
        .share-section h3 {
            font-size: 1.1rem;
            color: var(--neutral-700);
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #25D366;
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .share-btn:hover {
            background: #1fb952;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
            text-decoration: none;
        }
        
        .lgpd-notice {
            font-size: 0.85rem;
            color: var(--neutral-500);
            text-align: center;
            margin-top: 1rem;
            line-height: 1.5;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .form-section {
                position: static;
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 640px) {
            .event-title {
                font-size: 2rem;
            }
            
            .event-meta {
                gap: 1rem;
                font-size: 0.9rem;
            }
            
            .content-section,
            .form-section {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.25rem;
            }
            
            .participant-card {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .participant-avatar {
                width: 80px;
                height: 80px;
            }
            
            .detail-item {
                padding: 1rem;
            }
            
            .detail-icon {
                width: 40px;
                height: 40px;
            }
        }
        
        /* Animações suaves */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .content-section,
        .form-section {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Melhorias de acessibilidade */
        .form-input:focus-visible,
        .submit-btn:focus-visible,
        .share-btn:focus-visible {
            outline: 2px solid var(--neutral-900);
            outline-offset: 2px;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="event-container">
        <!-- Header com imagem -->
        <header class="event-header">
            <?php if ($evento['imagem_cabecalho']): ?>
                <img src="../<?php echo htmlspecialchars($evento['imagem_cabecalho']); ?>" 
                     alt="<?php echo htmlspecialchars($evento['titulo']); ?>" 
                     class="event-header-image">
            <?php endif; ?>
            
            <div class="event-header-content">
                <h1 class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></h1>
                
                <div class="event-meta">
                    <?php if ($evento['data_evento']): ?>
                        <div class="meta-item">
                            <i class="fas fa-calendar meta-icon"></i>
                            <span><?php echo formatar_data_br($evento['data_evento']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($evento['local_evento']): ?>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt meta-icon"></i>
                            <span><?php echo htmlspecialchars($evento['local_evento']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="meta-item">
                        <i class="fas fa-ticket-alt meta-icon"></i>
                        <span>Inscrição Gratuita</span>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Coluna de Informações -->
            <div class="content-section">
                <?php if ($evento['descricao']): ?>
                    <h2 class="section-title">Sobre o Evento</h2>
                    <div class="event-description">
                        <?php echo nl2br(htmlspecialchars($evento['descricao'])); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Detalhes do Evento -->
                <div class="detail-grid">
                    <?php if ($evento['data_evento']): ?>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="detail-content">
                                <h4>Data e Hora</h4>
                                <p><?php echo formatar_data_br($evento['data_evento']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($evento['local_evento']): ?>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-location-dot"></i>
                            </div>
                            <div class="detail-content">
                                <h4>Local do Evento</h4>
                                <p><?php echo htmlspecialchars($evento['local_evento']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="detail-content">
                            <h4>Participação</h4>
                            <p>Evento gratuito e aberto ao público</p>
                        </div>
                    </div>
                </div>
                
                <!-- Participantes/Palestrantes -->
                <?php if (!empty($participantes)): ?>
                    <h2 class="section-title">
                        <?php echo htmlspecialchars($evento['titulo_participantes'] ?: 'Participantes'); ?>
                    </h2>
                    
                    <!-- Debug temporário - remova após verificar -->
                    <?php if (isset($_GET['debug'])): ?>
                    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px; white-space: pre-wrap;">
                        <?php echo "Total de participantes: " . count($participantes) . "\n"; ?>
                        <?php foreach ($participantes as $p): ?>
                            <?php echo "Participante: " . print_r($p, true) . "\n"; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="participants-grid">
                        <?php foreach ($participantes as $participante): ?>
                            <div class="participant-card">
                                <img src="../<?php echo $participante['foto_url'] ?: 'https://via.placeholder.com/72x72/1a1a1a/ffffff?text=' . urlencode(substr($participante['nome'], 0, 1)); ?>" 
                                     alt="<?php echo htmlspecialchars($participante['nome']); ?>" 
                                     class="participant-avatar">
                                <div class="participant-info">
                                    <h4><?php echo htmlspecialchars($participante['nome']); ?></h4>
                                    <?php if ($participante['descricao']): ?>
                                        <p><?php echo htmlspecialchars($participante['descricao']); ?></p>
                                    <?php endif; ?>
                                    <div class="participant-social">
                                        <?php if ($participante['instagram_url']): ?>
                                            <a href="<?php echo htmlspecialchars($participante['instagram_url']); ?>" target="_blank" rel="noopener">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($participante['linkedin_url']): ?>
                                            <a href="<?php echo htmlspecialchars($participante['linkedin_url']); ?>" target="_blank" rel="noopener">
                                                <i class="fab fa-linkedin"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Compartilhamento -->
                <div class="share-section">
                    <h3>Compartilhe este evento</h3>
                    <a href="https://api.whatsapp.com/send?text=<?php echo $texto_whatsapp; ?>" 
                       class="share-btn" 
                       target="_blank"
                       rel="noopener">
                        <i class="fab fa-whatsapp"></i> Compartilhar no WhatsApp
                    </a>
                </div>
            </div>
            
            <!-- Formulário de Inscrição -->
            <aside class="form-section">
                <h2 class="form-title">Faça sua Inscrição</h2>
                
                <div id="message-container"></div>
                
                <form id="inscricaoForm">
                    <input type="hidden" name="evento_id" value="<?php echo $evento['id']; ?>">
                    <?php if ($referencia): ?>
                        <input type="hidden" name="referencia" value="<?php echo htmlspecialchars($referencia); ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <input type="text" 
                               name="nome" 
                               class="form-input" 
                               placeholder="Nome completo *" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <input type="email" 
                               name="email" 
                               class="form-input" 
                               placeholder="E-mail (opcional)">
                    </div>
                    
                    <div class="form-group">
                        <input type="tel" 
                               name="whatsapp" 
                               class="form-input" 
                               placeholder="WhatsApp com DDD *" 
                               data-mask="phone"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <input type="text" 
                               name="cidade" 
                               class="form-input" 
                               placeholder="Sua cidade *" 
                               required>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        Confirmar Inscrição
                    </button>
                    
                    <div class="lgpd-notice">
                        Ao se inscrever, você concorda com o uso dos seus dados para os fins deste evento, 
                        em conformidade com a LGPD.
                    </div>
                </form>
            </aside>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('inscricaoForm');
            const messageContainer = document.getElementById('message-container');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Processando...';
                
                try {
                    const formData = new FormData(this);
                    
                    const response = await fetch('processa_inscricao.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        messageContainer.innerHTML = `
                            <div class="alert alert-success">
                                ${result.message}
                            </div>
                        `;
                        
                        // Limpar formulário
                        this.reset();
                        
                        // Redirecionar para grupo do WhatsApp se houver
                        <?php if ($evento['link_whatsapp']): ?>
                            setTimeout(() => {
                                if (confirm('Deseja entrar no grupo do WhatsApp do evento?')) {
                                    window.open('<?php echo htmlspecialchars($evento['link_whatsapp']); ?>', '_blank');
                                }
                            }, 2000);
                        <?php endif; ?>
                        
                    } else {
                        messageContainer.innerHTML = `
                            <div class="alert alert-danger">
                                ${result.message}
                            </div>
                        `;
                    }
                } catch (error) {
                    messageContainer.innerHTML = `
                        <div class="alert alert-danger">
                            Erro ao processar inscrição. Tente novamente.
                        </div>
                    `;
                    console.error('Erro:', error);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    
                    // Scroll para a mensagem
                    messageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
            
            // Aplicar máscara de telefone
            const phoneInput = document.querySelector('input[data-mask="phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
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
            }
        });
    </script>
</body>
</html>