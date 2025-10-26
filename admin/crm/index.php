<?php
/**
 * MOBILIZA+ CRM - Dashboard Principal
 */

$titulo_pagina = 'CRM - Dashboard';
require_once '../header.php';
require_once '../../crm_core.php';

$crm = new MobilizaCRM();

// Verificar se as tabelas existem
try {
    $stats = $crm->estatisticasGerais();
    $ranking = $crm->rankingAtendentes(30);
} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<h4>⚠️ CRM não instalado</h4>';
    echo '<p>Execute o instalador do CRM primeiro: <a href="../../crm_install.php" class="btn btn-primary">Instalar CRM</a></p>';
    echo '</div>';
    require_once '../footer.php';
    exit;
}

// Buscar atendente logado
$eh_atendente = false;
$atendente_id = null;
$stmt = $pdo->prepare("SELECT id FROM crm_atendentes WHERE usuario_id = ? AND ativo = 1");
$stmt->execute([$_SESSION['usuario_id']]);
$atendente = $stmt->fetch();
if ($atendente) {
    $eh_atendente = true;
    $atendente_id = $_SESSION['usuario_id'];
}
?>

<style>
.crm-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.stat-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 20px;
}

.stat-box h2 { margin: 0; font-size: 3rem; font-weight: bold; }
.stat-box p { margin: 10px 0 0 0; opacity: 0.9; }

.stat-box.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stat-box.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-box.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stat-box.purple { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }

