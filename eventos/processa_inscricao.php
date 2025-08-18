<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Capturar dados
    $evento_id = (int)($_POST['evento_id'] ?? 0);
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '', 'email');
    $whatsapp = sanitizar($_POST['whatsapp'] ?? '');
    $cidade = sanitizar($_POST['cidade'] ?? '');
    $referencia = sanitizar($_POST['referencia'] ?? '');
    
    // Validações
    if ($evento_id <= 0) {
        throw new Exception('Evento inválido');
    }
    
    if (empty($nome)) {
        throw new Exception('Nome é obrigatório');
    }
    
    if (empty($whatsapp)) {
        throw new Exception('WhatsApp é obrigatório');
    }
    
    if (empty($cidade)) {
        throw new Exception('Cidade é obrigatória');
    }
    
    // Conectar ao banco
    $pdo = conectar_db();
    
    // Verificar se o evento existe e está ativo
    $stmt = $pdo->prepare("SELECT id, titulo, link_whatsapp FROM eventos WHERE id = ? AND ativo = 1");
    $stmt->execute([$evento_id]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        throw new Exception('Evento não encontrado ou inativo');
    }
    
    // Limpar WhatsApp (remover caracteres especiais)
    $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
    
    // Verificar se já está inscrito (pelo WhatsApp)
    $stmt = $pdo->prepare("
        SELECT id FROM inscricoes_eventos 
        WHERE evento_id = ? AND whatsapp = ?
    ");
    $stmt->execute([$evento_id, $whatsapp]);
    
    if ($stmt->fetch()) {
        throw new Exception('Você já está inscrito neste evento!');
    }
    
    // Inserir inscrição
    $stmt = $pdo->prepare("
        INSERT INTO inscricoes_eventos 
        (evento_id, nome, email, whatsapp, cidade, referencia, data_inscricao) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $evento_id,
        $nome,
        $email ?: null,
        $whatsapp,
        $cidade,
        $referencia ?: null
    ]);
    
    // Registrar log
    registrar_log('inscricao_evento', 'inscricoes_eventos', $pdo->lastInsertId(), 
        "Nova inscrição em: " . $evento['titulo']);
    
    // Preparar resposta
    $response = [
        'success' => true,
        'message' => 'Inscrição realizada com sucesso! Em breve você receberá mais informações.',
        'link_whatsapp' => $evento['link_whatsapp']
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>