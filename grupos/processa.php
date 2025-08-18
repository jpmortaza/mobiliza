<?php
/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/processa.php
 * Descrição: Script unificado para processar cadastros de participantes e mobilizadores.
 */

require_once __DIR__ . '/../config.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'cadastro_participante') {
        processarCadastroParticipante();
    } elseif ($action === 'cadastro_mobilizador') {
        processarCadastroMobilizador();
    } else {
        echo json_encode(["status" => "erro", "msg" => "Ação inválida."]);
        exit;
    }

} catch (Exception $e) {
    error_log("Erro no processamento de formulário: " . $e->getMessage());
    echo json_encode(["status" => "erro", "msg" => "Erro interno do sistema."]);
}

function processarCadastroParticipante() {
    $pdo = conectar_db();
    
    // Receber dados do formulário
    $nome = sanitizar($_POST["nome"] ?? "");
    $whatsapp = sanitizar($_POST["whatsapp"] ?? "", 'telefone');
    $cidade = sanitizar($_POST["cidade"] ?? "");
    $voluntario = sanitizar($_POST["voluntario"] ?? "");
    $ref_link = sanitizar($_POST["ref"] ?? "");
    
    // Validar dados obrigatórios
    if (empty($nome) || empty($whatsapp) || empty($cidade) || empty($voluntario)) {
        throw new Exception("Todos os campos são obrigatórios.");
    }
    
    // Verificar se WhatsApp já existe
    $stmt_check = $pdo->prepare("SELECT id FROM cadastro WHERE whatsapp = ?");
    $stmt_check->execute([$whatsapp]);
    
    if ($stmt_check->fetch()) {
        throw new Exception("Este WhatsApp já está cadastrado.");
    }
    
    $indicador_nome = "sem indicação";
    $compartilhador_id = null;

    if (!empty($ref_link)) {
        $stmt_comp = $pdo->prepare("SELECT id, nome FROM compartilhadores WHERE link_personalizado = ? AND status = 'aprovado'");
        $stmt_comp->execute([$ref_link]);
        $compartilhador = $stmt_comp->fetch(PDO::FETCH_ASSOC);
        
        if ($compartilhador) {
            $compartilhador_id = $compartilhador['id'];
            $indicador_nome = $compartilhador['nome'];
        }
    }
    
    $stmt_cadastro = $pdo->prepare("INSERT INTO cadastro (nome, whatsapp, cidade, voluntario, indicador) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt_cadastro->execute([$nome, $whatsapp, $cidade, $voluntario, $indicador_nome])) {
        throw new Exception("Erro ao salvar dados.");
    }
    
    $cadastro_id = $pdo->lastInsertId();

    if ($compartilhador_id) {
        $ip_origem = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt_indic = $pdo->prepare("INSERT INTO indicacoes (compartilhador_id, cadastro_id, link_usado, ip_origem, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt_indic->execute([$compartilhador_id, $cadastro_id, $ref_link, $ip_origem, $user_agent]);
    }

    $stmt_grupos = $pdo->prepare("SELECT link_whatsapp FROM cidades_grupos WHERE cidade = ?");
    $stmt_grupos->execute([$cidade]);
    $resultado = $stmt_grupos->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        $link_grupo = $resultado['link_whatsapp'];
        $mensagem_sucesso = "Cadastro realizado com sucesso! Redirecionando para o grupo...";
        if ($indicador_nome !== "sem indicação") {
            $mensagem_sucesso .= " Obrigado pela indicação de " . $indicador_nome . "!";
        }
        
        echo json_encode([
            "status" => "sucesso",
            "msg" => $mensagem_sucesso,
            "link" => $link_grupo,
            "cidade" => $cidade
        ]);
    } else {
        echo json_encode([
            "status" => "erro",
            "msg" => "Não há um grupo de WhatsApp cadastrado para esta cidade."
        ]);
    }
}

function processarCadastroMobilizador() {
    $pdo = conectar_db();

    $nome = sanitizar($_POST['nome'] ?? '');
    $email = sanitizar($_POST['email'] ?? '', 'email');
    $whatsapp = sanitizar($_POST['whatsapp'] ?? '', 'telefone');
    $cidade = sanitizar($_POST['cidade'] ?? '');
    $link_personalizado = sanitizar($_POST['link_personalizado'] ?? '', 'slug');
    $aceito_termos = $_POST['aceito_termos'] ?? '';
    
    if (empty($nome) || empty($email) || empty($whatsapp) || empty($cidade) || empty($link_personalizado)) {
        throw new Exception("Todos os campos são obrigatórios.");
    }
    
    if (!$aceito_termos) {
        throw new Exception("Você deve aceitar os termos.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("E-mail inválido.");
    }
    
    if (strlen($whatsapp) < 10 || strlen($whatsapp) > 15) {
        throw new Exception("WhatsApp deve ter entre 10 e 15 dígitos.");
    }
    
    // Verificar se email já existe
    $stmt = $pdo->prepare("SELECT id FROM compartilhadores WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("Este e-mail já está cadastrado.");
    }
    
    // Verificar se link já existe
    $stmt = $pdo->prepare("SELECT id FROM compartilhadores WHERE link_personalizado = ?");
    $stmt->execute([$link_personalizado]);
    if ($stmt->fetch()) {
        throw new Exception("Este link já está em uso.");
    }
    
    $stmt = $pdo->prepare("INSERT INTO compartilhadores (nome, email, whatsapp, cidade, link_personalizado) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$nome, $email, $whatsapp, $cidade, $link_personalizado])) {
        $link_completo = SITE_URL . '/grupos/index.php?ref=' . $link_personalizado;
        
        echo json_encode([
            "status" => "sucesso",
            "msg" => "Solicitação enviada! Aguarde aprovação da administração.",
            "link_completo" => $link_completo
        ]);
    } else {
        throw new Exception("Erro ao salvar dados.");
    }
}
