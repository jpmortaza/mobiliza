<?php
/**
 * MOBILIZA+ - Sistema de Segurança Avançada
 *
 * Proteção CSRF, Rate Limiting, Validações Avançadas
 */

require_once 'cache.php';

class MobilizaSecurity {

    /**
     * Gera token CSRF
     */
    public static function gerarTokenCSRF() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Valida token CSRF
     */
    public static function validarTokenCSRF($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Gera campo hidden para formulários
     */
    public static function campoCSRF() {
        $token = self::gerarTokenCSRF();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Middleware para verificar CSRF em POST requests
     */
    public static function verificarCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';

            if (!self::validarTokenCSRF($token)) {
                http_response_code(403);
                die(json_encode([
                    'sucesso' => false,
                    'erro' => 'Token CSRF inválido. Recarregue a página e tente novamente.'
                ]));
            }
        }
    }

    /**
     * Rate Limiting - Limita número de requisições por IP
     */
    public static function rateLimiting($acao, $limite = 10, $janela = 60) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cache_key = "rate_limit:{$acao}:{$ip}";

        $tentativas = cache_get($cache_key, 0);

        if ($tentativas >= $limite) {
            http_response_code(429);
            die(json_encode([
                'sucesso' => false,
                'erro' => 'Muitas requisições. Tente novamente em ' . $janela . ' segundos.'
            ]));
        }