.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-novo { background: #e3f2fd; color: #1976d2; }
.badge-aguardando { background: #fff3e0; color: #f57c00; }
.badge-contatado { background: #e8f5e9; color: #388e3c; }
.badge-interessado { background: #f3e5f5; color: #7b1fa2; }

.ranking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.ranking-item:last-child { border-bottom: none; }

.ranking-position {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.ranking-position.gold { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
.ranking-position.silver { background: linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%); color: #333; }
.ranking-position.bronze { background: linear-gradient(135deg, #cd7f32 0%, #daa520 100%); }
</style>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📞 CRM - Central de Relacionamento</h1>
        <div>
            <a href="contatos.php" class="btn btn-primary">📋 Ver Todos os Contatos</a>
            <a href="novo_contato.php" class="btn btn-success">➕ Novo Contato</a>
            <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
            <a href="atendentes.php" class="btn btn-info">👥 Gerenciar Atendentes</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estatísticas Principais -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-box">
                <h2><?php echo number_format($stats['total_contatos']); ?></h2>
                <p>Total de Contatos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box green">
                <h2><?php echo number_format($stats['ligacoes_hoje']); ?></h2>
                <p>Ligações Hoje</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box orange">
                <h2><?php echo $stats['taxa_conversao_geral']; ?>%</h2>
                <p>Taxa de Conversão</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box blue">
                <h2><?php echo number_format($stats['tarefas_pendentes']); ?></h2>
                <p>Tarefas Pendentes</p>
            </div>
        </div>
    </div>

    <!-- Distribuição por Status -->
    <div class="row">
        <div class="col-md-6">
            <div class="crm-card">
                <h4 class="mb-3">📊 Contatos por Status</h4>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-right">Quantidade</th>
                            <th class="text-right">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $status_labels = [
                            'novo' => 'Novos',
                            'aguardando_contato' => 'Aguardando Contato',
                            'em_contato' => 'Em Contato',
                            'contatado' => 'Contatados',
                            'interessado' => 'Interessados',
                            'muito_interessado' => 'Muito Interessados',
                            'voluntario' => 'Voluntários',
                            'nao_interessado' => 'Não Interessados',
                            'nao_responde' => 'Não Responde'
                        ];

                        foreach ($status_labels as $status => $label):
                            $total = $stats['por_status'][$status] ?? 0;
                            $percentual = $stats['total_contatos'] > 0 ? round(($total / $stats['total_contatos']) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?php echo $label; ?></td>
                            <td class="text-right"><strong><?php echo number_format($total); ?></strong></td>
                            <td class="text-right"><?php echo $percentual; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-md-6">
            <div class="crm-card">
                <h4 class="mb-3">🏆 Ranking de Atendentes (30 dias)</h4>
                <?php if (empty($ranking)): ?>
                    <p class="text-muted">Nenhum atendente cadastrado ainda.</p>
                    <?php if ($_SESSION['usuario_tipo'] === 'admin'): ?>
                    <a href="atendentes.php" class="btn btn-primary btn-sm">Cadastrar Atendente</a>
                    <?php endif; ?>
                <?php else: ?>
                    <?php foreach ($ranking as $index => $rank): ?>
                    <div class="ranking-item">
                        <div class="d-flex align-items-center">
                            <div class="ranking-position <?php
                                if ($index === 0) echo 'gold';
                                elseif ($index === 1) echo 'silver';
                                elseif ($index === 2) echo 'bronze';
                            ?>">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="ml-3">
                                <strong><?php echo htmlspecialchars($rank['atendente']); ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?php echo $rank['ligacoes_periodo']; ?> ligações |
                                    <?php echo $rank['total_conversoes']; ?> conversões
                                </small>
                            </div>
                        </div>
                        <div class="text-right">
                            <strong class="text-success"><?php echo number_format($rank['taxa_conversao'], 1); ?>%</strong>
                            <br>
                            <small class="text-muted">Taxa de conversão</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row">
        <div class="col-md-4">
            <div class="crm-card">
                <h5>🎯 Contatos Prioritários</h5>
                <p class="text-muted">Contatos de alta prioridade aguardando atendimento</p>
                <a href="contatos.php?prioridade=alta" class="btn btn-danger btn-block">
                    Ver Prioritários
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="crm-card">
                <h5>📞 Fila de Atendimento</h5>
                <p class="text-muted"><?php echo number_format($stats['sem_atendente']); ?> contatos sem atendente</p>
                <a href="contatos.php?sem_atendente=1" class="btn btn-warning btn-block">
                    Atribuir Contatos
                </a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="crm-card">
                <h5>📊 Relatórios</h5>
                <p class="text-muted">Exportar e analisar dados</p>
                <a href="relatorios.php" class="btn btn-info btn-block">
                    Ver Relatórios
                </a>
            </div>
        </div>
    </div>

    <!-- Se for atendente, mostrar suas tarefas -->
    <?php if ($eh_atendente): ?>
    <div class="row">
        <div class="col-12">
            <div class="crm-card">
                <h4 class="mb-3">✅ Minhas Tarefas Pendentes</h4>
                <?php
                $stmt = $pdo->prepare("
                    SELECT t.*, c.nome as contato_nome, c.whatsapp
                    FROM crm_tarefas t
                    JOIN crm_contatos c ON t.contato_id = c.id
                    WHERE t.atendente_id = ? AND t.status != 'concluida'
                    ORDER BY t.data_vencimento ASC
                    LIMIT 10
                ");
                $stmt->execute([$atendente_id]);
                $tarefas = $stmt->fetchAll();

                if (empty($tarefas)): ?>
                    <p class="text-muted">Você não tem tarefas pendentes no momento.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Contato</th>
                                    <th>Tarefa</th>
                                    <th>Vencimento</th>
                                    <th>Prioridade</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tarefas as $tarefa): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tarefa['contato_nome']); ?></strong><br>
                                        <small class="text-muted"><?php echo $tarefa['whatsapp']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($tarefa['titulo']); ?></td>
                                    <td><?php echo formatar_data_br($tarefa['data_vencimento']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php
                                            echo $tarefa['prioridade'] === 'urgente' ? 'danger' :
                                                ($tarefa['prioridade'] === 'alta' ? 'warning' : 'info');
                                        ?>">
                                            <?php echo ucfirst($tarefa['prioridade']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="contato.php?id=<?php echo $tarefa['contato_id']; ?>" class="btn btn-sm btn-primary">
                                            Ver Contato
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estatísticas da Semana -->
    <div class="row">
        <div class="col-12">
            <div class="crm-card">
                <h4 class="mb-3">📈 Estatísticas da Semana</h4>
                <div class="row text-center">
                    <div class="col-md-2">
                        <h3 class="text-primary"><?php echo number_format($stats['ligacoes_semana']); ?></h3>
                        <p class="text-muted">Ligações</p>
                    </div>
                    <div class="col-md-2">
                        <h3 class="text-success"><?php echo round($stats['score_medio']); ?></h3>
                        <p class="text-muted">Score Médio</p>
                    </div>
                    <div class="col-md-2">
                        <h3 class="text-info">
                            <?php
                            $novos_semana = $pdo->query("SELECT COUNT(*) FROM crm_contatos WHERE data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
                            echo number_format($novos_semana);
                            ?>
                        </h3>
                        <p class="text-muted">Novos Contatos</p>
                    </div>
                    <div class="col-md-2">
                        <h3 class="text-warning">
                            <?php
                            $ativos_semana = $pdo->query("SELECT COUNT(*) FROM crm_contatos WHERE ultima_interacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
                            echo number_format($ativos_semana);
                            ?>
                        </h3>
                        <p class="text-muted">Contatos Ativos</p>
                    </div>
                    <div class="col-md-2">
                        <h3 class="text-danger">
                            <?php
                            $agendamentos = $pdo->query("SELECT COUNT(*) FROM crm_ligacoes WHERE agendar_retorno = 1 AND data_retorno >= NOW()")->fetchColumn();
                            echo number_format($agendamentos);
                            ?>
                        </h3>
                        <p class="text-muted">Retornos Agendados</p>
                    </div>
                    <div class="col-md-2">
                        <h3 class="text-secondary">
                            <?php
                            $grupos_total = $pdo->query("SELECT COUNT(*) FROM crm_grupos_whatsapp WHERE ativo = 1")->fetchColumn();
                            echo number_format($grupos_total);
                            ?>
                        </h3>
                        <p class="text-muted">Grupos Ativos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once '../footer.php'; ?>
