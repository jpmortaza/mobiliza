<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$email = sanitizar($_POST['email'] ?? '', 'email');
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($email) || empty($senha)) {
    header("Location: index.php?erro=" . urlencode("Email e senha são obrigatórios."));
    exit();
}

try {
    $pdo = conectar_db();
    
    // Buscar usuário por email
    $stmt = $pdo->prepare("
        SELECT id, nome, email, senha, tipo, evento_permitido_id, ativo 
        FROM usuarios_sistema 
        WHERE email = ? AND ativo = 1
    ");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        registrar_log("tentativa_login_falhou", null, null, "Email não encontrado: $email");
        header("Location: index.php?erro=" . urlencode("Email ou senha inválidos."));
        exit();
    }
    
    // Verificar senha
    if (!password_verify($senha, $usuario['senha'])) {
        registrar_log("tentativa_login_falhou", 'usuarios_sistema', $usuario['id'], "Senha incorreta");
        header("Location: index.php?erro=" . urlencode("Email ou senha inválidos."));
        exit();
    }
    
    // Login bem-sucedido - criar sessão
    session_regenerate_id(true); // Segurança contra session fixation
    
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];
    
    // Para usuários de check-in, salvar evento permitido
    if ($usuario['tipo'] === 'checkin' && $usuario['evento_permitido_id']) {
        $_SESSION['evento_permitido_id'] = $usuario['evento_permitido_id'];
    }
    
    // Atualizar último login
    $stmt = $pdo->prepare("
        UPDATE usuarios_sistema 
        SET ultimo_login = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$usuario['id']]);
    
    // Registrar log de sucesso
    registrar_log("login_realizado", 'usuarios_sistema', $usuario['id'], "Login bem-sucedido");
    
    // Redirecionar baseado no tipo de usuário
    switch ($usuario['tipo']) {
        case 'admin':
        case 'organizador':
            header("Location: dashboard.php");
            break;
        case 'checkin':
            // Verificar se tem evento permitido
            if ($usuario['evento_permitido_id']) {
                header("Location: ../eventos/checkin/");
            } else {
                header("Location: index.php?erro=" . urlencode("Usuário de check-in sem evento associado."));
            }
            break;
        default:
            header("Location: dashboard.php");
    }
    
} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    header("Location: index.php?erro=" . urlencode("Erro interno. Tente novamente."));
} catch (Exception $e) {
    error_log("Erro geral no login: " . $e->getMessage());
    header("Location: index.php?erro=" . urlencode("Erro interno. Tente novamente."));
}

exit();