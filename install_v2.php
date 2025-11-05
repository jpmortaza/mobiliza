<?php
/**
 * MOBILIZA+ V2 - Sistema de Mobilização Digital Completo
 *
 * Instalador completo com todas as funcionalidades:
 * - Formulários de Apoio (moções/petições/pesquisas)
 * - Formulários de Eventos (com controle de vagas)
 * - Grupos WhatsApp (integração Evolution API)
 * - CRM Completo (enriquecimento de dados + atendimentos)
 * - Sistema de Indicações Universal
 * - Disparos de mensagens com agendamento e variações
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
    <title>Instalação Mobiliza+ V2</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .success { color: #28a745; background: #d4edda; padding: 12px; border-radius: 8px; margin: 8px 0; border-left: 4px solid #28a745; }
        .error { color: #dc3545; background: #f8d7da; padding: 12px; border-radius: 8px; margin: 8px 0; border-left: 4px solid #dc3545; }
        .warning { color: #856404; background: #fff3cd; padding: 12px; border-radius: 8px; margin: 8px 0; border-left: 4px solid #ffc107; }
        .step { margin: 25px 0; padding: 20px; border-left: 5px solid #667eea; background: #f8f9fa; border-radius: 8px; }
        h1 { color: #667eea; text-align: center; font-size: 2.5em; margin-bottom: 10px; }
        h2 { color: #764ba2; text-align: center; font-size: 1.2em; margin-bottom: 30px; }
        h3 { color: #495057; margin-top: 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; border: 1px solid #dee2e6; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 0.85em; font-weight: 600; }
        .badge-new { background: #28a745; color: white; }
        .progress-bar { width: 100%; background: #e9ecef; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Mobiliza+ V2</h1>
        <h2>Sistema de Mobilização Digital Completo</h2>";

try {
    // Conexão com o banco
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<div class='success'>✅ Conexão com banco de dados estabelecida!</div>";

    // SQL das tabelas conforme especificação
    $tabelas = [

        // 1. USUÁRIOS DO SISTEMA (Operadores)
        'usuarios_sistema' => "
            CREATE TABLE IF NOT EXISTS usuarios_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(100) UNIQUE NOT NULL,
                senha VARCHAR(255) NOT NULL,
                nome_completo VARCHAR(150) NOT NULL,
                tipo ENUM('admin', 'operador', 'mobilizador') DEFAULT 'operador',
                permissoes TEXT,
                ativo BOOLEAN DEFAULT TRUE,
                foto_perfil VARCHAR(255),
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ultimo_login TIMESTAMP NULL,
                INDEX idx_email (email),
                INDEX idx_tipo (tipo),
                INDEX idx_ativo (ativo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Operadores do sistema'
        ",

        // 2. PESSOAS (Cadastro Universal)
        'pessoas' => "
            CREATE TABLE IF NOT EXISTS pessoas (
                id INT AUTO_INCREMENT PRIMARY KEY,

                -- DADOS BÁSICOS
                nome VARCHAR(150) NOT NULL,
                whatsapp VARCHAR(20) NOT NULL,
                email VARCHAR(150),
                cpf VARCHAR(14),

                -- ENDEREÇO
                cidade VARCHAR(100),
                estado VARCHAR(2),
                bairro VARCHAR(100),
                cep VARCHAR(10),

                -- ORIGEM DO CADASTRO
                origem ENUM('formulario_apoio', 'formulario_evento', 'grupo_whatsapp', 'crm_manual') NOT NULL,
                data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_cadastro VARCHAR(45),

                -- SISTEMA DE INDICAÇÕES
                indicado_por INT NULL,
                link_indicacao_usado VARCHAR(20),
                mobilizador_referente INT NULL,
                numero_indicacoes INT DEFAULT 0,

                -- STATUS E ENGAJAMENTO
                status ENUM('ativo', 'inativo', 'bloqueado') DEFAULT 'ativo',
                e_voluntario BOOLEAN DEFAULT FALSE,
                e_mobilizador BOOLEAN DEFAULT FALSE,
                nivel_engajamento INT DEFAULT 0,
                ultima_interacao TIMESTAMP NULL,

                -- CRM (enriquecimento de dados)
                profissao VARCHAR(100),
                escolaridade VARCHAR(50),
                renda_familiar VARCHAR(50),
                interesses TEXT,
                tags TEXT,
                observacoes TEXT,

                -- WHATSAPP
                grupos_participando TEXT,
                aceita_mensagens BOOLEAN DEFAULT TRUE,

                INDEX idx_whatsapp (whatsapp),
                INDEX idx_email (email),
                INDEX idx_cpf (cpf),
                INDEX idx_cidade (cidade),
                INDEX idx_origem (origem),
                INDEX idx_status (status),
                INDEX idx_indicado_por (indicado_por),
                INDEX idx_mobilizador_referente (mobilizador_referente),

                FOREIGN KEY (indicado_por) REFERENCES pessoas(id) ON DELETE SET NULL,
                FOREIGN KEY (mobilizador_referente) REFERENCES pessoas(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cadastro universal de pessoas'
        ",

        // 3. MOBILIZADORES
        'mobilizadores' => "
            CREATE TABLE IF NOT EXISTS mobilizadores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pessoa_id INT NOT NULL UNIQUE,

                -- IDENTIFICAÇÃO
                link_personalizado VARCHAR(50) UNIQUE NOT NULL,
                codigo_referencia VARCHAR(6) UNIQUE NOT NULL,
                status ENUM('pendente', 'aprovado', 'suspenso') DEFAULT 'pendente',

                -- ESTATÍSTICAS (auto-calculadas)
                total_indicacoes_diretas INT DEFAULT 0,
                total_indicacoes_rede INT DEFAULT 0,
                total_assinaturas_geradas INT DEFAULT 0,
                total_inscricoes_eventos_geradas INT DEFAULT 0,
                pontos INT DEFAULT 0,
                nivel INT DEFAULT 1,

                -- HIERARQUIA E METAS
                grupo_mobilizador_id INT NULL,
                coordenador_id INT NULL,
                meta_indicacoes_mes INT DEFAULT 10,
                recebe_notificacoes BOOLEAN DEFAULT TRUE,

                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_aprovacao TIMESTAMP NULL,

                INDEX idx_codigo (codigo_referencia),
                INDEX idx_link (link_personalizado),
                INDEX idx_status (status),
                INDEX idx_nivel (nivel),

                FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE,
                FOREIGN KEY (grupo_mobilizador_id) REFERENCES grupos_mobilizadores(id) ON DELETE SET NULL,
                FOREIGN KEY (coordenador_id) REFERENCES mobilizadores(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mobilizadores do sistema'
        ",

        // 4. GRUPOS DE MOBILIZADORES
        'grupos_mobilizadores' => "
            CREATE TABLE IF NOT EXISTS grupos_mobilizadores (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                descricao TEXT,
                cidade VARCHAR(100),
                regiao VARCHAR(100),
                coordenador_id INT NULL,
                status ENUM('ativo', 'inativo') DEFAULT 'ativo',
                meta_grupo_mes INT DEFAULT 50,
                total_membros INT DEFAULT 0,
                total_indicacoes_grupo INT DEFAULT 0,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_status (status),
                INDEX idx_cidade (cidade),

                FOREIGN KEY (coordenador_id) REFERENCES mobilizadores(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Equipes de mobilizadores'
        ",

        // 5. FORMULÁRIOS DE APOIO (Moções/Petições)
        'formularios_apoio' => "
            CREATE TABLE IF NOT EXISTS formularios_apoio (
                id INT AUTO_INCREMENT PRIMARY KEY,

                -- BÁSICO
                titulo VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                descricao TEXT,
                imagem_cabecalho VARCHAR(255),
                tipo ENUM('apoio', 'rejeicao', 'apoio_ou_rejeicao', 'pesquisa_opiniao') DEFAULT 'apoio',
                status ENUM('rascunho', 'ativo', 'pausado', 'encerrado') DEFAULT 'rascunho',

                -- OBJETIVO E RESULTADO
                mostrar_objetivo BOOLEAN DEFAULT FALSE,
                meta_assinaturas INT DEFAULT 1000,
                mostrar_resultado BOOLEAN DEFAULT FALSE,
                mostrar_resultado_parcial BOOLEAN DEFAULT FALSE,

                -- BOTÕES
                permitir_apoio BOOLEAN DEFAULT TRUE,
                permitir_rejeicao BOOLEAN DEFAULT FALSE,
                permitir_abstencao BOOLEAN DEFAULT FALSE,
                texto_botao_apoio VARCHAR(50) DEFAULT 'Apoiar',
                texto_botao_rejeicao VARCHAR(50) DEFAULT 'Rejeitar',
                texto_botao_abstencao VARCHAR(50) DEFAULT 'Abstenção',

                -- CAMPOS DO FORMULÁRIO
                campo_cidade_ativo BOOLEAN DEFAULT TRUE,
                campo_email_ativo BOOLEAN DEFAULT FALSE,
                campo_email_obrigatorio BOOLEAN DEFAULT FALSE,
                campo_cpf_ativo BOOLEAN DEFAULT FALSE,
                campo_cpf_obrigatorio BOOLEAN DEFAULT FALSE,

                -- CAMPOS EXTRAS (até 3)
                campo_extra1_nome VARCHAR(100),
                campo_extra1_tipo ENUM('texto', 'select', 'textarea', 'numero', 'email'),
                campo_extra1_opcoes TEXT,
                campo_extra1_obrigatorio BOOLEAN DEFAULT FALSE,

                campo_extra2_nome VARCHAR(100),
                campo_extra2_tipo ENUM('texto', 'select', 'textarea', 'numero', 'email'),
                campo_extra2_opcoes TEXT,
                campo_extra2_obrigatorio BOOLEAN DEFAULT FALSE,

                campo_extra3_nome VARCHAR(100),
                campo_extra3_tipo ENUM('texto', 'select', 'textarea', 'numero', 'email'),
                campo_extra3_opcoes TEXT,
                campo_extra3_obrigatorio BOOLEAN DEFAULT FALSE,

                -- WHATSAPP
                link_grupo_whatsapp VARCHAR(500),
                grupo_evolution_id VARCHAR(100),
                mensagem_apos_assinatura TEXT,

                -- DATAS
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_inicio TIMESTAMP NULL,
                data_fim TIMESTAMP NULL,
                criado_por INT,

                INDEX idx_slug (slug),
                INDEX idx_status (status),
                INDEX idx_tipo (tipo),

                FOREIGN KEY (criado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formulários de apoio/petições'
        ",

        // 6. ASSINATURAS
        'assinaturas' => "
            CREATE TABLE IF NOT EXISTS assinaturas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                formulario_apoio_id INT NOT NULL,
                pessoa_id INT NOT NULL,

                -- DADOS DA ASSINATURA
                posicao ENUM('apoio', 'rejeicao', 'abstencao') NOT NULL,
                campo_extra1_valor TEXT,
                campo_extra2_valor TEXT,
                campo_extra3_valor TEXT,

                -- TRACKING
                data_assinatura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                origem_referencia VARCHAR(20),
                mobilizador_referente INT NULL,

                INDEX idx_formulario (formulario_apoio_id),
                INDEX idx_pessoa (pessoa_id),
                INDEX idx_posicao (posicao),
                INDEX idx_data (data_assinatura),
                INDEX idx_mobilizador (mobilizador_referente),

                FOREIGN KEY (formulario_apoio_id) REFERENCES formularios_apoio(id) ON DELETE CASCADE,
                FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE,
                FOREIGN KEY (mobilizador_referente) REFERENCES mobilizadores(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assinaturas de formulários'
        ",

        // 7. FORMULÁRIOS DE EVENTOS
        'formularios_eventos' => "
            CREATE TABLE IF NOT EXISTS formularios_eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,

                -- BÁSICO
                titulo VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                descricao TEXT,
                imagem_cabecalho VARCHAR(255),
                tipo_evento ENUM('presencial', 'online', 'hibrido') DEFAULT 'presencial',
                status ENUM('rascunho', 'ativo', 'lotado', 'realizado', 'cancelado') DEFAULT 'rascunho',

                -- DATA E LOCAL
                data_evento DATETIME,
                hora_inicio TIME,
                hora_fim TIME,
                local_evento VARCHAR(255),
                endereco_completo TEXT,
                link_online VARCHAR(500),
                link_transmissao VARCHAR(500),

                -- VAGAS
                tem_limite_vagas BOOLEAN DEFAULT TRUE,
                vagas_total INT DEFAULT 100,
                vagas_ocupadas INT DEFAULT 0,
                permitir_lista_espera BOOLEAN DEFAULT FALSE,
                texto_botao_inscricao VARCHAR(50) DEFAULT 'Inscrever-se',

                -- CAMPOS DO FORMULÁRIO (igual ao de apoio)
                campo_cidade_ativo BOOLEAN DEFAULT TRUE,
                campo_email_ativo BOOLEAN DEFAULT TRUE,
                campo_email_obrigatorio BOOLEAN DEFAULT FALSE,
                campo_cpf_ativo BOOLEAN DEFAULT FALSE,
                campo_cpf_obrigatorio BOOLEAN DEFAULT FALSE,

                campo_extra1_nome VARCHAR(100),
                campo_extra1_tipo ENUM('texto', 'select', 'textarea', 'numero', 'email'),
                campo_extra1_opcoes TEXT,
                campo_extra1_obrigatorio BOOLEAN DEFAULT FALSE,

                campo_extra2_nome VARCHAR(100),
                campo_extra2_tipo ENUM('texto', 'select', 'textarea', 'numero', 'email'),
                campo_extra2_opcoes TEXT,
                campo_extra2_obrigatorio BOOLEAN DEFAULT FALSE,

                campo_extra3_nome VARCHAR(100),
                campo_extra3_tipo ENUM('texto', 'select', 'textarea', 'numero', 'email'),
                campo_extra3_opcoes TEXT,
                campo_extra3_obrigatorio BOOLEAN DEFAULT FALSE,

                -- WHATSAPP E LEMBRETES
                link_grupo_whatsapp VARCHAR(500),
                grupo_evolution_id VARCHAR(100),
                mensagem_apos_inscricao TEXT,
                enviar_lembrete_automatico BOOLEAN DEFAULT FALSE,
                horas_antes_lembrete INT DEFAULT 24,

                -- CONFIRMAÇÃO
                requer_confirmacao_presenca BOOLEAN DEFAULT FALSE,
                prazo_confirmacao_horas INT DEFAULT 48,

                -- DATAS
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_abertura_inscricoes TIMESTAMP NULL,
                data_encerramento_inscricoes TIMESTAMP NULL,
                criado_por INT,

                INDEX idx_slug (slug),
                INDEX idx_status (status),
                INDEX idx_tipo_evento (tipo_evento),
                INDEX idx_data_evento (data_evento),

                FOREIGN KEY (criado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Formulários de eventos'
        ",

        // 8. INSCRIÇÕES EM EVENTOS
        'inscricoes_eventos' => "
            CREATE TABLE IF NOT EXISTS inscricoes_eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                formulario_evento_id INT NOT NULL,
                pessoa_id INT NOT NULL,

                -- STATUS DA INSCRIÇÃO
                status_inscricao ENUM('confirmada', 'lista_espera', 'cancelada', 'compareceu', 'nao_compareceu') DEFAULT 'confirmada',

                -- CAMPOS EXTRAS
                campo_extra1_valor TEXT,
                campo_extra2_valor TEXT,
                campo_extra3_valor TEXT,

                -- CONTROLE
                data_inscricao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_confirmacao_presenca TIMESTAMP NULL,
                data_comparecimento TIMESTAMP NULL,
                codigo_ingresso VARCHAR(50) UNIQUE,

                -- TRACKING
                origem_referencia VARCHAR(20),
                mobilizador_referente INT NULL,
                observacoes TEXT,

                INDEX idx_formulario (formulario_evento_id),
                INDEX idx_pessoa (pessoa_id),
                INDEX idx_status (status_inscricao),
                INDEX idx_codigo (codigo_ingresso),
                INDEX idx_mobilizador (mobilizador_referente),

                FOREIGN KEY (formulario_evento_id) REFERENCES formularios_eventos(id) ON DELETE CASCADE,
                FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE,
                FOREIGN KEY (mobilizador_referente) REFERENCES mobilizadores(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Inscrições em eventos'
        ",

        // 9. GRUPOS WHATSAPP
        'grupos_whatsapp' => "
            CREATE TABLE IF NOT EXISTS grupos_whatsapp (
                id INT AUTO_INCREMENT PRIMARY KEY,

                -- BÁSICO
                nome VARCHAR(150) NOT NULL,
                descricao TEXT,
                tipo ENUM('cidade', 'regional', 'tematico', 'mobilizadores', 'coordenacao') DEFAULT 'cidade',
                cidade VARCHAR(100),
                status ENUM('ativo', 'pausado', 'inativo') DEFAULT 'ativo',

                -- EVOLUTION API
                evolution_group_id VARCHAR(100) UNIQUE,
                evolution_instance VARCHAR(50),
                link_convite VARCHAR(500),
                qr_code_convite TEXT,
                foto_grupo VARCHAR(255),

                -- CONFIGURAÇÕES
                publico BOOLEAN DEFAULT FALSE,
                requer_aprovacao BOOLEAN DEFAULT FALSE,
                limite_membros INT DEFAULT 256,
                membros_atuais INT DEFAULT 0,
                permitir_mensagens_membros BOOLEAN DEFAULT TRUE,
                permitir_apenas_admins BOOLEAN DEFAULT FALSE,
                horario_silencio_inicio TIME,
                horario_silencio_fim TIME,

                -- DATAS
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ultima_mensagem TIMESTAMP NULL,
                criado_por INT,

                INDEX idx_tipo (tipo),
                INDEX idx_status (status),
                INDEX idx_evolution_id (evolution_group_id),
                INDEX idx_cidade (cidade),

                FOREIGN KEY (criado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos do WhatsApp'
        ",

        // 10. MENSAGENS AGENDADAS
        'mensagens_agendadas' => "
            CREATE TABLE IF NOT EXISTS mensagens_agendadas (
                id INT AUTO_INCREMENT PRIMARY KEY,

                -- BÁSICO
                titulo VARCHAR(255) NOT NULL,
                tipo_mensagem ENUM('texto', 'imagem', 'audio', 'video', 'documento') DEFAULT 'texto',
                conteudo_texto TEXT,
                arquivo_midia VARCHAR(255),
                legenda TEXT,

                -- DESTINATÁRIOS
                tipo_envio ENUM('grupo_especifico', 'multiplos_grupos', 'todos_grupos', 'lista_contatos') NOT NULL,
                grupos_destino TEXT,
                contatos_destino TEXT,
                filtros_pessoa JSON,

                -- AGENDAMENTO
                enviar_agora BOOLEAN DEFAULT FALSE,
                data_hora_agendada DATETIME NULL,
                repetir BOOLEAN DEFAULT FALSE,
                frequencia_repeticao ENUM('diaria', 'semanal', 'mensal'),
                dias_semana VARCHAR(50),
                hora_envio TIME,
                data_fim_repeticao DATE NULL,

                -- VARIAÇÕES DE CONTEÚDO
                usa_variacoes BOOLEAN DEFAULT FALSE,
                variacao1_texto TEXT,
                variacao1_horario TIME,
                variacao2_texto TEXT,
                variacao2_horario TIME,
                variacao3_texto TEXT,
                variacao3_horario TIME,

                -- STATUS
                status ENUM('pendente', 'enviando', 'enviada', 'erro', 'cancelada') DEFAULT 'pendente',
                prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',
                total_destinatarios INT DEFAULT 0,
                total_enviados INT DEFAULT 0,
                total_erros INT DEFAULT 0,
                log_erros TEXT,

                -- DATAS
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_envio_real TIMESTAMP NULL,
                criado_por INT,

                INDEX idx_status (status),
                INDEX idx_data_agendada (data_hora_agendada),
                INDEX idx_prioridade (prioridade),

                FOREIGN KEY (criado_por) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mensagens agendadas do WhatsApp'
        ",

        // 11. LOG DE ENVIO DE MENSAGENS
        'logs_envio_mensagem' => "
            CREATE TABLE IF NOT EXISTS logs_envio_mensagem (
                id INT AUTO_INCREMENT PRIMARY KEY,
                mensagem_agendada_id INT NOT NULL,

                -- DESTINATÁRIO
                destinatario_tipo ENUM('grupo', 'pessoa') NOT NULL,
                grupo_id INT NULL,
                pessoa_id INT NULL,

                -- STATUS DO ENVIO
                status ENUM('enviado', 'erro', 'lido', 'respondido') DEFAULT 'enviado',
                evolution_message_id VARCHAR(100),
                data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_leitura TIMESTAMP NULL,
                erro_mensagem TEXT,
                tentativas INT DEFAULT 0,

                INDEX idx_mensagem (mensagem_agendada_id),
                INDEX idx_status (status),
                INDEX idx_data_envio (data_envio),

                FOREIGN KEY (mensagem_agendada_id) REFERENCES mensagens_agendadas(id) ON DELETE CASCADE,
                FOREIGN KEY (grupo_id) REFERENCES grupos_whatsapp(id) ON DELETE CASCADE,
                FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log de envios de mensagens'
        ",

        // 12. ATENDIMENTOS CRM
        'atendimentos_crm' => "
            CREATE TABLE IF NOT EXISTS atendimentos_crm (
                id INT AUTO_INCREMENT PRIMARY KEY,

                -- BÁSICO
                pessoa_id INT NOT NULL,
                operador_id INT,
                tipo_atendimento ENUM('contato_inicial', 'followup', 'resolucao_problema', 'engajamento') NOT NULL,
                status ENUM('aberto', 'em_andamento', 'aguardando_resposta', 'resolvido', 'cancelado') DEFAULT 'aberto',
                prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',

                -- DETALHES
                titulo VARCHAR(255) NOT NULL,
                descricao TEXT,
                canal_contato ENUM('whatsapp', 'telefone', 'email', 'presencial', 'formulario') NOT NULL,
                resultado TEXT,
                proxima_acao TEXT,
                data_proxima_acao DATETIME NULL,

                -- CATEGORIZAÇÃO
                tags TEXT,
                categoria VARCHAR(100),
                subcategoria VARCHAR(100),

                -- MÉTRICAS
                data_abertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_primeira_resposta TIMESTAMP NULL,
                data_fechamento TIMESTAMP NULL,
                tempo_resposta_minutos INT,
                tempo_resolucao_horas INT,

                INDEX idx_pessoa (pessoa_id),
                INDEX idx_operador (operador_id),
                INDEX idx_status (status),
                INDEX idx_prioridade (prioridade),
                INDEX idx_tipo (tipo_atendimento),
                INDEX idx_data_abertura (data_abertura),

                FOREIGN KEY (pessoa_id) REFERENCES pessoas(id) ON DELETE CASCADE,
                FOREIGN KEY (operador_id) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Atendimentos do CRM'
        ",

        // 13. ATUALIZAÇÕES DE ATENDIMENTO (Timeline)
        'atualizacoes_atendimento' => "
            CREATE TABLE IF NOT EXISTS atualizacoes_atendimento (
                id INT AUTO_INCREMENT PRIMARY KEY,
                atendimento_id INT NOT NULL,
                operador_id INT,

                tipo ENUM('comentario', 'mudanca_status', 'atribuicao', 'resolucao') NOT NULL,
                mensagem TEXT,
                status_anterior VARCHAR(50),
                status_novo VARCHAR(50),
                arquivo_anexo VARCHAR(255),
                visivel_pessoa BOOLEAN DEFAULT FALSE,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_atendimento (atendimento_id),
                INDEX idx_data (data_atualizacao),

                FOREIGN KEY (atendimento_id) REFERENCES atendimentos_crm(id) ON DELETE CASCADE,
                FOREIGN KEY (operador_id) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Timeline dos atendimentos'
        ",

        // 14. CONFIGURAÇÕES DO SISTEMA
        'configuracoes_sistema' => "
            CREATE TABLE IF NOT EXISTS configuracoes_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chave VARCHAR(100) UNIQUE NOT NULL,
                valor TEXT,
                tipo ENUM('texto', 'numero', 'boolean', 'json', 'url', 'image') DEFAULT 'texto',
                descricao VARCHAR(255),
                grupo VARCHAR(50),
                editavel BOOLEAN DEFAULT TRUE,
                data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY unique_chave (chave),
                INDEX idx_grupo (grupo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações do sistema'
        ",

        // 15. LOGS DO SISTEMA
        'logs_sistema' => "
            CREATE TABLE IF NOT EXISTS logs_sistema (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NULL,
                acao VARCHAR(150) NOT NULL,
                entidade_tipo VARCHAR(50),
                entidade_id INT,
                detalhes JSON,
                ip_address VARCHAR(45),
                user_agent TEXT,
                data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                sucesso BOOLEAN DEFAULT TRUE,
                erro_mensagem TEXT,

                INDEX idx_usuario (usuario_id),
                INDEX idx_acao (acao),
                INDEX idx_entidade (entidade_tipo, entidade_id),
                INDEX idx_data (data_hora),

                FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs e auditoria do sistema'
        "
    ];

    // Criar tabelas
    echo "<div class='step'><h3>📊 Criando Base de Dados Completa</h3>";
    echo "<p>Total de tabelas: <strong>" . count($tabelas) . "</strong></p>";

    $progresso = 0;
    $total = count($tabelas);

    foreach ($tabelas as $nome => $sql) {
        $pdo->exec($sql);
        $progresso++;
        $percentual = round(($progresso / $total) * 100);

        echo "<div class='success'>✅ <strong>{$nome}</strong> criada com sucesso! ({$progresso}/{$total})</div>";
    }
    echo "</div>";

    // Dados iniciais
    echo "<div class='step'><h3>👤 Configurando Sistema</h3>";

    // Criar usuário admin
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema WHERE tipo = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;

    if (!$adminExists) {
        $senhaAdmin = 'admin123';
        $senhaHash = password_hash($senhaAdmin, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO usuarios_sistema (email, senha, nome_completo, tipo, permissoes)
            VALUES (?, ?, ?, 'admin', ?)
        ");
        $stmt->execute([
            'admin@mobiliza.com',
            $senhaHash,
            'Administrador do Sistema',
            json_encode(['all'])
        ]);

        echo "<div class='success'>✅ Usuário administrador criado!</div>";
        echo "<div class='warning'>
                📝 <strong>Dados de Login:</strong><br>
                Email: <code>admin@mobiliza.com</code><br>
                Senha: <code>{$senhaAdmin}</code><br>
                <strong>⚠️ ALTERE ESTA SENHA IMEDIATAMENTE!</strong>
              </div>";
    } else {
        echo "<div class='warning'>ℹ️ Usuário administrador já existe.</div>";
    }

    // Configurações padrão
    $configs = [
        // Sistema
        ['nome_sistema', 'Mobiliza+', 'texto', 'Nome do sistema', 'sistema'],
        ['logo_sistema', '', 'image', 'Logo do sistema', 'sistema'],
        ['cor_primaria', '#1a8f0f', 'texto', 'Cor primária (verde)', 'sistema'],
        ['cor_secundaria', '#4f46e5', 'texto', 'Cor secundária (indigo)', 'sistema'],
        ['timezone', 'America/Sao_Paulo', 'texto', 'Fuso horário', 'sistema'],

        // Evolution API
        ['evolution_api_url', '', 'url', 'URL da Evolution API', 'evolution_api'],
        ['evolution_api_key', '', 'texto', 'API Key da Evolution', 'evolution_api'],
        ['evolution_instance_default', 'instance1', 'texto', 'Instância padrão', 'evolution_api'],
        ['evolution_webhook_url', '', 'url', 'URL do webhook', 'evolution_api'],
        ['evolution_rate_limit', '20', 'numero', 'Mensagens por minuto', 'evolution_api'],
        ['evolution_intervalo_msgs', '3', 'numero', 'Intervalo entre mensagens (segundos)', 'evolution_api'],

        // Email
        ['smtp_host', '', 'texto', 'Host SMTP', 'email'],
        ['smtp_port', '587', 'numero', 'Porta SMTP', 'email'],
        ['smtp_user', '', 'texto', 'Usuário SMTP', 'email'],
        ['smtp_password', '', 'texto', 'Senha SMTP', 'email'],
        ['email_remetente', 'contato@mobiliza.com', 'texto', 'Email remetente padrão', 'email'],

        // Integrações
        ['pixel_facebook_id', '', 'texto', 'ID do Pixel do Facebook', 'integracao'],
        ['google_analytics_id', '', 'texto', 'ID do Google Analytics', 'integracao'],

        // Gamificação
        ['pontos_indicacao', '10', 'numero', 'Pontos por indicação', 'gamificacao'],
        ['pontos_assinatura', '5', 'numero', 'Pontos por assinatura gerada', 'gamificacao'],
        ['pontos_inscricao_evento', '7', 'numero', 'Pontos por inscrição em evento', 'gamificacao']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO configuracoes_sistema (chave, valor, tipo, descricao, grupo)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valor=valor
    ");

    foreach ($configs as $config) {
        $stmt->execute($config);
    }

    echo "<div class='success'>✅ Configurações padrão inseridas!</div>";
    echo "</div>";

    // Criar diretórios
    echo "<div class='step'><h3>📁 Criando Estrutura de Diretórios</h3>";
    $diretorios = [
        'uploads',
        'uploads/eventos',
        'uploads/apoie',
        'uploads/usuarios',
        'uploads/grupos',
        'uploads/mensagens',
        'uploads/crm'
    ];

    foreach ($diretorios as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
            echo "<div class='success'>✅ '{$dir}' criado</div>";
        } else {
            echo "<div class='warning'>ℹ️ '{$dir}' já existe</div>";
        }
    }
    echo "</div>";

    // Sucesso final
    echo "<div class='step' style='border-color: #28a745; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);'>
            <h3 style='color: #155724;'>🎉 Instalação Concluída com Sucesso!</h3>
            <p><strong>Sistema Mobiliza+ V2 instalado e pronto para uso!</strong></p>

            <div style='background: white; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                <h4>📋 Módulos Instalados:</h4>
                <ul style='list-style: none; padding: 0;'>
                    <li>✅ Formulários de Apoio (moções/petições)</li>
                    <li>✅ Formulários de Eventos (com controle de vagas)</li>
                    <li>✅ Sistema de Mobilizadores e Indicações</li>
                    <li>✅ Grupos WhatsApp (Evolution API)</li>
                    <li>✅ Mensagens Agendadas com Variações</li>
                    <li>✅ CRM Completo com Atendimentos</li>
                    <li>✅ Sistema de Gamificação</li>
                    <li>✅ Relatórios e Analytics</li>
                </ul>
            </div>

            <div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #ffc107;'>
                <h4>🚀 Próximos Passos:</h4>
                <ol>
                    <li>Acesse o painel admin: <a href='admin/'><code>/admin/</code></a></li>
                    <li>Faça login com <code>admin@mobiliza.com</code></li>
                    <li>Altere a senha do administrador</li>
                    <li>Configure a Evolution API em Configurações</li>
                    <li>Crie seu primeiro formulário de apoio ou evento</li>
                    <li>Cadastre mobilizadores</li>
                </ol>
            </div>

            <div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #dc3545;'>
                <h4>⚠️ IMPORTANTE:</h4>
                <ul>
                    <li>Apague o arquivo <code>install_v2.php</code> por segurança</li>
                    <li>Configure backups automáticos do banco de dados</li>
                    <li>Altere as credenciais padrão do banco em <code>config.php</code></li>
                    <li>Configure SSL/HTTPS no servidor</li>
                </ul>
            </div>
          </div>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ <strong>Erro de Banco de Dados:</strong><br>";
    echo "Erro: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Verifique as configurações de conexão.</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ <strong>Erro:</strong><br>";
    echo htmlspecialchars($e->getMessage()) . "</div>";
}

echo "    </div>
    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^=\"#\"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>";
?>
