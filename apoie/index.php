<?php
require_once '../config.php';

// Capturar slug da URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    http_response_code(404);
    die("Petição não especificada.");
}

try {
    $pdo = conectar_db();
    
    // Buscar petição
    $stmt = $pdo->prepare("SELECT * FROM peticoes WHERE slug = ? AND ativo = 1");
    $stmt->execute([$slug]);
    $peticao = $stmt->fetch();
    
    if (!$peticao) {
        http_response_code(404);
        die("Petição não encontrada.");
    }
    
    // Buscar protagonistas
    $stmt = $pdo->prepare("
        SELECT * FROM protagonistas_peticao 
        WHERE peticao_id = ? 
        ORDER BY ordem ASC
    ");
    $stmt->execute([$peticao['id']]);
    $protagonistas = $stmt->fetchAll();
    
    // Buscar total de assinaturas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM assinaturas_peticoes WHERE peticao_id = ?");
    $stmt->execute([$peticao['id']]);
    $total_assinaturas = $stmt->fetch()['total'];
    
    // Capturar referência se houver
    $referencia = $_GET['ref'] ?? '';
    
} catch (Exception $e) {
    error_log("Erro ao buscar petição: " . $e->getMessage());
    http_response_code(500);
    die("Erro interno do servidor.");
}

// URL atual para compartilhamento
$url_atual = SITE_URL . "/apoie/" . $peticao['slug'];
$texto_whatsapp = "Apoie esta causa: " . urlencode($peticao['titulo']) . " - " . $url_atual;

// Calcular progresso
$percentual = 0;
if ($peticao['meta_assinaturas'] > 0) {
    $percentual = min(100, round(($total_assinaturas / $peticao['meta_assinaturas']) * 100));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($peticao['titulo']); ?> - Mobiliza+</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($peticao['descricao'] ?? ''), 0, 160)); ?>">
    <link rel="canonical" href="<?php echo $url_atual; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo htmlspecialchars($peticao['titulo']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($peticao['descricao'] ?? ''), 0, 200)); ?>">
    <meta property="og:url" content="<?php echo $url_atual; ?>">
    <?php if ($peticao['imagem_cabecalho']): ?>
    <meta property="og:image" content="<?php echo SITE_URL . '/' . $peticao['imagem_cabecalho']; ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($peticao['titulo']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($peticao['descricao'] ?? ''), 0, 200)); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../estilo_global.css">
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
            --neutral-50: #fafafa;
            --white: #ffffff;
            
            --primary-color: #1a1a1a;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            --border-radius: 8px;
            --border-radius-lg: 12px;
            --border-radius-xl: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--neutral-50);
            color: var(--neutral-900);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .petition-container {
            max-width: 1000px;
            margin: 2rem auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            padding: 0 1rem;
            align-items: start;
        }
        
        .petition-main {
            background: var(--white);
            border-radius: var(--border-radius-xl);
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }

        .petition-header-image {
            width: 100%;
            height: auto;
            max-height: 400px;
            object-fit: cover;
            display: block;
            margin-bottom: 2rem;
            border-radius: var(--border-radius-lg);
        }
        
        .petition-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .petition-description {
            color: var(--neutral-700);
            line-height: 1.8;
            margin-bottom: 2rem;
        }
        
        .progress-section {
            padding: 2rem 0;
            text-align: center;
            border-top: 1px solid var(--neutral-200);
            margin-top: 2rem;
        }
        
        .signatures-count {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .signatures-label {
            font-size: 1.1rem;
            color: var(--neutral-600);
            margin-bottom: 1rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: var(--neutral-200);
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--primary-color);
            transition: width 0.5s ease;
        }

        .progress-info {
            font-size: 0.9rem;
            color: var(--neutral-600);
        }

        .protagonists-section {
            padding-top: 2rem;
            border-top: 1px solid var(--neutral-200);
            margin-top: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--neutral-900);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--neutral-100);
            text-align: center;
        }
        
        .protagonists-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            margin-top: 1.5rem;
        }

        .protagonist-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.25rem;
            background: var(--neutral-50);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--neutral-200);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        
        .protagonist-card:hover {
            border-color: var(--neutral-300);
            box-shadow: var(--shadow);
        }

        .protagonist-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: var(--shadow);
            flex-shrink: 0;
        }

        .protagonist-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--neutral-900);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .protagonist-info p {
            margin: 0 0 0.5rem 0;
            color: var(--neutral-600);
            font-size: 0.9rem;
        }

        .protagonist-social {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .protagonist-social a {
            color: var(--neutral-500);
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .protagonist-social a:hover {
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
            background: var(--primary-color);
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
            .petition-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .form-section {
                position: static;
                margin-top: 2rem;
            }
        }
        
        @media (max-width: 640px) {
            .petition-title {
                font-size: 2rem;
            }
            
            .petition-header-content {
                padding: 1.5rem;
            }
            
            .petition-description,
            .protagonists-section,
            .form-section {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.25rem;
            }
            
            .protagonist-card {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .protagonist-avatar {
                width: 80px;
                height: 80px;
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
        
        .petition-main,
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
    <div class="petition-container">
        <!-- Conteúdo Principal da Petição -->
        <div class="petition-main">
            <?php if ($peticao['imagem_cabecalho']): ?>
                <img src="../<?php echo htmlspecialchars($peticao['imagem_cabecalho']); ?>" 
                     alt="<?php echo htmlspecialchars($peticao['titulo']); ?>" 
                     class="petition-header-image">
            <?php endif; ?>
            
            <h1 class="petition-title"><?php echo htmlspecialchars($peticao['titulo']); ?></h1>
            
            <?php if ($peticao['descricao']): ?>
                <div class="petition-description">
                    <?php echo nl2br(htmlspecialchars($peticao['descricao'])); ?>
                </div>
            <?php endif; ?>
            
            <!-- Protagonistas/Criadores -->
            <?php if (!empty($protagonistas)): ?>
                <div class="protagonists-section">
                    <h2 class="section-title">
                        <?php echo htmlspecialchars($peticao['criador_titulo'] ?: 'Criadores'); ?>
                    </h2>
                    <div class="protagonists-grid">
                        <?php foreach ($protagonistas as $protagonista): ?>
                            <div class="protagonist-card">
                                <img src="../<?php echo $protagonista['foto_url'] ?: 'https://via.placeholder.com/72x72/1a1a1a/ffffff?text=' . urlencode(substr($protagonista['nome'], 0, 1)); ?>" 
                                     alt="<?php echo htmlspecialchars($protagonista['nome']); ?>" 
                                     class="protagonist-avatar">
                                <div class="protagonist-info">
                                    <h4><?php echo htmlspecialchars($protagonista['nome']); ?></h4>
                                    <?php if ($protagonista['descricao']): ?>
                                        <p><?php echo htmlspecialchars($protagonista['descricao']); ?></p>
                                    <?php endif; ?>
                                    <div class="protagonist-social">
                                        <?php if ($protagonista['instagram_url']): ?>
                                            <a href="<?php echo htmlspecialchars($protagonista['instagram_url']); ?>" target="_blank" rel="noopener">
                                                <i class="fab fa-instagram"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($protagonista['linkedin_url']): ?>
                                            <a href="<?php echo htmlspecialchars($protagonista['linkedin_url']); ?>" target="_blank" rel="noopener">
                                                <i class="fab fa-linkedin"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Compartilhamento -->
            <div class="share-section">
                <h3>Compartilhe esta causa</h3>
                <a href="https://api.whatsapp.com/send?text=<?php echo $texto_whatsapp; ?>" 
                   class="share-btn" 
                   target="_blank"
                   rel="noopener">
                    <i class="fab fa-whatsapp"></i> Compartilhar no WhatsApp
                </a>
            </div>
        </div>
        
        <!-- Formulário de Assinatura -->
        <aside class="form-section">
            <h2 class="form-title"><?php echo htmlspecialchars($peticao['form_titulo']); ?></h2>
            
            <div id="message-container"></div>
            
            <!-- Progresso duplicado no formulário para melhor visibilidade -->
            <div class="progress-section" style="border:none; margin:0 0 2rem 0; padding:0;">
                <div class="signatures-count"><?php echo number_format($total_assinaturas); ?></div>
                <div class="signatures-label">pessoas já apoiaram</div>
                
                <?php if ($peticao['meta_assinaturas'] > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentual; ?>%"></div>
                    </div>
                    <div class="progress-info">
                        Meta: <?php echo number_format($peticao['meta_assinaturas']); ?> assinaturas (<?php echo $percentual; ?>%)
                    </div>
                <?php endif; ?>
            </div>

            <form id="assinaturaForm">
                <input type="hidden" name="peticao_id" value="<?php echo $peticao['id']; ?>">
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
                    <?php echo htmlspecialchars($peticao['form_botao_texto']); ?>
                </button>
                
                <div class="lgpd-notice">
                    Ao assinar, você concorda com o uso dos seus dados para os fins desta petição, 
                    em conformidade com a LGPD. Seus dados não serão compartilhados com terceiros.
                </div>
            </form>
        </aside>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('assinaturaForm');
            const messageContainer = document.getElementById('message-container');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Processando...';
                
                try {
                    const formData = new FormData(this);
                    
                    const response = await fetch('processa_assinatura.php', {
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
                        
                        // Redirecionar para a página de obrigado
                        const peticaoSlug = '<?php echo htmlspecialchars($peticao['slug']); ?>';
                        setTimeout(() => {
                            window.location.href = `obrigado.php?slug=${peticaoSlug}`;
                        }, 2000);
                        
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
                            Erro ao processar assinatura. Tente novamente.
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
