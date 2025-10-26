<?php
/**
 * MOBILIZA+ CRM - Lista de Contatos
 */

$titulo_pagina = 'CRM - Contatos';
require_once '../header.php';
require_once '../../crm_core.php';

$crm = new MobilizaCRM();

// Filtros
$filtros = [];
if (!empty($_GET['busca'])) $filtros['busca'] = $_GET['busca'];
if (!empty($_GET['status'])) $filtros['status'] = $_GET['status'];
if (!empty($_GET['cidade'])) $filtros['cidade'] = $_GET['cidade'];
if (!empty($_GET['prioridade'])) $filtros['prioridade'] = $_GET['prioridade'];
if (!empty($_GET['atendente_id'])) $filtros['atendente_id'] = $_GET['atendente_id'];
if (!empty($_GET['sem_atendente'])) $filtros['sem_atendente'] = true;
if (isset($_GET['score_min'])) $filtros['score_min'] = $_GET['score_min'];

// Paginação
$pagina = $_GET['pagina'] ?? 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

$contatos = $crm->buscarContatos($filtros, $limite, $offset);

// Buscar atendentes para filtro
$atendentes = $pdo->query("
    SELECT u.id, u.nome
    FROM crm_atendentes a
    JOIN usuarios_sistema u ON a.usuario_id = u.id
    WHERE a.ativo = 1
    ORDER BY u.nome
")->fetchAll();

// Buscar cidades para filtro
$cidades = $pdo->query("
    SELECT DISTINCT cidade FROM crm_contatos
    WHERE cidade IS NOT NULL AND cidade != ''
    ORDER BY cidade
")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
.filters-panel {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.contact-row {
    background: white;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    border-left: 4px solid #ddd;
    transition: all 0.3s;
}

.contact-row:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.contact-row.prioridade-urgente { border-left-color: #dc3545; }
.contact-row.prioridade-alta { border-left-color: #ffc107; }
.contact-row.prioridade-media { border-left-color: #17a2b8; }
.contact-row.prioridade-baixa { border-left-color: #6c757d; }

.score-badge {
    display: inline-block;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.score-badge.high { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.score-badge.medium { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.score-badge.low { background: linear-gradient(135deg, #bdc3c7 0%, #95a5a6 100%); }

.quick-actions {
    display: flex;
    gap: 5px;
}
</style>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📋 Contatos do CRM</h1>
        <div>
            <a href="novo_contato.php" class="btn btn-success">➕ Novo Contato</a>
            <a href="sincronizar.php" class="btn btn-info">🔄 Sincronizar</a>
            <a href="../../export.php?exportar=crm_contatos&formato=csv" class="btn btn-primary">📥 Exportar CSV</a>
        </div>
    </div>

    <!-- Painel de Filtros -->
    <div class="filters-panel">
        <form method="GET" class="form-inline">
            <div class="form-row w-100">
                <div class="col-md-3 mb-2">
                    <input type="text" name="busca" class="form-control w-100"
                           placeholder="Buscar nome, WhatsApp, email..."
                           value="<?php echo htmlspecialchars($filtros['busca'] ?? ''); ?>">
                </div>

                <div class="col-md-2 mb-2">
                    <select name="status" class="form-control w-100">
                        <option value="">Todos os status</option>
                        <option value="novo" <?php echo ($filtros['status'] ?? '') === 'novo' ? 'selected' : ''; ?>>Novos</option>
                        <option value="aguardando_contato" <?php echo ($filtros['status'] ?? '') === 'aguardando_contato' ? 'selected' : ''; ?>>Aguardando Contato</option>
                        <option value="em_contato" <?php echo ($filtros['status'] ?? '') === 'em_contato' ? 'selected' : ''; ?>>Em Contato</option>
                        <option value="contatado" <?php echo ($filtros['status'] ?? '') === 'contatado' ? 'selected' : ''; ?>>Contatados</option>
                        <option value="interessado" <?php echo ($filtros['status'] ?? '') === 'interessado' ? 'selected' : ''; ?>>Interessados</option>
                        <option value="voluntario" <?php echo ($filtros['status'] ?? '') === 'voluntario' ? 'selected' : ''; ?>>Voluntários</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="prioridade" class="form-control w-100">
                        <option value="">Todas prioridades</option>
                        <option value="urgente" <?php echo ($filtros['prioridade'] ?? '') === 'urgente' ? 'selected' : ''; ?>>Urgente</option>
                        <option value="alta" <?php echo ($filtros['prioridade'] ?? '') === 'alta' ? 'selected' : ''; ?>>Alta</option>
                        <option value="media" <?php echo ($filtros['prioridade'] ?? '') === 'media' ? 'selected' : ''; ?>>Média</option>
                        <option value="baixa" <?php echo ($filtros['prioridade'] ?? '') === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="atendente_id" class="form-control w-100">
                        <option value="">Todos atendentes</option>
                        <?php foreach ($atendentes as $atendente): ?>
                        <option value="<?php echo $atendente['id']; ?>"
                                <?php echo ($filtros['atendente_id'] ?? 0) == $atendente['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($atendente['nome']); ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="0" <?php echo isset($filtros['sem_atendente']) ? 'selected' : ''; ?>>Sem Atendente</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="cidade" class="form-control w-100">
                        <option value="">Todas cidades</option>
                        <?php foreach ($cidades as $cidade): ?>
                        <option value="<?php echo htmlspecialchars($cidade); ?>"
                                <?php echo ($filtros['cidade'] ?? '') === $cidade ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cidade); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1 mb-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    <div class="mb-3">
        <strong><?php echo count($contatos); ?></strong> contato(s) encontrado(s)
    </div>

    <?php if (empty($contatos)): ?>
        <div class="alert alert-info">
            Nenhum contato encontrado com os filtros aplicados.
        </div>
    <?php else: ?>
        <?php foreach ($contatos as $contato): ?>
        <div class="contact-row prioridade-<?php echo $contato['prioridade']; ?>">
            <div class="row align-items-center">

                <!-- Score -->
                <div class="col-md-1 text-center">
                    <div class="score-badge <?php
                        echo $contato['score_engajamento'] >= 70 ? 'high' :
                            ($contato['score_engajamento'] >= 40 ? 'medium' : 'low');
                    ?>">
                        <?php echo $contato['score_engajamento']; ?>
                    </div>
                    <small class="text-muted d-block mt-1">Score</small>
                </div>

                <!-- Dados do Contato -->
                <div class="col-md-4">
                    <h5 class="mb-1">
                        <a href="contato.php?id=<?php echo $contato['id']; ?>">
                            <?php echo htmlspecialchars($contato['nome']); ?>
                        </a>
                    </h5>
                    <small class="text-muted">
                        📞 <?php echo $contato['whatsapp']; ?>
                        <?php if ($contato['email']): ?>
                            <br>✉️ <?php echo htmlspecialchars($contato['email']); ?>
                        <?php endif; ?>
                        <br>📍 <?php echo htmlspecialchars($contato['cidade']); ?>
                    </small>
                </div>

                <!-- Estatísticas -->
                <div class="col-md-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <strong class="text-primary"><?php echo $contato['total_eventos']; ?></strong>
                            <br><small class="text-muted">Eventos</small>
                        </div>
                        <div class="col-4">
                            <strong class="text-success"><?php echo $contato['total_peticoes']; ?></strong>
                            <br><small class="text-muted">Petições</small>
                        </div>
                        <div class="col-4">
                            <strong class="text-info"><?php echo $contato['total_grupos']; ?></strong>
                            <br><small class="text-muted">Grupos</small>
                        </div>
                    </div>
                </div>

                <!-- Status e Atendente -->
                <div class="col-md-2">
                    <span class="badge badge-<?php
                        $badge_colors = [
                            'novo' => 'primary',
                            'aguardando_contato' => 'warning',
                            'contatado' => 'success',
                            'interessado' => 'info',
                            'voluntario' => 'success'
                        ];
                        echo $badge_colors[$contato['status_contato']] ?? 'secondary';
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $contato['status_contato'])); ?>
                    </span>

                    <?php if ($contato['atendente_nome']): ?>
                        <br><small class="text-muted">👤 <?php echo htmlspecialchars($contato['atendente_nome']); ?></small>
                    <?php else: ?>
                        <br><small class="text-danger">Sem atendente</small>
                    <?php endif; ?>

                    <?php if ($contato['total_ligacoes'] > 0): ?>
                        <br><small class="text-muted">📞 <?php echo $contato['total_ligacoes']; ?> ligação(ões)</small>
                    <?php endif; ?>
                </div>

                <!-- Ações -->
                <div class="col-md-2">
                    <div class="quick-actions">
                        <a href="contato.php?id=<?php echo $contato['id']; ?>"
                           class="btn btn-sm btn-primary"
                           title="Ver detalhes">
                            👁️
                        </a>
                        <a href="registrar_ligacao.php?contato_id=<?php echo $contato['id']; ?>"
                           class="btn btn-sm btn-success"
                           title="Registrar ligação">
                            📞
                        </a>
                        <a href="editar_contato.php?id=<?php echo $contato['id']; ?>"
                           class="btn btn-sm btn-info"
                           title="Editar">
                            ✏️
                        </a>
                    </div>
                    <?php if ($contato['prioridade'] === 'urgente'): ?>
                        <small class="text-danger d-block mt-1"><strong>⚠️ URGENTE</strong></small>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>

        <!-- Paginação -->
        <?php if (count($contatos) >= $limite): ?>
        <div class="text-center mt-4">
            <a href="?pagina=<?php echo $pagina + 1; ?>&<?php echo http_build_query($filtros); ?>"
               class="btn btn-primary">
                Carregar Mais
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once '../footer.php'; ?>
