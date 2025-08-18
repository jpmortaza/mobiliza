<?php
/**
 * MOBILIZA+ - Instalador do Sistema
 * 
 * Este arquivo cria todas as tabelas necessárias para o funcionamento
 * do sistema Mobiliza+ (Eventos + Apoie + CRM)
 * 
 * Execute este arquivo apenas UMA VEZ após configurar o config.php
 */

// Configurações do banco (ajuste conforme necessário)
$config = [
    'host' => 'X',
    'user' => 'X', 
    'pass' => 'X',
    'name' => 'X',
    'charset' => 'utf8mb4'
];

echo "<!DOCTYPE html><html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação Mobiliza+</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
        h1 { color: #147b0b; text-align: center; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Instalação do Mobiliza+</h1>";

try {
    // Conexão com o banco
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}", 
        $config['user'], 
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<div class='success'>✅ Conexão com banco de dados estabelecida!</div>";
    
    // SQL das tabelas
    $tabelas = [
        
        // Tabela de usuários do sistema
        'usuarios_sistema' => "
            CREATE TABLE IF NOT EXISTS usuarios_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                tipo ENUM('admin', 'checkin', 'organizador') DEFAULT 'admin',
                evento_permitido_id INT NULL,
                ativo BOOLEAN DEFAULT TRUE,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ultimo_login TIMESTAMP NULL,
                INDEX idx_email (email),
                INDEX idx_tipo (tipo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Tabela de eventos
        'eventos' => "
            CREATE TABLE IF NOT EXISTS eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                descricao TEXT,
                data_evento DATETIME NULL,
                local_evento VARCHAR(255),
                imagem_cabecalho VARCHAR(255),
                titulo_participantes VARCHAR(100) DEFAULT 'Palestrantes',
                link_whatsapp VARCHAR(500),
                link_referencia VARCHAR(255),
                ativo BOOLEAN DEFAULT TRUE,
                criado_por INT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_slug (slug),
                INDEX idx_ativo (ativo),
                INDEX idx_data_evento (data_evento),
                FOREIGN KEY (criado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Participantes dos eventos (palestrantes, etc)
        'participantes_evento' => "
            CREATE TABLE IF NOT EXISTS participantes_evento (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evento_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                descricao TEXT,
                foto_url VARCHAR(255),
                instagram_url VARCHAR(255),
                linkedin_url VARCHAR(255),
                ordem INT DEFAULT 0,
                FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
                INDEX idx_evento_ordem (evento_id, ordem)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Inscrições nos eventos
        'inscricoes_eventos' => "
            CREATE TABLE IF NOT EXISTS inscricoes_eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evento_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                whatsapp VARCHAR(20) NOT NULL,
                cidade VARCHAR(100) NOT NULL,
                checkin BOOLEAN DEFAULT FALSE,
                referencia VARCHAR(100) NULL,
                data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_checkin TIMESTAMP NULL,
                FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
                INDEX idx_evento (evento_id),
                INDEX idx_checkin (checkin),
                INDEX idx_referencia (referencia),
                INDEX idx_whatsapp (whatsapp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Petições/Abaixo-assinados
        'peticoes' => "
            CREATE TABLE IF NOT EXISTS peticoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                descricao TEXT,
                imagem_cabecalho VARCHAR(255),
                criador_titulo VARCHAR(100) DEFAULT 'Criadores',
                form_titulo VARCHAR(255) DEFAULT 'Assine este abaixo-assinado',
                form_botao_texto VARCHAR(100) DEFAULT 'Eu apoio',
                meta_assinaturas INT DEFAULT 0,
                ativo BOOLEAN DEFAULT TRUE,
                criado_por INT,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_slug (slug),
                INDEX idx_ativo (ativo),
                FOREIGN KEY (criado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Protagonistas das petições
        'protagonistas_peticao' => "
            CREATE TABLE IF NOT EXISTS protagonistas_peticao (
                id INT AUTO_INCREMENT PRIMARY KEY,
                peticao_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                descricao TEXT,
                foto_url VARCHAR(255),
                instagram_url VARCHAR(255),
                linkedin_url VARCHAR(255),
                ordem INT DEFAULT 0,
                FOREIGN KEY (peticao_id) REFERENCES peticoes(id) ON DELETE CASCADE,
                INDEX idx_peticao_ordem (peticao_id, ordem)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Assinaturas das petições
        'assinaturas_peticoes' => "
            CREATE TABLE IF NOT EXISTS assinaturas_peticoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                peticao_id INT NOT NULL,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                whatsapp VARCHAR(20) NOT NULL,
                cidade VARCHAR(100) NOT NULL,
                referencia VARCHAR(100) NULL,
                data_assinatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (peticao_id) REFERENCES peticoes(id) ON DELETE CASCADE,
                INDEX idx_peticao (peticao_id),
                INDEX idx_referencia (referencia),
                INDEX idx_whatsapp (whatsapp)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Logs do sistema para auditoria
        'logs_sistema' => "
            CREATE TABLE IF NOT EXISTS logs_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NULL,
                acao VARCHAR(100) NOT NULL,
                tabela_afetada VARCHAR(50),
                registro_id INT NULL,
                detalhes TEXT,
                ip VARCHAR(45),
                user_agent TEXT,
                data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_usuario (usuario_id),
                INDEX idx_acao (acao),
                INDEX idx_data (data_log),
                FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        // Configurações gerais do sistema
        'configuracoes' => "
            CREATE TABLE IF NOT EXISTS configuracoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chave VARCHAR(100) UNIQUE NOT NULL,
                valor TEXT,
                descricao VARCHAR(255),
                tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto',
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_chave (chave)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    // Criar tabelas
    echo "<div class='step'><h3>📊 Criando Tabelas</h3>";
    foreach ($tabelas as $nome => $sql) {
        $pdo->exec($sql);
        echo "<div class='success'>✅ Tabela '$nome' criada com sucesso!</div>";
    }
    echo "</div>";
    
    // Dados iniciais
    echo "<div class='step'><h3>👤 Criando Usuário Administrador</h3>";
    
    // Verificar se já existe admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema WHERE tipo = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;
    
    if (!$adminExists) {
        $senhaAdmin = 'admin123'; // Senha padrão
        $senhaHash = password_hash($senhaAdmin, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios_sistema (nome, email, senha, tipo) 
            VALUES (?, ?, ?, 'admin')
        ");
        $stmt->execute(['Administrador', 'admin@mobiliza.com', $senhaHash]);
        
        echo "<div class='success'>✅ Usuário administrador criado!</div>";
        echo "<div class='warning'>📝 <strong>Dados de Login:</strong><br>";
        echo "Email: <code>admin@mobiliza.com</code><br>";
        echo "Senha: <code>$senhaAdmin</code><br>";
        echo "<strong>⚠️ ALTERE ESTA SENHA IMEDIATAMENTE!</strong></div>";
    } else {
        echo "<div class='warning'>ℹ️ Usuário administrador já existe no sistema.</div>";
    }
    echo "</div>";
    
    // Configurações padrão
    echo "<div class='step'><h3>⚙️ Inserindo Configurações Padrão</h3>";
    
    $configs = [
        ['site_nome', 'Mobiliza+', 'Nome do site/sistema'],
        ['site_descricao', 'Plataforma de mobilização política', 'Descrição do site'],
        ['email_contato', 'contato@mobiliza.com', 'Email de contato principal'],
        ['ranking_publico', '1', 'Se o ranking de mobilizadores é público (1) ou privado (0)'],
        ['cadastro_mobilizador_aberto', '0', 'Se o cadastro de mobilizadores está aberto (1) ou fechado (0)'],
        ['whatsapp_ativo', '0', 'Se o módulo WhatsApp está ativo'],
        ['eventos_ativo', '1', 'Se o módulo Eventos está ativo'],
        ['apoie_ativo', '1', 'Se o módulo Apoie está ativo']
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO configuracoes (chave, valor, descricao) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($configs as $config) {
        $stmt->execute($config);
        echo "<div class='success'>✅ Configuração '{$config[0]}' definida</div>";
    }
    echo "</div>";
    
    // Criar diretórios necessários
    echo "<div class='step'><h3>📁 Criando Diretórios</h3>";
    $diretorios = [
        'uploads',
        'uploads/eventos', 
        'uploads/apoie',
        'uploads/usuarios'
    ];
    
    foreach ($diretorios as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            echo "<div class='success'>✅ Diretório '$dir' criado</div>";
        } else {
            echo "<div class='warning'>ℹ️ Diretório '$dir' já existe</div>";
        }
    }
    echo "</div>";
    
    // Sucesso final
    echo "<div class='step' style='border-color: #28a745; background: #d4edda;'>
            <h3 style='color: #155724;'>🎉 Instalação Concluída com Sucesso!</h3>
            <p><strong>O sistema Mobiliza+ foi instalado e está pronto para uso!</strong></p>
            <p>📋 <strong>Próximos passos:</strong></p>
            <ol>
                <li>Acesse o painel admin: <a href='admin/' target='_blank'><code>/admin/</code></a></li>
                <li>Faça login com as credenciais fornecidas acima</li>
                <li>Altere a senha do administrador</li>
                <li>Configure as informações do sistema</li>
                <li>Crie seu primeiro evento ou petição</li>
            </ol>
            <p>⚠️ <strong>IMPORTANTE:</strong> Apague este arquivo <code>install.php</code> do servidor por segurança!</p>
          </div>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ <strong>Erro de Banco de Dados:</strong><br>";
    echo "Erro: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Verifique as configurações de conexão no arquivo config.php</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Erro Geral:</strong><br>";
    echo "Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>