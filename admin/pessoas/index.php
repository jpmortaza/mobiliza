<?php
/**
 * MOBILIZA+ V2 - Gestão de Pessoas (CRM)
 * Lista e gerencia todas as pessoas cadastradas no sistema
 */

require_once '../../config.php';
verificar_autenticacao();

$pdo = conectar_db();
$titulo_pagina = "Gestão de Pessoas";

// Filtros
$filtro_origem = $_GET['origem'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_cidade = $_GET['cidade'] ?? '';
$busca = $_GET['busca'] ?? '';

// Paginação
$pagina_atual = $_GET['pagina'] ?? 1;
$registros_por_pagina = 20;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Montar query
$where = ['1=1'];
$params = [];

if ($filtro_origem) {
    $where[] = "p.origem = ?";
    $params[] = $filtro_origem;
}

if ($filtro_status) {
    $where[] = "p.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_cidade) {
    $where[] = "p.cidade = ?";
    $params[] = $filtro_cidade;
}

if ($busca) {
    $where[] = "(p.nome LIKE ? OR p.whatsapp LIKE ? OR p.email LIKE ? OR p.cpf LIKE ?)";
    $busca_param = "%{$busca}%";
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
    $params[] = $busca_param;
}

$where_sql = implode(' AND ', $where);

// Contar total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pessoas p WHERE {$where_sql}");
$stmt->execute($params);
$total_registros = $stmt->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Buscar pessoas
$sql = "
    SELECT
        p.*,
        m.codigo_referencia,
        m.nivel as mobilizador_nivel,
        m.status as mobilizador_status,
        COUNT(DISTINCT a.id) as total_assinaturas,
        COUNT(DISTINCT ie.id) as total_inscricoes,
        COUNT(DISTINCT at.id) as total_atendimentos
    FROM pessoas p
    LEFT JOIN mobilizadores m ON p.id = m.pessoa_id
    LEFT JOIN assinaturas a ON p.id = a.pessoa_id
    LEFT JOIN inscricoes_eventos ie ON p.id = ie.pessoa_id
    LEFT JOIN atendimentos_crm at ON p.id = at.pessoa_id
    WHERE {$where_sql}
    GROUP BY p.id
    ORDER BY p.data_cadastro DESC
    LIMIT ? OFFSET ?
";

$params[] = $registros_por_pagina;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pessoas = $stmt->fetchAll();

// Buscar estatísticas
$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'ativo' THEN 1 ELSE 0 END) as ativos,
        SUM(CASE WHEN e_mobilizador = 1 THEN 1 ELSE 0 END) as mobilizadores,
        SUM(CASE WHEN e_voluntario = 1 THEN 1 ELSE 0 END) as voluntarios
    FROM pessoas
");
$stats = $stmt->fetch();

