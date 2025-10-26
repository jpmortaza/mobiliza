<?php
/**
 * MOBILIZA+ CRM - Gestão de Atendentes
 */

$titulo_pagina = 'CRM - Atendentes';
require_once '../header.php';
require_once '../../crm_core.php';

// Apenas admins podem acessar
if ($_SESSION['usuario_tipo'] !== 'admin') {
    echo '<div class="alert alert-danger">Acesso negado. Apenas administradores podem gerenciar atendentes.</div>';
    require_once '../footer.php';
    exit;
}

$crm = new MobilizaCRM();

// Processar cadastro de novo atendente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_atendente'])) {
    $usuario_id = $_POST['usuario_id'];

    // Verificar se já é atendente
    $stmt = $pdo->prepare("SELECT id FROM crm_atendentes WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);

    if ($stmt->fetch()) {
        $msg = '<div class="alert alert-warning">Este usuário já é um atendente.</div>';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO crm_atendentes (usuario_id, meta_ligacoes_dia, meta_conversoes_mes)
            VALUES (?, ?, ?)
        ");

        if ($stmt->execute([$usuario_id, $_POST['meta_ligacoes_dia'], $_POST['meta_conversoes_mes']])) {
            $msg = '<div class="alert alert-success">Atendente cadastrado com sucesso!</div>';
        } else {
            $msg = '<div class="alert alert-danger">Erro ao cadastrar atendente.</div>';
        }
    }
}

// Atualizar status do atendente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $atendente_id = $_POST['atendente_id'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $disponivel = isset($_POST['disponivel']) ? 1 : 0;

    $stmt = $pdo->prepare("
        UPDATE crm_atendentes
        SET ativo = ?, disponivel = ?
        WHERE id = ?
    ");

    if ($stmt->execute([$ativo, $disponivel, $atendente_id])) {
        $msg = '<div class="alert alert-success">Status atualizado com sucesso!</div>';
    }
}

