<?php
// Desabilitar qualquer output antes do JSON
ob_start();

// Mostrar todos os erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config.php';

// Limpar qualquer output anterior
ob_clean();

// Verificar se as funções existem
if (!function_exists('verificar_autenticacao')) {
    die(json_encode(['success' => false, 'message' => 'Função verificar_autenticacao não existe']));
}

if (!function_exists('sanitizar')) {
    die(json_encode(['success' => false, 'message' => 'Função sanitizar não existe']));
}

if (!function_exists('conectar_db')) {
    die(json_encode(['success' => false, 'message' => 'Função conectar_db não existe']));
}

if (!function_exists('registrar_log')) {
    die(json_encode(['success' => false, 'message' => 'Função registrar_log não existe']));
}

// Verificar autenticação
try {
    verificar_autenticacao();
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Erro na autenticação: ' . $e->getMessage()]));
}

// Verificar se é admin
if (!isset($_SESSION['usuario_tipo']) || $_SESSION['usuario_tipo'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Acesso negado - não é admin']));
}

header('Content-Type: application/json');

try {
    $pdo = conectar_db();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if ($action === 'salvar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $tipo = $_POST['tipo'] ?? 'admin';
        $ativo = (int)($_POST['ativo'] ?? 1);
        
        // Validações básicas
        if (empty($nome) || empty($email)) {
            throw new Exception('Nome e email são obrigatórios');
        }
        
        if ($id == 0 && empty($senha)) {
            throw new Exception('Senha é obrigatória para novo usuário');
        }
        
        // Tentar inserir
        if ($id == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios_sistema (nome, email, senha, tipo, ativo, data_criacao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $nome,
                $email,
                password_hash($senha, PASSWORD_DEFAULT),
                $tipo,
                $ativo
            ]);
            
            if (!$result) {
                throw new Exception('Erro ao inserir: ' . implode(' - ', $stmt->errorInfo()));
            }
            
            echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso']);
        }
    } else {
        throw new Exception('Ação não implementada: ' . $action);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Garantir que nada mais seja enviado
exit();
?>