<?php
/**
 * MOBILIZA+ - Sistema de Notificações e Alertas
 *
 * Gerencia notificações automáticas e alertas do sistema
 */

require_once 'config.php';
require_once 'cache.php';

class MobilizaNotifications {

    private $pdo;

    public function __construct() {
        $this->pdo = conectar_db();
    }

    /**
     * Cria tabela de notificações se não existir
     */
    public function init() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS notificacoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NULL,
                tipo ENUM('info', 'sucesso', 'aviso', 'erro', 'meta') DEFAULT 'info',
                titulo VARCHAR(255) NOT NULL,
                mensagem TEXT NOT NULL,
                link VARCHAR(255) NULL,
                lida BOOLEAN DEFAULT FALSE,
                data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                data_leitura TIMESTAMP NULL,
                INDEX idx_usuario (usuario_id),
                INDEX idx_lida (lida),
                FOREIGN KEY (usuario_id) REFERENCES usuarios_sistema(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Cria uma notificação
     */
    public function criar($usuario_id, $tipo, $titulo, $mensagem, $link = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, link)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([$usuario_id, $tipo, $titulo, $mensagem, $link]);
    }

    /**
     * Busca notificações não lidas
     */
    public function buscarNaoLidas($usuario_id, $limite = 10) {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM notificacoes
            WHERE usuario_id = ? AND lida = FALSE
            ORDER BY data_criacao DESC
            LIMIT ?
        ");
        $stmt->execute([$usuario_id, $limite]);

        return $stmt->fetchAll();
    }

    /**
     * Conta notificações não lidas
     */
    public function contarNaoLidas($usuario_id) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM notificacoes
            WHERE usuario_id = ? AND lida = FALSE
        ");
        $stmt->execute([$usuario_id]);