        cache_set($cache_key, $tentativas + 1, $janela);
        return true;
    }

    /**
     * Validação avançada de WhatsApp
     */
    public static function validarWhatsApp($whatsapp) {
        // Remove caracteres não numéricos
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);

        // Verifica se tem entre 10 e 13 dígitos (BR: DDD + número)
        if (strlen($whatsapp) < 10 || strlen($whatsapp) > 13) {
            return [
                'valido' => false,
                'erro' => 'WhatsApp deve ter entre 10 e 13 dígitos'
            ];
        }

        // Verifica se não é número sequencial óbvio
        if (preg_match('/^(\d)\1+$/', $whatsapp)) {
            return [
                'valido' => false,
                'erro' => 'WhatsApp inválido'
            ];
        }

        // Formata para padrão brasileiro se for 11 dígitos
        if (strlen($whatsapp) === 11) {
            $formatado = '(' . substr($whatsapp, 0, 2) . ') ' .
                         substr($whatsapp, 2, 5) . '-' .
                         substr($whatsapp, 7);
        } else {
            $formatado = $whatsapp;
        }

        return [
            'valido' => true,
            'numero' => $whatsapp,
            'formatado' => $formatado
        ];
    }

    /**
     * Validação avançada de email com verificação de domínio
     */
    public static function validarEmail($email) {
        // Validação básica
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valido' => false,
                'erro' => 'Formato de email inválido'
            ];
        }

        // Lista de domínios descartáveis conhecidos
        $dominios_descartaveis = [
            'tempmail.com', 'throwaway.email', 'guerrillamail.com',
            'mailinator.com', '10minutemail.com', 'temp-mail.org'
        ];

        $dominio = substr(strrchr($email, "@"), 1);

        if (in_array(strtolower($dominio), $dominios_descartaveis)) {
            return [
                'valido' => false,
                'erro' => 'Emails temporários não são permitidos'
            ];
        }

        // Verifica se o domínio tem registro MX (opcional, pode ser lento)
        // Descomente se quiser validação mais rigorosa
        // if (!checkdnsrr($dominio, 'MX')) {
        //     return ['valido' => false, 'erro' => 'Domínio de email inválido'];
        // }

        return [
            'valido' => true,
            'email' => strtolower($email)
        ];
    }

    /**
     * Detecta possível duplicata antes de inserir
     */
    public static function detectarDuplicata($whatsapp, $tipo = 'evento', $id_alvo = null) {
        $pdo = conectar_db();

        // Remove formatação
        $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);

        if ($tipo === 'evento' && $id_alvo) {
            $stmt = $pdo->prepare("
                SELECT nome, email, data_inscricao
                FROM inscricoes_eventos
                WHERE whatsapp = ? AND evento_id = ?
                ORDER BY data_inscricao DESC
                LIMIT 1
            ");
            $stmt->execute([$whatsapp, $id_alvo]);
        } elseif ($tipo === 'peticao' && $id_alvo) {
            $stmt = $pdo->prepare("
                SELECT nome, email, data_assinatura as data_inscricao
                FROM assinaturas_peticoes
                WHERE whatsapp = ? AND peticao_id = ?
                ORDER BY data_assinatura DESC
                LIMIT 1
            ");
            $stmt->execute([$whatsapp, $id_alvo]);
        } else {
            return ['duplicata' => false];
        }

        $existente = $stmt->fetch();

        if ($existente) {
            return [
                'duplicata' => true,
                'nome' => $existente['nome'],
                'email' => $existente['email'],
                'data' => $existente['data_inscricao']
            ];
        }

        return ['duplicata' => false];
    }

    /**
     * Sanitização inteligente de nome
     */
    public static function sanitizarNome($nome) {
        // Remove espaços extras
        $nome = preg_replace('/\s+/', ' ', trim($nome));

        // Capitaliza corretamente (respeita conectivos)
        $conectivos = ['de', 'da', 'do', 'das', 'dos', 'e'];
        $palavras = explode(' ', mb_strtolower($nome));

        $nome_formatado = array_map(function($palavra) use ($conectivos) {
            if (in_array($palavra, $conectivos)) {
                return $palavra;
            }
            return mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');
        }, $palavras);

        return implode(' ', $nome_formatado);
    }

    /**
     * Proteção contra SQL Injection em ORDER BY dinâmico
     */
    public static function validarOrderBy($campo, $campos_permitidos) {
        if (!in_array($campo, $campos_permitidos)) {
            return $campos_permitidos[0]; // Retorna o padrão
        }
        return $campo;
    }

    /**
     * Proteção contra XSS em outputs
     */
    public static function limparOutput($texto, $permitir_html = false) {
        if ($permitir_html) {
            // Remove apenas scripts perigosos mas mantém formatação básica
            $texto = strip_tags($texto, '<p><br><strong><em><u><a><ul><ol><li>');
            return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        }

        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validação de data
     */
    public static function validarData($data, $formato = 'Y-m-d H:i:s') {
        $d = DateTime::createFromFormat($formato, $data);
        return $d && $d->format($formato) === $data;
    }

    /**
     * Log de atividades suspeitas
     */
    public static function logAtividadeSuspeita($tipo, $detalhes = []) {
        $pdo = conectar_db();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO logs_sistema
                (usuario_id, acao, tabela_afetada, detalhes, ip, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['usuario_id'] ?? null,
                'ATIVIDADE_SUSPEITA_' . $tipo,
                'security',
                json_encode($detalhes),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            error_log("Erro ao registrar atividade suspeita: " . $e->getMessage());
        }
    }

    /**
     * Bloqueia IPs após múltiplas tentativas suspeitas
     */
    public static function verificarBloqueioIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cache_key = "ip_block:{$ip}";

        $bloqueado_ate = cache_get($cache_key);

        if ($bloqueado_ate && time() < $bloqueado_ate) {
            $tempo_restante = $bloqueado_ate - time();
            http_response_code(403);
            die(json_encode([
                'sucesso' => false,
                'erro' => 'IP bloqueado temporariamente. Tente novamente em ' . ceil($tempo_restante / 60) . ' minutos.'
            ]));
        }
    }

    /**
     * Registra tentativa suspeita e bloqueia se necessário
     */
    public static function registrarTentativaSuspeita($tipo) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cache_key = "suspicious:{$tipo}:{$ip}";

        $tentativas = cache_get($cache_key, 0) + 1;
        cache_set($cache_key, $tentativas, 300); // 5 minutos

        if ($tentativas >= 5) {
            // Bloqueia IP por 1 hora
            cache_set("ip_block:{$ip}", time() + 3600, 3600);

            self::logAtividadeSuspeita($tipo, [
                'tentativas' => $tentativas,
                'ip' => $ip
            ]);

            self::verificarBloqueioIP(); // Vai retornar erro 403
        }
    }

    /**
     * Validação de força de senha
     */
    public static function validarForcaSenha($senha) {
        $erros = [];

        if (strlen($senha) < 8) {
            $erros[] = 'Senha deve ter no mínimo 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $senha)) {
            $erros[] = 'Senha deve conter ao menos uma letra maiúscula';
        }

        if (!preg_match('/[a-z]/', $senha)) {
            $erros[] = 'Senha deve conter ao menos uma letra minúscula';
        }

        if (!preg_match('/[0-9]/', $senha)) {
            $erros[] = 'Senha deve conter ao menos um número';
        }

        // Opcional: caractere especial
        // if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
        //     $erros[] = 'Senha deve conter ao menos um caractere especial';
        // }

        $score = 0;
        if (empty($erros)) {
            $score = 60; // Base
            if (strlen($senha) >= 12) $score += 20;
            if (preg_match('/[^A-Za-z0-9]/', $senha)) $score += 20;
        }

        return [
            'valida' => empty($erros),
            'erros' => $erros,
            'score' => $score,
            'nivel' => $score >= 80 ? 'Forte' : ($score >= 60 ? 'Média' : 'Fraca')
        ];
    }

    /**
     * Gera senha segura aleatória
     */
    public static function gerarSenhaSegura($tamanho = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $senha = '';

        for ($i = 0; $i < $tamanho; $i++) {
            $senha .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $senha;
    }
}

/**
 * Funções auxiliares globais
 */
function csrf_token() {
    return MobilizaSecurity::gerarTokenCSRF();
}

function csrf_field() {
    return MobilizaSecurity::campoCSRF();
}

function verificar_csrf() {
    return MobilizaSecurity::verificarCSRF();
}

function rate_limit($acao, $limite = 10, $janela = 60) {
    return MobilizaSecurity::rateLimiting($acao, $limite, $janela);
}
?>