// Buscar todos os atendentes
$atendentes = $pdo->query("
    SELECT a.*, u.nome, u.email,
           (SELECT COUNT(*) FROM crm_contatos WHERE atendente_responsavel_id = u.id) as contatos_ativos
    FROM crm_atendentes a
    JOIN usuarios_sistema u ON a.usuario_id = u.id
    ORDER BY a.ativo DESC, u.nome
")->fetchAll();

// Buscar usuários que podem ser atendentes
$usuarios_disponiveis = $pdo->query("
    SELECT id, nome, email
    FROM usuarios_sistema
    WHERE id NOT IN (SELECT usuario_id FROM crm_atendentes)
    ORDER BY nome
")->fetchAll();
?>

<style>
.atendente-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #28a745;
}

.atendente-card.inativo {
    border-left-color: #dc3545;
    opacity: 0.7;
}

.performance-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-excelente { background: #d4edda; color: #155724; }
.badge-bom { background: #d1ecf1; color: #0c5460; }
.badge-regular { background: #fff3cd; color: #856404; }
.badge-abaixo { background: #f8d7da; color: #721c24; }
</style>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>👥 Gerenciamento de Atendentes</h1>
        <button class="btn btn-primary" data-toggle="modal" data-target="#modalNovoAtendente">
            ➕ Cadastrar Atendente
        </button>
    </div>

    <?php if (isset($msg)) echo $msg; ?>

    <!-- Estatísticas Gerais -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-success"><?php echo count(array_filter($atendentes, fn($a) => $a['ativo'])); ?></h2>
                    <p>Atendentes Ativos</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-primary">
                        <?php
                        $total_ligacoes_hoje = $pdo->query("SELECT COUNT(*) FROM crm_ligacoes WHERE DATE(data_ligacao) = CURDATE()")->fetchColumn();
                        echo number_format($total_ligacoes_hoje);
                        ?>
                    </h2>
                    <p>Ligações Hoje</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-info">
                        <?php
                        $media_conversao = $pdo->query("SELECT AVG(taxa_conversao) FROM crm_atendentes WHERE ativo = 1")->fetchColumn();
                        echo round($media_conversao, 1);
                        ?>%
                    </h2>
                    <p>Taxa Média de Conversão</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-warning">
                        <?php
                        $contatos_sem_atendente = $pdo->query("SELECT COUNT(*) FROM crm_contatos WHERE atendente_responsavel_id IS NULL")->fetchColumn();
                        echo number_format($contatos_sem_atendente);
                        ?>
                    </h2>
                    <p>Contatos Sem Atendente</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Atendentes -->
    <h3 class="mb-3">Lista de Atendentes</h3>

    <?php if (empty($atendentes)): ?>
    <div class="alert alert-info">
        Nenhum atendente cadastrado ainda. Clique em "Cadastrar Atendente" para começar.
    </div>
    <?php else: ?>
        <?php foreach ($atendentes as $atendente): ?>
        <div class="atendente-card <?php echo $atendente['ativo'] ? '' : 'inativo'; ?>">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-1">
                        <?php echo htmlspecialchars($atendente['nome']); ?>
                        <?php if (!$atendente['ativo']): ?>
                        <span class="badge badge-danger">Inativo</span>
                        <?php elseif (!$atendente['disponivel']): ?>
                        <span class="badge badge-warning">Indisponível</span>
                        <?php else: ?>
                        <span class="badge badge-success">Disponível</span>
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted"><?php echo htmlspecialchars($atendente['email']); ?></small>
                </div>

                <div class="col-md-5">
                    <div class="row text-center">
                        <div class="col-3">
                            <strong class="text-primary"><?php echo $atendente['contatos_ativos']; ?></strong>
                            <br><small class="text-muted">Contatos</small>
                        </div>
                        <div class="col-3">
                            <strong class="text-success"><?php echo number_format($atendente['total_ligacoes_realizadas']); ?></strong>
                            <br><small class="text-muted">Ligações</small>
                        </div>
                        <div class="col-3">
                            <strong class="text-info"><?php echo number_format($atendente['total_conversoes']); ?></strong>
                            <br><small class="text-muted">Conversões</small>
                        </div>
                        <div class="col-3">
                            <strong class="text-<?php echo $atendente['taxa_conversao'] >= 20 ? 'success' : ($atendente['taxa_conversao'] >= 10 ? 'warning' : 'danger'); ?>">
                                <?php echo number_format($atendente['taxa_conversao'], 1); ?>%
                            </strong>
                            <br><small class="text-muted">Taxa</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 text-right">
                    <div class="mb-2">
                        <span class="performance-badge <?php
                            echo $atendente['taxa_conversao'] >= 25 ? 'badge-excelente' :
                                ($atendente['taxa_conversao'] >= 15 ? 'badge-bom' :
                                ($atendente['taxa_conversao'] >= 10 ? 'badge-regular' : 'badge-abaixo'));
                        ?>">
                            <?php
                            echo $atendente['taxa_conversao'] >= 25 ? 'Excelente' :
                                ($atendente['taxa_conversao'] >= 15 ? 'Bom' :
                                ($atendente['taxa_conversao'] >= 10 ? 'Regular' : 'Abaixo'));
                            ?>
                        </span>
                    </div>
                    <div>
                        <small class="text-muted">
                            Meta: <?php echo $atendente['meta_ligacoes_dia']; ?> ligações/dia
                        </small>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalEditarAtendente<?php echo $atendente['id']; ?>">
                            ✏️ Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar Atendente -->
        <div class="modal fade" id="modalEditarAtendente<?php echo $atendente['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar Atendente</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="atendente_id" value="<?php echo $atendente['id']; ?>">

                            <div class="form-group">
                                <label>Nome</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($atendente['nome']); ?>" readonly>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="ativo" id="ativo<?php echo $atendente['id']; ?>"
                                       class="form-check-input" <?php echo $atendente['ativo'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo<?php echo $atendente['id']; ?>">
                                    Atendente Ativo
                                </label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="disponivel" id="disponivel<?php echo $atendente['id']; ?>"
                                       class="form-check-input" <?php echo $atendente['disponivel'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="disponivel<?php echo $atendente['id']; ?>">
                                    Disponível para Atendimento
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" name="atualizar_status" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- Modal Novo Atendente -->
<div class="modal fade" id="modalNovoAtendente">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Atendente</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Selecione o Usuário *</label>
                        <select name="usuario_id" class="form-control" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($usuarios_disponiveis as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['nome']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            Se não houver usuários disponíveis, cadastre-os primeiro em "Gestão de Usuários".
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Meta de Ligações por Dia *</label>
                        <input type="number" name="meta_ligacoes_dia" class="form-control"
                               value="50" min="1" max="500" required>
                    </div>

                    <div class="form-group">
                        <label>Meta de Conversões por Mês *</label>
                        <input type="number" name="meta_conversoes_mes" class="form-control"
                               value="20" min="1" max="1000" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="cadastrar_atendente" class="btn btn-primary">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../footer.php'; ?>
