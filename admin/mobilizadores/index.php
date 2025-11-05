<?php
/**
 * MOBILIZA+ V2 - Gestão de Mobilizadores
 * Sistema completo de mobilizadores com gamificação
 */

require_once '../../config.php';
verificar_autenticacao();

$pdo = conectar_db();
$titulo_pagina = "Mobilizadores";

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_cidade = $_GET['cidade'] ?? '';
$filtro_nivel = $_GET['nivel'] ?? '';
$busca = $_GET['busca'] ?? '';
$ordenar = $_GET['ordenar'] ?? 'pontos'; // pontos, nivel, indicacoes

// Paginação
$pagina_atual = $_GET['pagina'] ?? 1;
$registros_por_pagina = 20;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Montar query
$where = ['1=1'];
$params = [];

if ($filtro_status) {
    $where[] = "m.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_cidade) {
    $where[] = "p.cidade = ?";
    $params[] = $filtro_cidade;
}

if ($filtro_nivel) {
    $where[] = "m.nivel = ?";
    $params[] = $filtro_nivel;
}

if ($busca) {
    $where[] = "(p.nome LIKE ? OR m.codigo_referencia LIKE ? OR p.whatsapp LIKE ?)";
    $busca_param = "%{$busca}%";
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
}

$where_sql = implode(' AND ', $where);

// Ordenação
$order_by = match($ordenar) {
    'nivel' => 'm.nivel DESC, m.pontos DESC',
    'indicacoes' => 'm.total_indicacoes_diretas DESC',
    'assinaturas' => 'm.total_assinaturas_geradas DESC',
    'eventos' => 'm.total_inscricoes_eventos_geradas DESC',
    default => 'm.pontos DESC'
};

// Contar total
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM mobilizadores m
    JOIN pessoas p ON m.pessoa_id = p.id
    WHERE {$where_sql}
");
$stmt->execute($params);
$total_registros = $stmt->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar mobilizadores
$sql = "
    SELECT
        m.*,
        p.nome, p.whatsapp, p.email, p.cidade, p.estado,
        gm.nome as grupo_nome,
        coord.codigo_referencia as coordenador_codigo,
        p_coord.nome as coordenador_nome
    FROM mobilizadores m
    JOIN pessoas p ON m.pessoa_id = p.id
    LEFT JOIN grupos_mobilizadores gm ON m.grupo_mobilizador_id = gm.id
    LEFT JOIN mobilizadores coord ON m.coordenador_id = coord.id
    LEFT JOIN pessoas p_coord ON coord.pessoa_id = p_coord.id
    WHERE {$where_sql}
    ORDER BY {$order_by}
    LIMIT ? OFFSET ?
";

$params[] = $registros_por_pagina;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$mobilizadores = $stmt->fetchAll();

// Estatísticas gerais
$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'aprovado' THEN 1 ELSE 0 END) as aprovados,
        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
        SUM(CASE WHEN status = 'suspenso' THEN 1 ELSE 0 END) as suspensos,
        SUM(total_indicacoes_diretas) as total_indicacoes,
        SUM(total_assinaturas_geradas) as total_assinaturas,
        SUM(pontos) as total_pontos,
        AVG(nivel) as nivel_medio
    FROM mobilizadores
");
$stats = $stmt->fetch();

