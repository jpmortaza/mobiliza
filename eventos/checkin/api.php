<?php
// Define o tipo de conteúdo da resposta como JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config.php';

// Inicia a sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função para enviar resposta JSON e sair
function enviarResposta($sucesso, $mensagem = '', $dados = null) {
    $resposta = [
        'success' => $sucesso,
        'message' => $mensagem
    ];
    
    if ($dados !== null) {
        $resposta['data'] = $dados;
    }
    
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);
    exit();
}

// Pegar a ação da requisição
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Rota de autenticação
if ($action === 'login') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($usuario) || empty($senha)) {
        enviarResposta(false, 'Usuário e senha são obrigatórios.');
    }
    
    $conn = conectar_db();
    $stmt = $conn->prepare("
        SELECT u.id, u.usuario, u.tipo, u.evento_permitido_id, e.titulo as evento_titulo 
        FROM admin_eventos_usuarios u 
        LEFT JOIN eventos e ON u.evento_permitido_id = e.id 
        WHERE u.usuario = ? AND u.tipo = 'checkin'
    ");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['checkin_usuario_id'] = $user['id'];
        $_SESSION['checkin_evento_id'] = $user['evento_permitido_id'];
        $_SESSION['checkin_evento_titulo'] = $user['evento_titulo'];
        $_SESSION['checkin_logado'] = true;
        
        enviarResposta(true, 'Login realizado com sucesso', [
            'evento_id' => $user['evento_permitido_id'],
            'evento_titulo' => $user['evento_titulo']
        ]);
    } else {
        enviarResposta(false, 'Usuário ou senha inválidos.');
    }
}

// Middleware de autenticação para outras rotas
if (!isset($_SESSION['checkin_logado']) || !$_SESSION['checkin_logado']) {
    enviarResposta(false, 'Acesso não autorizado. Faça login primeiro.');
}

$evento_permitido_id = $_SESSION['checkin_evento_id'];

switch ($action) {
    case 'get_inscritos':
        $busca = trim($_GET['search'] ?? '');
        
        $conn = conectar_db();
        
        $sql = "
            SELECT id, nome, whatsapp, cidade, checkin, data_inscricao 
            FROM inscricoes_eventos 
            WHERE evento_id = ?
        ";
        $params = [$evento_permitido_id];
        $types = "i";
        
        if (!empty($busca)) {
            $sql .= " AND (nome LIKE ? OR whatsapp LIKE ?)";
            $termoBusca = "%{$busca}%";
            $params[] = $termoBusca;
            $params[] = $termoBusca;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY nome ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $inscritos = [];
        while ($row = $result->fetch_assoc()) {
            $inscritos[] = [
                'id' => (int)$row['id'],
                'nome' => $row['nome'],
                'whatsapp' => $row['whatsapp'],
                'cidade' => $row['cidade'],
                'checkin' => (int)$row['checkin'],
                'data_inscricao' => $row['data_inscricao']
            ];
        }
        
        $stmt->close();
        $conn->close();
        
        enviarResposta(true, 'Inscritos carregados com sucesso', $inscritos);
        break;
        
    case 'toggle_checkin':
        $inscrito_id = filter_input(INPUT_POST, 'inscrito_id', FILTER_VALIDATE_INT);
        
        if (!$inscrito_id) {
            enviarResposta(false, 'ID do inscrito inválido.');
        }
        
        $conn = conectar_db();
        
        // Verificação de segurança: garantir que o inscrito pertence ao evento permitido
        $stmt_check = $conn->prepare("
            SELECT id, checkin FROM inscricoes_eventos 
            WHERE id = ? AND evento_id = ?
        ");
        $stmt_check->bind_param("ii", $inscrito_id, $evento_permitido_id);
        $stmt_check->execute();
        $inscrito = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();
        
        if (!$inscrito) {
            $conn->close();
            enviarResposta(false, 'Inscrito não encontrado ou sem permissão.');
        }
        
        // Alternar status do check-in
        $novo_status = $inscrito['checkin'] ? 0 : 1;
        
        $stmt_update = $conn->prepare("
            UPDATE inscricoes_eventos 
            SET checkin = ?, data_checkin = ? 
            WHERE id = ?
        ");
        $data_checkin = $novo_status ? date('Y-m-d H:i:s') : null;
        $stmt_update->bind_param("isi", $novo_status, $data_checkin, $inscrito_id);
        
        if ($stmt_update->execute()) {
            $stmt_update->close();
            $conn->close();
            
            $status_texto = $novo_status ? 'realizado' : 'cancelado';
            enviarResposta(true, "Check-in {$status_texto} com sucesso", [
                'novo_status' => $novo_status
            ]);
        } else {
            $stmt_update->close();
            $conn->close();
            enviarResposta(false, 'Erro ao atualizar check-in.');
        }
        break;
        
    case 'get_stats':
        $conn = conectar_db();
        
        $stmt_stats = $conn->prepare("
            SELECT 
                COUNT(*) as total_inscritos,
                SUM(checkin) as total_checkins,
                (COUNT(*) - SUM(checkin)) as pendentes
            FROM inscricoes_eventos 
            WHERE evento_id = ?
        ");
        $stmt_stats->bind_param("i", $evento_permitido_id);
        $stmt_stats->execute();
        $stats = $stmt_stats->get_result()->fetch_assoc();
        $stmt_stats->close();
        $conn->close();
        
        enviarResposta(true, 'Estatísticas carregadas', [
            'total_inscritos' => (int)$stats['total_inscritos'],
            'total_checkins' => (int)$stats['total_checkins'],
            'pendentes' => (int)$stats['pendentes'],
            'percentual_checkin' => $stats['total_inscritos'] > 0 
                ? round(($stats['total_checkins'] / $stats['total_inscritos']) * 100, 1) 
                : 0
        ]);
        break;
        
    case 'logout':
        session_unset();
        session_destroy();
        enviarResposta(true, 'Logout realizado com sucesso');
        break;
        
    default:
        enviarResposta(false, 'Ação inválida.');
        break;
}
?>