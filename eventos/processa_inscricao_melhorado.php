<?php
/**
 * MOBILIZA+ - Processamento Melhorado de Inscrição em Evento
 *
 * Este arquivo demonstra como integrar todas as melhorias
 * no processamento de inscrições de eventos
 *
 * INSTRUÇÕES: Este é um arquivo de exemplo/demonstração.
 * Para usar, renomeie o arquivo original e use este no lugar.
 */

require_once '../config.php';
require_once '../security.php';
require_once '../cache.php';
require_once '../notifications.php';

header('Content-Type: application/json; charset=UTF-8');

try {
    // 1. RATE LIMITING - Previne spam (máximo 5 inscrições por minuto por IP)
    rate_limit('inscricao_evento', 5, 60);

    // 2. VERIFICAR CSRF TOKEN
    verificar_csrf();

    // 3. VALIDAR MÉTODO
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // 4. BUSCAR EVENTO (com cache)
    $slug = $_POST['evento_slug'] ?? '';
    if (empty($slug)) {
        throw new Exception('Evento não especificado');
    }

    $evento = cache_remember("evento_{$slug}", 600, function() use ($slug) {
        $pdo = conectar_db();
        $stmt = $pdo->prepare("SELECT * FROM eventos WHERE slug = ? AND ativo = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    });

    if (!$evento) {
        throw new Exception('Evento não encontrado ou inativo');
    }

    // 5. VALIDAÇÕES AVANÇADAS

    // Validar nome
    $nome = $_POST['nome'] ?? '';
    if (empty($nome) || strlen($nome) < 3) {
        throw new Exception('Nome deve ter no mínimo 3 caracteres');
    }
    $nome = MobilizaSecurity::sanitizarNome($nome);

    // Validar WhatsApp
    $whatsapp_input = $_POST['whatsapp'] ?? '';
    $whatsapp_validacao = MobilizaSecurity::validarWhatsApp($whatsapp_input);

    if (!$whatsapp_validacao['valido']) {
        throw new Exception($whatsapp_validacao['erro']);
    }

    $whatsapp = $whatsapp_validacao['numero'];
    $whatsapp_formatado = $whatsapp_validacao['formatado'];

    // Validar Email (opcional mas recomendado)
    $email = $_POST['email'] ?? '';
    if (!empty($email)) {
        $email_validacao = MobilizaSecurity::validarEmail($email);
        if (!$email_validacao['valido']) {
            throw new Exception($email_validacao['erro']);
        }
        $email = $email_validacao['email'];
    }

    // Validar cidade
    $cidade = trim($_POST['cidade'] ?? '');
    if (empty($cidade)) {
        throw new Exception('Cidade é obrigatória');
    }
    $cidade = MobilizaSecurity::sanitizarNome($cidade);

    // Referência (mobilizador)
    $referencia = trim($_POST['referencia'] ?? '');

    // 6. DETECTAR DUPLICATA
    $duplicata = MobilizaSecurity::detectarDuplicata($whatsapp, 'evento', $evento['id']);

    if ($duplicata['duplicata']) {
        // Pode decidir se bloqueia ou apenas avisa
        $data_anterior = formatar_data_br($duplicata['data']);

        throw new Exception(
            "Este WhatsApp já está inscrito neste evento desde {$data_anterior}. " .
            "Se você não se inscreveu antes, entre em contato conosco."
        );

        // Alternativa: apenas avisar mas permitir
        // $mensagem_extra = "Nota: Detectamos uma inscrição anterior com este WhatsApp.";
    }

    // 7. INSERIR NO BANCO
    $pdo = conectar_db();

    $stmt = $pdo->prepare("
        INSERT INTO inscricoes_eventos
        (evento_id, nome, email, whatsapp, cidade, referencia, data_inscricao)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $sucesso = $stmt->execute([
        $evento['id'],
        $nome,
        $email,
        $whatsapp,
        $cidade,
        $referencia
    ]);

    if (!$sucesso) {
        throw new Exception('Erro ao processar inscrição. Tente novamente.');
    }

    $inscricao_id = $pdo->lastInsertId();

    // 8. REGISTRAR LOG
    registrar_log(
        'INSCRICAO_EVENTO',
        'inscricoes_eventos',
        $inscricao_id,
        json_encode([
            'evento' => $evento['titulo'],
            'cidade' => $cidade,
            'referencia' => $referencia
        ])
    );

    // 9. INVALIDAR CACHE RELACIONADO
    cache_forget("evento_inscricoes_{$evento['id']}");
    cache_forget("stats_gerais");
    cache_forget("insights_dashboard");

    // 10. VERIFICAR SE DEVE CRIAR NOTIFICAÇÃO
    // Se atingiu múltiplo de 50 inscrições, notificar admin
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscricoes_eventos WHERE evento_id = ?");
    $stmt->execute([$evento['id']]);
    $total_inscricoes = $stmt->fetchColumn();

    if ($total_inscricoes % 50 === 0) {
        $notif = new MobilizaNotifications();
        $notif->init();

        // Notificar criador do evento
        if ($evento['criado_por']) {
            $notif->criar(
                $evento['criado_por'],
                'sucesso',
                'Marco alcançado! 🎉',
                "O evento '{$evento['titulo']}' atingiu {$total_inscricoes} inscrições!",
                "/admin/eventos/inscricoes.php?id={$evento['id']}"
            );
        }
    }

    // 11. PREPARAR RESPOSTA DE SUCESSO
    $resposta = [
        'sucesso' => true,
        'mensagem' => 'Inscrição realizada com sucesso!',
        'dados' => [
            'nome' => $nome,
            'whatsapp' => $whatsapp_formatado,
            'cidade' => $cidade,
            'inscricao_id' => $inscricao_id
        ],
        'redirect' => "obrigado.php?evento={$evento['slug']}"
    ];

    // Se tem link do WhatsApp, incluir na resposta
    if (!empty($evento['link_whatsapp'])) {
        $resposta['whatsapp_grupo'] = $evento['link_whatsapp'];
    }

    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Em caso de erro, registrar atividade suspeita se for erro de validação
    if (strpos($e->getMessage(), 'WhatsApp') !== false ||
        strpos($e->getMessage(), 'duplicata') !== false) {
        MobilizaSecurity::registrarTentativaSuspeita('inscricao_duplicata');
    }

    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'erro' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
