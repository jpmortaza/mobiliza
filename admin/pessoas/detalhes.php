<?php
/**
 * MOBILIZA+ V2 - Detalhes da Pessoa
 * Visualização completa de uma pessoa com histórico e CRM
 */

require_once '../../config.php';
verificar_autenticacao();

$pdo = conectar_db();
$pessoa_id = $_GET['id'] ?? null;

if (!$pessoa_id) {
    header('Location: index.php');
    exit;
}

// Buscar dados da pessoa
$stmt = $pdo->prepare("
    SELECT p.*, m.codigo_referencia, m.link_personalizado, m.nivel, m.pontos,
           m.status as mobilizador_status, m.total_indicacoes_diretas,
           m.total_assinaturas_geradas, m.total_inscricoes_eventos_geradas,
           p_indicador.nome as nome_indicador
    FROM pessoas p
    LEFT JOIN mobilizadores m ON p.id = m.pessoa_id
    LEFT JOIN pessoas p_indicador ON p.indicado_por = p_indicador.id
    WHERE p.id = ?
");
$stmt->execute([$pessoa_id]);
$pessoa = $stmt->fetch();

if (!$pessoa) {
    header('Location: index.php');
    exit;
}

// Buscar assinaturas
$stmt = $pdo->prepare("
    SELECT a.*, fa.titulo, fa.tipo, fa.slug
    FROM assinaturas a
    JOIN formularios_apoio fa ON a.formulario_apoio_id = fa.id
    WHERE a.pessoa_id = ?
    ORDER BY a.data_assinatura DESC
");
$stmt->execute([$pessoa_id]);
$assinaturas = $stmt->fetchAll();

// Buscar inscrições em eventos
$stmt = $pdo->prepare("
    SELECT ie.*, fe.titulo, fe.slug, fe.data_evento
    FROM inscricoes_eventos ie
    JOIN formularios_eventos fe ON ie.formulario_evento_id = fe.id
    WHERE ie.pessoa_id = ?
    ORDER BY ie.data_inscricao DESC
");
$stmt->execute([$pessoa_id]);
$inscricoes = $stmt->fetchAll();

// Buscar atendimentos
$stmt = $pdo->prepare("
    SELECT a.*, u.nome_completo as operador_nome
    FROM atendimentos_crm a
    LEFT JOIN usuarios_sistema u ON a.operador_id = u.id
    WHERE a.pessoa_id = ?
    ORDER BY a.data_abertura DESC
");
$stmt->execute([$pessoa_id]);
$atendimentos = $stmt->fetchAll();

// Buscar indicações feitas por esta pessoa
$stmt = $pdo->prepare("
    SELECT id, nome, whatsapp, cidade, origem, data_cadastro
    FROM pessoas
    WHERE indicado_por = ?
    ORDER BY data_cadastro DESC
    LIMIT 10
");
$stmt->execute([$pessoa_id]);
$indicacoes = $stmt->fetchAll();

$titulo_pagina = "Detalhes: " . htmlspecialchars($pessoa['nome']);

include '../header.php';
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.info-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    font-weight: 600;
    color: #666;
}
.info-value {
    color: #333;
}
.badge-big {
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 1em;
    font-weight: 600;
}
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline:before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
}
.timeline-item {
    position: relative;
    margin-bottom: 20px;
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: -24px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #667eea;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #667eea;
}
.tab-content {
    display: none;
}
.tab-content.active {
    display: block;
}
.tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e0e0e0;
}
.tab {
    padding: 12px 24px;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 3px solid transparent;
    font-weight: 600;
    color: #666;
}
.tab.active {
    color: #667eea;
    border-bottom-color: #667eea;
}
</style>

<div class="container-fluid" style="padding: 30px;">
    <div class="row mb-3">
        <div class="col">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        <div class="col-auto">
            <button class="btn btn-success" onclick="editarPessoa(<?php echo $pessoa_id; ?>)">
                <i class="fas fa-edit"></i> Editar
            </button>
            <button class="btn btn-primary" onclick="novoAtendimento()">
                <i class="fas fa-plus"></i> Novo Atendimento
            </button>
            <button class="btn btn-success" onclick="abrirWhatsApp('<?php echo $pessoa['whatsapp']; ?>')">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </button>
        </div>
    </div>

    <!-- Header do Perfil -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 style="margin: 0;">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($pessoa['nome']); ?>
                </h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">
                    ID: <?php echo $pessoa['id']; ?> |
                    Cadastrado em: <?php echo formatar_data_br($pessoa['data_cadastro']); ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge-big" style="background: rgba(255,255,255,0.2);">
                    <?php echo ucfirst($pessoa['status']); ?>
                </span>
                <?php if ($pessoa['e_mobilizador']): ?>
                    <span class="badge-big" style="background: rgba(255,255,255,0.2);">
                        Mobilizador Nível <?php echo $pessoa['nivel']; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Coluna Esquerda: Informações -->
        <div class="col-md-4">
            <!-- Dados Básicos -->
            <div class="info-card">
                <h5><i class="fas fa-id-card"></i> Dados Básicos</h5>
                <hr>
                <div class="info-row">
                    <span class="info-label">WhatsApp:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['whatsapp']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['email'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">CPF:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['cpf'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Cidade:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['cidade'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Estado:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['estado'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bairro:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['bairro'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">CEP:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['cep'] ?? '-'); ?></span>
                </div>
            </div>

            <!-- Origem e Tracking -->
            <div class="info-card">
                <h5><i class="fas fa-chart-line"></i> Origem e Tracking</h5>
                <hr>
                <div class="info-row">
                    <span class="info-label">Origem:</span>
                    <span class="info-value">
                        <?php
                        $origem_labels = [
                            'formulario_apoio' => 'Formulário de Apoio',
                            'formulario_evento' => 'Formulário de Evento',
                            'grupo_whatsapp' => 'Grupo WhatsApp',
                            'crm_manual' => 'CRM Manual'
                        ];
                        echo $origem_labels[$pessoa['origem']] ?? $pessoa['origem'];
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Indicado por:</span>
                    <span class="info-value">
                        <?php if ($pessoa['nome_indicador']): ?>
                            <a href="detalhes.php?id=<?php echo $pessoa['indicado_por']; ?>">
                                <?php echo htmlspecialchars($pessoa['nome_indicador']); ?>
                            </a>
                        <?php else: ?>
                            Não foi indicado
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Link usado:</span>
                    <span class="info-value"><?php echo htmlspecialchars($pessoa['link_indicacao_usado'] ?? '-'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Indicações feitas:</span>
                    <span class="info-value"><strong><?php echo $pessoa['numero_indicacoes']; ?></strong></span>
                </div>
            </div>

            <!-- Se for Mobilizador -->
            <?php if ($pessoa['e_mobilizador']): ?>
                <div class="info-card" style="border: 2px solid #667eea;">
                    <h5><i class="fas fa-star"></i> Dados do Mobilizador</h5>
                    <hr>
                    <div class="info-row">
                        <span class="info-label">Código:</span>
                        <span class="info-value"><strong><?php echo $pessoa['codigo_referencia']; ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Link:</span>
                        <span class="info-value">
                            <small><?php echo htmlspecialchars($pessoa['link_personalizado']); ?></small>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <?php echo ucfirst($pessoa['mobilizador_status'] ?? '-'); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nível:</span>
                        <span class="info-value"><strong>Nível <?php echo $pessoa['nivel']; ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Pontos:</span>
                        <span class="info-value"><strong><?php echo number_format($pessoa['pontos'], 0, ',', '.'); ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Indicações diretas:</span>
                        <span class="info-value"><?php echo $pessoa['total_indicacoes_diretas']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Assinaturas geradas:</span>
                        <span class="info-value"><?php echo $pessoa['total_assinaturas_geradas']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Inscrições geradas:</span>
                        <span class="info-value"><?php echo $pessoa['total_inscricoes_eventos_geradas']; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CRM Enriquecido -->
            <?php if ($pessoa['profissao'] || $pessoa['escolaridade'] || $pessoa['interesses']): ?>
                <div class="info-card">
                    <h5><i class="fas fa-user-tag"></i> Perfil Enriquecido</h5>
                    <hr>
                    <?php if ($pessoa['profissao']): ?>
                        <div class="info-row">
                            <span class="info-label">Profissão:</span>
                            <span class="info-value"><?php echo htmlspecialchars($pessoa['profissao']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($pessoa['escolaridade']): ?>
                        <div class="info-row">
                            <span class="info-label">Escolaridade:</span>
                            <span class="info-value"><?php echo htmlspecialchars($pessoa['escolaridade']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($pessoa['renda_familiar']): ?>
                        <div class="info-row">
                            <span class="info-label">Renda Familiar:</span>
                            <span class="info-value"><?php echo htmlspecialchars($pessoa['renda_familiar']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($pessoa['interesses']): ?>
                        <div class="info-row">
                            <span class="info-label">Interesses:</span>
                            <span class="info-value"><?php echo htmlspecialchars($pessoa['interesses']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Direita: Histórico e Ações -->
        <div class="col-md-8">
            <!-- Tabs -->
            <div class="info-card">
                <div class="tabs">
                    <button class="tab active" onclick="mudarTab('assinaturas')">
                        <i class="fas fa-signature"></i> Assinaturas (<?php echo count($assinaturas); ?>)
                    </button>
                    <button class="tab" onclick="mudarTab('eventos')">
                        <i class="fas fa-calendar"></i> Eventos (<?php echo count($inscricoes); ?>)
                    </button>
                    <button class="tab" onclick="mudarTab('indicacoes')">
                        <i class="fas fa-users"></i> Indicações (<?php echo count($indicacoes); ?>)
                    </button>
                    <button class="tab" onclick="mudarTab('atendimentos')">
                        <i class="fas fa-headset"></i> Atendimentos (<?php echo count($atendimentos); ?>)
                    </button>
                </div>

                <!-- Tab Assinaturas -->
                <div id="tab-assinaturas" class="tab-content active">
                    <div class="timeline">
                        <?php if (empty($assinaturas)): ?>
                            <p class="text-muted">Nenhuma assinatura ainda</p>
                        <?php else: ?>
                            <?php foreach ($assinaturas as $ass): ?>
                                <div class="timeline-item">
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                        <h6>
                                            <a href="/apoie/?slug=<?php echo $ass['slug']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($ass['titulo']); ?>
                                            </a>
                                        </h6>
                                        <p style="margin: 5px 0;">
                                            <span class="badge" style="background: <?php echo $ass['posicao'] == 'apoio' ? '#28a745' : '#dc3545'; ?>; color: white;">
                                                <?php echo ucfirst($ass['posicao']); ?>
                                            </span>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo formatar_data_br($ass['data_assinatura']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab Eventos -->
                <div id="tab-eventos" class="tab-content">
                    <div class="timeline">
                        <?php if (empty($inscricoes)): ?>
                            <p class="text-muted">Nenhuma inscrição em eventos</p>
                        <?php else: ?>
                            <?php foreach ($inscricoes as $insc): ?>
                                <div class="timeline-item">
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                        <h6>
                                            <a href="/eventos/?slug=<?php echo $insc['slug']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($insc['titulo']); ?>
                                            </a>
                                        </h6>
                                        <p style="margin: 5px 0;">
                                            Status: <span class="badge badge-<?php echo $insc['status_inscricao']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $insc['status_inscricao'])); ?>
                                            </span>
                                        </p>
                                        <?php if ($insc['data_evento']): ?>
                                            <p style="margin: 5px 0;">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo formatar_data_br($insc['data_evento']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            Inscrito em: <?php echo formatar_data_br($insc['data_inscricao']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab Indicações -->
                <div id="tab-indicacoes" class="tab-content">
                    <div class="timeline">
                        <?php if (empty($indicacoes)): ?>
                            <p class="text-muted">Nenhuma indicação feita ainda</p>
                        <?php else: ?>
                            <?php foreach ($indicacoes as $ind): ?>
                                <div class="timeline-item">
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                        <h6>
                                            <a href="detalhes.php?id=<?php echo $ind['id']; ?>">
                                                <?php echo htmlspecialchars($ind['nome']); ?>
                                            </a>
                                        </h6>
                                        <p style="margin: 5px 0;">
                                            <i class="fab fa-whatsapp"></i> <?php echo $ind['whatsapp']; ?> |
                                            <i class="fas fa-map-marker-alt"></i> <?php echo $ind['cidade'] ?? '-'; ?>
                                        </p>
                                        <small class="text-muted">
                                            Origem: <?php echo ucfirst(str_replace('_', ' ', $ind['origem'])); ?> -
                                            <?php echo formatar_data_br($ind['data_cadastro']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab Atendimentos -->
                <div id="tab-atendimentos" class="tab-content">
                    <div class="timeline">
                        <?php if (empty($atendimentos)): ?>
                            <p class="text-muted">Nenhum atendimento registrado</p>
                            <button class="btn btn-primary" onclick="novoAtendimento()">
                                <i class="fas fa-plus"></i> Criar Primeiro Atendimento
                            </button>
                        <?php else: ?>
                            <?php foreach ($atendimentos as $at): ?>
                                <div class="timeline-item">
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                        <h6>
                                            <a href="../crm/atendimento.php?id=<?php echo $at['id']; ?>">
                                                <?php echo htmlspecialchars($at['titulo']); ?>
                                            </a>
                                        </h6>
                                        <p style="margin: 5px 0;">
                                            Status: <span class="badge badge-<?php echo $at['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $at['status'])); ?>
                                            </span>
                                            Prioridade: <span class="badge badge-<?php echo $at['prioridade']; ?>">
                                                <?php echo ucfirst($at['prioridade']); ?>
                                            </span>
                                        </p>
                                        <p style="margin: 5px 0;">
                                            Operador: <?php echo $at['operador_nome'] ?? 'Não atribuído'; ?>
                                        </p>
                                        <small class="text-muted">
                                            Aberto em: <?php echo formatar_data_br($at['data_abertura']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <?php if ($pessoa['observacoes']): ?>
                <div class="info-card">
                    <h5><i class="fas fa-sticky-note"></i> Observações</h5>
                    <hr>
                    <p><?php echo nl2br(htmlspecialchars($pessoa['observacoes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function mudarTab(nome) {
    // Esconder todos
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });

    // Mostrar selecionado
    document.getElementById('tab-' + nome).classList.add('active');
    event.target.classList.add('active');
}

function editarPessoa(id) {
    window.location.href = 'form.php?id=' + id;
}

function novoAtendimento() {
    window.location.href = '../crm/form_atendimento.php?pessoa_id=<?php echo $pessoa_id; ?>';
}

function abrirWhatsApp(numero) {
    const numeroLimpo = numero.replace(/\D/g, '');
    window.open('https://wa.me/55' + numeroLimpo, '_blank');
}
</script>

<?php include '../footer.php'; ?>
