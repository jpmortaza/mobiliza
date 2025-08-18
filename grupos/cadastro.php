<?php
/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/cadastro.php
 * Descrição: Formulário para se candidatar a mobilizador.
 */
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Mobilizador</title>
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/uploads/brasil.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="grupos-styles.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <img src="<?php echo SITE_URL; ?>/uploads/grupos/lzaps.png" alt="Logo" class="logo">
            
            <div class="form-card" style="max-width: 600px;">
                <h1 class="titulo">📢 Torne-se um Mobilizador</h1>
                <p class="subtitulo">Ajude a expandir nossa rede e receba seu link personalizado.</p>

                <div class="info-section">
                    <h3>🎯 Como funciona:</h3>
                    <ul>
                        <li><strong>Receba seu link personalizado</strong> - Um link único para suas indicações</li>
                        <li><strong>Compartilhe com seus contatos</strong> - Envie para amigos e familiares</li>
                        <li><strong>Acompanhe suas indicações</strong> - Veja quantas pessoas você trouxe</li>
                        <li><strong>Apareça no ranking</strong> - Destaque-se como Mobilizador</li>
                    </ul>
                </div>

                <form id="compartilhadorForm" action="processa.php" method="POST">
                    <input type="hidden" name="action" value="cadastro_mobilizador">
                    <div class="input-group">
                        <input type="text" name="nome" id="nome" placeholder="Nome completo *" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="email" name="email" id="email" placeholder="E-mail *" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="whatsapp" id="whatsapp" placeholder="WhatsApp (apenas números) *" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="cidade" id="cidade" placeholder="Sua cidade *" required>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="link_personalizado" id="link_personalizado" placeholder="Link personalizado (ex: joao-silva) *" required>
                        <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                            Apenas letras, números e hífens.
                        </small>
                    </div>
                    
                    <div class="link-preview" id="linkPreview" style="display: none;">
                        <strong>Seu link será:</strong><br>
                        <code id="linkPreviewText"></code>
                    </div>
                    
                    <div class="lgpd-notice">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer;">
                            <input type="checkbox" name="aceito_termos" id="aceito_termos" required style="margin-top: 4px;">
                            <span class="lgpd-text" style="text-align: left;">
                                Declaro que tenho mais de 18 anos e concordo em compartilhar de forma responsável. Entendo que meu cadastro passará por aprovação.
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" id="btnSubmit">Solicitar Cadastro</button>
                </form>
                
                <div id="loading" class="loading hidden">
                    <div class="spinner"></div>
                    <p>Processando...</p>
                </div>
                
                <div id="resultado" class="resultado hidden"></div>
                
                <div class="links-adicionais">
                    <a href="index.php" class="link-ranking">← Voltar</a>
                    <a href="ranking.php" class="link-ranking">🏆 Ver ranking</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="grupos-public.js"></script>
</body>
</html>
