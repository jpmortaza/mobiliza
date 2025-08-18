<?php
require_once '../../config.php';

// Verificar se está logado
if (!verificar_login()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = conectar_db();
    
    switch ($action) {
        case 'listar':
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       COUNT(a.id) as total_assinaturas,
                       u.nome as criador_nome
                FROM peticoes p 
                LEFT JOIN assinaturas_peticoes a ON p.id = a.peticao_id 
                LEFT JOIN usuarios_sistema u ON p.criado_por = u.id
                WHERE p.ativo = 1 
                GROUP BY p.id 
                ORDER BY p.data_criacao DESC
            ");
            $stmt->execute();
            $peticoes = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $peticoes]);
            break;
            
        case 'obter':
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID da petição não fornecido');
            }
            
            // Buscar petição
            $stmt = $pdo->prepare("SELECT * FROM peticoes WHERE id = ? AND ativo = 1");
            $stmt->execute([$id]);
            $peticao = $stmt->fetch();
            
            if (!$peticao) {
                throw new Exception('Petição não encontrada');
            }
            
            // Buscar protagonistas
            $stmt = $pdo->prepare("
                SELECT * FROM protagonistas_peticao 
                WHERE peticao_id = ? 
                ORDER BY ordem ASC
            ");
            $stmt->execute([$id]);
            $protagonistas = $stmt->fetchAll();
            
            $peticao['protagonistas'] = $protagonistas;
            
            echo json_encode(['success' => true, 'data' => $peticao]);
            break;
            
        case 'salvar':
            $pdo->beginTransaction();
            
            try {
                $id = $_POST['id'] ?? null;
                $titulo = sanitizar($_POST['titulo'] ?? '');
                $slug = $_POST['slug'] ?? '';
                $descricao = sanitizar($_POST['descricao'] ?? '');
                $meta_assinaturas = (int)($_POST['meta_assinaturas'] ?? 0);
                $criador_titulo = sanitizar($_POST['criador_titulo'] ?? 'Criadores');
                $form_titulo = sanitizar($_POST['form_titulo'] ?? 'Assine este abaixo-assinado');
                $form_botao_texto = sanitizar($_POST['form_botao_texto'] ?? 'Eu apoio');
                
                // Validações
                if (empty($titulo)) {
                    throw new Exception('Título é obrigatório');
                }
                
                // Gerar/validar slug
                if (empty($slug)) {
                    $slug = gerar_slug($titulo, 'peticoes', 'slug', $id);
                } else {
                    $slug = sanitizar($slug, 'slug');
                    
                    // Verificar se slug já existe
                    $stmt = $pdo->prepare("SELECT id FROM peticoes WHERE slug = ? AND id != ?");
                    $stmt->execute([$slug, $id ?: 0]);
                    if ($stmt->fetch()) {
                        throw new Exception('Este slug já está em uso');
                    }
                }
                
                // Upload da imagem de cabeçalho
                $imagem_cabecalho = $_POST['imagem_cabecalho_atual'] ?? '';
                if (isset($_FILES['imagem_cabecalho']) && $_FILES['imagem_cabecalho']['error'] === UPLOAD_ERR_OK) {
                    $imagem_cabecalho = fazer_upload($_FILES['imagem_cabecalho'], 'apoie');
                }
                
                if ($id) {
                    // Atualizar petição existente
                    $stmt = $pdo->prepare("
                        UPDATE peticoes SET 
                        titulo = ?, slug = ?, descricao = ?, meta_assinaturas = ?,
                        criador_titulo = ?, form_titulo = ?, form_botao_texto = ?,
                        imagem_cabecalho = ?, data_atualizacao = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $titulo, $slug, $descricao, $meta_assinaturas,
                        $criador_titulo, $form_titulo, $form_botao_texto,
                        $imagem_cabecalho, $id
                    ]);
                    
                    registrar_log('peticao_atualizada', 'peticoes', $id, "Petição atualizada: $titulo");
                } else {
                    // Criar nova petição
                    $stmt = $pdo->prepare("
                        INSERT INTO peticoes 
                        (titulo, slug, descricao, meta_assinaturas, criador_titulo, 
                         form_titulo, form_botao_texto, imagem_cabecalho, criado_por) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $titulo, $slug, $descricao, $meta_assinaturas, $criador_titulo,
                        $form_titulo, $form_botao_texto, $imagem_cabecalho,
                        $_SESSION['usuario_id']
                    ]);
                    
                    $id = $pdo->lastInsertId();
                    registrar_log('peticao_criada', 'peticoes', $id, "Petição criada: $titulo");
                }
                
                // Gerenciar protagonistas
                if (isset($_POST['protagonistas']) && is_array($_POST['protagonistas'])) {
                    // Remover protagonistas existentes
                    $stmt = $pdo->prepare("DELETE FROM protagonistas_peticao WHERE peticao_id = ?");
                    $stmt->execute([$id]);
                    
                    // Adicionar novos protagonistas
                    $stmt = $pdo->prepare("
                        INSERT INTO protagonistas_peticao 
                        (peticao_id, nome, descricao, foto_url, instagram_url, linkedin_url, ordem) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($_POST['protagonistas'] as $index => $protagonista) {
                        if (empty($protagonista['nome'])) continue;
                        
                        $foto_url = $protagonista['foto_atual'] ?? '';
                        
                        // Upload da foto do protagonista
                        if (isset($_FILES["protagonista_foto_$index"]) && 
                            $_FILES["protagonista_foto_$index"]['error'] === UPLOAD_ERR_OK) {
                            $foto_url = fazer_upload($_FILES["protagonista_foto_$index"], 'apoie');
                        }
                        
                        $stmt->execute([
                            $id,
                            sanitizar($protagonista['nome']),
                            sanitizar($protagonista['descricao'] ?? ''),
                            $foto_url,
                            sanitizar($protagonista['instagram'] ?? '', 'url'),
                            sanitizar($protagonista['linkedin'] ?? '', 'url'),
                            $index
                        ]);
                    }
                }
                
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Petição salva com sucesso!',
                    'data' => ['id' => $id, 'slug' => $slug]
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'excluir':
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID da petição não fornecido');
            }
            
            // Verificar se petição existe
            $stmt = $pdo->prepare("SELECT titulo FROM peticoes WHERE id = ?");
            $stmt->execute([$id]);
            $peticao = $stmt->fetch();
            
            if (!$peticao) {
                throw new Exception('Petição não encontrada');
            }
            
            // Soft delete (marcar como inativo)
            $stmt = $pdo->prepare("UPDATE peticoes SET ativo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            registrar_log('peticao_excluida', 'peticoes', $id, "Petição excluída: " . $peticao['titulo']);
            
            echo json_encode(['success' => true, 'message' => 'Petição excluída com sucesso!']);
            break;
            
        case 'assinaturas':
            $peticao_id = $_GET['peticao_id'] ?? 0;
            $search = $_GET['search'] ?? '';
            $page = max(1, $_GET['page'] ?? 1);
            $limit = 50;
            $offset = ($page - 1) * $limit;
            
            $where = [];
            $params = [];
            
            if ($peticao_id) {
                $where[] = "a.peticao_id = ?";
                $params[] = $peticao_id;
            }
            
            if ($search) {
                $where[] = "(a.nome LIKE ? OR a.whatsapp LIKE ? OR a.cidade LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Contar total
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM assinaturas_peticoes a 
                JOIN peticoes p ON a.peticao_id = p.id 
                $whereClause
            ");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Buscar registros
            $stmt = $pdo->prepare("
                SELECT a.*, p.titulo as peticao_titulo 
                FROM assinaturas_peticoes a 
                JOIN peticoes p ON a.peticao_id = p.id 
                $whereClause 
                ORDER BY a.data_assinatura DESC 
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $assinaturas = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $assinaturas,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / $limit),
                    'limit' => $limit
                ]
            ]);
            break;
            
        case 'exportar_assinaturas_csv':
            $peticao_id = $_GET['peticao_id'] ?? 0;
            $search = $_GET['search'] ?? '';
            
            $where = [];
            $params = [];
            
            if ($peticao_id) {
                $where[] = "a.peticao_id = ?";
                $params[] = $peticao_id;
            }
            
            if ($search) {
                $where[] = "(a.nome LIKE ? OR a.whatsapp LIKE ? OR a.cidade LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $stmt = $pdo->prepare("
                SELECT a.nome, a.whatsapp, a.email, a.cidade, a.referencia, 
                       p.titulo as peticao_titulo, a.data_assinatura
                FROM assinaturas_peticoes a 
                JOIN peticoes p ON a.peticao_id = p.id 
                $whereClause 
                ORDER BY a.data_assinatura DESC
            ");
            $stmt->execute($params);
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=assinaturas_peticoes_' . date('Y-m-d_H-i') . '.csv');
            
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para UTF-8
            
            // Cabeçalhos
            fputcsv($output, [
                'Nome',
                'WhatsApp', 
                'Email',
                'Cidade',
                'Petição',
                'Data Assinatura',
                'Referência'
            ], ';');
            
            // Dados
            while ($row = $stmt->fetch()) {
                fputcsv($output, [
                    $row['nome'],
                    $row['whatsapp'],
                    $row['email'] ?: '',
                    $row['cidade'],
                    $row['peticao_titulo'],
                    date('d/m/Y H:i', strtotime($row['data_assinatura'])),
                    $row['referencia'] ?: ''
                ], ';');
            }
            
            fclose($output);
            exit();
            
        case 'toggle_ativo':
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID da petição não fornecido');
            }
            
            $stmt = $pdo->prepare("
                UPDATE peticoes 
                SET ativo = 1 - ativo 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            // Buscar status atual
            $stmt = $pdo->prepare("SELECT ativo, titulo FROM peticoes WHERE id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            registrar_log('peticao_status_alterado', 'peticoes', $id, 
                "Petição " . ($result['ativo'] ? 'ativada' : 'desativada') . ": " . $result['titulo']);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Status da petição alterado!',
                'ativo' => (bool)$result['ativo']
            ]);
            break;
            
        case 'duplicar':
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID da petição não fornecido');
            }
            
            $pdo->beginTransaction();
            
            try {
                // Buscar petição original
                $stmt = $pdo->prepare("SELECT * FROM peticoes WHERE id = ?");
                $stmt->execute([$id]);
                $peticao_original = $stmt->fetch();
                
                if (!$peticao_original) {
                    throw new Exception('Petição não encontrada');
                }
                
                // Criar nova petição duplicada
                $novo_titulo = $peticao_original['titulo'] . ' - Cópia';
                $novo_slug = gerar_slug($novo_titulo, 'peticoes');
                
                $stmt = $pdo->prepare("
                    INSERT INTO peticoes 
                    (titulo, slug, descricao, meta_assinaturas, criador_titulo, 
                     form_titulo, form_botao_texto, imagem_cabecalho, criado_por) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $novo_titulo,
                    $novo_slug,
                    $peticao_original['descricao'],
                    $peticao_original['meta_assinaturas'],
                    $peticao_original['criador_titulo'],
                    $peticao_original['form_titulo'],
                    $peticao_original['form_botao_texto'],
                    $peticao_original['imagem_cabecalho'],
                    $_SESSION['usuario_id']
                ]);
                
                $nova_peticao_id = $pdo->lastInsertId();
                
                // Duplicar protagonistas
                $stmt = $pdo->prepare("
                    SELECT * FROM protagonistas_peticao 
                    WHERE peticao_id = ? 
                    ORDER BY ordem ASC
                ");
                $stmt->execute([$id]);
                $protagonistas = $stmt->fetchAll();
                
                if ($protagonistas) {
                    $stmt = $pdo->prepare("
                        INSERT INTO protagonistas_peticao 
                        (peticao_id, nome, descricao, foto_url, instagram_url, linkedin_url, ordem) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($protagonistas as $protagonista) {
                        $stmt->execute([
                            $nova_peticao_id,
                            $protagonista['nome'],
                            $protagonista['descricao'],
                            $protagonista['foto_url'],
                            $protagonista['instagram_url'],
                            $protagonista['linkedin_url'],
                            $protagonista['ordem']
                        ]);
                    }
                }
                
                $pdo->commit();
                
                registrar_log('peticao_duplicada', 'peticoes', $nova_peticao_id, "Petição duplicada de: " . $peticao_original['titulo']);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Petição duplicada com sucesso!',
                    'data' => ['id' => $nova_peticao_id, 'slug' => $novo_slug]
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'estatisticas':
            $id = $_GET['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID da petição não fornecido');
            }
            
            // Estatísticas gerais
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_assinaturas,
                    COUNT(DISTINCT cidade) as cidades_diferentes,
                    COUNT(DISTINCT DATE(data_assinatura)) as dias_ativos,
                    COUNT(CASE WHEN referencia IS NOT NULL AND referencia != '' THEN 1 END) as via_referencia
                FROM assinaturas_peticoes 
                WHERE peticao_id = ?
            ");
            $stmt->execute([$id]);
            $stats_gerais = $stmt->fetch();
            
            // Assinaturas por dia (últimos 30 dias)
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(data_assinatura) as data,
                    COUNT(*) as total
                FROM assinaturas_peticoes 
                WHERE peticao_id = ? 
                AND data_assinatura >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(data_assinatura)
                ORDER BY data DESC
            ");
            $stmt->execute([$id]);
            $assinaturas_por_dia = $stmt->fetchAll();
            
            // Top cidades
            $stmt = $pdo->prepare("
                SELECT cidade, COUNT(*) as total
                FROM assinaturas_peticoes 
                WHERE peticao_id = ? AND cidade != ''
                GROUP BY cidade 
                ORDER BY total DESC 
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $top_cidades = $stmt->fetchAll();
            
            // Top referências
            $stmt = $pdo->prepare("
                SELECT referencia, COUNT(*) as total
                FROM assinaturas_peticoes 
                WHERE peticao_id = ? AND referencia IS NOT NULL AND referencia != ''
                GROUP BY referencia 
                ORDER BY total DESC 
                LIMIT 10
            ");
            $stmt->execute([$id]);
            $top_referencias = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'geral' => $stats_gerais,
                    'assinaturas_por_dia' => $assinaturas_por_dia,
                    'top_cidades' => $top_cidades,
                    'top_referencias' => $top_referencias
                ]
            ]);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>