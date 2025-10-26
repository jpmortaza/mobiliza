<?php
/**
 * MOBILIZA+ CRM - Perfil Completo do Contato
 */

$titulo_pagina = 'CRM - Perfil do Contato';
require_once '../header.php';
require_once '../../crm_core.php';

$crm = new MobilizaCRM();

$contato_id = $_GET['id'] ?? 0;
if (!$contato_id) {
    header('Location: contatos.php');
    exit;
}

$contato = $crm->buscarContato($contato_id);
if (!$contato) {
    echo '<div class="alert alert-danger">Contato não encontrado</div>';
    require_once '../footer.php';
    exit;
}

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_status'])) {
    $novo_status = $_POST['status_contato'];
    $observacao = $_POST['observacao_status'] ?? null;
    $crm->atualizarStatusContato($contato_id, $novo_status, $observacao);
    header('Location: contato.php?id=' . $contato_id . '&msg=status_atualizado');
    exit;
}

// Processar enriquecimento de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enriquecer_dados'])) {
    $dados_enriquecimento = [];
    $campos = ['cpf', 'data_nascimento', 'genero', 'profissao', 'escolaridade', 'renda',
               'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'estado',
               'instagram', 'facebook', 'twitter', 'linkedin'];

    foreach ($campos as $campo) {
        if (!empty($_POST[$campo])) {
            $dados_enriquecimento[$campo] = $_POST[$campo];
        }
    }

    if ($crm->enriquecerContato($contato_id, $dados_enriquecimento)) {
        header('Location: contato.php?id=' . $contato_id . '&msg=dados_atualizados');
        exit;
    }
}
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.profile-score {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
    color: #667eea;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

.info-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-item {
    border-left: 3px solid #667eea;
    padding-left: 20px;
    padding-bottom: 20px;
    margin-left: 10px;
    position: relative;
}

.timeline-item::before {
    content: '';
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background: #667eea;
    position: absolute;
    left: -9px;
    top: 0;
}

.timeline-item.tipo-ligacao::before { background: #28a745; }
.timeline-item.tipo-evento::before { background: #17a2b8; }
.timeline-item.tipo-peticao::before { background: #ffc107; }
.timeline-item.tipo-grupo_whatsapp::before { background: #9333ea; }

.ligacao-card {
    border-left: 4px solid #28a745;
    padding: 15px;
    margin-bottom: 10px;
    background: #f8f9fa;
    border-radius: 8px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-item h3 { margin: 0; color: #667eea; }
.stat-item p { margin: 5px 0 0 0; font-size: 0.9rem; color: #666; }
</style>

<div class="container-fluid py-4">

    <!-- Cabeçalho do Perfil -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="profile-score">
                    <?php echo $contato['score_engajamento']; ?>
                </div>
                <small class="d-block mt-2">Score de Engajamento</small>
            </div>
            <div class="col-md-7">
                <h1 class="mb-2"><?php echo htmlspecialchars($contato['nome']); ?></h1>
                <p class="mb-1">
                    <strong>📞 WhatsApp:</strong> <?php echo $contato['whatsapp']; ?> &nbsp;|&nbsp;
                    <?php if ($contato['email']): ?>
                    <strong>✉️ Email:</strong> <?php echo htmlspecialchars($contato['email']); ?> &nbsp;|&nbsp;
                    <?php endif; ?>
                    <strong>📍 Cidade:</strong> <?php echo htmlspecialchars($contato['cidade']); ?>
                    <?php if ($contato['estado']): ?>
                        - <?php echo $contato['estado']; ?>
                    <?php endif; ?>
                </p>
                <p class="mb-0">
                    <span class="badge badge-light">
                        Status: <?php echo ucfirst(str_replace('_', ' ', $contato['status_contato'])); ?>
                    </span>
                    <span class="badge badge-light">
                        Prioridade: <?php echo ucfirst($contato['prioridade']); ?>
                    </span>
                    <?php if ($contato['atendente_nome']): ?>
                    <span class="badge badge-light">
                        Atendente: <?php echo htmlspecialchars($contato['atendente_nome']); ?>
                    </span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-3 text-right">
                <a href="registrar_ligacao.php?contato_id=<?php echo $contato_id; ?>" class="btn btn-success btn-lg mb-2">
                    📞 Registrar Ligação
                </a>
                <br>
                <a href="editar_contato.php?id=<?php echo $contato_id; ?>" class="btn btn-light">
                    ✏️ Editar Dados
                </a>
                <a href="contatos.php" class="btn btn-light">
                    ← Voltar
                </a>
            </div>
        </div>
    </div>

    <!-- Estatísticas Rápidas -->
    <div class="quick-stats">
        <div class="stat-item">
            <h3><?php echo $contato['total_eventos']; ?></h3>
            <p>Eventos</p>
        </div>
        <div class="stat-item">
            <h3><?php echo $contato['total_peticoes']; ?></h3>
            <p>Petições</p>
        </div>
        <div class="stat-item">
            <h3><?php echo $contato['total_grupos']; ?></h3>
            <p>Grupos WhatsApp</p>
        </div>
        <div class="stat-item">
            <h3><?php echo count($contato['ligacoes']); ?></h3>
            <p>Ligações</p>
        </div>
        <div class="stat-item">
            <h3><?php echo $contato['total_acoes']; ?></h3>
            <p>Total de Ações</p>
        </div>
    </div>

    <div class="row">

        <!-- Coluna Esquerda: Informações e Histórico -->
        <div class="col-md-8">

            <!-- Atualizar Status -->
            <div class="info-card">
                <h4 class="mb-3">🔄 Atualizar Status do Contato</h4>
                <form method="POST">
                    <div class="form-row">
                        <div class="col-md-4">
                            <select name="status_contato" class="form-control" required>
                                <option value="novo" <?php echo $contato['status_contato'] === 'novo' ? 'selected' : ''; ?>>Novo</option>
                                <option value="aguardando_contato" <?php echo $contato['status_contato'] === 'aguardando_contato' ? 'selected' : ''; ?>>Aguardando Contato</option>
                                <option value="em_contato" <?php echo $contato['status_contato'] === 'em_contato' ? 'selected' : ''; ?>>Em Contato</option>
                                <option value="contatado" <?php echo $contato['status_contato'] === 'contatado' ? 'selected' : ''; ?>>Contatado</option>
                                <option value="interessado" <?php echo $contato['status_contato'] === 'interessado' ? 'selected' : ''; ?>>Interessado</option>
                                <option value="muito_interessado" <?php echo $contato['status_contato'] === 'muito_interessado' ? 'selected' : ''; ?>>Muito Interessado</option>
                                <option value="voluntario" <?php echo $contato['status_contato'] === 'voluntario' ? 'selected' : ''; ?>>Voluntário</option>
                                <option value="nao_interessado" <?php echo $contato['status_contato'] === 'nao_interessado' ? 'selected' : ''; ?>>Não Interessado</option>
                                <option value="nao_responde" <?php echo $contato['status_contato'] === 'nao_responde' ? 'selected' : ''; ?>>Não Responde</option>
                                <option value="numero_invalido" <?php echo $contato['status_contato'] === 'numero_invalido' ? 'selected' : ''; ?>>Número Inválido</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="observacao_status" class="form-control" placeholder="Observação (opcional)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="atualizar_status" class="btn btn-primary btn-block">Atualizar</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Ligações Registradas -->
            <?php if (!empty($contato['ligacoes'])): ?>
            <div class="info-card">
                <h4 class="mb-3">📞 Histórico de Ligações (<?php echo count($contato['ligacoes']); ?>)</h4>
                <?php foreach (array_slice($contato['ligacoes'], 0, 5) as $ligacao): ?>
                <div class="ligacao-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong><?php echo formatar_data_br($ligacao['data_ligacao']); ?></strong>
                            -
                            <span class="badge badge-<?php
                                echo $ligacao['status_ligacao'] === 'completada' ? 'success' : 'warning';
                            ?>">
                                <?php echo ucfirst($ligacao['status_ligacao']); ?>
                            </span>
                            <?php if ($ligacao['resultado']): ?>
                            <span class="badge badge-info"><?php echo ucfirst($ligacao['resultado']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted">
                            <?php
                            $minutos = floor($ligacao['duracao_segundos'] / 60);
                            $segundos = $ligacao['duracao_segundos'] % 60;
                            echo $minutos > 0 ? "{$minutos}min " : "";
                            echo "{$segundos}s";
                            ?>
                            | <?php echo htmlspecialchars($ligacao['atendente_nome']); ?>
                        </div>
                    </div>
                    <?php if ($ligacao['resumo']): ?>
                    <p class="mt-2 mb-0"><strong>Resumo:</strong> <?php echo nl2br(htmlspecialchars($ligacao['resumo'])); ?></p>
                    <?php endif; ?>
                    <?php if ($ligacao['proximos_passos']): ?>
                    <p class="mt-1 mb-0"><strong>Próximos passos:</strong> <?php echo nl2br(htmlspecialchars($ligacao['proximos_passos'])); ?></p>
                    <?php endif; ?>
                    <?php if ($ligacao['agendar_retorno']): ?>
                    <p class="mt-1 mb-0 text-warning">
                        <strong>⏰ Retorno agendado:</strong> <?php echo formatar_data_br($ligacao['data_retorno']); ?>
                        <?php if ($ligacao['motivo_retorno']): ?>
                        - <?php echo htmlspecialchars($ligacao['motivo_retorno']); ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if (count($contato['ligacoes']) > 5): ?>
                <button class="btn btn-link" onclick="$('.ligacao-extra').toggle()">
                    Ver todas as <?php echo count($contato['ligacoes']); ?> ligações
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Histórico Completo -->
            <div class="info-card">
                <h4 class="mb-3">📋 Histórico de Interações</h4>
                <div style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($contato['historico'] as $item): ?>
                    <div class="timeline-item tipo-<?php echo $item['tipo_interacao']; ?>">
                        <strong><?php echo formatar_data_br($item['data_registro']); ?></strong>
                        <br>
                        <span class="badge badge-secondary"><?php echo ucfirst(str_replace('_', ' ', $item['tipo_interacao'])); ?></span>
                        <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($item['descricao'])); ?></p>
                        <?php if ($item['resultado']): ?>
                        <small class="text-muted">Resultado: <?php echo htmlspecialchars($item['resultado']); ?></small>
                        <?php endif; ?>
                        <?php if ($item['registrado_por_nome']): ?>
                        <br><small class="text-muted">Por: <?php echo htmlspecialchars($item['registrado_por_nome']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Coluna Direita: Dados Adicionais -->
        <div class="col-md-4">

            <!-- Dados Pessoais -->
            <div class="info-card">
                <h4 class="mb-3">👤 Dados Pessoais</h4>
                <form method="POST">
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" name="cpf" class="form-control" value="<?php echo $contato['cpf'] ?? ''; ?>" placeholder="000.000.000-00">
                    </div>
                    <div class="form-group">
                        <label>Data de Nascimento</label>
                        <input type="date" name="data_nascimento" class="form-control" value="<?php echo $contato['data_nascimento'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Gênero</label>
                        <select name="genero" class="form-control">
                            <option value="">Não informado</option>
                            <option value="M" <?php echo ($contato['genero'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                            <option value="F" <?php echo ($contato['genero'] ?? '') === 'F' ? 'selected' : ''; ?>>Feminino</option>
                            <option value="Outro" <?php echo ($contato['genero'] ?? '') === 'Outro' ? 'selected' : ''; ?>>Outro</option>
                            <option value="Prefiro não informar" <?php echo ($contato['genero'] ?? '') === 'Prefiro não informar' ? 'selected' : ''; ?>>Prefiro não informar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Profissão</label>
                        <input type="text" name="profissao" class="form-control" value="<?php echo $contato['profissao'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Escolaridade</label>
                        <select name="escolaridade" class="form-control">
                            <option value="">Não informado</option>
                            <option value="Fundamental" <?php echo ($contato['escolaridade'] ?? '') === 'Fundamental' ? 'selected' : ''; ?>>Fundamental</option>
                            <option value="Médio" <?php echo ($contato['escolaridade'] ?? '') === 'Médio' ? 'selected' : ''; ?>>Médio</option>
                            <option value="Superior" <?php echo ($contato['escolaridade'] ?? '') === 'Superior' ? 'selected' : ''; ?>>Superior</option>
                            <option value="Pós-graduação" <?php echo ($contato['escolaridade'] ?? '') === 'Pós-graduação' ? 'selected' : ''; ?>>Pós-graduação</option>
                        </select>
                    </div>
                    <button type="submit" name="enriquecer_dados" class="btn btn-success btn-block">💾 Salvar Dados</button>
                </form>
            </div>

            <!-- Eventos Participados -->
            <?php if (!empty($contato['eventos'])): ?>
            <div class="info-card">
                <h4 class="mb-3">📅 Eventos (<?php echo count($contato['eventos']); ?>)</h4>
                <?php foreach (array_slice($contato['eventos'], 0, 5) as $evento): ?>
                <div class="mb-2">
                    <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                    <?php if ($evento['checkin']): ?>
                    <span class="badge badge-success">Presente</span>
                    <?php endif; ?>
                    <br>
                    <small class="text-muted"><?php echo formatar_data_br($evento['data_inscricao'], false); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Petições Assinadas -->
            <?php if (!empty($contato['peticoes'])): ?>
            <div class="info-card">
                <h4 class="mb-3">✍️ Petições (<?php echo count($contato['peticoes']); ?>)</h4>
                <?php foreach (array_slice($contato['peticoes'], 0, 5) as $peticao): ?>
                <div class="mb-2">
                    <strong><?php echo htmlspecialchars($peticao['titulo']); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo formatar_data_br($peticao['data_assinatura'], false); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Grupos WhatsApp -->
            <?php if (!empty($contato['grupos'])): ?>
            <div class="info-card">
                <h4 class="mb-3">💬 Grupos WhatsApp (<?php echo count($contato['grupos']); ?>)</h4>
                <?php foreach ($contato['grupos'] as $grupo): ?>
                <div class="mb-2">
                    <strong><?php echo htmlspecialchars($grupo['nome']); ?></strong>
                    <span class="badge badge-info"><?php echo ucfirst($grupo['categoria']); ?></span>
                    <br>
                    <small class="text-muted">Desde <?php echo formatar_data_br($grupo['data_entrada'], false); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Tarefas Pendentes -->
            <?php if (!empty($contato['tarefas'])): ?>
            <div class="info-card">
                <h4 class="mb-3">✅ Tarefas Pendentes (<?php echo count($contato['tarefas']); ?>)</h4>
                <?php foreach ($contato['tarefas'] as $tarefa): ?>
                <div class="mb-2 p-2 border-left border-warning">
                    <strong><?php echo htmlspecialchars($tarefa['titulo']); ?></strong>
                    <br>
                    <small class="text-muted">
                        Vence: <?php echo formatar_data_br($tarefa['data_vencimento']); ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>

    </div>

</div>

<?php require_once '../footer.php'; ?>
