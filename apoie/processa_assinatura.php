<?php
require_once '../config.php';
header('Content-Type: application/json');

try {
    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Capturar dados
    $peticao_id = (int)($_POST['peticao_id'] ?? 0);
    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '', 'email');
    $whatsapp = sanitizar($_POST['whatsapp'] ?? '');
    $cidade = sanitizar($_POST['cidade'] ?? '');
    $referencia = sanitizar($_POST['referencia'] ?? '');
    
    // Validações
    if ($peticao_id <= 0) {
        throw new Exception('Petição inválida');
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
    
    // Verificar se a petição existe e está ativa
    $stmt = $pdo->prepare("SELECT id, titulo FROM peticoes WHERE id = ? AND ativo = 1");
    $stmt->execute([$peticao_id]);
    $peticao = $stmt->fetch();
    
    if (!$peticao) {
        throw new Exception('Petição não encontrada ou inativa');
    }
    
    // Limpar WhatsApp (remover caracteres especiais)
    $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);
    
    // Verificar se já assinou (pelo WhatsApp)
    $stmt = $pdo->prepare("
        SELECT id FROM assinaturas_peticoes 
        WHERE peticao_id = ? AND whatsapp = ?
    ");
    $stmt->execute([$peticao_id, $whatsapp]);
    
    if ($stmt->fetch()) {
        throw new Exception('Você já assinou esta petição!');
    }
    
    // Inserir assinatura
    $stmt = $pdo->prepare("
        INSERT INTO assinaturas_peticoes 
        (peticao_id, nome, email, whatsapp, cidade, referencia, data_assinatura) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $peticao_id,
        $nome,
        $email ?: null,
        $whatsapp,
        $cidade,
        $referencia ?: null
    ]);
    
    // Registrar log
    registrar_log('assinatura_peticao', 'assinaturas_peticoes', $pdo->lastInsertId(), 
        "Nova assinatura em: " . $peticao['titulo']);
    
    // Buscar total atualizado
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM assinaturas_peticoes WHERE peticao_id = ?");
    $stmt->execute([$peticao_id]);
    $total = $stmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Obrigado por apoiar esta causa! Sua assinatura foi registrada com sucesso.',
        'total' => $total
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>