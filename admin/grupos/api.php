<?php
/**
 * MOBILIZA+ - Módulo Grupos (Admin)
 * Arquivo: admin/grupos/api.php
 * Descrição: API unificada para todas as ações do painel de administração de grupos.
 */

require_once __DIR__ . '/../../config.php';
header("Content-Type: application/json");

// Verificar se está logado
if (!verificar_login()) {
    echo json_encode(["status" => "erro", "msg" => "Acesso não autorizado."]);
    exit;
}

try {
    $pdo = conectar_db();
    $action = $_POST['action'] ?? $_GET['action'] ?? 'listar';

    switch ($action) {
        case 'estatisticas':
            obterEstatisticas($pdo);
            break;
            
        case 'cadastros':
            listarCadastros($pdo);
            break;
            
        case 'exportar_cadastros_csv':
            exportarCadastrosCsv($pdo);
            break;
            
        case 'grupos':
            listarGrupos($pdo);
            break;
            
        case 'compartilhadores':
            listarCompartilhadores($pdo);
            break;
            
        case 'atualizar_link':
            atualizarLinkGrupo($pdo);
            break;
            
        case 'aprovar':
            aprovarCompartilhador($pdo);
            break;
            
        case 'rejeitar':
            rejeitarCompartilhador($pdo);
            break;
            
        case 'cadastros_por_cidade':
            listarCadastrosPorCidade($pdo);
            break;
            
        default:
            echo json_encode(["status" => "erro", "msg" => "Ação não especificada."]);
    }

} catch (Exception $e) {
    error_log("Erro na API de grupos: " . $e->getMessage());
    echo json_encode(["status" => "erro", "msg" => "Erro interno do sistema."]);
}

/**
 * Funções da API
 */

function obterEstatisticas($pdo) {
    $sql = "SELECT 
        (SELECT COUNT(*) FROM cadastro) as total_cadastros,
        (SELECT COUNT(*) FROM cadastro WHERE DATE(data_cadastro) = CURDATE()) as cadastros_hoje,
        (SELECT COUNT(*) FROM compartilhadores WHERE status = 'aprovado') as total_compartilhadores,
        (SELECT COUNT(*) FROM indicacoes) as total_indicacoes";
    
    $stmt = $pdo->query($sql);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "sucesso",
        "dados" => [
            "total_cadastros" => (int)$dados['total_cadastros'],
            "cadastros_hoje" => (int)$dados['cadastros_hoje'],
            "total_compartilhadores" => (int)$dados['total_compartilhadores'],
            "total_indicacoes" => (int)$dados['total_indicacoes']
        ]
    ]);
}

function listarCadastros($pdo) {
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT c.*, 
            COALESCE(comp.nome, 'Cadastro direto') as indicado_por
            FROM cadastro c
            LEFT JOIN indicacoes i ON c.id = i.cadastro_id
            LEFT JOIN compartilhadores comp ON i.compartilhador_id = comp.id";
    
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE c.nome LIKE ? OR c.whatsapp LIKE ? OR c.cidade LIKE ?";
        $params = ["%" . $search . "%", "%" . $search . "%", "%" . $search . "%"];
    }
    
    $sql .= " ORDER BY c.data_cadastro DESC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($cadastros as &$row) {
        $row['data_cadastro_formatada'] = formatar_data_br($row['data_cadastro']);
    }
    
    echo json_encode([
        "status" => "sucesso",
        "dados" => $cadastros
    ]);
}

function exportarCadastrosCsv($pdo) {
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT c.nome, c.whatsapp, c.cidade, c.voluntario, c.data_cadastro, 
            COALESCE(comp.nome, 'Cadastro direto') as indicado_por
            FROM cadastro c
            LEFT JOIN indicacoes i ON c.id = i.cadastro_id
            LEFT JOIN compartilhadores comp ON i.compartilhador_id = comp.id";
    
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE c.nome LIKE ? OR c.whatsapp LIKE ? OR c.cidade LIKE ?";
        $params = ["%" . $search . "%", "%" . $search . "%", "%" . $search . "%"];
    }
    
    $sql .= " ORDER BY c.data_cadastro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=cadastros_exportacao.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    $headers = ['Nome', 'WhatsApp', 'Cidade', 'Voluntário', 'Data do Cadastro', 'Indicado Por'];
    fputcsv($output, $headers, ';');
    
    foreach ($result as $row) {
        $row['data_cadastro'] = formatar_data_br($row['data_cadastro']);
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit();
}

function listarGrupos($pdo) {
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT * FROM cidades_grupos";
    $params = [];
    if (!empty($search)) {
        $sql .= " WHERE cidade LIKE ?";
        $params = ["%" . $search . "%"];
    }
    $sql .= " ORDER BY cidade ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "sucesso", "dados" => $grupos]);
}

function listarCompartilhadores($pdo) {
    $sql = "SELECT c.*, COUNT(i.id) as total_indicacoes 
            FROM compartilhadores c 
            LEFT JOIN indicacoes i ON c.id = i.compartilhador_id 
            GROUP BY c.id 
            ORDER BY c.data_solicitacao DESC";
    
    $stmt = $pdo->query($sql);
    $compartilhadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($compartilhadores as &$row) {
        $row['data_solicitacao_formatada'] = formatar_data_br($row['data_solicitacao']);
        $row['data_aprovacao_formatada'] = $row['data_aprovacao'] ? formatar_data_br($row['data_aprovacao']) : null;
    }
    
    echo json_encode(["status" => "sucesso", "dados" => $compartilhadores]);
}

function atualizarLinkGrupo($pdo) {
    $id = $_POST['id'] ?? '';
    $link_whatsapp = $_POST['link_whatsapp'] ?? '';
    
    if (empty($id) || empty($link_whatsapp)) {
        echo json_encode(["status" => "erro", "msg" => "Dados incompletos."]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE cidades_grupos SET link_whatsapp = ? WHERE id = ?");
    $stmt->execute([$link_whatsapp, $id]);
    
    echo json_encode(["status" => "sucesso", "msg" => "Link atualizado com sucesso!"]);
}

function aprovarCompartilhador($pdo) {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(["status" => "erro", "msg" => "ID não informado."]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE compartilhadores SET status = 'aprovado', data_aprovacao = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(["status" => "sucesso", "msg" => "Mobilizador aprovado!"]);
}

function rejeitarCompartilhador($pdo) {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(["status" => "erro", "msg" => "ID não informado."]);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE compartilhadores SET status = 'rejeitado' WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(["status" => "sucesso", "msg" => "Mobilizador rejeitado."]);
}

function listarCadastrosPorCidade($pdo) {
    $sql = "SELECT cidade, COUNT(id) as total FROM cadastro GROUP BY cidade ORDER BY total DESC";
    $stmt = $pdo->query($sql);
    $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        "status" => "sucesso",
        "dados" => $cidades
    ]);
}
?>
