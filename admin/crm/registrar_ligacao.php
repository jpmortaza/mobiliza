<?php
/**
 * MOBILIZA+ CRM - Registrar Ligação
 */

$titulo_pagina = 'CRM - Registrar Ligação';
require_once '../header.php';
require_once '../../crm_core.php';
require_once '../../security.php';

$crm = new MobilizaCRM();

$contato_id = $_GET['contato_id'] ?? 0;
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

$sucesso = false;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    try {
        // Converter duração de minutos:segundos para segundos
        $duracao_minutos = (int)($_POST['duracao_minutos'] ?? 0);
        $duracao_segundos = (int)($_POST['duracao_segundos'] ?? 0);
        $duracao_total = ($duracao_minutos * 60) + $duracao_segundos;

        $dados_ligacao = [
            'contato_id' => $contato_id,
            'atendente_id' => $_SESSION['usuario_id'],
            'duracao_segundos' => $duracao_total,
            'tipo_ligacao' => $_POST['tipo_ligacao'],
            'status_ligacao' => $_POST['status_ligacao'],
            'resultado' => $_POST['resultado'] ?? null,
            'objetivo' => $_POST['objetivo'] ?? null,
            'resumo' => $_POST['resumo'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null,
            'proximos_passos' => $_POST['proximos_passos'] ?? null,
            'agendar_retorno' => isset($_POST['agendar_retorno']),
            'data_retorno' => !empty($_POST['data_retorno']) ? $_POST['data_retorno'] : null,
            'motivo_retorno' => $_POST['motivo_retorno'] ?? null
        ];

        $ligacao_id = $crm->registrarLigacao($dados_ligacao);

        if ($ligacao_id) {
            // Se marcou como converteu em ação
            if (isset($_POST['converteu_em_acao'])) {
                $pdo->prepare("
                    UPDATE crm_ligacoes
                    SET converteu_em_acao = 1, tipo_acao_convertida = ?
                    WHERE id = ?
                ")->execute([$_POST['tipo_conversao'], $ligacao_id]);
            }

            // Atualizar status do contato se selecionado
            if (!empty($_POST['novo_status_contato'])) {
                $crm->atualizarStatusContato($contato_id, $_POST['novo_status_contato']);
            }

            $sucesso = true;
        }

    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>

<style>
.form-section {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section h4 {
    border-bottom: 2px solid #667eea;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.contact-info-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.quick-select {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 15px;
}

.quick-select-btn {
    padding: 8px 15px;
    border: 2px solid #667eea;
    background: white;
    color: #667eea;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.quick-select-btn:hover,
.quick-select-btn.active {
    background: #667eea;
    color: white;
}

.duration-inputs {
    display: flex;
    gap: 10px;
    align-items: center;
}

.timer-display {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 20px;
}

.timer-controls {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 20px;
}
</style>

<div class="container-fluid py-4">

    <!-- Informação do Contato -->
    <div class="contact-info-header">
        <h2 class="mb-2">📞 Registrar Ligação</h2>
        <div class="row">
            <div class="col-md-8">
                <h4><?php echo htmlspecialchars($contato['nome']); ?></h4>
                <p class="mb-0">
                    <strong>WhatsApp:</strong> <?php echo $contato['whatsapp']; ?> &nbsp;|&nbsp;
                    <strong>Cidade:</strong> <?php echo htmlspecialchars($contato['cidade']); ?> &nbsp;|&nbsp;
                    <strong>Score:</strong> <?php echo $contato['score_engajamento']; ?>/100
                </p>
            </div>
            <div class="col-md-4 text-right">
                <a href="contato.php?id=<?php echo $contato_id; ?>" class="btn btn-light">
                    👁️ Ver Perfil Completo
                </a>
            </div>
        </div>
    </div>

    <?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>✅ Ligação registrada com sucesso!</strong>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <br>
        <a href="contato.php?id=<?php echo $contato_id; ?>" class="btn btn-sm btn-success mt-2">Ver Perfil do Contato</a>
        <a href="contatos.php" class="btn btn-sm btn-primary mt-2">Voltar para Lista</a>
        <a href="registrar_ligacao.php?contato_id=<?php echo $contato_id; ?>" class="btn btn-sm btn-secondary mt-2">Registrar Outra Ligação</a>
    </div>
    <?php endif; ?>

    <?php if ($erro): ?>
    <div class="alert alert-danger">
        <strong>Erro:</strong> <?php echo htmlspecialchars($erro); ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="formLigacao">
        <?php echo csrf_field(); ?>

        <!-- Cronômetro -->
        <div class="form-section">
            <h4>⏱️ Cronômetro de Ligação</h4>
            <div class="timer-display" id="timer">00:00</div>
            <div class="timer-controls">
                <button type="button" class="btn btn-success" id="btnIniciar">▶️ Iniciar</button>
                <button type="button" class="btn btn-warning" id="btnPausar" style="display:none;">⏸️ Pausar</button>
                <button type="button" class="btn btn-danger" id="btnParar" style="display:none;">⏹️ Parar</button>
                <button type="button" class="btn btn-secondary" id="btnReiniciar">🔄 Reiniciar</button>
            </div>
        </div>

        <!-- Dados Básicos da Ligação -->
        <div class="form-section">
            <h4>📋 Dados da Ligação</h4>

            <div class="form-row">
                <div class="col-md-4 mb-3">
                    <label><strong>Tipo de Ligação *</strong></label>
                    <select name="tipo_ligacao" class="form-control" required>
                        <option value="ativa">Ativa (eu liguei)</option>
                        <option value="receptiva">Receptiva (recebi a ligação)</option>
                        <option value="retorno">Retorno Agendado</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label><strong>Status da Ligação *</strong></label>
                    <select name="status_ligacao" class="form-control" required id="statusLigacao">
                        <option value="completada">Completada</option>
                        <option value="nao_atendeu">Não Atendeu</option>
                        <option value="ocupado">Ocupado</option>
                        <option value="caixa_postal">Caixa Postal</option>
                        <option value="numero_invalido">Número Inválido</option>
                        <option value="desligou">Desligou</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label><strong>Duração *</strong></label>
                    <div class="duration-inputs">
                        <input type="number" name="duracao_minutos" id="duracaoMinutos" class="form-control" min="0" max="999" value="0" placeholder="Min">
                        <span>:</span>
                        <input type="number" name="duracao_segundos" id="duracaoSegundos" class="form-control" min="0" max="59" value="0" placeholder="Seg">
                    </div>
                </div>
            </div>

            <div class="form-group" id="resultadoGroup">
                <label><strong>Resultado da Ligação</strong></label>
                <div class="quick-select">
                    <button type="button" class="quick-select-btn" data-value="muito_positivo">😃 Muito Positivo</button>
                    <button type="button" class="quick-select-btn" data-value="positivo">🙂 Positivo</button>
                    <button type="button" class="quick-select-btn" data-value="neutro">😐 Neutro</button>
                    <button type="button" class="quick-select-btn" data-value="negativo">☹️ Negativo</button>
                    <button type="button" class="quick-select-btn" data-value="callback_solicitado">📞 Solicitou Retorno</button>
                </div>
                <select name="resultado" class="form-control" id="resultadoSelect">
                    <option value="">Selecione...</option>
                    <option value="muito_positivo">Muito Positivo</option>
                    <option value="positivo">Positivo</option>
                    <option value="neutro">Neutro</option>
                    <option value="negativo">Negativo</option>
                    <option value="callback_solicitado">Callback Solicitado</option>
                </select>
            </div>

        </div>

        <!-- Detalhes da Conversa -->
        <div class="form-section">
            <h4>💬 Detalhes da Conversa</h4>

            <div class="form-group">
                <label><strong>Objetivo da Ligação</strong></label>
                <input type="text" name="objetivo" class="form-control"
                       placeholder="Ex: Convidar para evento, Confirmar interesse, Follow-up...">
            </div>

            <div class="form-group">
                <label><strong>Resumo da Conversa</strong></label>
                <textarea name="resumo" class="form-control" rows="4"
                          placeholder="Escreva um resumo do que foi conversado..."></textarea>
            </div>

            <div class="form-group">
                <label><strong>Observações Adicionais</strong></label>
                <textarea name="observacoes" class="form-control" rows="3"
                          placeholder="Informações importantes, contexto, dados coletados..."></textarea>
            </div>

            <div class="form-group">
                <label><strong>Próximos Passos</strong></label>
                <textarea name="proximos_passos" class="form-control" rows="3"
                          placeholder="O que fazer depois desta ligação? Ações necessárias..."></textarea>
            </div>

        </div>

        <!-- Conversão -->
        <div class="form-section">
            <h4>🎯 Conversão</h4>

            <div class="form-check mb-3">
                <input type="checkbox" name="converteu_em_acao" id="converteuAcao" class="form-check-input">
                <label class="form-check-label" for="converteuAcao">
                    <strong>Esta ligação resultou em uma ação/conversão?</strong>
                </label>
            </div>

            <div id="conversaoDetalhes" style="display:none;">
                <div class="form-group">
                    <label>Tipo de Conversão</label>
                    <select name="tipo_conversao" class="form-control">
                        <option value="evento">Inscrição em Evento</option>
                        <option value="peticao">Assinatura de Petição</option>
                        <option value="grupo">Entrada em Grupo WhatsApp</option>
                        <option value="voluntariado">Cadastro como Voluntário</option>
                        <option value="doacao">Doação</option>
                    </select>
                </div>
            </div>

        </div>

        <!-- Agendamento de Retorno -->
        <div class="form-section">
            <h4>📅 Agendamento de Retorno</h4>

            <div class="form-check mb-3">
                <input type="checkbox" name="agendar_retorno" id="agendarRetorno" class="form-check-input">
                <label class="form-check-label" for="agendarRetorno">
                    <strong>Agendar retorno para esta pessoa</strong>
                </label>
            </div>

            <div id="retornoDetalhes" style="display:none;">
                <div class="form-row">
                    <div class="col-md-6 mb-3">
                        <label>Data e Hora do Retorno *</label>
                        <input type="datetime-local" name="data_retorno" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Motivo do Retorno</label>
                        <input type="text" name="motivo_retorno" class="form-control"
                               placeholder="Ex: Confirmar presença no evento...">
                    </div>
                </div>
            </div>

        </div>

        <!-- Atualizar Status do Contato -->
        <div class="form-section">
            <h4>🔄 Atualizar Status do Contato</h4>

            <div class="form-group">
                <label><strong>Novo Status (opcional)</strong></label>
                <select name="novo_status_contato" class="form-control">
                    <option value="">Manter status atual (<?php echo ucfirst(str_replace('_', ' ', $contato['status_contato'])); ?>)</option>
                    <option value="em_contato">Em Contato</option>
                    <option value="contatado">Contatado</option>
                    <option value="interessado">Interessado</option>
                    <option value="muito_interessado">Muito Interessado</option>
                    <option value="voluntario">Voluntário</option>
                    <option value="nao_interessado">Não Interessado</option>
                    <option value="nao_responde">Não Responde</option>
                    <option value="numero_invalido">Número Inválido</option>
                </select>
            </div>

        </div>

        <!-- Botões -->
        <div class="text-center">
            <button type="submit" class="btn btn-success btn-lg px-5">
                💾 Salvar Ligação
            </button>
            <a href="contato.php?id=<?php echo $contato_id; ?>" class="btn btn-secondary btn-lg">
                Cancelar
            </a>
        </div>

    </form>

</div>

<script>
// Cronômetro
let segundos = 0;
let intervalo = null;
let rodando = false;

document.getElementById('btnIniciar').addEventListener('click', function() {
    if (!rodando) {
        rodando = true;
        intervalo = setInterval(atualizarTimer, 1000);
        document.getElementById('btnIniciar').style.display = 'none';
        document.getElementById('btnPausar').style.display = 'inline-block';
        document.getElementById('btnParar').style.display = 'inline-block';
    }
});

document.getElementById('btnPausar').addEventListener('click', function() {
    rodando = false;
    clearInterval(intervalo);
    document.getElementById('btnIniciar').style.display = 'inline-block';
    document.getElementById('btnPausar').style.display = 'none';
});

document.getElementById('btnParar').addEventListener('click', function() {
    rodando = false;
    clearInterval(intervalo);
    atualizarCamposDuracao();
    document.getElementById('btnIniciar').style.display = 'inline-block';
    document.getElementById('btnPausar').style.display = 'none';
    document.getElementById('btnParar').style.display = 'none';
});

document.getElementById('btnReiniciar').addEventListener('click', function() {
    rodando = false;
    clearInterval(intervalo);
    segundos = 0;
    document.getElementById('timer').textContent = '00:00';
    document.getElementById('duracaoMinutos').value = 0;
    document.getElementById('duracaoSegundos').value = 0;
    document.getElementById('btnIniciar').style.display = 'inline-block';
    document.getElementById('btnPausar').style.display = 'none';
    document.getElementById('btnParar').style.display = 'none';
});

function atualizarTimer() {
    segundos++;
    const min = Math.floor(segundos / 60);
    const seg = segundos % 60;
    document.getElementById('timer').textContent =
        String(min).padStart(2, '0') + ':' + String(seg).padStart(2, '0');
}

function atualizarCamposDuracao() {
    const min = Math.floor(segundos / 60);
    const seg = segundos % 60;
    document.getElementById('duracaoMinutos').value = min;
    document.getElementById('duracaoSegundos').value = seg;
}

// Quick select para resultado
document.querySelectorAll('.quick-select-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.quick-select-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('resultadoSelect').value = this.dataset.value;
    });
});

// Mostrar/ocultar campos de conversão
document.getElementById('converteuAcao').addEventListener('change', function() {
    document.getElementById('conversaoDetalhes').style.display = this.checked ? 'block' : 'none';
});

// Mostrar/ocultar campos de retorno
document.getElementById('agendarRetorno').addEventListener('change', function() {
    document.getElementById('retornoDetalhes').style.display = this.checked ? 'block' : 'none';
});

// Ocultar resultado se não foi completada
document.getElementById('statusLigacao').addEventListener('change', function() {
    const grupo = document.getElementById('resultadoGroup');
    grupo.style.display = this.value === 'completada' ? 'block' : 'none';
});
</script>

<?php require_once '../footer.php'; ?>
