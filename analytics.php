<?php
/**
 * MOBILIZA+ - Sistema de Analytics Inteligente
 *
 * Gera insights automáticos, recomendações e análises preditivas
 * baseadas nos dados de mobilização
 */

require_once 'config.php';

class MobilizaAnalytics {

    private $pdo;

    public function __construct() {
        $this->pdo = conectar_db();
    }

    /**
     * Gera insights automáticos sobre a mobilização
     */
    public function gerarInsightsGerais() {
        $insights = [];

        // Análise de crescimento
        $crescimento = $this->analisarCrescimento();
        if ($crescimento['tendencia'] === 'crescendo') {
            $insights[] = [
                'tipo' => 'positivo',
                'icone' => '📈',
                'titulo' => 'Crescimento Acelerado',
                'mensagem' => "Sua mobilização está crescendo {$crescimento['percentual']}% em relação ao período anterior!",
                'acao' => 'Continue investindo nas estratégias que estão funcionando.'
            ];
        } elseif ($crescimento['tendencia'] === 'decrescendo') {
            $insights[] = [
                'tipo' => 'atencao',
                'icone' => '⚠️',
                'titulo' => 'Desaceleração Detectada',
                'mensagem' => "As mobilizações reduziram {$crescimento['percentual']}% em relação ao período anterior.",
                'acao' => 'Considere criar novos eventos ou petições para reengajar sua base.'
            ];
        }

        // Análise de horários de pico
        $horariosPico = $this->analisarHorariosPico();
        if ($horariosPico) {
            $insights[] = [
                'tipo' => 'dica',
                'icone' => '⏰',
                'titulo' => 'Melhor Horário de Engajamento',
                'mensagem' => "Seu público é mais ativo entre {$horariosPico['inicio']}h e {$horariosPico['fim']}h.",
                'acao' => 'Publique conteúdos importantes neste horário para maior alcance.'
            ];
        }

        // Análise de cidades mais engajadas
        $topCidades = $this->analisarTopCidades();
        if (count($topCidades) > 0) {
            $cidade_principal = $topCidades[0];
            $insights[] = [
                'tipo' => 'info',
                'icone' => '🏙️',
                'titulo' => 'Cidade Mais Engajada',
                'mensagem' => "{$cidade_principal['cidade']} lidera com {$cidade_principal['total']} mobilizações.",
                'acao' => 'Considere criar eventos presenciais ou grupos locais nesta cidade.'
            ];
        }

        // Análise de taxa de conversão
        $taxaConversao = $this->analisarTaxaConversao();
        if ($taxaConversao['taxa'] < 30) {
            $insights[] = [
                'tipo' => 'atencao',
                'icone' => '🎯',
                'titulo' => 'Taxa de Conversão Baixa',
                'mensagem' => "Apenas {$taxaConversao['taxa']}% dos visitantes estão se inscrevendo.",
                'acao' => 'Revise os formulários e CTAs para torná-los mais atrativos.'
            ];
        } elseif ($taxaConversao['taxa'] > 60) {
            $insights[] = [
                'tipo' => 'positivo',
                'icone' => '🎯',
                'titulo' => 'Excelente Taxa de Conversão',
                'mensagem' => "{$taxaConversao['taxa']}% dos visitantes estão se mobilizando!",
                'acao' => 'Suas páginas estão muito eficazes. Documente o que está funcionando.'
            ];
        }

        // Análise de mobilizadores
        $topMobilizadores = $this->analisarTopMobilizadores();
        if (count($topMobilizadores) > 0) {
            $top = $topMobilizadores[0];
            $insights[] = [
                'tipo' => 'destaque',
                'icone' => '⭐',
                'titulo' => 'Mobilizador Destaque',
                'mensagem' => "{$top['nome']} já mobilizou {$top['total']} pessoas!",
                'acao' => 'Reconheça e incentive seus melhores mobilizadores.'
            ];
        }

        // Análise de duplicatas
        $duplicatas = $this->detectarDuplicatas();
        if ($duplicatas['total'] > 0) {
            $insights[] = [
                'tipo' => 'atencao',
                'icone' => '🔍',
                'titulo' => 'Duplicatas Detectadas',
                'mensagem' => "Encontramos {$duplicatas['total']} possíveis contatos duplicados.",
                'acao' => 'Revise e limpe sua base de dados para melhor qualidade.'
            ];
        }

        // Previsão de meta
        $previsaoMeta = $this->preverAlcanceMeta();
        if ($previsaoMeta) {
            $insights[] = [
                'tipo' => 'info',
                'icone' => '📊',
                'titulo' => 'Previsão de Meta',
                'mensagem' => $previsaoMeta['mensagem'],
                'acao' => $previsaoMeta['acao']
            ];
        }

        return $insights;
    }

