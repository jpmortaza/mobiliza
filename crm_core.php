<?php
/**
 * MOBILIZA+ CRM - Core
 *
 * Classe principal de gerenciamento do CRM
 */

require_once 'config.php';
require_once 'cache.php';

class MobilizaCRM {

    private $pdo;

    public function __construct() {
        $this->pdo = conectar_db();
    }

    /**
     * Sincroniza contatos de eventos, petições e grupos para o CRM
     */
    public function sincronizarContatos() {
        $total_sincronizados = 0;

        // Sincronizar de eventos
        $stmt = $this->pdo->query("
            SELECT DISTINCT
                nome,
                whatsapp,
                email,
                cidade,
                MIN(data_inscricao) as primeira_interacao,
                MAX(data_inscricao) as ultima_interacao
            FROM inscricoes_eventos
            GROUP BY whatsapp
        ");

        $eventos = $stmt->fetchAll();

        foreach ($eventos as $pessoa) {
            if ($this->criarOuAtualizarContato($pessoa, 'evento')) {
                $total_sincronizados++;
            }
        }

        // Sincronizar de petições
        $stmt = $this->pdo->query("
            SELECT DISTINCT
                nome,
                whatsapp,
                email,
                cidade,
                MIN(data_assinatura) as primeira_interacao,
                MAX(data_assinatura) as ultima_interacao
            FROM assinaturas_peticoes
            GROUP BY whatsapp
        ");

        $peticoes = $stmt->fetchAll();

        foreach ($peticoes as $pessoa) {
            if ($this->criarOuAtualizarContato($pessoa, 'peticao')) {
                $total_sincronizados++;
            }
        }

        // Atualizar estatísticas
        $this->atualizarEstatisticasContatos();

        return $total_sincronizados;
    }

    /**
     * Cria ou atualiza um contato no CRM
     */
    private function criarOuAtualizarContato($dados, $origem) {
        // Verificar se já existe
        $stmt = $this->pdo->prepare("SELECT id FROM crm_contatos WHERE whatsapp = ?");
        $stmt->execute([$dados['whatsapp']]);
        $existe = $stmt->fetch();

        if ($existe) {
            // Atualizar
            $stmt = $this->pdo->prepare("
                UPDATE crm_contatos
                SET nome = COALESCE(?, nome),
                    email = COALESCE(?, email),
                    cidade = COALESCE(?, cidade),
                    ultima_interacao = GREATEST(COALESCE(ultima_interacao, ?), ?),
                    primeira_interacao = LEAST(COALESCE(primeira_interacao, ?), ?)
                WHERE whatsapp = ?
            ");

            return $stmt->execute([
                $dados['nome'],
                $dados['email'] ?? null,
                $dados['cidade'] ?? null,
                $dados['ultima_interacao'],
                $dados['ultima_interacao'],
                $dados['primeira_interacao'],
                $dados['primeira_interacao'],
                $dados['whatsapp']
            ]);

        } else {
            // Criar novo
            $stmt = $this->pdo->prepare("
                INSERT INTO crm_contatos
                (nome, whatsapp, email, cidade, origem, primeira_interacao, ultima_interacao)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $dados['nome'],
                $dados['whatsapp'],
                $dados['email'] ?? null,
                $dados['cidade'] ?? null,
                $origem,
                $dados['primeira_interacao'],
                $dados['ultima_interacao']
            ]);
        }
    }

    /**
     * Atualiza estatísticas de todos os contatos
     */
    public function atualizarEstatisticasContatos() {
        // Atualizar contagem de eventos
        $this->pdo->exec("
            UPDATE crm_contatos c
            SET total_eventos = (
                SELECT COUNT(*) FROM inscricoes_eventos WHERE whatsapp = c.whatsapp
            )
        ");

        // Atualizar contagem de petições
        $this->pdo->exec("
            UPDATE crm_contatos c
            SET total_peticoes = (
                SELECT COUNT(*) FROM assinaturas_peticoes WHERE whatsapp = c.whatsapp
            )
        ");

        // Atualizar contagem de grupos
        $this->pdo->exec("
            UPDATE crm_contatos c
            SET total_grupos = (
                SELECT COUNT(*) FROM crm_grupos_participantes WHERE contato_id = c.id AND ativo = 1
            )
        ");

        // Calcular total de ações
        $this->pdo->exec("
            UPDATE crm_contatos
            SET total_acoes = total_eventos + total_peticoes + total_grupos
        ");

        // Calcular score de engajamento
        $this->pdo->exec("
            UPDATE crm_contatos
            SET score_engajamento = LEAST(100,
                (total_eventos * 10) +
                (total_peticoes * 5) +
                (total_grupos * 15)
            )
        ");

        return true;
    }

    /**
     * Registra uma ligação
     */
    public function registrarLigacao($dados) {
        $stmt = $this->pdo->prepare("
            INSERT INTO crm_ligacoes
            (contato_id, atendente_id, data_ligacao, duracao_segundos, tipo_ligacao,
             status_ligacao, resultado, objetivo, resumo, observacoes, proximos_passos,
             agendar_retorno, data_retorno, motivo_retorno)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $sucesso = $stmt->execute([
            $dados['contato_id'],
            $dados['atendente_id'],
            $dados['duracao_segundos'] ?? 0,
            $dados['tipo_ligacao'] ?? 'ativa',
            $dados['status_ligacao'],
            $dados['resultado'] ?? null,
            $dados['objetivo'] ?? null,
            $dados['resumo'] ?? null,
            $dados['observacoes'] ?? null,
            $dados['proximos_passos'] ?? null,
            $dados['agendar_retorno'] ?? false,
            $dados['data_retorno'] ?? null,
            $dados['motivo_retorno'] ?? null
        ]);

        if ($sucesso) {
            $ligacao_id = $this->pdo->lastInsertId();

            // Registrar no histórico
            $this->registrarHistorico(
                $dados['contato_id'],
                'ligacao',
                "Ligação realizada - Status: {$dados['status_ligacao']}",
                $dados['resultado'] ?? null,
                $dados['atendente_id']
            );

            // Atualizar última interação do contato
            $this->pdo->prepare("UPDATE crm_contatos SET ultima_interacao = NOW() WHERE id = ?")
                      ->execute([$dados['contato_id']]);

            // Atualizar estatísticas do atendente
            $this->atualizarEstatisticasAtendente($dados['atendente_id']);

            return $ligacao_id;
        }

        return false;
    }

    /**
     * Registra uma interação no histórico
     */
    public function registrarHistorico($contato_id, $tipo, $descricao, $resultado = null, $registrado_por = null, $dados_adicionais = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO crm_historico
            (contato_id, tipo_interacao, descricao, resultado, registrado_por, dados_adicionais)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $contato_id,
            $tipo,
            $descricao,
            $resultado,
            $registrado_por ?? $_SESSION['usuario_id'] ?? null,
            $dados_adicionais ? json_encode($dados_adicionais) : null
        ]);
    }

    /**
     * Busca contatos com filtros avançados
     */
    public function buscarContatos($filtros = [], $limite = 50, $offset = 0) {
        $where = ["c.ativo = 1"];
        $params = [];

        if (!empty($filtros['busca'])) {
            $where[] = "(c.nome LIKE ? OR c.whatsapp LIKE ? OR c.email LIKE ? OR c.cidade LIKE ?)";
            $busca = "%{$filtros['busca']}%";
            $params = array_merge($params, [$busca, $busca, $busca, $busca]);
        }

        if (!empty($filtros['status'])) {
            $where[] = "c.status_contato = ?";
            $params[] = $filtros['status'];
        }

        if (!empty($filtros['atendente_id'])) {
            $where[] = "c.atendente_responsavel_id = ?";
            $params[] = $filtros['atendente_id'];
        }

        if (!empty($filtros['cidade'])) {
            $where[] = "c.cidade = ?";
            $params[] = $filtros['cidade'];
        }

        if (!empty($filtros['prioridade'])) {
            $where[] = "c.prioridade = ?";
            $params[] = $filtros['prioridade'];
        }

        if (isset($filtros['score_min'])) {
            $where[] = "c.score_engajamento >= ?";
            $params[] = $filtros['score_min'];
        }

        if (!empty($filtros['sem_atendente'])) {
            $where[] = "c.atendente_responsavel_id IS NULL";
        }

        $where_sql = implode(' AND ', $where);

        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nome as atendente_nome,
                   (SELECT COUNT(*) FROM crm_ligacoes WHERE contato_id = c.id) as total_ligacoes,
                   (SELECT MAX(data_ligacao) FROM crm_ligacoes WHERE contato_id = c.id) as ultima_ligacao
            FROM crm_contatos c
            LEFT JOIN usuarios_sistema u ON c.atendente_responsavel_id = u.id
            WHERE {$where_sql}
            ORDER BY c.prioridade DESC, c.score_engajamento DESC, c.data_cadastro DESC
            LIMIT ? OFFSET ?
        ");

        $params[] = $limite;
        $params[] = $offset;

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca um contato completo por ID
     */
    public function buscarContato($id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nome as atendente_nome, u.email as atendente_email
            FROM crm_contatos c
            LEFT JOIN usuarios_sistema u ON c.atendente_responsavel_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $contato = $stmt->fetch();

        if ($contato) {
            // Buscar histórico
            $stmt = $this->pdo->prepare("
                SELECT h.*, u.nome as registrado_por_nome
                FROM crm_historico h
                LEFT JOIN usuarios_sistema u ON h.registrado_por = u.id
                WHERE h.contato_id = ?
                ORDER BY h.data_registro DESC
            ");
            $stmt->execute([$id]);
            $contato['historico'] = $stmt->fetchAll();

            // Buscar ligações
            $stmt = $this->pdo->prepare("
                SELECT l.*, u.nome as atendente_nome
                FROM crm_ligacoes l
                LEFT JOIN usuarios_sistema u ON l.atendente_id = u.id
                WHERE l.contato_id = ?
                ORDER BY l.data_ligacao DESC
            ");
            $stmt->execute([$id]);
            $contato['ligacoes'] = $stmt->fetchAll();

            // Buscar tarefas pendentes
            $stmt = $this->pdo->prepare("
                SELECT t.*, u.nome as atendente_nome
                FROM crm_tarefas t
                LEFT JOIN usuarios_sistema u ON t.atendente_id = u.id
                WHERE t.contato_id = ? AND t.status != 'concluida'
                ORDER BY t.data_vencimento ASC
            ");
            $stmt->execute([$id]);
            $contato['tarefas'] = $stmt->fetchAll();

            // Buscar eventos participados
            $stmt = $this->pdo->prepare("
                SELECT e.titulo, e.data_evento, i.data_inscricao, i.checkin
                FROM inscricoes_eventos i
                JOIN eventos e ON i.evento_id = e.id
                WHERE i.whatsapp = ?
                ORDER BY i.data_inscricao DESC
            ");
            $stmt->execute([$contato['whatsapp']]);
            $contato['eventos'] = $stmt->fetchAll();

            // Buscar petições assinadas
            $stmt = $this->pdo->prepare("
                SELECT p.titulo, a.data_assinatura
                FROM assinaturas_peticoes a
                JOIN peticoes p ON a.peticao_id = p.id
                WHERE a.whatsapp = ?
                ORDER BY a.data_assinatura DESC
            ");
            $stmt->execute([$contato['whatsapp']]);
            $contato['peticoes'] = $stmt->fetchAll();

            // Buscar grupos
            $stmt = $this->pdo->prepare("
                SELECT g.nome, g.categoria, gp.data_entrada
                FROM crm_grupos_participantes gp
                JOIN crm_grupos_whatsapp g ON gp.grupo_id = g.id
                WHERE gp.contato_id = ? AND gp.ativo = 1
                ORDER BY gp.data_entrada DESC
            ");
            $stmt->execute([$id]);
            $contato['grupos'] = $stmt->fetchAll();
        }

        return $contato;
    }

    /**
     * Atribui contato a um atendente
     */
    public function atribuirContato($contato_id, $atendente_id) {
        $stmt = $this->pdo->prepare("
            UPDATE crm_contatos
            SET atendente_responsavel_id = ?,
                status_contato = CASE
                    WHEN status_contato = 'novo' THEN 'aguardando_contato'
                    ELSE status_contato
                END
            WHERE id = ?
        ");

        $sucesso = $stmt->execute([$atendente_id, $contato_id]);

        if ($sucesso) {
            // Registrar no histórico
            $this->registrarHistorico(
                $contato_id,
                'nota',
                "Contato atribuído ao atendente",
                null,
                $atendente_id
            );

            // Atualizar contador do atendente
            $this->pdo->exec("
                UPDATE crm_atendentes
                SET total_contatos_atribuidos = (
                    SELECT COUNT(*) FROM crm_contatos WHERE atendente_responsavel_id = usuario_id
                )
                WHERE usuario_id = {$atendente_id}
            ");
        }

        return $sucesso;
    }

    /**
     * Atualiza status do contato
     */
    public function atualizarStatusContato($contato_id, $novo_status, $observacao = null) {
        $stmt = $this->pdo->prepare("UPDATE crm_contatos SET status_contato = ? WHERE id = ?");
        $sucesso = $stmt->execute([$novo_status, $contato_id]);

        if ($sucesso) {
            $descricao = "Status alterado para: {$novo_status}";
            if ($observacao) {
                $descricao .= " - {$observacao}";
            }

            $this->registrarHistorico($contato_id, 'status_alterado', $descricao);
        }

        return $sucesso;
    }

    /**
     * Enriquece dados do contato
     */
    public function enriquecerContato($contato_id, $dados) {
        $campos_permitidos = [
            'cpf', 'data_nascimento', 'genero', 'profissao', 'escolaridade', 'renda',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'estado',
            'instagram', 'facebook', 'twitter', 'linkedin',
            'interesses', 'causas_apoiadas', 'disponibilidade_voluntariado', 'areas_voluntariado'
        ];

        $sets = [];
        $params = [];

        foreach ($dados as $campo => $valor) {
            if (in_array($campo, $campos_permitidos)) {
                $sets[] = "{$campo} = ?";
                $params[] = $valor;
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $contato_id;
        $sql = "UPDATE crm_contatos SET " . implode(', ', $sets) . " WHERE id = ?";

        $stmt = $this->pdo->prepare($sql);
        $sucesso = $stmt->execute($params);

        if ($sucesso) {
            $this->registrarHistorico($contato_id, 'dados_atualizados', 'Dados enriquecidos');
        }

        return $sucesso;
    }

    /**
     * Atualiza estatísticas de um atendente
     */
    private function atualizarEstatisticasAtendente($atendente_id) {
        $this->pdo->prepare("
            UPDATE crm_atendentes a
            SET
                total_ligacoes_realizadas = (
                    SELECT COUNT(*) FROM crm_ligacoes WHERE atendente_id = a.usuario_id
                ),
                total_conversoes = (
                    SELECT COUNT(*) FROM crm_ligacoes
                    WHERE atendente_id = a.usuario_id AND converteu_em_acao = 1
                ),
                tempo_medio_ligacao = (
                    SELECT AVG(duracao_segundos) FROM crm_ligacoes
                    WHERE atendente_id = a.usuario_id AND status_ligacao = 'completada'
                )
            WHERE usuario_id = ?
        ")->execute([$atendente_id]);

        // Calcular taxa de conversão
        $this->pdo->exec("
            UPDATE crm_atendentes
            SET taxa_conversao = CASE
                WHEN total_ligacoes_realizadas > 0
                THEN (total_conversoes * 100.0 / total_ligacoes_realizadas)
                ELSE 0
            END
            WHERE usuario_id = {$atendente_id}
        ");
    }

    /**
     * Estatísticas gerais do CRM
     */
    public function estatisticasGerais() {
        return cache_remember('crm_stats_gerais', 300, function() {
            $stats = [];

            // Total de contatos
            $stats['total_contatos'] = $this->pdo->query("SELECT COUNT(*) FROM crm_contatos WHERE ativo = 1")->fetchColumn();

            // Por status
            $stmt = $this->pdo->query("
                SELECT status_contato, COUNT(*) as total
                FROM crm_contatos
                WHERE ativo = 1
                GROUP BY status_contato
            ");
            $stats['por_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Contatos sem atendente
            $stats['sem_atendente'] = $this->pdo->query("
                SELECT COUNT(*) FROM crm_contatos
                WHERE ativo = 1 AND atendente_responsavel_id IS NULL
            ")->fetchColumn();

            // Total de ligações hoje
            $stats['ligacoes_hoje'] = $this->pdo->query("
                SELECT COUNT(*) FROM crm_ligacoes WHERE DATE(data_ligacao) = CURDATE()
            ")->fetchColumn();

            // Ligações esta semana
            $stats['ligacoes_semana'] = $this->pdo->query("
                SELECT COUNT(*) FROM crm_ligacoes
                WHERE data_ligacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ")->fetchColumn();

            // Taxa de conversão geral
            $total_ligacoes = $this->pdo->query("SELECT COUNT(*) FROM crm_ligacoes WHERE status_ligacao = 'completada'")->fetchColumn();
            $conversoes = $this->pdo->query("SELECT COUNT(*) FROM crm_ligacoes WHERE converteu_em_acao = 1")->fetchColumn();
            $stats['taxa_conversao_geral'] = $total_ligacoes > 0 ? round(($conversoes / $total_ligacoes) * 100, 2) : 0;

            // Tarefas pendentes
            $stats['tarefas_pendentes'] = $this->pdo->query("
                SELECT COUNT(*) FROM crm_tarefas WHERE status != 'concluida'
            ")->fetchColumn();

            // Score médio
            $stats['score_medio'] = $this->pdo->query("
                SELECT AVG(score_engajamento) FROM crm_contatos WHERE ativo = 1
            ")->fetchColumn();

            return $stats;
        });
    }

    /**
     * Ranking de atendentes
     */
    public function rankingAtendentes($periodo_dias = 30) {
        $stmt = $this->pdo->prepare("
            SELECT
                u.nome as atendente,
                a.total_contatos_atribuidos,
                a.total_ligacoes_realizadas,
                a.total_conversoes,
                a.taxa_conversao,
                a.tempo_medio_ligacao,
                COUNT(DISTINCT l.contato_id) as contatos_contatados,
                COUNT(CASE WHEN DATE(l.data_ligacao) >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1 END) as ligacoes_periodo
            FROM crm_atendentes a
            JOIN usuarios_sistema u ON a.usuario_id = u.id
            LEFT JOIN crm_ligacoes l ON l.atendente_id = a.usuario_id
            WHERE a.ativo = 1
            GROUP BY a.id, u.nome
            ORDER BY a.total_conversoes DESC, a.taxa_conversao DESC
        ");

        $stmt->execute([$periodo_dias]);
        return $stmt->fetchAll();
    }
}
?>
