<?php
/**
 * MOBILIZA+ CRM - Sincronizar Contatos
 *
 * Sincroniza contatos de eventos, petições e grupos para o CRM
 */

$titulo_pagina = 'CRM - Sincronizar Contatos';
require_once '../header.php';
require_once '../../crm_core.php';

$crm = new MobilizaCRM();

$sincronizando = false;
$total_sincronizados = 0;

if (isset($_POST['sincronizar'])) {
    $sincronizando = true;
    $total_sincronizados = $crm->sincronizarContatos();
}
?>

<div class="container-fluid py-4">

    <h1 class="mb-4">🔄 Sincronizar Contatos</h1>

    <?php if ($sincronizando): ?>
    <div class="alert alert-success">
        <h4>✅ Sincronização Concluída!</h4>
        <p><strong><?php echo number_format($total_sincronizados); ?></strong> contato(s) sincronizado(s) com sucesso!</p>
        <a href="contatos.php" class="btn btn-primary">Ver Todos os Contatos</a>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Sincronização de Dados</h5>
            <p class="card-text">
                Esta função sincroniza todos os contatos de:
            </p>
            <ul>
                <li>✅ Inscrições em Eventos</li>
                <li>✅ Assinaturas de Petições</li>
                <li>✅ Participantes de Grupos WhatsApp</li>
            </ul>
            <p class="text-muted">
                <strong>Observação:</strong> Contatos duplicados (mesmo WhatsApp) serão mesclados automaticamente.
                Esta operação é segura e pode ser executada múltiplas vezes.
            </p>

            <form method="POST">
                <button type="submit" name="sincronizar" class="btn btn-primary btn-lg">
                    🔄 Iniciar Sincronização
                </button>
            </form>
        </div>
    </div>

    <!-- Estatísticas Atuais -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-primary">
                        <?php echo number_format($pdo->query("SELECT COUNT(*) FROM crm_contatos")->fetchColumn()); ?>
                    </h2>
                    <p>Contatos no CRM</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-success">
                        <?php echo number_format($pdo->query("SELECT COUNT(DISTINCT whatsapp) FROM inscricoes_eventos")->fetchColumn()); ?>
                    </h2>
                    <p>Contatos em Eventos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <h2 class="text-info">
                        <?php echo number_format($pdo->query("SELECT COUNT(DISTINCT whatsapp) FROM assinaturas_peticoes")->fetchColumn()); ?>
                    </h2>
                    <p>Contatos em Petições</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once '../footer.php'; ?>
