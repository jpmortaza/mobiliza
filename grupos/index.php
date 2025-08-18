<?php
/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/index.php
 * Descrição: Página de cadastro de participantes.
 */

require_once __DIR__ . '/../config.php';

// Capturar parâmetro de referência da URL
$ref_link = $_GET['ref'] ?? '';

// Configurações básicas
$seo_title = 'Grupos Oficiais de Apoio';
$seo_description = 'Cadastre-se e entre para o grupo fechado de WhatsApp da sua cidade.';
$main_title = 'Participe da Rede Oficial de Apoio';
$subtitle = 'Preencha seus dados e entre no grupo da sua cidade para receber conteúdos e informações.';

// Se há um link de referência, buscar informação do compartilhador
$compartilhador_info = null;
if (!empty($ref_link)) {
    try {
        $pdo = conectar_db();
        $stmt = $pdo->prepare("SELECT nome, cidade FROM compartilhadores WHERE link_personalizado = ? AND status = 'aprovado'");
        $stmt->execute([$ref_link]);
        $compartilhador_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erro ao buscar compartilhador: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seo_title); ?></title>
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/uploads/brasil.ico" type="image/x-icon">
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="grupos-styles.css">
    
    <!-- Structured Data com JSON-LD (mantido do original) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Participe da Rede Oficial de Apoio",
      "description": "Cadastre-se e entre no grupo fechado de WhatsApp da sua cidade. Receba conteúdos exclusivos, atualizações e informações oficiais.",
      "url": "<?php echo SITE_URL; ?>/grupos/",
      "image": "<?php echo SITE_URL; ?>/uploads/grupos/lzaps.png",
      "publisher": {
        "@type": "Organization",
        "name": "Mobiliza+"
      }
    }
    </script>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <img src="<?php echo SITE_URL; ?>/uploads/grupos/lzaps.png" alt="Logo" class="logo">
            
            <div class="form-card">
                <?php if ($compartilhador_info): ?>
                <div class="referral-info">
                    <div class="referral-badge">
                        <span class="referral-icon">👥</span>
                        <div class="referral-text">
                            <strong>Indicado por:</strong><br>
                            <?php echo htmlspecialchars($compartilhador_info['nome']); ?> - <?php echo htmlspecialchars($compartilhador_info['cidade']); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <h1 class="titulo"><?php echo htmlspecialchars($main_title); ?></h1>
                <p class="subtitulo"><?php echo htmlspecialchars($subtitle); ?></p>

                <form id="cadastroForm" action="processa.php" method="POST">
                    <input type="hidden" name="action" value="cadastro_participante">
                    <?php if (!empty($ref_link)): ?>
                    <input type="hidden" name="ref" value="<?php echo htmlspecialchars($ref_link); ?>">
                    <?php endif; ?>
                    
                    <div class="input-group">
                        <input type="text" name="nome" id="nome" placeholder="Nome completo *" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="whatsapp" id="whatsapp" placeholder="WhatsApp (apenas números) *" required>
                    </div>
                    
                    <div class="input-group cidade-container">
                        <input type="text" name="cidade" id="cidade" placeholder="Digite sua cidade *" autocomplete="off" required>
                        <div id="cidadesSugestoes" class="sugestoes-container"></div>
                    </div>
                    
                    <div class="input-group">
                        <select name="voluntario" id="voluntario" required>
                            <option value="">Deseja ser voluntário? *</option>
                            <option value="Sim">Sim</option>
                            <option value="Não">Não</option>
                        </select>
                    </div>
                    
                    <div class="lgpd-notice">
                        <p class="lgpd-text">
                            Ao clicar em "Entrar no Grupo", aceito receber conteúdos e concordo com o tratamento dos meus dados pessoais conforme a LGPD.
                        </p>
                    </div>
                    
                    <button type="submit" id="btnSubmit">Entrar no Grupo</button>
                </form>
                
                <div id="loading" class="loading hidden">
                    <div class="spinner"></div>
                    <p>Processando...</p>
                </div>
                
                <div id="resultado" class="resultado hidden"></div>
                
                <div id="grupoLinkContainer" class="grupo-link-container">
                    <div class="grupo-info">
                        <h3>🎉 Cadastro realizado com sucesso!</h3>
                        <p>Clique no botão abaixo para entrar no grupo da sua cidade:</p>
                    </div>
                    <a href="#" id="grupoLinkBtn" class="grupo-link-btn" target="_blank">
                        📱 Entrar no Grupo do WhatsApp
                    </a>
                </div>
                
                <div class="links-adicionais">
                    <a href="cadastro.php" class="link-compartilhador">
                        📢 Quero ser um Mobilizador
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="grupos-public.js"></script>
</body>
</html>
