<?php
/**
 * MOBILIZA+ - Tarefas Agendadas (Cron Jobs)
 *
 * Execute este arquivo periodicamente para manter o sistema
 * funcionando com todas as automações
 *
 * CONFIGURAÇÃO RECOMENDADA:
 * - A cada 15 minutos para alertas
 * - A cada hora para limpeza de cache
 * - A cada dia para limpeza de dados antigos
 *
 * CRONTAB EXEMPLO:
 * */15 * * * * php /caminho/para/mobiliza/cron.php alertas
 * 0 * * * * php /caminho/para/mobiliza/cron.php limpeza
 * 0 3 * * * php /caminho/para/mobiliza/cron.php diario
 */

// Só permite execução via CLI ou com token de segurança
$cli_mode = php_sapi_name() === 'cli';
$token_valido = isset($_GET['token']) && $_GET['token'] === 'SEU_TOKEN_SECRETO_AQUI';

if (!$cli_mode && !$token_valido) {
    http_response_code(403);
    die('Acesso negado');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/analytics.php';

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Pegar ação
$acao = $argv[1] ?? $_GET['acao'] ?? 'alertas';

echo "=== MOBILIZA+ CRON JOB ===\n";
echo "Ação: {$acao}\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "===========================\n\n";

switch ($acao) {
    case 'alertas':
        executarAlertas();
        break;

    case 'limpeza':
        executarLimpeza();
        break;

    case 'diario':
        executarTarefasDiarias();
        break;

    case 'todos':
        executarAlertas();
        executarLimpeza();
        break;

    default:
        echo "Ação desconhecida: {$acao}\n";
        echo "Ações disponíveis: alertas, limpeza, diario, todos\n";
        exit(1);
}

echo "\n=== CONCLUÍDO ===\n";

/**
 * Verifica e cria alertas automáticos
 */
function executarAlertas() {
    echo "[ALERTAS] Verificando condições para alertas...\n";

    try {
        $notif = new MobilizaNotifications();
        $notif->init();
        $notif->verificarAlertasAutomaticos();

        echo "[ALERTAS] ✓ Verificação concluída\n";
    } catch (Exception $e) {
        echo "[ALERTAS] ✗ Erro: " . $e->getMessage() . "\n";
    }
}

/**
 * Limpa cache expirado e dados temporários
 */
function executarLimpeza() {
    echo "[LIMPEZA] Iniciando limpeza...\n";

    try {
        // Limpar cache expirado
        $removidos = MobilizaCache::clearExpired();
        echo "[LIMPEZA] ✓ {$removidos} itens de cache expirados removidos\n";

        // Estatísticas do cache
        $stats = MobilizaCache::stats();
        echo "[LIMPEZA] Cache: {$stats['active_items']} itens ativos, {$stats['size_formatted']}\n";

    } catch (Exception $e) {
        echo "[LIMPEZA] ✗ Erro: " . $e->getMessage() . "\n";
    }
}

/**
 * Tarefas diárias (executar 1x por dia, preferencialmente de madrugada)
 */
function executarTarefasDiarias() {
    echo "[DIARIO] Executando tarefas diárias...\n";

    try {
        $pdo = conectar_db();
        $notif = new MobilizaNotifications();
        $notif->init();

        // 1. Limpar notificações antigas (mais de 30 dias)
        $removidos = $notif->limparAntigas();
        echo "[DIARIO] ✓ {$removidos} notificações antigas removidas\n";

        // 2. Limpar logs antigos (mais de 90 dias)
        $stmt = $pdo->query("
            DELETE FROM logs_sistema
            WHERE data_log < DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        $logs_removidos = $stmt->rowCount();
        echo "[DIARIO] ✓ {$logs_removidos} logs antigos removidos\n";

        // 3. Otimizar tabelas do banco
        $tabelas = ['eventos', 'peticoes', 'inscricoes_eventos', 'assinaturas_peticoes',
                    'logs_sistema', 'notificacoes'];

        foreach ($tabelas as $tabela) {
            $pdo->exec("OPTIMIZE TABLE {$tabela}");
        }
        echo "[DIARIO] ✓ Tabelas otimizadas\n";

        // 4. Gerar relatório diário (pode ser enviado por email futuramente)
        $analytics = new MobilizaAnalytics();

        $stmt = $pdo->query("
            SELECT COUNT(*) FROM (
                SELECT data_inscricao FROM inscricoes_eventos WHERE DATE(data_inscricao) = CURDATE()
                UNION ALL
                SELECT data_assinatura FROM assinaturas_peticoes WHERE DATE(data_assinatura) = CURDATE()
            ) as hoje
        ");
        $mobilizacoes_hoje = $stmt->fetchColumn();

        echo "[DIARIO] Resumo do dia:\n";
        echo "  - Mobilizações: {$mobilizacoes_hoje}\n";

        // Notificar admins com resumo diário
        if ($mobilizacoes_hoje > 0) {
            $stmt = $pdo->query("SELECT id FROM usuarios_sistema WHERE tipo = 'admin' AND ativo = 1");
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($admins as $admin_id) {
                $notif->criar(
                    $admin_id,
                    'info',
                    'Resumo do Dia',
                    "Hoje você teve {$mobilizacoes_hoje} novas mobilizações!",
                    '/admin/dashboard_inteligente.php'
                );
            }

            echo "[DIARIO] ✓ Resumo enviado aos administradores\n";
        }

        // 5. Verificar eventos que aconteceram hoje e enviar parabenização
        $stmt = $pdo->query("
            SELECT e.*, u.id as admin_id, COUNT(i.id) as total_inscricoes,
                   COUNT(CASE WHEN i.checkin = 1 THEN 1 END) as total_checkins
            FROM eventos e
            JOIN usuarios_sistema u ON e.criado_por = u.id
            LEFT JOIN inscricoes_eventos i ON e.id = i.evento_id
            WHERE DATE(e.data_evento) = CURDATE() AND e.ativo = 1
            GROUP BY e.id
        ");

        $eventos_hoje = $stmt->fetchAll();

        foreach ($eventos_hoje as $evento) {
            $taxa = $evento['total_inscricoes'] > 0 ?
                round(($evento['total_checkins'] / $evento['total_inscricoes']) * 100) : 0;

            $notif->criar(
                $evento['admin_id'],
                'sucesso',
                'Evento realizado!',
                "O evento '{$evento['titulo']}' foi realizado hoje com {$evento['total_checkins']} presentes ({$taxa}% de comparecimento).",
                "/admin/eventos/inscricoes.php?id={$evento['id']}"
            );
        }

        if (count($eventos_hoje) > 0) {
            echo "[DIARIO] ✓ " . count($eventos_hoje) . " eventos do dia processados\n";
        }

    } catch (Exception $e) {
        echo "[DIARIO] ✗ Erro: " . $e->getMessage() . "\n";
    }
}

/**
 * Função auxiliar para enviar email (implementação futura)
 */
function enviarEmailRelatorio($destinatario, $assunto, $corpo) {
    // TODO: Implementar com PHPMailer ou similar
    // Por enquanto apenas loga
    echo "[EMAIL] Enviaria email para: {$destinatario}\n";
    echo "[EMAIL] Assunto: {$assunto}\n";
}
?>
