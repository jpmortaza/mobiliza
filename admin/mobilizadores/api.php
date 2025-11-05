<?php
/**
 * MOBILIZA+ V2 - API de Mobilizadores
 * Endpoints para ações rápidas
 */

require_once '../../config.php';
verificar_autenticacao();

header('Content-Type: application/json');

$pdo = conectar_db();
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    switch ($action) {
        case 'aprovar':
            if (!$id) throw new Exception('ID não fornecido');

            $pdo->beginTransaction();

            // Aprovar mobilizador
            $stmt = $pdo->prepare("
                UPDATE mobilizadores
                SET status = 'aprovado', data_aprovacao = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            // Atualizar pessoa
            $stmt = $pdo->prepare("
                UPDATE pessoas p
                JOIN mobilizadores m ON p.id = m.pessoa_id
                SET p.e_mobilizador = TRUE
                WHERE m.id = ?
            ");
            $stmt->execute([$id]);

            // Registrar log
            registrar_log(
                'mobilizador_aprovado',
                'mobilizadores',
                $id,
                json_encode(['usuario_id' => $_SESSION['usuario_id']])
            );

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Mobilizador aprovado']);
            break;

        case 'suspender':
            if (!$id) throw new Exception('ID não fornecido');

            $stmt = $pdo->prepare("
                UPDATE mobilizadores
                SET status = 'suspenso'
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            registrar_log('mobilizador_suspenso', 'mobilizadores', $id);

            echo json_encode(['success' => true, 'message' => 'Mobilizador suspenso']);
            break;

        case 'reativar':
            if (!$id) throw new Exception('ID não fornecido');

            $stmt = $pdo->prepare("
                UPDATE mobilizadores
                SET status = 'aprovado'
                WHERE id = ?
            ");
            $stmt->execute([$id]);

            registrar_log('mobilizador_reativado', 'mobilizadores', $id);

            echo json_encode(['success' => true, 'message' => 'Mobilizador reativado']);
            break;

        case 'recalcular_pontos':
            if (!$id) throw new Exception('ID não fornecido');

            // Buscar configurações de pontuação
            $pontos_indicacao = obter_config('pontos_indicacao', 10);
            $pontos_assinatura = obter_config('pontos_assinatura', 5);
            $pontos_inscricao = obter_config('pontos_inscricao_evento', 7);

            $stmt = $pdo->prepare("
                SELECT
                    total_indicacoes_diretas,
                    total_assinaturas_geradas,
                    total_inscricoes_eventos_geradas
                FROM mobilizadores
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $mob = $stmt->fetch();

            // Calcular pontos
            $total_pontos = ($mob['total_indicacoes_diretas'] * $pontos_indicacao) +
                           ($mob['total_assinaturas_geradas'] * $pontos_assinatura) +
                           ($mob['total_inscricoes_eventos_geradas'] * $pontos_inscricao);

            // Calcular nível baseado em pontos
            $nivel = calcular_nivel($total_pontos);

            // Atualizar
            $stmt = $pdo->prepare("
                UPDATE mobilizadores
                SET pontos = ?, nivel = ?
                WHERE id = ?
            ");
            $stmt->execute([$total_pontos, $nivel, $id]);

            echo json_encode([
                'success' => true,
                'pontos' => $total_pontos,
                'nivel' => $nivel
            ]);
            break;

        case 'recalcular_todos':
            // Recalcular pontos e níveis de todos os mobilizadores
            $pontos_indicacao = obter_config('pontos_indicacao', 10);
            $pontos_assinatura = obter_config('pontos_assinatura', 5);
            $pontos_inscricao = obter_config('pontos_inscricao_evento', 7);

            $stmt = $pdo->query("SELECT id, total_indicacoes_diretas, total_assinaturas_geradas, total_inscricoes_eventos_geradas FROM mobilizadores");
            $mobilizadores = $stmt->fetchAll();

            $stmt_update = $pdo->prepare("UPDATE mobilizadores SET pontos = ?, nivel = ? WHERE id = ?");

            $count = 0;
            foreach ($mobilizadores as $mob) {
                $total_pontos = ($mob['total_indicacoes_diretas'] * $pontos_indicacao) +
                               ($mob['total_assinaturas_geradas'] * $pontos_assinatura) +
                               ($mob['total_inscricoes_eventos_geradas'] * $pontos_inscricao);

                $nivel = calcular_nivel($total_pontos);
                $stmt_update->execute([$total_pontos, $nivel, $mob['id']]);
                $count++;
            }

            echo json_encode(['success' => true, 'total' => $count]);
            break;

        case 'gerar_codigo':
            // Gerar código único para novo mobilizador
            $codigo = gerar_codigo_mobilizador();
            echo json_encode(['success' => true, 'codigo' => $codigo]);
            break;

        default:
            throw new Exception('Ação não reconhecida');
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Calcular nível baseado em pontos
 */
function calcular_nivel($pontos) {
    if ($pontos >= 25000) return 10;
    if ($pontos >= 15000) return 9;
    if ($pontos >= 10000) return 8;
    if ($pontos >= 7500) return 7;
    if ($pontos >= 5000) return 6;
    if ($pontos >= 2500) return 5;
    if ($pontos >= 1000) return 4;
    if ($pontos >= 500) return 3;
    if ($pontos >= 100) return 2;
    return 1;
}

/**
 * Gerar código único de mobilizador
 */
function gerar_codigo_mobilizador() {
    global $pdo;

    do {
        // Gerar código de 6 caracteres alfanuméricos
        $codigo = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));

        // Verificar se já existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mobilizadores WHERE codigo_referencia = ?");
        $stmt->execute([$codigo]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);

    return $codigo;
}
?>
