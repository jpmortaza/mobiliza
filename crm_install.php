<?php
/**
 * MOBILIZA+ CRM - Instalador do Banco de Dados
 *
 * Cria todas as tabelas necessárias para o CRM completo
 * Execute este arquivo UMA VEZ após a instalação principal
 */

require_once 'config.php';

echo "<!DOCTYPE html><html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Instalação CRM Mobiliza+</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #0d9488; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📞 Instalação do CRM Mobiliza+</h1>";

try {
    $pdo = conectar_db();
    echo "<div class='success'>✅ Conexão com banco estabelecida!</div>";

    // 1. Tabela de Contatos Consolidados (CRM Central)
    echo "<h3>Criando tabela de contatos...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_contatos (
            id INT AUTO_INCREMENT PRIMARY KEY,

            -- Dados Básicos
            nome VARCHAR(255) NOT NULL,
            whatsapp VARCHAR(20) UNIQUE NOT NULL,
            email VARCHAR(255),
            cidade VARCHAR(100),
            estado VARCHAR(2),

            -- Dados de Enriquecimento
            cpf VARCHAR(14) UNIQUE NULL,
            data_nascimento DATE NULL,
            genero ENUM('M', 'F', 'Outro', 'Prefiro não informar') NULL,
            profissao VARCHAR(100),
            escolaridade ENUM('Fundamental', 'Médio', 'Superior', 'Pós-graduação', 'Mestrado', 'Doutorado') NULL,
            renda ENUM('Até 1 SM', '1-3 SM', '3-5 SM', '5-10 SM', 'Acima de 10 SM') NULL,

            -- Endereço Completo
            cep VARCHAR(9),
            logradouro VARCHAR(255),
            numero VARCHAR(10),
            complemento VARCHAR(100),
            bairro VARCHAR(100),

            -- Interesses e Preferências
            interesses TEXT COMMENT 'JSON com array de interesses',
            causas_apoiadas TEXT COMMENT 'JSON com causas que apoia',
            disponibilidade_voluntariado BOOLEAN DEFAULT FALSE,
            areas_voluntariado TEXT COMMENT 'JSON com áreas de interesse',

            -- Redes Sociais
            instagram VARCHAR(100),
            facebook VARCHAR(100),
            twitter VARCHAR(100),
            linkedin VARCHAR(100),

            -- Estatísticas de Engajamento
            total_eventos INT DEFAULT 0,
            total_peticoes INT DEFAULT 0,
            total_grupos INT DEFAULT 0,
            total_acoes INT DEFAULT 0,
            score_engajamento INT DEFAULT 0 COMMENT 'Score de 0-100',

            -- Gestão de Atendimento
            atendente_responsavel_id INT NULL,
            status_contato ENUM(
                'novo',
                'aguardando_contato',
                'em_contato',
                'contatado',
                'interessado',
                'muito_interessado',
                'voluntario',
                'nao_interessado',
                'nao_responde',
                'numero_invalido'
            ) DEFAULT 'novo',

            prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
            tags TEXT COMMENT 'JSON com tags personalizadas',
            observacoes TEXT,

            -- Controle
            origem VARCHAR(50) COMMENT 'evento, peticao, grupo, manual',
            primeira_interacao TIMESTAMP NULL,
            ultima_interacao TIMESTAMP NULL,
            ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT TRUE,

            -- Índices para performance
            INDEX idx_whatsapp (whatsapp),
            INDEX idx_email (email),
            INDEX idx_cidade (cidade),
            INDEX idx_status (status_contato),
            INDEX idx_atendente (atendente_responsavel_id),
            INDEX idx_score (score_engajamento),
            INDEX idx_prioridade (prioridade),
            INDEX idx_cpf (cpf),

            FOREIGN KEY (atendente_responsavel_id) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_contatos criada!</div>";

    // 2. Tabela de Histórico de Interações
    echo "<h3>Criando tabela de histórico...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contato_id INT NOT NULL,

            tipo_interacao ENUM(
                'evento',
                'peticao',
                'grupo_whatsapp',
                'ligacao',
                'email',
                'mensagem',
                'reuniao',
                'nota',
                'status_alterado',
                'dados_atualizados'
            ) NOT NULL,

            descricao TEXT NOT NULL,
            resultado VARCHAR(255) NULL,

            -- Dados específicos
            evento_id INT NULL,
            peticao_id INT NULL,
            grupo_id INT NULL,

            -- Quem registrou
            registrado_por INT NULL,

            -- Metadados
            dados_adicionais TEXT COMMENT 'JSON com dados extras',

            data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_contato (contato_id),
            INDEX idx_tipo (tipo_interacao),
            INDEX idx_data (data_registro),

            FOREIGN KEY (contato_id) REFERENCES crm_contatos(id) ON DELETE CASCADE,
            FOREIGN KEY (registrado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_historico criada!</div>";

    // 3. Tabela de Ligações
    echo "<h3>Criando tabela de ligações...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_ligacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contato_id INT NOT NULL,
            atendente_id INT NOT NULL,

            -- Dados da Ligação
            data_ligacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            duracao_segundos INT DEFAULT 0,

            tipo_ligacao ENUM('ativa', 'receptiva', 'retorno') DEFAULT 'ativa',

            status_ligacao ENUM(
                'completada',
                'nao_atendeu',
                'ocupado',
                'caixa_postal',
                'numero_invalido',
                'desligou',
                'agendada',
                'cancelada'
            ) NOT NULL,

            resultado ENUM(
                'muito_positivo',
                'positivo',
                'neutro',
                'negativo',
                'callback_solicitado',
                'nao_aplicavel'
            ) NULL,

            -- Conteúdo
            objetivo TEXT COMMENT 'Objetivo da ligação',
            resumo TEXT COMMENT 'Resumo da conversa',
            observacoes TEXT,
            proximos_passos TEXT,

            -- Agendamento de retorno
            agendar_retorno BOOLEAN DEFAULT FALSE,
            data_retorno TIMESTAMP NULL,
            motivo_retorno VARCHAR(255),

            -- Conversão
            converteu_em_acao BOOLEAN DEFAULT FALSE,
            tipo_acao_convertida ENUM('evento', 'peticao', 'grupo', 'voluntariado', 'doacao') NULL,

            -- Gravação (se houver)
            arquivo_gravacao VARCHAR(255) NULL,

            INDEX idx_contato (contato_id),
            INDEX idx_atendente (atendente_id),
            INDEX idx_data (data_ligacao),
            INDEX idx_status (status_ligacao),
            INDEX idx_retorno (agendar_retorno, data_retorno),

            FOREIGN KEY (contato_id) REFERENCES crm_contatos(id) ON DELETE CASCADE,
            FOREIGN KEY (atendente_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_ligacoes criada!</div>";

    // 4. Tabela de Atendentes (extensão de usuarios_sistema)
    echo "<h3>Criando tabela de atendentes...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_atendentes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT UNIQUE NOT NULL,

            -- Status
            ativo BOOLEAN DEFAULT TRUE,
            disponivel BOOLEAN DEFAULT TRUE,

            -- Metas
            meta_ligacoes_dia INT DEFAULT 50,
            meta_conversoes_mes INT DEFAULT 20,

            -- Estatísticas (atualizadas automaticamente)
            total_contatos_atribuidos INT DEFAULT 0,
            total_ligacoes_realizadas INT DEFAULT 0,
            total_conversoes INT DEFAULT 0,
            taxa_conversao DECIMAL(5,2) DEFAULT 0.00,
            tempo_medio_ligacao INT DEFAULT 0 COMMENT 'Em segundos',

            -- Avaliação
            avaliacao_media DECIMAL(3,2) DEFAULT 0.00,
            total_avaliacoes INT DEFAULT 0,

            -- Especialidades
            especialidades TEXT COMMENT 'JSON com áreas de especialidade',

            data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_usuario (usuario_id),
            INDEX idx_ativo (ativo),
            INDEX idx_disponivel (disponivel),

            FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_atendentes criada!</div>";

    // 5. Tabela de Grupos de WhatsApp
    echo "<h3>Criando tabela de grupos WhatsApp...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_grupos_whatsapp (
            id INT AUTO_INCREMENT PRIMARY KEY,

            nome VARCHAR(255) NOT NULL,
            descricao TEXT,
            link_convite VARCHAR(500),

            categoria ENUM('mobilizacao', 'evento', 'causa', 'regional', 'geral') DEFAULT 'geral',

            responsavel_id INT NULL,

            total_membros INT DEFAULT 0,
            ativo BOOLEAN DEFAULT TRUE,

            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_categoria (categoria),
            INDEX idx_ativo (ativo),

            FOREIGN KEY (responsavel_id) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_grupos_whatsapp criada!</div>";

    // 6. Tabela de Participação em Grupos
    echo "<h3>Criando tabela de participação em grupos...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_grupos_participantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            grupo_id INT NOT NULL,
            contato_id INT NOT NULL,

            data_entrada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ativo BOOLEAN DEFAULT TRUE,

            UNIQUE KEY unique_grupo_contato (grupo_id, contato_id),
            INDEX idx_grupo (grupo_id),
            INDEX idx_contato (contato_id),

            FOREIGN KEY (grupo_id) REFERENCES crm_grupos_whatsapp(id) ON DELETE CASCADE,
            FOREIGN KEY (contato_id) REFERENCES crm_contatos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_grupos_participantes criada!</div>";

    // 7. Tabela de Tarefas/Follow-ups
    echo "<h3>Criando tabela de tarefas...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_tarefas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contato_id INT NOT NULL,
            atendente_id INT NOT NULL,

            titulo VARCHAR(255) NOT NULL,
            descricao TEXT,

            tipo ENUM('ligacao', 'email', 'mensagem', 'reuniao', 'outro') DEFAULT 'ligacao',
            prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',

            data_vencimento TIMESTAMP NULL,
            data_conclusao TIMESTAMP NULL,

            status ENUM('pendente', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'pendente',

            resultado TEXT NULL,

            INDEX idx_contato (contato_id),
            INDEX idx_atendente (atendente_id),
            INDEX idx_status (status),
            INDEX idx_vencimento (data_vencimento),

            FOREIGN KEY (contato_id) REFERENCES crm_contatos(id) ON DELETE CASCADE,
            FOREIGN KEY (atendente_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_tarefas criada!</div>";

    // 8. Tabela de Configurações do CRM
    echo "<h3>Criando tabela de configurações CRM...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS crm_configuracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT,
            descricao VARCHAR(255),
            tipo ENUM('texto', 'numero', 'boolean', 'json') DEFAULT 'texto'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Tabela crm_configuracoes criada!</div>";

    // Inserir configurações padrão
    echo "<h3>Inserindo configurações padrão...</h3>";
    $configs_padrao = [
        ['crm_dias_inatividade', '30', 'Dias sem interação para considerar contato inativo'],
        ['crm_prioridade_novos', 'media', 'Prioridade padrão para novos contatos'],
        ['crm_auto_atribuir', '1', 'Atribuir automaticamente contatos a atendentes'],
        ['crm_max_contatos_atendente', '100', 'Máximo de contatos por atendente'],
        ['crm_score_evento', '10', 'Pontos de score por participação em evento'],
        ['crm_score_peticao', '5', 'Pontos de score por assinatura de petição'],
        ['crm_score_grupo', '15', 'Pontos de score por participação em grupo']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO crm_configuracoes (chave, valor, descricao) VALUES (?, ?, ?)");
    foreach ($configs_padrao as $config) {
        $stmt->execute($config);
    }
    echo "<div class='success'>✅ Configurações padrão inseridas!</div>";

    // Criar view consolidada
    echo "<h3>Criando views auxiliares...</h3>";
    $pdo->exec("
        CREATE OR REPLACE VIEW crm_contatos_completo AS
        SELECT
            c.*,
            a.nome as atendente_nome,
            (SELECT COUNT(*) FROM crm_ligacoes WHERE contato_id = c.id) as total_ligacoes,
            (SELECT COUNT(*) FROM crm_tarefas WHERE contato_id = c.id AND status = 'pendente') as tarefas_pendentes,
            (SELECT MAX(data_ligacao) FROM crm_ligacoes WHERE contato_id = c.id) as ultima_ligacao
        FROM crm_contatos c
        LEFT JOIN usuarios_sistema a ON c.atendente_responsavel_id = a.id
    ");
    echo "<div class='success'>✅ Views criadas!</div>";

    echo "<div class='success' style='margin-top: 30px; padding: 20px; border: 2px solid #28a745;'>
        <h2 style='color: #155724; margin-top: 0;'>🎉 CRM Instalado com Sucesso!</h2>
        <p><strong>O que foi criado:</strong></p>
        <ul>
            <li>✅ Sistema completo de gestão de contatos</li>
            <li>✅ Registro de ligações e interações</li>
            <li>✅ Gestão de atendentes</li>
            <li>✅ Grupos de WhatsApp</li>
            <li>✅ Sistema de tarefas e follow-ups</li>
            <li>✅ Enriquecimento de dados</li>
        </ul>
        <p><strong>Próximos passos:</strong></p>
        <ol>
            <li>Acessar o CRM: <a href='admin/crm/'>/admin/crm/</a></li>
            <li>Sincronizar contatos existentes</li>
            <li>Cadastrar atendentes</li>
            <li>Começar a usar!</li>
        </ol>
    </div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</div></body></html>";
?>
