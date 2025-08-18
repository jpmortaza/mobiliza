<?php
require_once '../config.php';

// Recuperar dados da sessão (definidos no processa_inscricao.php)
$evento_titulo = $_SESSION['evento_titulo'] ?? 'nosso evento';
$evento_slug = $_SESSION['evento_slug'] ?? '';
$whatsapp_link = $_SESSION['whatsapp_link'] ?? '';
$evento_data = $_SESSION['evento_data'] ?? '';
$evento_local = $_SESSION['evento_local'] ?? '';

// Limpar a sessão para evitar que os dados persistam
unset($_SESSION['evento_titulo']);
unset($_SESSION['evento_slug']);
unset($_SESSION['whatsapp_link']);
unset($_SESSION['evento_data']);
unset($_SESSION['evento_local']);

// Preparar links de compartilhamento
$url_evento = "https://" . $_SERVER['HTTP_HOST'] . "/eventos/" . $evento_slug;
$texto_whatsapp_share = "Acabei de me inscrever neste evento incrível: " . urlencode($evento_titulo) . " - Inscreva-se você também: " . $url_evento;
$link_whatsapp_share = "https://api.whatsapp.com/send?text=" . $texto_whatsapp_share;

$texto_twitter = "Acabei de me inscrever: " . urlencode($evento_titulo) . " " . $url_evento;
$link_twitter = "https://twitter.com/intent/tweet?text=" . $texto_twitter;

$link_facebook = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url_evento);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição Confirmada - <?php echo htmlspecialchars($evento_titulo); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../estilo_eventos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .thank-you-container {
            max-width: 550px;
            margin: 8vh auto;
            padding: 20px;
        }
        
        .thank-you-card {
            background: white;
            padding: 40px 35px;
            border-radius: 24px;
            text-align: center;
            box-shadow: var(--sombra);
            position: relative;
            overflow: hidden;
        }
        
        .thank-you-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--cor-primaria) 0%, var(--cor-secundaria) 100%);
        }
        
        .success-icon {
            font-size: 4.5rem;
            color: var(--cor-sucesso);
            margin-bottom: 25px;
            animation: celebration 1.5s ease-in-out;
        }
        
        @keyframes celebration {
            0% { transform: scale(0) rotate(-180deg); opacity: 0; }
            50% { transform: scale(1.2) rotate(-10deg); }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }
        
        .thank-you-card h1 {
            color: var(--cor-primaria);
            margin-bottom: 15px;
            font-size: 2.2rem;
            font-weight: 800;
        }
        
        .evento-nome {
            color: var(--cor-secundaria);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 12px;
            border-left: 4px solid var(--cor-primaria);
        }
        
        .thank-you-card p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: #555;
            line-height: 1.6;
        }
        
        .evento-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            text-align: left;
        }
        
        .evento-details h3 {
            color: var(--cor-primaria);
            margin: 0 0 15px 0;
            font-size: 1.1rem;
            text-align: center;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .detail-item i {
            color: var(--cor-secundaria);
            width: 20px;
            margin-right: 10px;
        }
        
        .whatsapp-section {
            background: #e7f5e7;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
        }
        
        .whatsapp-section h3 {
            color: var(--cor-primaria);
            margin: 0 0 15px 0;
            font-size: 1.2rem;
        }
        
        .whatsapp-section p {
            margin-bottom: 20px;
            font-size: 1rem;
        }
        
        .whatsapp-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: #25D366;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .whatsapp-button:hover {
            background: #1EBE57;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
        }
        
        .share-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
        }
        
        .share-section h3 {
            color: var(--cor-primaria);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .share-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: white;
        }
        
        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .share-btn.whatsapp-share { background-color: #25D366; }
        .share-btn.facebook { background-color: #4267B2; }
        .share-btn.twitter { background-color: #1DA1F2; }
        .share-btn.copy { background-color: #6c757d; }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background-color: var(--cor-secundaria);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background-color: #259879;
            transform: translateY(-2px);
        }
        
        .calendar-reminder {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 0.9rem;
            border-left: 4px solid #ffc107;
        }
        
        .copied-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--cor-sucesso);
            color: white;
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
            
            <h1>Inscrição Confirmada!</h1>
            
            <div class="evento-nome">
                <?php echo htmlspecialchars($evento_titulo); ?>
            </div>
            
            <p>
                <strong>Parabéns!</strong> Sua inscrição foi registrada com sucesso.<br>
                Estamos ansiosos para te ver no evento!
            </p>
            
            <?php if (!empty($evento_data) || !empty($evento_local)): ?>
            <div class="evento-details">
                <h3><i class="fas fa-info-circle"></i> Detalhes do Evento</h3>
                
                <?php if (!empty($evento_data)): ?>
                <div class="detail-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo formatar_data_br($evento_data); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($evento_local)): ?>
                <div class="detail-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($evento_local); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($whatsapp_link)): ?>
            <div class="whatsapp-section">
                <h3><i class="fab fa-whatsapp"></i> Grupo Exclusivo</h3>
                <p>Entre no nosso grupo do WhatsApp para receber atualizações, lembretes e se conectar com outros participantes!</p>
                
                <a href="<?php echo htmlspecialchars($whatsapp_link); ?>" class="whatsapp-button" target="_blank" rel="noopener">
                    <i class="fab fa-whatsapp"></i>
                    Entrar no Grupo do Evento
                </a>
            </div>
            <?php endif; ?>
            
            <div class="calendar-reminder">
                <i class="fas fa-calendar-plus"></i>
                <strong>Lembrete:</strong> Adicione este evento ao seu calendário para não perder a data!
            </div>
            
            <div class="share-section">
                <h3>Convide seus amigos!</h3>
                <p style="font-size: 0.9rem; margin-bottom: 15px;">
                    Compartilhe este evento com pessoas que também podem se interessar:
                </p>
                
                <div class="share-buttons">
                    <a href="<?php echo $link_whatsapp_share; ?>" class="share-btn whatsapp-share" target="_blank" rel="noopener">
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
            
            <?php if (!empty($evento_slug)): ?>
            <a href="<?php echo $evento_slug; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o evento
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
        const url = "<?php echo $url_evento; ?>";
        
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
            card.style.transition = 'all 0.8s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });
    </script>
</body>
</html>