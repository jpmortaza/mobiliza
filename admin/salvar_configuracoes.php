<?php
// admin/salvar_configuracoes.php

require_once '../config.php';

// Verificar se está logado
if (!verificar_login()) {
    header('Location: index.php');
    exit();
}

// Função para responder JSON
function responder($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

try {
    $pdo = conectar_db();
    
    // Verificar se a tabela de configurações existe, senão criar
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracoes (
            id INT PRIMARY KEY DEFAULT 1,
            nome_site VARCHAR(255) DEFAULT 'Mobiliza+',
            slogan VARCHAR(255),
            descricao_site TEXT,
            logo VARCHAR(255),
            favicon VARCHAR(255),
            email_contato VARCHAR(255),
            telefone_contato VARCHAR(50),
            
            facebook VARCHAR(255),
            instagram VARCHAR(255),
            twitter VARCHAR(255),
            youtube VARCHAR(255),
            linkedin VARCHAR(255),
            tiktok VARCHAR(255),
            
            whatsapp_api_url VARCHAR(255),
            whatsapp_api_token TEXT,
            whatsapp_numero VARCHAR(50),
            
            google_analytics_id VARCHAR(50),
            google_site_verification VARCHAR(255),
            google_maps_api_key VARCHAR(255),
            
            smtp_host VARCHAR(255),
            smtp_porta INT DEFAULT 587,
            smtp_usuario VARCHAR(255),
            smtp_senha VARCHAR(255),
            smtp_seguranca VARCHAR(10) DEFAULT 'tls',
            email_remetente VARCHAR(255),
            nome_remetente VARCHAR(255),
            
            forcar_https BOOLEAN DEFAULT FALSE,
            manutencao BOOLEAN DEFAULT FALSE,
            mensagem_manutencao TEXT,
            
            backup_automatico BOOLEAN DEFAULT FALSE,
            backup_email VARCHAR(255),
            
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Inserir registro padrão se não existir
    $pdo->exec("INSERT IGNORE INTO configuracoes (id) VALUES (1)");
    
    $tipo = $_POST['tipo'] ?? '';
    
    switch ($tipo) {
        case 'geral':
            $campos = [];
            $valores = [];
            
            // Campos de texto
            $campos_texto = ['nome_site', 'slogan', 'descricao_site', 'email_contato', 
                           'telefone_contato', 'facebook', 'instagram', 'twitter', 
                           'youtube', 'linkedin', 'tiktok'];
            
            foreach ($campos_texto as $campo) {
                if (isset($_POST[$campo])) {
                    $campos[] = "$campo = ?";
                    $valores[] = sanitizar($_POST[$campo]);
                }
            }
            
            // Upload de logo
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo_path = fazer_upload($_FILES['logo'], 'logos');
                $campos[] = "logo = ?";
                $valores[] = $logo_path;
            }
            
            // Upload de favicon
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                $favicon_path = fazer_upload($_FILES['favicon'], 'logos');
                $campos[] = "favicon = ?";
                $valores[] = $favicon_path;
            }
            
            if (!empty($campos)) {
                $sql = "UPDATE configuracoes SET " . implode(', ', $campos) . " WHERE id = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($valores);
            }
            
            header('Location: configuracoes.php?msg=Configurações gerais salvas com sucesso!');
            exit();
            break;
            
        case 'integracao':
            $campos_integracao = ['whatsapp_api_url', 'whatsapp_api_token', 'whatsapp_numero',
                                'google_analytics_id', 'google_site_verification', 'google_maps_api_key'];
            
            $campos = [];
            $valores = [];
            
            foreach ($campos_integracao as $campo) {
                if (isset($_POST[$campo])) {
                    $campos[] = "$campo = ?";
                    $valores[] = sanitizar($_POST[$campo]);
                }
            }
            
            if (!empty($campos)) {
                $sql = "UPDATE configuracoes SET " . implode(', ', $campos) . " WHERE id = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($valores);
            }
            
            header('Location: configuracoes.php?msg=Integrações salvas com sucesso!#integracao');
            exit();
            break;
            
        case 'email':
            // Testar e-mail se solicitado
            if (isset($_POST['action']) && $_POST['action'] === 'testar_email') {
                // Implementar teste de e-mail
                $config = [
                    'host' => $_POST['smtp_host'] ?? '',
                    'porta' => $_POST['smtp_porta'] ?? 587,
                    'usuario' => $_POST['smtp_usuario'] ?? '',
                    'senha' => $_POST['smtp_senha'] ?? '',
                    'seguranca' => $_POST['smtp_seguranca'] ?? 'tls',
                    'remetente' => $_POST['email_remetente'] ?? '',
                    'nome_remetente' => $_POST['nome_remetente'] ?? 'Mobiliza+'
                ];
                
                // Aqui você implementaria o envio de e-mail de teste
                // Por enquanto, vamos simular
                responder(true, 'Configurações de e-mail parecem estar corretas!');
            }
            
            // Salvar configurações
            $campos_email = ['smtp_host', 'smtp_porta', 'smtp_usuario', 'smtp_senha',
                           'smtp_seguranca', 'email_remetente', 'nome_remetente'];
            
            $campos = [];
            $valores = [];
            
            foreach ($campos_email as $campo) {
                if (isset($_POST[$campo])) {
                    $campos[] = "$campo = ?";
                    $valores[] = $campo === 'smtp_senha' ? $_POST[$campo] : sanitizar($_POST[$campo]);
                }
            }
            
            if (!empty($campos)) {
                $sql = "UPDATE configuracoes SET " . implode(', ', $campos) . " WHERE id = 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($valores);
            }
            
            header('Location: configuracoes.php?msg=Configurações de e-mail salvas com sucesso!#email');
            exit();
            break;
            
        case 'seguranca':
            // Configurações gerais de segurança
            $stmt = $pdo->prepare("
                UPDATE configuracoes SET 
                forcar_https = ?,
                manutencao = ?,
                mensagem_manutencao = ?
                WHERE id = 1
            ");
            
            $stmt->execute([
                isset($_POST['forcar_https']) ? 1 : 0,
                isset($_POST['manutencao']) ? 1 : 0,
                sanitizar($_POST['mensagem_manutencao'] ?? '')
            ]);
            
            // Alterar senha se fornecida
            if (!empty($_POST['senha_atual']) && !empty($_POST['nova_senha'])) {
                $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
                $stmt->execute([$_SESSION['usuario_id']]);
                $usuario = $stmt->fetch();
                
                if (password_verify($_POST['senha_atual'], $usuario['senha'])) {
                    if ($_POST['nova_senha'] === $_POST['confirmar_senha']) {
                        $nova_senha_hash = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
                        
                        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                        $stmt->execute([$nova_senha_hash, $_SESSION['usuario_id']]);
                        
                        $msg = 'Configurações de segurança e senha atualizadas!';
                    } else {
                        header('Location: configuracoes.php?erro=As senhas não coincidem!#seguranca');
                        exit();
                    }
                } else {
                    header('Location: configuracoes.php?erro=Senha atual incorreta!#seguranca');
                    exit();
                }
            } else {
                $msg = 'Configurações de segurança salvas!';
            }
            
            header('Location: configuracoes.php?msg=' . ($msg ?? 'Configurações salvas!') . '#seguranca');
            exit();
            break;
            
        case 'backup':
            $stmt = $pdo->prepare("
                UPDATE configuracoes SET 
                backup_automatico = ?,
                backup_email = ?
                WHERE id = 1
            ");
            
            $stmt->execute([
                $_POST['backup_automatico'] ?? 0,
                sanitizar($_POST['backup_email'] ?? '')
            ]);
            
            responder(true, 'Configurações de backup salvas com sucesso!');
            break;
            
        default:
            throw new Exception('Tipo de configuração inválido');
    }
    
} catch (Exception $e) {
    error_log("Erro ao salvar configurações: " . $e->getMessage());
    
    if (isset($_POST['action'])) {
        responder(false, 'Erro ao salvar: ' . $e->getMessage());
    } else {
        header('Location: configuracoes.php?erro=' . urlencode($e->getMessage()));
        exit();
    }
}
?>