// Top 10 mobilizadores
$stmt = $pdo->query("
    SELECT m.*, p.nome, p.cidade
    FROM mobilizadores m
    JOIN pessoas p ON m.pessoa_id = p.id
    WHERE m.status = 'aprovado'
    ORDER BY m.pontos DESC
    LIMIT 10
");
$top_mobilizadores = $stmt->fetchAll();

// Buscar cidades
$stmt = $pdo->query("
    SELECT DISTINCT p.cidade
    FROM mobilizadores m
    JOIN pessoas p ON m.pessoa_id = p.id
    WHERE p.cidade IS NOT NULL
    ORDER BY p.cidade
");
$cidades = $stmt->fetchAll(PDO::FETCH_COLUMN);

include '../header.php';
?>

<style>
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.stats-card h3 {
    font-size: 2em;
    margin: 0;
}
.stats-card p {
    margin: 5px 0 0 0;
    opacity: 0.9;
}
.nivel-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #333;
    font-weight: bold;
    font-size: 1.2em;
    box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
}
.nivel-1 { background: linear-gradient(135deg, #cd7f32 0%, #b86f29 100%); color: white; }
.nivel-2 { background: linear-gradient(135deg, #c0c0c0 0%, #a8a8a8 100%); color: white; }
.nivel-3 { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); }
.nivel-4 { background: linear-gradient(135deg, #00d4ff 0%, #0099ff 100%); color: white; }
.nivel-5 { background: linear-gradient(135deg, #9d00ff 0%, #7a00cc 100%); color: white; }
.nivel-6 { background: linear-gradient(135deg, #ff0080 0%, #cc0066 100%); color: white; }
.nivel-7 { background: linear-gradient(135deg, #ff4500 0%, #cc3700 100%); color: white; }
.nivel-8 { background: linear-gradient(135deg, #00ff00 0%, #00cc00 100%); }
.nivel-9 { background: linear-gradient(135deg, #ff1493 0%, #cc1177 100%); color: white; }
.nivel-10 { background: linear-gradient(135deg, #ffd700 0%, #ff8c00 100%); box-shadow: 0 0 20px rgba(255, 215, 0, 0.8); }

.badge-status {
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 600;
}
.badge-aprovado { background: #d4edda; color: #155724; }
.badge-pendente { background: #fff3cd; color: #856404; }
.badge-suspenso { background: #f8d7da; color: #721c24; }

.ranking-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.ranking-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}
.ranking-item:last-child {
    border-bottom: none;
}
.ranking-position {
    font-size: 1.5em;
    font-weight: bold;
    width: 40px;
    text-align: center;
}
.ranking-position.top3 {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.stats-mini {
    display: flex;
    gap: 15px;
    font-size: 0.85em;
}
.stats-mini div {
    text-align: center;
}
.stats-mini strong {
    display: block;
    font-size: 1.3em;
    color: #667eea;
}
</style>

<div class="container-fluid" style="padding: 30px;">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-users-cog"></i> <?php echo $titulo_pagina; ?></h1>
            <p class="text-muted">Gerencie mobilizadores e acompanhe desempenho</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" onclick="novoMobilizador()">
                <i class="fas fa-plus"></i> Novo Mobilizador
            </button>
            <button class="btn btn-success" onclick="exportarRanking()">
                <i class="fas fa-trophy"></i> Exportar Ranking
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Esquerda: Lista -->
        <div class="col-md-9">
            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h3><?php echo number_format($stats['total'], 0, ',', '.'); ?></h3>
                        <p>Total</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <h3><?php echo number_format($stats['aprovados'], 0, ',', '.'); ?></h3>
                        <p>Aprovados</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                        <h3><?php echo number_format($stats['pendentes'], 0, ',', '.'); ?></h3>
                        <p>Pendentes</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                        <h3><?php echo number_format($stats['total_indicacoes'], 0, ',', '.'); ?></h3>
                        <p>Indicações</p>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="busca"
                               value="<?php echo htmlspecialchars($busca); ?>"
                               placeholder="Buscar...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="status">
                            <option value="">Todos Status</option>
                            <option value="aprovado" <?php echo $filtro_status == 'aprovado' ? 'selected' : ''; ?>>Aprovados</option>
                            <option value="pendente" <?php echo $filtro_status == 'pendente' ? 'selected' : ''; ?>>Pendentes</option>
                            <option value="suspenso" <?php echo $filtro_status == 'suspenso' ? 'selected' : ''; ?>>Suspensos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="nivel">
                            <option value="">Todos Níveis</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filtro_nivel == $i ? 'selected' : ''; ?>>
                                    Nível <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="cidade">
                            <option value="">Todas Cidades</option>
                            <?php foreach ($cidades as $cidade): ?>
                                <option value="<?php echo $cidade; ?>" <?php echo $filtro_cidade == $cidade ? 'selected' : ''; ?>>
                                    <?php echo $cidade; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="ordenar">
                            <option value="pontos" <?php echo $ordenar == 'pontos' ? 'selected' : ''; ?>>Ordenar por Pontos</option>
                            <option value="nivel" <?php echo $ordenar == 'nivel' ? 'selected' : ''; ?>>Ordenar por Nível</option>
                            <option value="indicacoes" <?php echo $ordenar == 'indicacoes' ? 'selected' : ''; ?>>Ordenar por Indicações</option>
                            <option value="assinaturas" <?php echo $ordenar == 'assinaturas' ? 'selected' : ''; ?>>Ordenar por Assinaturas</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div style="background: white; border-radius: 10px; overflow: hidden;">
                <table class="table table-hover mb-0">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th>Mobilizador</th>
                            <th>Código</th>
                            <th>Nível</th>
                            <th>Pontos</th>
                            <th>Indicações</th>
                            <th>Assinaturas</th>
                            <th>Eventos</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mobilizadores)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-users-cog fa-3x text-muted mb-3"></i>
                                    <p>Nenhum mobilizador encontrado</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mobilizadores as $mob): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mob['nome']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($mob['cidade'] ?? '-'); ?> |
                                            <?php echo htmlspecialchars($mob['whatsapp']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: #e7e7ff; color: #4f46e5; font-family: monospace;">
                                            <?php echo $mob['codigo_referencia']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="nivel-badge nivel-<?php echo $mob['nivel']; ?>">
                                            <?php echo $mob['nivel']; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo number_format($mob['pontos'], 0, ',', '.'); ?></strong></td>
                                    <td><?php echo $mob['total_indicacoes_diretas']; ?></td>
                                    <td><?php echo $mob['total_assinaturas_geradas']; ?></td>
                                    <td><?php echo $mob['total_inscricoes_eventos_geradas']; ?></td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $mob['status']; ?>">
                                            <?php echo ucfirst($mob['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="verDetalhes(<?php echo $mob['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($mob['status'] == 'pendente'): ?>
                                            <button class="btn btn-sm btn-success" onclick="aprovar(<?php echo $mob['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
                <div style="display: flex; justify-content: center; gap: 10px; margin-top: 20px;">
                    <?php if ($pagina_atual > 1): ?>
                        <a href="?pagina=<?php echo $pagina_atual - 1; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>"
                           class="btn btn-secondary">&laquo; Anterior</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagina_atual - 2); $i <= min($total_paginas, $pagina_atual + 2); $i++): ?>
                        <a href="?pagina=<?php echo $i; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>"
                           class="btn <?php echo $i == $pagina_atual ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagina_atual < $total_paginas): ?>
                        <a href="?pagina=<?php echo $pagina_atual + 1; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>"
                           class="btn btn-secondary">Próxima &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Direita: Ranking -->
        <div class="col-md-3">
            <div class="ranking-card">
                <h5><i class="fas fa-trophy"></i> Top 10 Mobilizadores</h5>
                <hr>
                <?php foreach ($top_mobilizadores as $index => $top): ?>
                    <div class="ranking-item">
                        <div class="ranking-position <?php echo $index < 3 ? 'top3' : ''; ?>">
                            #<?php echo $index + 1; ?>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <strong><?php echo htmlspecialchars($top['nome']); ?></strong><br>
                            <small class="text-muted"><?php echo $top['cidade'] ?? '-'; ?></small>
                            <div class="stats-mini mt-1">
                                <div>
                                    <strong><?php echo $top['pontos']; ?></strong>
                                    <span>pts</span>
                                </div>
                                <div>
                                    <strong><?php echo $top['total_indicacoes_diretas']; ?></strong>
                                    <span>ind</span>
                                </div>
                            </div>
                        </div>
                        <div class="nivel-badge nivel-<?php echo $top['nivel']; ?>">
                            <?php echo $top['nivel']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Legenda de Níveis -->
            <div class="ranking-card">
                <h6><i class="fas fa-info-circle"></i> Níveis</h6>
                <hr>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="nivel-badge nivel-<?php echo $i; ?>" style="width: 30px; height: 30px; font-size: 0.9em;">
                                <?php echo $i; ?>
                            </div>
                            <small>
                                <?php
                                $ranges = [
                                    1 => '0-99',
                                    2 => '100-499',
                                    3 => '500-999',
                                    4 => '1k-2.5k',
                                    5 => '2.5k-5k',
                                    6 => '5k-7.5k',
                                    7 => '7.5k-10k',
                                    8 => '10k-15k',
                                    9 => '15k-25k',
                                    10 => '25k+'
                                ];
                                echo $ranges[$i];
                                ?>
                            </small>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function verDetalhes(id) {
    window.location.href = 'detalhes.php?id=' + id;
}

function novoMobilizador() {
    window.location.href = 'form.php';
}

function aprovar(id) {
    if (confirm('Aprovar este mobilizador?')) {
        fetch('api.php?action=aprovar&id=' + id, { method: 'POST' })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Mobilizador aprovado!');
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            });
    }
}

function exportarRanking() {
    window.location.href = 'exportar_ranking.php';
}
</script>

<?php include '../footer.php'; ?>
