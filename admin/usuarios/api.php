<?php
require_once '../../config.php';
verificar_autenticacao();

// Verificar se é admin
if ($_SESSION['usuario_tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

header('Content-Type: application/json');

try {
    $pdo = conectar_db();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'salvar':
            $id = (int)($_POST['id'] ?? 0);
            $nome = sanitizar($_POST['nome'] ?? '');
            $email = sanitizar($_POST['email'] ?? '', 'email');
            $senha = $_POST['senha'] ?? '';
            $tipo = $_POST['tipo'] ?? 'admin';
            $ativo = (int)($_POST['ativo'] ?? 1);
            $evento_permitido_id = (!empty($_POST['evento_permitido_id'])) ? (int)$_POST['evento_permitido_id'] : null;
            
            // Debug
            error_log("Salvando usuário - Nome: $nome, Email: $email, Tipo: $tipo, Ativo: $ativo");
            
            // Validações
            if (empty($nome) || empty($email)) {
                throw new Exception('Nome e email são obrigatórios');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido');
            }
            
            if (!in_array($tipo, ['admin', 'checkin', 'organizador'])) {
                $tipo = 'admin';
            }
            
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios_sistema WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Este email já está em uso');
            }
            
            if ($id > 0) {
                // Editar
                try {
                    if (!empty($senha)) {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios_sistema 
                            SET nome = ?, email = ?, senha = ?, tipo = ?, ativo = ?, evento_permitido_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $nome, 
                            $email, 
                            password_hash($senha, PASSWORD_DEFAULT), 
                            $tipo, 
                            $ativo, 
                            $evento_permitido_id, 
                            $id
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE usuarios_sistema 
                            SET nome = ?, email = ?, tipo = ?, ativo = ?, evento_permitido_id = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $nome, 
                            $email, 
                            $tipo, 
                            $ativo, 
                            $evento_permitido_id, 
                            $id
                        ]);
                    }
                    
                    registrar_log('usuario_editado', 'usuarios_sistema', $id, "Usuário editado: $nome");
                    $message = 'Usuário atualizado com sucesso';
                } catch (PDOException $e) {
                    error_log("Erro PDO ao atualizar: " . $e->getMessage());
                    throw new Exception('Erro ao atualizar usuário: ' . $e->getMessage());
                }
            } else {
                // Criar novo usuário
                if (empty($senha)) {
                    throw new Exception('Senha é obrigatória para novo usuário');
                }
                
                try {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios_sistema 
                        (nome, email, senha, tipo, ativo, evento_permitido_id, data_criacao) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $result = $stmt->execute([
                        $nome, 
                        $email, 
                        $senha_hash, 
                        $tipo, 
                        $ativo, 
                        $evento_permitido_id
                    ]);
                    
                    if (!$result) {
                        $errorInfo = $stmt->errorInfo();
                        error_log("Erro SQL: " . print_r($errorInfo, true));
                        throw new Exception('Erro ao inserir usuário no banco de dados');
                    }
                    
                    $novo_id = $pdo->lastInsertId();
                    registrar_log('usuario_criado', 'usuarios_sistema', $novo_id, "Novo usuário criado: $nome");
                    $message = 'Usuário criado com sucesso';
                    
                } catch (PDOException $e) {
                    error_log("Erro PDO ao inserir: " . $e->getMessage());
                    error_log("SQL State: " . $e->getCode());
                    throw new Exception('Erro ao criar usuário: ' . $e->getMessage());
                }
            }
            
            echo json_encode(['success' => true, 'message' => $message]);
            break;
            
        case 'buscar':
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT id, nome, email, tipo, ativo, evento_permitido_id 
                FROM usuarios_sistema 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            echo json_encode(['success' => true, 'usuario' => $usuario]);
            break;
            
        case 'alterar_status':
            $id = (int)($_POST['id'] ?? 0);
            $ativo = (int)($_POST['ativo'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            // Não pode desativar a si mesmo
            if ($id == $_SESSION['usuario_id'] && !$ativo) {
                throw new Exception('Você não pode desativar sua própria conta');
            }
            
            // Verificar se é o último admin ativo
            if (!$ativo) {
                $stmt = $pdo->prepare("SELECT tipo FROM usuarios_sistema WHERE id = ?");
                $stmt->execute([$id]);
                $usuario = $stmt->fetch();
                
                if ($usuario && $usuario['tipo'] == 'admin') {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema WHERE tipo = 'admin' AND ativo = 1 AND id != " . $id);
                    $outros_admins = $stmt->fetchColumn();
                    
                    if ($outros_admins == 0) {
                        throw new Exception('Não é possível desativar o último administrador ativo');
                    }
                }
            }
            
            $stmt = $pdo->prepare("UPDATE usuarios_sistema SET ativo = ? WHERE id = ?");
            $stmt->execute([$ativo, $id]);
            
            $acao = $ativo ? 'ativado' : 'desativado';
            registrar_log('usuario_status', 'usuarios_sistema', $id, "Usuário $acao");
            
            echo json_encode([
                'success' => true, 
                'message' => "Usuário $acao com sucesso"
            ]);
            break;
            
        case 'excluir':
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }
            
            // Não pode excluir a si mesmo
            if ($id == $_SESSION['usuario_id']) {
                throw new Exception('Você não pode excluir sua própria conta');
            }
            
            // Verificar se é o último admin
            $stmt = $pdo->prepare("SELECT tipo FROM usuarios_sistema WHERE id = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();
            
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            if ($usuario['tipo'] == 'admin') {
                $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios_sistema WHERE tipo = 'admin' AND id != " . $id);
                $outros_admins = $stmt->fetchColumn();
                
                if ($outros_admins == 0) {
                    throw new Exception('Não é possível excluir o último administrador');
                }
            }
            
            // Excluir usuário
            $stmt = $pdo->prepare("DELETE FROM usuarios_sistema WHERE id = ?");
            $stmt->execute([$id]);
            
            registrar_log('usuario_excluido', 'usuarios_sistema', $id, "Usuário excluído");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Usuário excluído com sucesso'
            ]);
            break;
            
        default:
            throw new Exception('Ação inválida: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Erro geral na API de usuários: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'action' => $action,
            'post' => $_POST,
            'get' => $_GET
        ]
    ]);
}
?>