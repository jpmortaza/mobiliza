<?php
require_once '../config.php';

// Recuperar dados da sessão (definidos no processa_assinatura.php)
$peticao_titulo = $_SESSION['peticao_titulo'] ?? 'esta importante causa';
$peticao_slug = $_SESSION['peticao_slug'] ?? '';
$total_assinaturas = $_SESSION['total_assinaturas'] ?? 0;

// Se o slug estiver na sessão, buscar a descrição no banco de dados
$peticao_descricao = '';
if (!empty($peticao_slug)) {
    try {
        $pdo = conectar_db();
        $stmt = $pdo->prepare("SELECT descricao FROM peticoes WHERE slug = ?");
        $stmt->execute([$peticao_slug]);
        $peticao_db = $stmt->fetch();
        if ($peticao_db) {
            $peticao_descricao = $peticao_db['descricao'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar descrição da petição: " . $e->getMessage());
    }
}

// Limpar a sessão para evitar que os dados persistam
unset($_SESSION['peticao_titulo']);
unset($_SESSION['peticao_slug']);
unset($_SESSION['total_assinaturas']);

// Preparar links de compartilhamento
$url_peticao = SITE_URL . "/apoie/" . $peticao_slug;
$texto_compartilhamento = "Acabei de assinar esta petição importante: " . $peticao_titulo;
if (!empty($peticao_descricao)) {
    // Adiciona a descrição ao texto de compartilhamento
    $texto_compartilhamento .= " - " . strip_tags(substr($peticao_descricao, 0, 100)) . "...";
}
$texto_compartilhamento .= " Assine você também: " . $url_peticao;

$link_whatsapp = "https://api.whatsapp.com/send?text=" . urlencode($texto_compartilhamento);
$link_twitter = "https://twitter.com/intent/tweet?text=" . urlencode("Acabei de assinar: " . $peticao_titulo . " " . $url_peticao);
$link_facebook = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url_peticao);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apoio Registrado - Obrigado!</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
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

        .thank-you-container {
            max-width: 500px;
            margin: 10vh auto;
            padding: 20px;
        }
        
        .thank-you-card {
            background: var(--white);
            padding: 40px 30px;
            border-radius: var(--border-radius-xl);
            text-align: center;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .thank-you-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--success-color);
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .thank-you-card h1 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .thank-you-card p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: var(--neutral-600);
            line-height: 1.6;
        }
        
        .assinaturas-count {
            background: var(--neutral-50);
            padding: 20px;
            border-radius: var(--border-radius-lg);
            margin: 25px 0;
            border-left: 4px solid var(--primary-color);
        }
        
        .assinaturas-count .number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            display: block;
        }
        
        .assinaturas-count .text {
            font-size: 0.9rem;
            color: var(--neutral-600);
            margin-top: 5px;
        }
        
        .share-section {
            margin-top: 30px;
        }
        
        .share-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .share-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: white;
            box-shadow: var(--shadow-sm);
        }
        
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }
        
        .share-btn.whatsapp {
            background-color: #25D366;
        }
        
        .share-btn.twitter {
            background-color: #1DA1F2;
        }
        
        .share-btn.facebook {
            background-color: #4267B2;
        }
        
        .share-btn.copy {
            background-color: var(--neutral-600);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background-color: var(--neutral-800);
            transform: translateY(-2px);
        }
        
        .copied-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: var(--white);
            padding: 15px 20px;
            border-radius: 8px;
            font-weight: 600;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .copied-message.show {
            transform: translateX(0);
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <div class="thank-you-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Apoio Registrado!</h1>
            
            <p>
                <strong>Obrigado por fazer parte desta causa!</strong><br>
                Sua assinatura foi registrada com sucesso para:<br>
                <em>"<?php echo htmlspecialchars($peticao_titulo); ?>"</em>
            </p>
            
            <?php if ($total_assinaturas > 0): ?>
            <div class="assinaturas-count">
                <span class="number"><?php echo number_format($total_assinaturas, 0, ',', '.'); ?></span>
                <div class="text">
                    <?php echo $total_assinaturas == 1 ? 'pessoa já assinou' : 'pessoas já assinaram'; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="share-section">
                <h3>Ajude a amplificar esta causa!</h3>
                <p style="font-size: 0.9rem; margin-bottom: 15px; color: var(--neutral-600);">
                    Compartilhe com seus amigos e familiares para conseguirmos mais apoio:
                </p>
                
                <div class="share-buttons">
                    <a href="<?php echo $link_whatsapp; ?>" class="share-btn whatsapp" target="_blank" rel="noopener">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    
                    <a href="<?php echo $link_facebook; ?>" class="share-btn facebook" target="_blank" rel="noopener">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                    
                    <a href="<?php echo $link_twitter; ?>" class="share-btn twitter" target="_blank" rel="noopener">
                        <i class="fab fa-twitter"></i> Twitter
                    </a>
                    
                    <button onclick="copyLink()" class="share-btn copy">
                        <i class="fas fa-copy"></i> Copiar Link
                    </button>
                </div>
            </div>
            
            <?php if (!empty($peticao_slug)): ?>
            <a href="<?php echo $peticao_slug; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para a petição
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mensagem de confirmação de cópia -->
    <div id="copied-message" class="copied-message">
        <i class="fas fa-check"></i> Link copiado com sucesso!
    </div>

    <script>
    function copyLink() {
        const url = "<?php echo $url_peticao; ?>";
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(() => {
                showCopiedMessage();
            }).catch(err => {
                console.error('Erro ao copiar: ', err);
                fallbackCopyTextToClipboard(url);
            });
        } else {
            fallbackCopyTextToClipboard(url);
        }
    }
    
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopiedMessage();
            } else {
                console.error('Falha ao copiar texto');
            }
        } catch (err) {
            console.error('Erro ao copiar texto: ', err);
        }
        
        document.body.removeChild(textArea);
    }
    
    function showCopiedMessage() {
        const message = document.getElementById('copied-message');
        message.classList.add('show');
        
        setTimeout(() => {
            message.classList.remove('show');
        }, 3000);
    }
    
    // Animação de entrada suave
    document.addEventListener('DOMContentLoaded', function() {
        const card = document.querySelector('.thank-you-card');
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
    </script>
</body>
</html>
