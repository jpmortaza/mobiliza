<?php
// O título da página é definido aqui antes de incluir o header.
$titulo_pagina = 'Dashboard';
// O header.php já inclui o config.php e a verificação de login.
require_once 'header.php';

try {
    $pdo = conectar_db();
    
    // Estatísticas gerais
    $stats = [];
    
    // Total de eventos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos WHERE ativo = 1");
    $stats['eventos'] = $stmt->fetch()['total'];
    
    // Total de inscrições em eventos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_eventos");
    $stats['inscricoes_eventos'] = $stmt->fetch()['total'];
    
    // Total de check-ins realizados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inscricoes_eventos WHERE checkin = 1");
    $stats['checkins'] = $stmt->fetch()['total'];
    
    // Total de petições
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM peticoes WHERE ativo = 1");
    $stats['peticoes'] = $stmt->fetch()['total'];
    
    // Total de assinaturas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assinaturas_peticoes");
    $stats['assinaturas'] = $stmt->fetch()['total'];
    
    // Total de usuários únicos (contatos)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT whatsapp) as total 
        FROM (
            SELECT whatsapp FROM inscricoes_eventos 
            UNION 
            SELECT whatsapp FROM assinaturas_peticoes
        ) as contatos
    ");
    $stats['contatos_unicos'] = $stmt->fetch()['total'];
    
    // Eventos recentes
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COUNT(i.id) as total_inscricoes,
               COUNT(CASE WHEN i.checkin = 1 THEN 1 END) as total_checkins
        FROM eventos e 
        LEFT JOIN inscricoes_eventos i ON e.id = i.evento_id 
        WHERE e.ativo = 1 
        GROUP BY e.id 
        ORDER BY e.data_criacao DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $eventos_recentes = $stmt->fetchAll();
    
    // Petições recentes
    $stmt = $pdo->prepare("
        SELECT p.*, COUNT(a.id) as total_assinaturas
        FROM peticoes p 
        LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id 
        WHERE p.ativo = 1 
        GROUP BY p.id 
        ORDER BY p.data_criacao DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $peticoes_recentes = $stmt->fetchAll();
    
    // Atividades recentes (últimas inscrições e assinaturas)
    $stmt = $pdo->prepare("
        (SELECT 'inscricao' as tipo, nome, cidade, data_inscricao as data_acao, e.titulo as origem
         FROM inscricoes_eventos i 
         JOIN eventos e ON i.evento_id = e.id 
         ORDER BY data_inscricao DESC LIMIT 10)
        UNION ALL
        (SELECT 'assinatura' as tipo, nome, cidade, data_assinatura as data_acao, p.titulo as origem
         FROM assinaturas_peticoes a 
         JOIN peticoes p ON a.peticao_id = p.id 
         ORDER BY data_assinatura DESC LIMIT 10)
        ORDER BY data_acao DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $atividades_recentes = $stmt->fetchAll();
    
    // Dados para gráficos (últimos 30 dias)
    $stmt = $pdo->prepare("
        SELECT DATE(data_inscricao) as data, COUNT(*) as total
        FROM inscricoes_eventos 
        WHERE data_inscricao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(data_inscricao)
        ORDER BY data
    ");
    $stmt->execute();
    $inscricoes_chart = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("
        SELECT DATE(data_assinatura) as data, COUNT(*) as total
        FROM assinaturas_peticoes 
        WHERE data_assinatura >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(data_assinatura)
        ORDER BY data
    ");
    $stmt->execute();
    $assinaturas_chart = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
    $stats = ['eventos' => 0, 'inscricoes_eventos' => 0, 'checkins' => 0, 'peticoes' => 0, 'assinaturas' => 0, 'contatos_unicos' => 0];
    $eventos_recentes = [];
    $peticoes_recentes = [];
    $atividades_recentes = [];
    $inscricoes_chart = [];
    $assinaturas_chart = [];
}
?>

<!-- Estatísticas Principais -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['eventos']); ?></div>
        <div class="stat-label">Eventos Ativos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['inscricoes_eventos']); ?></div>
        <div class="stat-label">Inscrições em Eventos</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['checkins']); ?></div>
        <div class="stat-label">Check-ins Realizados</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['peticoes']); ?></div>
        <div class="stat-label">Petições Ativas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['assinaturas']); ?></div>
        <div class="stat-label">Assinaturas Coletadas</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['contatos_unicos']); ?></div>
        <div class="stat-label">Contatos Únicos</div>
    </div>
</div>

