<?php
/**
 * MOBILIZA+ - Dashboard Inteligente
 *
 * Dashboard aprimorado com insights automáticos, recomendações
 * e visualizações inteligentes
 */

$titulo_pagina = 'Dashboard Inteligente';
require_once 'header.php';
require_once '../analytics.php';
require_once '../notifications.php';
require_once '../cache.php';

// Inicializar sistemas
$analytics = new MobilizaAnalytics();
$notifications = new MobilizaNotifications();
$notifications->init();

// Verificar e criar alertas automáticos (executa 1x por hora via cache)
cache_remember('alertas_verificados', 3600, function() use ($notifications) {
    $notifications->verificarAlertasAutomaticos();
    return true;
});

// Buscar insights com cache de 30 minutos
$insights = cache_remember('insights_dashboard', 1800, function() use ($analytics) {
    return $analytics->gerarInsightsGerais();
});

try {
    $pdo = conectar_db();

    // Estatísticas gerais (com cache)
    $stats = cache_remember('stats_gerais', 300, function() use ($pdo) {
        $stats = [];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos WHERE ativo = 1");
        $stats['eventos'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_eventos");
        $stats['inscricoes_eventos'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_eventos WHERE checkin = 1");
        $stats['checkins'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM peticoes WHERE ativo = 1");
        $stats['peticoes'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM assinaturas_peticoes");
        $stats['assinaturas'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT whatsapp) as total
            FROM (
                SELECT whatsapp FROM inscricoes_eventos
                UNION
                SELECT whatsapp FROM assinaturas_peticoes
            ) as contatos
        ");
        $stats['contatos_unicos'] = $stmt->fetch()['total'];

        // Taxa de check-in
        $stats['taxa_checkin'] = $stats['inscricoes_eventos'] > 0 ?
            round(($stats['checkins'] / $stats['inscricoes_eventos']) * 100, 1) : 0;

        // Crescimento da última semana
        $stmt = $pdo->query("
            SELECT COUNT(*) as total FROM (
                SELECT data_inscricao as data FROM inscricoes_eventos
                WHERE data_inscricao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                UNION ALL
                SELECT data_assinatura as data FROM assinaturas_peticoes
                WHERE data_assinatura >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ) as semana
        ");
        $stats['crescimento_semana'] = $stmt->fetch()['total'];

        return $stats;
    });

    // Segmentação de contatos
    $segmentos = $analytics->segmentarContatos();

    // Top cidades
    $top_cidades = $analytics->analisarTopCidades(5);

    // Análise de retenção
    $retencao = $analytics->analisarRetencao();

    // Fontes de tráfego
    $fontes_trafego = $analytics->relatorioFontesTrafego();

    // Eventos e petições recentes
    $stmt = $pdo->query("
        SELECT e.*, COUNT(i.id) as total_inscricoes
        FROM eventos e
        LEFT JOIN inscricoes_eventos i ON e.id = i.evento_id
        WHERE e.ativo = 1
        GROUP BY e.id
        ORDER BY e.data_criacao DESC
        LIMIT 5
    ");
    $eventos_recentes = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT p.*, COUNT(a.id) as total_assinaturas
        FROM peticoes p
        LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id
        WHERE p.ativo = 1
        GROUP BY p.id
        ORDER BY p.data_criacao DESC
        LIMIT 5
    ");
    $peticoes_recentes = $stmt->fetchAll();

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erro ao carregar dados: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<style>
.insight-card {
    border-left: 4px solid;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.insight-card.positivo { border-left-color: #28a745; background-color: #f0fdf4; }
.insight-card.atencao { border-left-color: #ffc107; background-color: #fffbeb; }
.insight-card.dica { border-left-color: #17a2b8; background-color: #f0f9ff; }
.insight-card.info { border-left-color: #007bff; background-color: #eff6ff; }
.insight-card.destaque { border-left-color: #9333ea; background-color: #faf5ff; }

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stat-card h3 { font-size: 2.5rem; margin: 0; font-weight: bold; }
.stat-card p { margin: 5px 0 0 0; opacity: 0.9; }

.chart-container {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.segmento-badge {
    display: inline-block;
    padding: 10px 15px;
    margin: 5px;
    border-radius: 20px;
    font-weight: 600;
}

.badge-super { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.badge-novos { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.badge-inativos { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
</style>

<div class="container-fluid py-4">

    <!-- Insights Inteligentes -->
    <?php if (!empty($insights)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-3">🧠 Insights Inteligentes</h2>
            <div class="row">
                <?php foreach ($insights as $insight): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="insight-card <?php echo $insight['tipo']; ?>">
                        <h4><?php echo $insight['icone']; ?> <?php echo htmlspecialchars($insight['titulo']); ?></h4>
                        <p class="mb-2"><?php echo htmlspecialchars($insight['mensagem']); ?></p>
                        <small class="text-muted"><strong>Ação recomendada:</strong> <?php echo htmlspecialchars($insight['acao']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estatísticas Principais -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?php echo number_format($stats['contatos_unicos']); ?></h3>
                <p>Contatos Únicos</p>
                <small>Base total de mobilização</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h3><?php echo number_format($stats['crescimento_semana']); ?></h3>
                <p>Mobilizações (7 dias)</p>
                <small>Crescimento recente</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h3><?php echo $stats['taxa_checkin']; ?>%</h3>
                <p>Taxa de Check-in</p>
                <small>Conversão de presença</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h3><?php echo number_format($stats['eventos'] + $stats['peticoes']); ?></h3>
                <p>Campanhas Ativas</p>
                <small>Eventos e Petições</small>
            </div>
        </div>
    </div>

    <!-- Segmentação de Contatos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h4 class="mb-3">👥 Segmentação Inteligente de Contatos</h4>
                <div class="text-center">
                    <span class="segmento-badge badge-super">
                        ⭐ <?php echo number_format($segmentos['super_engajados'] ?? 0); ?> Super Engajados
                    </span>
                    <span class="segmento-badge badge-novos">
                        🆕 <?php echo number_format($segmentos['novos'] ?? 0); ?> Novos (7 dias)
                    </span>
                    <span class="segmento-badge badge-inativos">
                        😴 <?php echo number_format($segmentos['inativos'] ?? 0); ?> Inativos (30+ dias)
                    </span>
                </div>
                <p class="mt-3 text-muted small">
                    <strong>Super Engajados:</strong> Participaram de 3+ ações<br>
                    <strong>Novos:</strong> Primeira interação nos últimos 7 dias<br>
                    <strong>Inativos:</strong> Sem interação há mais de 30 dias
                </p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="chart-container">
                <h4 class="mb-3">📊 Análise de Retenção</h4>
                <?php if ($retencao): ?>
                <div class="progress mb-2" style="height: 30px;">
                    <div class="progress-bar bg-success" style="width: <?php echo ($retencao['tres_ou_mais'] / array_sum((array)$retencao)) * 100; ?>%">
                        3+ ações: <?php echo $retencao['tres_ou_mais']; ?>
                    </div>
                    <div class="progress-bar bg-info" style="width: <?php echo ($retencao['duas_acoes'] / array_sum((array)$retencao)) * 100; ?>%">
                        2 ações: <?php echo $retencao['duas_acoes']; ?>
                    </div>
                    <div class="progress-bar bg-warning" style="width: <?php echo ($retencao['uma_acao'] / array_sum((array)$retencao)) * 100; ?>%">
                        1 ação: <?php echo $retencao['uma_acao']; ?>
                    </div>
                </div>
                <p class="text-muted small">
                    Quanto mais pessoas com múltiplas ações, maior o engajamento da sua base!
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Cidades e Mobilizadores -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="chart-container">
                <h4 class="mb-3">🏙️ Top 5 Cidades</h4>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Cidade</th><th class="text-right">Mobilizações</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_cidades as $cidade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cidade['cidade']); ?></td>
                            <td class="text-right"><strong><?php echo number_format($cidade['total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <div class="chart-container">
                <h4 class="mb-3">🔗 Principais Fontes de Tráfego</h4>
                <table class="table table-sm">
                    <thead>
                        <tr><th>Fonte/Mobilizador</th><th class="text-right">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $top5_fontes = array_slice($fontes_trafego, 0, 5);
                        foreach ($top5_fontes as $fonte):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fonte['fonte']); ?></td>
                            <td class="text-right"><strong><?php echo number_format($fonte['total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="chart-container">
                <h4 class="mb-3">⚡ Ações Rápidas</h4>
                <div class="btn-group" role="group">
                    <a href="../export.php?exportar=contatos&formato=csv" class="btn btn-primary">
                        📥 Exportar Todos os Contatos (CSV)
                    </a>
                    <a href="../export.php?exportar=mobilizadores&formato=csv" class="btn btn-success">
                        📊 Relatório de Mobilizadores (CSV)
                    </a>
                    <a href="../export.php?exportar=completo&formato=csv" class="btn btn-info">
                        📈 Relatório Completo (CSV)
                    </a>
                    <a href="configuracoes.php" class="btn btn-secondary">
                        ⚙️ Configurações
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Eventos e Petições Recentes -->
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h4 class="mb-3">📅 Eventos Recentes</h4>
                <div class="list-group">
                    <?php foreach ($eventos_recentes as $evento): ?>
                    <a href="eventos/form.php?id=<?php echo $evento['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h6>
                            <small class="badge badge-primary"><?php echo $evento['total_inscricoes']; ?> inscrições</small>
                        </div>
                        <small class="text-muted">
                            <?php echo formatar_data_br($evento['data_evento'] ?? $evento['data_criacao'], false); ?>
                        </small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="chart-container">
                <h4 class="mb-3">✍️ Petições Recentes</h4>
                <div class="list-group">
                    <?php foreach ($peticoes_recentes as $peticao): ?>
                    <a href="apoie/form.php?id=<?php echo $peticao['id']; ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($peticao['titulo']); ?></h6>
                            <small class="badge badge-success"><?php echo $peticao['total_assinaturas']; ?> assinaturas</small>
                        </div>
                        <?php if ($peticao['meta_assinaturas'] > 0): ?>
                        <small class="text-muted">
                            Meta: <?php echo $peticao['meta_assinaturas']; ?>
                            (<?php echo round(($peticao['total_assinaturas'] / $peticao['meta_assinaturas']) * 100); ?>% alcançado)
                        </small>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
// Recarrega insights a cada 5 minutos
setInterval(() => {
    location.reload();
}, 300000);
</script>

<?php require_once 'footer.php'; ?>