// Buscar cidades únicas
$stmt = $pdo->query("SELECT DISTINCT cidade FROM pessoas WHERE cidade IS NOT NULL ORDER BY cidade");
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
.filter-section {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.table-pessoas {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 600;
}
.badge-ativo { background: #d4edda; color: #155724; }
.badge-inativo { background: #f8d7da; color: #721c24; }
.badge-bloqueado { background: #f8d7da; color: #721c24; }
.badge-mobilizador { background: #d1ecf1; color: #0c5460; }
.badge-voluntario { background: #fff3cd; color: #856404; }
.btn-action {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 0.85em;
    margin: 2px;
}
.btn-view { background: #007bff; color: white; }
.btn-edit { background: #28a745; color: white; }
.btn-whatsapp { background: #25d366; color: white; }
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
}
.pagination a {
    padding: 8px 16px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #333;
}
.pagination a.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}
</style>

<div class="container-fluid" style="padding: 30px;">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-users"></i> <?php echo $titulo_pagina; ?></h1>
            <p class="text-muted">Gerencie todas as pessoas cadastradas no sistema</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" onclick="abrirModalNovaPessoa()">
                <i class="fas fa-plus"></i> Nova Pessoa
            </button>
            <button class="btn btn-success" onclick="exportarCSV()">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?php echo number_format($stats['total'], 0, ',', '.'); ?></h3>
                <p>Total de Pessoas</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <h3><?php echo number_format($stats['ativos'], 0, ',', '.'); ?></h3>
                <p>Ativos</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
                <h3><?php echo number_format($stats['mobilizadores'], 0, ',', '.'); ?></h3>
                <p>Mobilizadores</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                <h3><?php echo number_format($stats['voluntarios'], 0, ',', '.'); ?></h3>
                <p>Voluntários</p>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-section">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" name="busca"
                       value="<?php echo htmlspecialchars($busca); ?>"
                       placeholder="Nome, WhatsApp, Email...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Origem</label>
                <select class="form-control" name="origem">
                    <option value="">Todas</option>
                    <option value="formulario_apoio" <?php echo $filtro_origem == 'formulario_apoio' ? 'selected' : ''; ?>>Formulário Apoio</option>
                    <option value="formulario_evento" <?php echo $filtro_origem == 'formulario_evento' ? 'selected' : ''; ?>>Formulário Evento</option>
                    <option value="grupo_whatsapp" <?php echo $filtro_origem == 'grupo_whatsapp' ? 'selected' : ''; ?>>Grupo WhatsApp</option>
                    <option value="crm_manual" <?php echo $filtro_origem == 'crm_manual' ? 'selected' : ''; ?>>CRM Manual</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-control" name="status">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo $filtro_status == 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                    <option value="inativo" <?php echo $filtro_status == 'inativo' ? 'selected' : ''; ?>>Inativo</option>
                    <option value="bloqueado" <?php echo $filtro_status == 'bloqueado' ? 'selected' : ''; ?>>Bloqueado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Cidade</label>
                <select class="form-control" name="cidade">
                    <option value="">Todas</option>
                    <?php foreach ($cidades as $cidade): ?>
                        <option value="<?php echo $cidade; ?>" <?php echo $filtro_cidade == $cidade ? 'selected' : ''; ?>>
                            <?php echo $cidade; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="table-pessoas">
        <table class="table table-hover mb-0">
            <thead style="background: #f8f9fa;">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>WhatsApp</th>
                    <th>Cidade</th>
                    <th>Origem</th>
                    <th>Status</th>
                    <th>Indicações</th>
                    <th>Participação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pessoas)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p>Nenhuma pessoa encontrada</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pessoas as $pessoa): ?>
                        <tr>
                            <td><?php echo $pessoa['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($pessoa['nome']); ?></strong>
                                <?php if ($pessoa['e_mobilizador']): ?>
                                    <span class="badge badge-mobilizador">Mobilizador Nv<?php echo $pessoa['mobilizador_nivel'] ?? 1; ?></span>
                                <?php endif; ?>
                                <?php if ($pessoa['e_voluntario']): ?>
                                    <span class="badge badge-voluntario">Voluntário</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($pessoa['whatsapp']); ?></td>
                            <td><?php echo htmlspecialchars($pessoa['cidade'] ?? '-'); ?></td>
                            <td>
                                <?php
                                $origem_labels = [
                                    'formulario_apoio' => 'Apoio',
                                    'formulario_evento' => 'Evento',
                                    'grupo_whatsapp' => 'WhatsApp',
                                    'crm_manual' => 'CRM'
                                ];
                                echo $origem_labels[$pessoa['origem']] ?? $pessoa['origem'];
                                ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $pessoa['status']; ?>">
                                    <?php echo ucfirst($pessoa['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $pessoa['numero_indicacoes']; ?></td>
                            <td>
                                <small>
                                    <?php echo $pessoa['total_assinaturas']; ?> assinaturas<br>
                                    <?php echo $pessoa['total_inscricoes']; ?> eventos<br>
                                    <?php echo $pessoa['total_atendimentos']; ?> atendimentos
                                </small>
                            </td>
                            <td>
                                <button class="btn-action btn-view" onclick="verDetalhes(<?php echo $pessoa['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" onclick="editarPessoa(<?php echo $pessoa['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-whatsapp"
                                        onclick="abrirWhatsApp('<?php echo $pessoa['whatsapp']; ?>')">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($total_paginas > 1): ?>
        <div class="pagination">
            <?php if ($pagina_atual > 1): ?>
                <a href="?pagina=<?php echo $pagina_atual - 1; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?><?php echo $filtro_origem ? '&origem=' . $filtro_origem : ''; ?>">
                    &laquo; Anterior
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina_atual || $i == 1 || $i == $total_paginas || abs($i - $pagina_atual) <= 2): ?>
                    <a href="?pagina=<?php echo $i; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?><?php echo $filtro_origem ? '&origem=' . $filtro_origem : ''; ?>"
                       class="<?php echo $i == $pagina_atual ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php elseif (abs($i - $pagina_atual) == 3): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagina_atual < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina_atual + 1; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?><?php echo $filtro_origem ? '&origem=' . $filtro_origem : ''; ?>">
                    Próxima &raquo;
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detalhes -->
<div id="modalDetalhes" style="display: none;">
    <!-- Será preenchido via JavaScript -->
</div>

<script>
function verDetalhes(id) {
    window.location.href = 'detalhes.php?id=' + id;
}

function editarPessoa(id) {
    window.location.href = 'form.php?id=' + id;
}

function abrirWhatsApp(numero) {
    const numeroLimpo = numero.replace(/\D/g, '');
    window.open('https://wa.me/55' + numeroLimpo, '_blank');
}

function abrirModalNovaPessoa() {
    window.location.href = 'form.php';
}

function exportarCSV() {
    window.location.href = 'exportar.php?' + window.location.search.substring(1);
}
</script>

<?php include '../footer.php'; ?>