<div class="row">
    <!-- Coluna Esquerda -->
    <div class="col" style="flex: 2;">
        <!-- Gráfico de Atividades -->
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">📊 Atividades dos Últimos 30 Dias</h3>
            </div>
            <div class="card-body">
                <canvas id="atividadesChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- Eventos Recentes -->
        <?php if (!empty($eventos_recentes)): ?>
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">🎯 Eventos Recentes</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Evento</th>
                            <th>Data</th>
                            <th>Inscrições</th>
                            <th>Check-ins</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eventos_recentes as $evento): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                <?php if ($evento['data_evento']): ?>
                                    <br><small class="text-secondary">
                                        <?php echo formatar_data_br($evento['data_evento']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatar_data_br($evento['data_criacao'], false); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $evento['total_inscricoes']; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-success"><?php echo $evento['total_checkins']; ?></span>
                            </td>
                            <td>
                                <a href="eventos/form.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../eventos/?slug=<?php echo $evento['slug']; ?>" target="_blank" class="btn btn-sm" title="Ver">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Petições Recentes -->
        <?php if (!empty($peticoes_recentes)): ?>
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">✊ Petições Recentes</h3>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Petição</th>
                            <th>Data</th>
                            <th>Assinaturas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($peticoes_recentes as $peticao): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($peticao['titulo']); ?></strong>
                            </td>
                            <td><?php echo formatar_data_br($peticao['data_criacao'], false); ?></td>
                            <td>
                                <span class="badge badge-primary"><?php echo $peticao['total_assinaturas']; ?></span>
                            </td>
                            <td>
                                <a href="apoie/form.php?id=<?php echo $peticao['id']; ?>" class="btn btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../apoie/?slug=<?php echo $peticao['slug']; ?>" target="_blank" class="btn btn-sm" title="Ver">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Coluna Direita -->
    <div class="col" style="flex: 1;">
        <!-- Ações Rápidas -->
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">⚡ Ações Rápidas</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    <a href="eventos/form.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Criar Evento
                    </a>
                    <a href="apoie/form.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Criar Petição
                    </a>
                    <a href="usuarios/form.php" class="btn btn-info">
                        <i class="fas fa-user-plus"></i> Adicionar Usuário
                    </a>
                    <a href="crm/" class="btn btn-success">
                        <i class="fas fa-users"></i> Ver CRM
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Atividades Recentes -->
        <?php if (!empty($atividades_recentes)): ?>
        <div class="card">
            <div class="card-header">
                <h3 style="margin: 0;">🔄 Atividade Recente</h3>
            </div>
            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($atividades_recentes as $atividade): ?>
                <div class="d-flex align-items-center gap-3" style="padding: 10px 0; border-bottom: 1px solid #eee;">
                    <div style="flex-shrink: 0;">
                        <?php if ($atividade['tipo'] === 'inscricao'): ?>
                            <i class="fas fa-calendar-check text-primary"></i>
                        <?php else: ?>
                            <i class="fas fa-hand-holding-heart text-secondary"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 500; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($atividade['nome']); ?>
                        </div>
                        <div style="font-size: 0.8rem; color: #666;">
                            <?php echo $atividade['tipo'] === 'inscricao' ? 'Inscreveu-se em' : 'Assinou'; ?>: 
                            <?php echo htmlspecialchars($atividade['origem']); ?>
                        </div>
                        <div style="font-size: 0.75rem; color: #999;">
                            <?php echo formatar_data_br($atividade['data_acao']); ?> • 
                            <?php echo htmlspecialchars($atividade['cidade']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dados para o gráfico
const inscricoesData = <?php echo json_encode($inscricoes_chart); ?>;
const assinaturasData = <?php echo json_encode($assinaturas_chart); ?>;

// Preparar dados para o Chart.js
const labels = [];
const inscricoesValues = [];
const assinaturasValues = [];

// Últimos 30 dias
for (let i = 29; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    const dateStr = date.toISOString().split('T')[0];
    labels.push(date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
    
    const inscricao = inscricoesData.find(item => item.data === dateStr);
    const assinatura = assinaturasData.find(item => item.data === dateStr);
    
    inscricoesValues.push(inscricao ? parseInt(inscricao.total) : 0);
    assinaturasValues.push(assinatura ? parseInt(assinatura.total) : 0);
}

// Criar gráfico
const ctx = document.getElementById('atividadesChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Inscrições em Eventos',
            data: inscricoesValues,
            borderColor: '#147b0b',
            backgroundColor: 'rgba(20, 123, 11, 0.1)',
            tension: 0.4
        }, {
            label: 'Assinaturas de Petições',
            data: assinaturasValues,
            borderColor: '#2ca58d',
            backgroundColor: 'rgba(44, 165, 141, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Auto-refresh das estatísticas a cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>

<?php require_once 'footer.php'; ?>