        return $stmt->fetchColumn();
    }

    /**
     * Marca notificação como lida
     */
    public function marcarLida($notificacao_id) {
        $stmt = $this->pdo->prepare("
            UPDATE notificacoes
            SET lida = TRUE, data_leitura = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$notificacao_id]);
    }

    /**
     * Marca todas como lidas
     */
    public function marcarTodasLidas($usuario_id) {
        $stmt = $this->pdo->prepare("
            UPDATE notificacoes
            SET lida = TRUE, data_leitura = NOW()
            WHERE usuario_id = ? AND lida = FALSE
        ");

        return $stmt->execute([$usuario_id]);
    }

    /**
     * Verifica e cria alertas automáticos
     */
    public function verificarAlertasAutomaticos() {
        // Alerta: Evento próximo sem inscrições suficientes
        $stmt = $this->pdo->query("
            SELECT e.*, u.id as usuario_id, COUNT(i.id) as total_inscricoes
            FROM eventos e
            JOIN usuarios_sistema u ON e.criado_por = u.id
            LEFT JOIN inscricoes_eventos i ON e.id = i.evento_id
            WHERE e.ativo = 1
              AND e.data_evento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            GROUP BY e.id
            HAVING total_inscricoes < 10
        ");

        $eventos_baixa_inscricao = $stmt->fetchAll();

        foreach ($eventos_baixa_inscricao as $evento) {
            // Verifica se já notificou nas últimas 24h
            $cache_key = "notif_evento_baixo_{$evento['id']}";
            if (!cache_get($cache_key)) {
                $this->criar(
                    $evento['usuario_id'],
                    'aviso',
                    'Evento com poucas inscrições',
                    "O evento '{$evento['titulo']}' acontece em breve mas tem apenas {$evento['total_inscricoes']} inscrições. Considere intensificar a divulgação.",
                    "/admin/eventos/form.php?id={$evento['id']}"
                );
                cache_set($cache_key, true, 86400); // 24h
            }
        }

        // Alerta: Petição próxima da meta
        $stmt = $this->pdo->query("
            SELECT p.*, u.id as usuario_id, COUNT(a.id) as total_assinaturas
            FROM peticoes p
            JOIN usuarios_sistema u ON p.criado_por = u.id
            LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id
            WHERE p.ativo = 1 AND p.meta_assinaturas > 0
            GROUP BY p.id
            HAVING total_assinaturas >= (p.meta_assinaturas * 0.9)
               AND total_assinaturas < p.meta_assinaturas
        ");

        $peticoes_proximas_meta = $stmt->fetchAll();

        foreach ($peticoes_proximas_meta as $peticao) {
            $cache_key = "notif_peticao_90_{$peticao['id']}";
            if (!cache_get($cache_key)) {
                $faltam = $peticao['meta_assinaturas'] - $peticao['total_assinaturas'];
                $this->criar(
                    $peticao['usuario_id'],
                    'info',
                    'Petição quase na meta! 🎯',
                    "A petição '{$peticao['titulo']}' está a apenas {$faltam} assinaturas de alcançar a meta!",
                    "/admin/apoie/assinaturas.php?id={$peticao['id']}"
                );
                cache_set($cache_key, true, 86400);
            }
        }

        // Alerta: Meta alcançada!
        $stmt = $this->pdo->query("
            SELECT p.*, u.id as usuario_id, COUNT(a.id) as total_assinaturas
            FROM peticoes p
            JOIN usuarios_sistema u ON p.criado_por = u.id
            LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id
            WHERE p.ativo = 1 AND p.meta_assinaturas > 0
            GROUP BY p.id
            HAVING total_assinaturas >= p.meta_assinaturas
        ");

        $peticoes_meta_alcancada = $stmt->fetchAll();

        foreach ($peticoes_meta_alcancada as $peticao) {
            $cache_key = "notif_peticao_meta_{$peticao['id']}";
            if (!cache_get($cache_key)) {
                $this->criar(
                    $peticao['usuario_id'],
                    'sucesso',
                    'Meta alcançada! 🎉',
                    "Parabéns! A petição '{$peticao['titulo']}' alcançou a meta de {$peticao['meta_assinaturas']} assinaturas!",
                    "/admin/apoie/assinaturas.php?id={$peticao['id']}"
                );
                cache_set($cache_key, true, 999999); // Notifica apenas uma vez
            }
        }

        // Alerta: Crescimento acelerado
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as hoje
            FROM (
                SELECT data_inscricao as data FROM inscricoes_eventos WHERE DATE(data_inscricao) = CURDATE()
                UNION ALL
                SELECT data_assinatura as data FROM assinaturas_peticoes WHERE DATE(data_assinatura) = CURDATE()
            ) as hoje
        ");
        $mobilizacoes_hoje = $stmt->fetchColumn();

        $stmt = $this->pdo->query("
            SELECT AVG(total) as media
            FROM (
                SELECT DATE(data_acao) as dia, COUNT(*) as total
                FROM (
                    SELECT data_inscricao as data_acao FROM inscricoes_eventos
                    WHERE data_inscricao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    UNION ALL
                    SELECT data_assinatura as data_acao FROM assinaturas_peticoes
                    WHERE data_assinatura >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ) as ultimas
                GROUP BY DATE(data_acao)
            ) as medias
        ");
        $media_semanal = $stmt->fetchColumn();

        if ($mobilizacoes_hoje > ($media_semanal * 2)) {
            $cache_key = "notif_crescimento_" . date('Y-m-d');
            if (!cache_get($cache_key)) {
                // Notifica todos os admins
                $stmt = $this->pdo->query("SELECT id FROM usuarios_sistema WHERE tipo = 'admin'");
                $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($admins as $admin_id) {
                    $this->criar(
                        $admin_id,
                        'sucesso',
                        'Crescimento acelerado hoje! 📈',
                        "Hoje você teve {$mobilizacoes_hoje} mobilizações, mais do que o dobro da média semanal! Continue assim!",
                        "/admin/dashboard.php"
                    );
                }
                cache_set($cache_key, true, 86400);
            }
        }
    }

    /**
     * Limpa notificações antigas (mais de 30 dias)
     */
    public function limparAntigas() {
        $stmt = $this->pdo->query("
            DELETE FROM notificacoes
            WHERE data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        return $stmt->rowCount();
    }

    /**
     * Renderiza notificações em HTML (para usar no header do admin)
     */
    public function renderizarBadge($usuario_id) {
        $total = $this->contarNaoLidas($usuario_id);

        if ($total > 0) {
            return '<span class="badge badge-danger">' . $total . '</span>';
        }

        return '';
    }

    /**
     * Renderiza lista de notificações
     */
    public function renderizarLista($usuario_id, $limite = 5) {
        $notificacoes = $this->buscarNaoLidas($usuario_id, $limite);

        if (empty($notificacoes)) {
            return '<div class="dropdown-item text-muted">Nenhuma notificação</div>';
        }

        $html = '';
        foreach ($notificacoes as $notif) {
            $icone = $this->getIcone($notif['tipo']);
            $cor = $this->getCor($notif['tipo']);

            $html .= '<a href="' . ($notif['link'] ?? '#') . '" class="dropdown-item" onclick="marcarNotificacaoLida(' . $notif['id'] . ')">';
            $html .= '<div class="notification-item">';
            $html .= '<span class="notification-icon ' . $cor . '">' . $icone . '</span>';
            $html .= '<div class="notification-content">';
            $html .= '<strong>' . htmlspecialchars($notif['titulo']) . '</strong>';
            $html .= '<p>' . htmlspecialchars($notif['mensagem']) . '</p>';
            $html .= '<small>' . $this->tempoDecorrido($notif['data_criacao']) . '</small>';
            $html .= '</div></div></a>';
        }

        return $html;
    }

    /**
     * Retorna ícone baseado no tipo
     */
    private function getIcone($tipo) {
        $icones = [
            'info' => 'ℹ️',
            'sucesso' => '✅',
            'aviso' => '⚠️',
            'erro' => '❌',
            'meta' => '🎯'
        ];

        return $icones[$tipo] ?? 'ℹ️';
    }

    /**
     * Retorna classe de cor baseada no tipo
     */
    private function getCor($tipo) {
        $cores = [
            'info' => 'text-info',
            'sucesso' => 'text-success',
            'aviso' => 'text-warning',
            'erro' => 'text-danger',
            'meta' => 'text-primary'
        ];

        return $cores[$tipo] ?? 'text-info';
    }

    /**
     * Calcula tempo decorrido de forma amigável
     */
    private function tempoDecorrido($data) {
        $agora = time();
        $tempo = strtotime($data);
        $diff = $agora - $tempo;

        if ($diff < 60) {
            return 'agora mesmo';
        } elseif ($diff < 3600) {
            $minutos = floor($diff / 60);
            return $minutos . ' minuto' . ($minutos > 1 ? 's' : '') . ' atrás';
        } elseif ($diff < 86400) {
            $horas = floor($diff / 3600);
            return $horas . ' hora' . ($horas > 1 ? 's' : '') . ' atrás';
        } else {
            $dias = floor($diff / 86400);
            return $dias . ' dia' . ($dias > 1 ? 's' : '') . ' atrás';
        }
    }
}

// API para marcar notificação como lida (usar via AJAX)
if (isset($_POST['marcar_lida'])) {
    session_start();
    require_once 'config.php';

    if (!isset($_SESSION['usuario_id'])) {
        die(json_encode(['sucesso' => false]));
    }

    $notif = new MobilizaNotifications();
    $notif->init();

    $notif_id = $_POST['notificacao_id'] ?? 0;
    $sucesso = $notif->marcarLida($notif_id);

    echo json_encode(['sucesso' => $sucesso]);
    exit;
}
?>
