<?php
/**
 * MOBILIZA+ - Sistema de Exportação de Dados
 *
 * Exporta dados em CSV e Excel com análises integradas
 */

require_once 'config.php';

class MobilizaExport {

    private $pdo;

    public function __construct() {
        $this->pdo = conectar_db();
    }

    /**
     * Exporta inscrições de evento para CSV
     */
    public function exportarInscricoesEvento($evento_id, $formato = 'csv') {
        $stmt = $this->pdo->prepare("
            SELECT
                i.id,
                i.nome,
                i.email,
                i.whatsapp,
                i.cidade,
                i.referencia as 'Indicado por',
                i.checkin as 'Check-in Realizado',
                DATE_FORMAT(i.data_inscricao, '%d/%m/%Y %H:%i') as 'Data Inscrição',
                DATE_FORMAT(i.data_checkin, '%d/%m/%Y %H:%i') as 'Data Check-in',
                e.titulo as 'Evento'
            FROM inscricoes_eventos i
            JOIN eventos e ON i.evento_id = e.id
            WHERE i.evento_id = ?
            ORDER BY i.data_inscricao DESC
        ");
        $stmt->execute([$evento_id]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar nome do evento para o arquivo
        $stmt = $this->pdo->prepare("SELECT slug FROM eventos WHERE id = ?");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch();

        $nome_arquivo = 'inscricoes_' . ($evento['slug'] ?? 'evento') . '_' . date('Y-m-d');

        if ($formato === 'csv') {
            $this->gerarCSV($dados, $nome_arquivo);
        } else {
            $this->gerarExcel($dados, $nome_arquivo);
        }
    }

    /**
     * Exporta assinaturas de petição para CSV
     */
    public function exportarAssinaturasPeticao($peticao_id, $formato = 'csv') {
        $stmt = $this->pdo->prepare("
            SELECT
                a.id,
                a.nome,
                a.email,
                a.whatsapp,
                a.cidade,
                a.referencia as 'Indicado por',
                DATE_FORMAT(a.data_assinatura, '%d/%m/%Y %H:%i') as 'Data Assinatura',
                p.titulo as 'Petição'
            FROM assinaturas_peticoes a
            JOIN peticoes p ON a.peticao_id = p.id
            WHERE a.peticao_id = ?
            ORDER BY a.data_assinatura DESC
        ");
        $stmt->execute([$peticao_id]);
        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("SELECT slug FROM peticoes WHERE id = ?");
        $stmt->execute([$peticao_id]);
        $peticao = $stmt->fetch();

        $nome_arquivo = 'assinaturas_' . ($peticao['slug'] ?? 'peticao') . '_' . date('Y-m-d');

        if ($formato === 'csv') {
            $this->gerarCSV($dados, $nome_arquivo);
        } else {
            $this->gerarExcel($dados, $nome_arquivo);
        }
    }

    /**
     * Exporta todos os contatos únicos (CRM)
     */
    public function exportarContatosUnicos($formato = 'csv') {
        $stmt = $this->pdo->query("
            SELECT
                whatsapp,
                MAX(nome) as nome,
                MAX(email) as email,
                MAX(cidade) as cidade,
                COUNT(*) as 'Total de Ações',
                MIN(primeira_acao) as 'Primeira Interação',
                MAX(ultima_acao) as 'Última Interação',
                GROUP_CONCAT(DISTINCT tipo_acao) as 'Tipos de Ação'
            FROM (
                SELECT
                    whatsapp,
                    nome,
                    email,
                    cidade,
                    'Evento' as tipo_acao,
                    data_inscricao as primeira_acao,
                    data_inscricao as ultima_acao
                FROM inscricoes_eventos
                UNION ALL
                SELECT
                    whatsapp,
                    nome,
                    email,
                    cidade,
                    'Petição' as tipo_acao,
                    data_assinatura as primeira_acao,
                    data_assinatura as ultima_acao
                FROM assinaturas_peticoes
            ) as todos_contatos
            GROUP BY whatsapp
            ORDER BY MAX(ultima_acao) DESC
        ");

        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nome_arquivo = 'contatos_unicos_' . date('Y-m-d');

        if ($formato === 'csv') {
            $this->gerarCSV($dados, $nome_arquivo);
        } else {
            $this->gerarExcel($dados, $nome_arquivo);
        }
    }

    /**
     * Exporta relatório de mobilizadores
     */
    public function exportarRelatorioMobilizadores($formato = 'csv') {
        $stmt = $this->pdo->query("
            SELECT
                referencia as 'Mobilizador',
                COUNT(*) as 'Total Mobilizações',
                COUNT(DISTINCT cidade) as 'Cidades Alcançadas',
                COUNT(DISTINCT CASE WHEN tipo = 'evento' THEN whatsapp END) as 'Inscrições em Eventos',
                COUNT(DISTINCT CASE WHEN tipo = 'peticao' THEN whatsapp END) as 'Assinaturas em Petições',
                DATE_FORMAT(MIN(data_acao), '%d/%m/%Y') as 'Primeira Mobilização',
                DATE_FORMAT(MAX(data_acao), '%d/%m/%Y') as 'Última Mobilização'
            FROM (
                SELECT referencia, whatsapp, cidade, 'evento' as tipo, data_inscricao as data_acao
                FROM inscricoes_eventos
                WHERE referencia IS NOT NULL AND referencia != ''
                UNION ALL
                SELECT referencia, whatsapp, cidade, 'peticao' as tipo, data_assinatura as data_acao
                FROM assinaturas_peticoes
                WHERE referencia IS NOT NULL AND referencia != ''
            ) as mobilizacoes
            GROUP BY referencia
            ORDER BY COUNT(*) DESC
        ");

        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nome_arquivo = 'relatorio_mobilizadores_' . date('Y-m-d');

        if ($formato === 'csv') {
            $this->gerarCSV($dados, $nome_arquivo);
        } else {
            $this->gerarExcel($dados, $nome_arquivo);
        }
    }

    /**
     * Gera arquivo CSV
     */
    private function gerarCSV($dados, $nome_arquivo) {
        if (empty($dados)) {
            die('Nenhum dado para exportar');
        }

        // Headers para download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM para UTF-8 (para Excel abrir corretamente)
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');

        // Cabeçalhos
        fputcsv($output, array_keys($dados[0]), ';');

        // Dados
        foreach ($dados as $row) {
            // Converte check-in para texto legível
            if (isset($row['Check-in Realizado'])) {
                $row['Check-in Realizado'] = $row['Check-in Realizado'] ? 'Sim' : 'Não';
            }
            fputcsv($output, $row, ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Gera arquivo Excel simples (HTML table que Excel entende)
     */
    private function gerarExcel($dados, $nome_arquivo) {
        if (empty($dados)) {
            die('Nenhum dado para exportar');
        }

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nome_arquivo . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // BOM UTF-8

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';

        // Cabeçalhos
        echo '<tr>';
        foreach (array_keys($dados[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';

        // Dados
        foreach ($dados as $row) {
            echo '<tr>';
            foreach ($row as $key => $cell) {
                // Converte check-in para texto legível
                if ($key === 'Check-in Realizado') {
                    $cell = $cell ? 'Sim' : 'Não';
                }
                echo '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            echo '</tr>';
        }

        echo '</table>';
        echo '</body></html>';
        exit;
    }

    /**
     * Exporta relatório completo com estatísticas
     */
    public function exportarRelatorioCompleto($formato = 'csv') {
        $stmt = $this->pdo->query("
            SELECT
                DATE_FORMAT(data_acao, '%d/%m/%Y') as 'Data',
                tipo as 'Tipo',
                COUNT(*) as 'Quantidade',
                COUNT(DISTINCT cidade) as 'Cidades',
                COUNT(DISTINCT referencia) as 'Mobilizadores Ativos'
            FROM (
                SELECT data_inscricao as data_acao, 'Inscrição Evento' as tipo, cidade, referencia
                FROM inscricoes_eventos
                UNION ALL
                SELECT data_assinatura as data_acao, 'Assinatura Petição' as tipo, cidade, referencia
                FROM assinaturas_peticoes
            ) as todas_acoes
            WHERE data_acao >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY DATE(data_acao), tipo
            ORDER BY data_acao DESC
        ");

        $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nome_arquivo = 'relatorio_completo_' . date('Y-m-d');

        if ($formato === 'csv') {
            $this->gerarCSV($dados, $nome_arquivo);
        } else {
            $this->gerarExcel($dados, $nome_arquivo);
        }
    }
}

// Uso da API de exportação
if (isset($_GET['exportar'])) {
    // Verificar login
    require_once 'config.php';
    verificar_autenticacao();

    $export = new MobilizaExport();
    $tipo = $_GET['exportar'] ?? '';
    $formato = $_GET['formato'] ?? 'csv';

    try {
        switch ($tipo) {
            case 'evento':
                $evento_id = $_GET['id'] ?? 0;
                $export->exportarInscricoesEvento($evento_id, $formato);
                break;

            case 'peticao':
                $peticao_id = $_GET['id'] ?? 0;
                $export->exportarAssinaturasPeticao($peticao_id, $formato);
                break;

            case 'contatos':
                $export->exportarContatosUnicos($formato);
                break;

            case 'mobilizadores':
                $export->exportarRelatorioMobilizadores($formato);
                break;

            case 'completo':
                $export->exportarRelatorioCompleto($formato);
                break;

            default:
                die('Tipo de exportação inválido');
        }
    } catch (Exception $e) {
        die('Erro na exportação: ' . $e->getMessage());
    }
}
?>