    /**
     * Analisa o crescimento da mobilização
     */
    private function analisarCrescimento() {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(CASE WHEN data_inscricao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana,
                COUNT(CASE WHEN data_inscricao >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                         AND data_inscricao < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as semana_anterior
            FROM inscricoes_eventos
        ");
        $eventos = $stmt->fetch();

        $stmt = $this->pdo->query("
            SELECT
                COUNT(CASE WHEN data_assinatura >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as ultima_semana,
                COUNT(CASE WHEN data_assinatura >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                         AND data_assinatura < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as semana_anterior
            FROM assinaturas_peticoes
        ");
        $peticoes = $stmt->fetch();

        $total_atual = $eventos['ultima_semana'] + $peticoes['ultima_semana'];
        $total_anterior = $eventos['semana_anterior'] + $peticoes['semana_anterior'];

        if ($total_anterior == 0) {
            return ['tendencia' => 'estavel', 'percentual' => 0];
        }

        $percentual = round((($total_atual - $total_anterior) / $total_anterior) * 100, 1);

        return [
            'tendencia' => $percentual > 0 ? 'crescendo' : ($percentual < 0 ? 'decrescendo' : 'estavel'),
            'percentual' => abs($percentual)
        ];
    }

    /**
     * Analisa horários de pico de engajamento
     */
    private function analisarHorariosPico() {
        $stmt = $this->pdo->query("
            SELECT HOUR(data_inscricao) as hora, COUNT(*) as total
            FROM inscricoes_eventos
            WHERE data_inscricao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY hora
            ORDER BY total DESC
            LIMIT 1
        ");

        $horario = $stmt->fetch();

        if ($horario) {
            return [
                'inicio' => $horario['hora'],
                'fim' => ($horario['hora'] + 2) % 24,
                'total' => $horario['total']
            ];
        }

        return null;
    }

    /**
     * Analisa as cidades mais engajadas
     */
    private function analisarTopCidades($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT cidade, COUNT(*) as total
            FROM (
                SELECT cidade FROM inscricoes_eventos WHERE cidade != ''
                UNION ALL
                SELECT cidade FROM assinaturas_peticoes WHERE cidade != ''
            ) as todas_cidades
            GROUP BY cidade
            ORDER BY total DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Analisa taxa de conversão (simulada - requer integração com analytics)
     */
    private function analisarTaxaConversao() {
        // Em produção, isso viria do Google Analytics ou similar
        // Por agora, vamos simular baseado em dados internos

        $stmt = $this->pdo->query("
            SELECT COUNT(*) as total FROM inscricoes_eventos
            WHERE data_inscricao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $inscricoes = $stmt->fetchColumn();

        // Simula visitantes (em produção viria do analytics)
        $visitantes_estimados = $inscricoes * 3; // Estimativa conservadora

        $taxa = $visitantes_estimados > 0 ? round(($inscricoes / $visitantes_estimados) * 100) : 0;

        return [
            'taxa' => $taxa,
            'inscricoes' => $inscricoes,
            'visitantes' => $visitantes_estimados
        ];
    }

    /**
     * Analisa top mobilizadores
     */
    private function analisarTopMobilizadores($limit = 5) {
        $stmt = $this->pdo->prepare("
            SELECT referencia as nome, COUNT(*) as total
            FROM (
                SELECT referencia FROM inscricoes_eventos WHERE referencia IS NOT NULL AND referencia != ''
                UNION ALL
                SELECT referencia FROM assinaturas_peticoes WHERE referencia IS NOT NULL AND referencia != ''
            ) as mobilizacoes
            GROUP BY referencia
            ORDER BY total DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    /**
     * Detecta possíveis duplicatas por WhatsApp
     */
    private function detectarDuplicatas() {
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as total
            FROM (
                SELECT whatsapp, COUNT(*) as vezes
                FROM (
                    SELECT whatsapp FROM inscricoes_eventos
                    UNION ALL
                    SELECT whatsapp FROM assinaturas_peticoes
                ) as todos_contatos
                GROUP BY whatsapp
                HAVING vezes > 1
            ) as duplicatas
        ");

        return ['total' => $stmt->fetchColumn()];
    }

    /**
     * Prevê se uma petição alcançará sua meta
     */
    private function preverAlcanceMeta() {
        $stmt = $this->pdo->query("
            SELECT p.id, p.titulo, p.meta_assinaturas, COUNT(a.id) as atual,
                   DATEDIFF(NOW(), p.data_criacao) as dias_decorridos
            FROM peticoes p
            LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id
            WHERE p.ativo = 1 AND p.meta_assinaturas > 0
            GROUP BY p.id
            HAVING atual < p.meta_assinaturas
            ORDER BY p.data_criacao DESC
            LIMIT 1
        ");

        $peticao = $stmt->fetch();

        if ($peticao && $peticao['dias_decorridos'] > 0) {
            $assinaturas_por_dia = $peticao['atual'] / $peticao['dias_decorridos'];
            $faltam = $peticao['meta_assinaturas'] - $peticao['atual'];
            $dias_previstos = $assinaturas_por_dia > 0 ? ceil($faltam / $assinaturas_por_dia) : 999;

            if ($dias_previstos <= 30) {
                return [
                    'mensagem' => "A petição '{$peticao['titulo']}' deve alcançar a meta em aproximadamente {$dias_previstos} dias!",
                    'acao' => 'Continue no ritmo atual para alcançar sua meta.'
                ];
            } else {
                return [
                    'mensagem' => "A petição '{$peticao['titulo']}' precisa de mais impulso para alcançar a meta.",
                    'acao' => 'Intensifique a divulgação para aumentar o ritmo de assinaturas.'
                ];
            }
        }

        return null;
    }

    /**
     * Gera relatório de engajamento por fonte de tráfego
     */
    public function relatorioFontesTrafego() {
        $stmt = $this->pdo->query("
            SELECT
                referencia as fonte,
                COUNT(*) as total,
                COUNT(DISTINCT cidade) as cidades_alcancadas
            FROM (
                SELECT referencia, cidade FROM inscricoes_eventos WHERE referencia IS NOT NULL
                UNION ALL
                SELECT referencia, cidade FROM assinaturas_peticoes WHERE referencia IS NOT NULL
            ) as todas_fontes
            GROUP BY referencia
            ORDER BY total DESC
        ");

        return $stmt->fetchAll();
    }

    /**
     * Análise de retenção (pessoas que participam de múltiplas ações)
     */
    public function analisarRetencao() {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(CASE WHEN acoes = 1 THEN 1 END) as uma_acao,
                COUNT(CASE WHEN acoes = 2 THEN 1 END) as duas_acoes,
                COUNT(CASE WHEN acoes >= 3 THEN 1 END) as tres_ou_mais
            FROM (
                SELECT whatsapp, COUNT(DISTINCT acao_tipo) as acoes
                FROM (
                    SELECT whatsapp, 'evento' as acao_tipo FROM inscricoes_eventos
                    UNION ALL
                    SELECT whatsapp, 'peticao' as acao_tipo FROM assinaturas_peticoes
                ) as todas_acoes
                GROUP BY whatsapp
            ) as retencao
        ");

        return $stmt->fetch();
    }

    /**
     * Segmentação inteligente de contatos
     */
    public function segmentarContatos() {
        $segmentos = [];

        // Super engajados (participaram de 3+ ações)
        $stmt = $this->pdo->query("
            SELECT COUNT(DISTINCT whatsapp) as total
            FROM (
                SELECT whatsapp FROM inscricoes_eventos
                UNION ALL
                SELECT whatsapp FROM assinaturas_peticoes
            ) as todos
            GROUP BY whatsapp
            HAVING COUNT(*) >= 3
        ");
        $segmentos['super_engajados'] = $stmt->fetchColumn();

        // Novos (primeira ação nos últimos 7 dias)
        $stmt = $this->pdo->query("
            SELECT COUNT(DISTINCT whatsapp) as total
            FROM (
                SELECT whatsapp, MIN(data_inscricao) as primeira_acao
                FROM inscricoes_eventos
                GROUP BY whatsapp
                UNION ALL
                SELECT whatsapp, MIN(data_assinatura) as primeira_acao
                FROM assinaturas_peticoes
                GROUP BY whatsapp
            ) as primeiras
            WHERE primeira_acao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $segmentos['novos'] = $stmt->fetchColumn();

        // Inativos (última ação há mais de 30 dias)
        $stmt = $this->pdo->query("
            SELECT COUNT(DISTINCT whatsapp) as total
            FROM (
                SELECT whatsapp, MAX(data_inscricao) as ultima_acao
                FROM inscricoes_eventos
                GROUP BY whatsapp
                UNION ALL
                SELECT whatsapp, MAX(data_assinatura) as ultima_acao
                FROM assinaturas_peticoes
                GROUP BY whatsapp
            ) as ultimas
            WHERE ultima_acao < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $segmentos['inativos'] = $stmt->fetchColumn();

        return $segmentos;
    }

    /**
     * Gera score de qualidade de evento
     */
    public function scoreQualidadeEvento($evento_id) {
        $stmt = $this->pdo->prepare("
            SELECT
                e.*,
                COUNT(i.id) as total_inscricoes,
                COUNT(CASE WHEN i.checkin = 1 THEN 1 END) as total_checkins,
                COUNT(p.id) as total_participantes
            FROM eventos e
            LEFT JOIN inscricoes_eventos i ON e.id = i.evento_id
            LEFT JOIN participantes_evento p ON e.id = p.evento_id
            WHERE e.id = ?
            GROUP BY e.id
        ");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch();

        if (!$evento) return null;

        $score = 0;
        $feedback = [];

        // Critério 1: Tem descrição completa (20 pontos)
        if (strlen($evento['descricao']) > 200) {
            $score += 20;
        } else {
            $feedback[] = 'Adicione uma descrição mais completa (mínimo 200 caracteres)';
        }

        // Critério 2: Tem imagem (15 pontos)
        if (!empty($evento['imagem_cabecalho'])) {
            $score += 15;
        } else {
            $feedback[] = 'Adicione uma imagem de cabeçalho atraente';
        }

        // Critério 3: Tem participantes cadastrados (20 pontos)
        if ($evento['total_participantes'] > 0) {
            $score += 20;
        } else {
            $feedback[] = 'Cadastre palestrantes ou participantes do evento';
        }

        // Critério 4: Tem link do WhatsApp (15 pontos)
        if (!empty($evento['link_whatsapp'])) {
            $score += 15;
        } else {
            $feedback[] = 'Adicione um grupo de WhatsApp para aumentar engajamento';
        }

        // Critério 5: Taxa de check-in boa (30 pontos)
        if ($evento['total_inscricoes'] > 0) {
            $taxa_checkin = ($evento['total_checkins'] / $evento['total_inscricoes']) * 100;
            if ($taxa_checkin >= 70) {
                $score += 30;
            } elseif ($taxa_checkin >= 50) {
                $score += 20;
                $feedback[] = 'Melhore a taxa de check-in (atual: ' . round($taxa_checkin) . '%)';
            } else {
                $score += 10;
                $feedback[] = 'Taxa de check-in baixa. Envie lembretes antes do evento.';
            }
        }

        return [
            'score' => $score,
            'classificacao' => $score >= 80 ? 'Excelente' : ($score >= 60 ? 'Bom' : 'Precisa melhorar'),
            'feedback' => $feedback
        ];
    }
}
?>
