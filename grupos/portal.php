<?php
/**
 * MOBILIZA+ - Módulo Grupos (Público)
 * Arquivo: grupos/portal.php
 * Descrição: Painel para o mobilizador ver suas indicações.
 */
session_start();
require_once __DIR__ . '/../config.php';

$pdo = conectar_db();
$logado = isset($_SESSION['mobilizador_id']);

// Lógica de Download CSV
if ($logado && isset($_GET['download'])) {
    $mobilizador_id = $_SESSION['mobilizador_id'];
    $search_term = $_GET['search'] ?? '';

    $sql_download = "SELECT c.nome, c.whatsapp, c.cidade, i.data_indicacao as data_formatada
        FROM indicacoes i JOIN cadastro c ON i.cadastro_id = c.id
        WHERE i.compartilhador_id = ?";
    
    $params = [$mobilizador_id];

    if ($_GET['download'] === 'filtered' && !empty($search_term) && strtolower($search_term) !== 'todos') {
        $sql_download .= " AND (c.nome LIKE ? OR c.whatsapp LIKE ? OR c.cidade LIKE ?)";
        $like_term = "%" . $search_term . "%";
        $params[] = $like_term;
        $params[] = $like_term;
        $params[] = $like_term;
    }
    
    $sql_download .= " ORDER BY i.data_indicacao DESC";
    $stmt_download = $pdo->prepare($sql_download);
    $stmt_download->execute($params);
    $result_download = $stmt_download->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=indicados.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Nome', 'WhatsApp', 'Cidade', 'Data da Indicação'], ';');

    foreach ($result_download as $row) {
        $row['data_formatada'] = formatar_data_br($row['data_formatada']);
        fputcsv($output, $row, ';');
    }
    exit();
}

// Lógica de Logout e Login
if (isset($_GET['logout'])) { 
    session_destroy(); 
    header('Location: portal.php'); 
    exit; 
}
$erro_login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $link_login = $_POST['username'] ?? '';
    $whatsapp_login = preg_replace('/[^0-9]/', '', $_POST['password'] ?? '');
    $stmt = $pdo->prepare("SELECT id, nome FROM compartilhadores WHERE link_personalizado = ? AND whatsapp = ? AND status = 'aprovado'");
    $stmt->execute([$link_login, $whatsapp_login]);
    $mobilizador = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($mobilizador) {
        $_SESSION['mobilizador_id'] = $mobilizador['id'];
        $_SESSION['mobilizador_nome'] = $mobilizador['nome'];
        header('Location: portal.php');
        exit;
    } else { 
        $erro_login = 'Usuário ou senha incorretos, ou cadastro não aprovado.'; 
    }
}

if ($logado) {
    $mobilizador_id = $_SESSION['mobilizador_id'];
    
    $total_indicacoes = 0;
    $total_cidades = 0;
    $cidades_data = [];

    $stats_stmt = $pdo->prepare("SELECT COUNT(i.id) as total_indicacoes, COUNT(DISTINCT c.cidade) as total_cidades
        FROM indicacoes i JOIN cadastro c ON i.cadastro_id = c.id
        WHERE i.compartilhador_id = ?");
    $stats_stmt->execute([$mobilizador_id]);
    $stats_result = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats_result) {
        $total_indicacoes = $stats_result['total_indicacoes'];
        $total_cidades = $stats_result['total_cidades'];
    }

    if($total_indicacoes > 0) {
        $cities_stmt = $pdo->prepare("SELECT c.cidade, COUNT(c.id) as total
            FROM indicacoes i JOIN cadastro c ON i.cadastro_id = c.id
            WHERE i.compartilhador_id = ?
            GROUP BY c.cidade ORDER BY total DESC");
        $cities_stmt->execute([$mobilizador_id]);
        $cidades_data = $cities_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $results_per_page = 25;
    $search_term = $_GET['search'] ?? '';
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $results_per_page;

    $count_sql = "SELECT COUNT(i.id) FROM indicacoes i JOIN cadastro c ON i.cadastro_id = c.id WHERE i.compartilhador_id = ?";
    $count_params = [$mobilizador_id];
    if (!empty($search_term) && strtolower($search_term) !== 'todos') {
        $like_term = "%" . $search_term . "%";
        $count_sql .= " AND (c.nome LIKE ? OR c.whatsapp LIKE ? OR c.cidade LIKE ?)";
        $count_params = array_merge($count_params, [$like_term, $like_term, $like_term]);
    }
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_results = $count_stmt->fetchColumn();
    $total_pages = ceil($total_results / $results_per_page);

    $indicados_sql = "SELECT c.nome, c.whatsapp, c.cidade, i.data_indicacao FROM indicacoes i JOIN cadastro c ON i.cadastro_id = c.id WHERE i.compartilhador_id = ?";
    $indicados_params = [$mobilizador_id];
    if (!empty($search_term) && strtolower($search_term) !== 'todos') {
        $like_term = "%" . $search_term . "%";
        $indicados_sql .= " AND (c.nome LIKE ? OR c.whatsapp LIKE ? OR c.cidade LIKE ?)";
        $indicados_params = array_merge($indicados_params, [$like_term, $like_term, $like_term]);
    }
    $indicados_sql .= " ORDER BY i.data_indicacao DESC LIMIT ? OFFSET ?";
    $indicados_params[] = $results_per_page;
    $indicados_params[] = $offset;
    
    $indicados_stmt = $pdo->prepare($indicados_sql);
    $indicados_stmt->execute($indicados_params);
    $indicados_result = $indicados_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Mobilizador</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="grupos-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (!$logado): ?>
    <div class="container">
        <div class="form-container">
            <div class="form-card" style="max-width: 400px;">
                <h1 class="titulo">Portal do Mobilizador</h1>
                <?php if ($erro_login): ?><div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;"><?php echo $erro_login; ?></div><?php endif; ?>
                <form method="POST" action="portal.php">
                    <div class="input-group"><input type="text" name="username" placeholder="Seu link personalizado (usuário)" required></div>
                    <div class="input-group"><input type="password" name="password" placeholder="Seu WhatsApp (apenas números)" required></div>
                    <button type="submit" name="login">Entrar</button>
                </form>
                <div class="links-adicionais" style="margin-top: 20px;"><a href="index.php" class="link-ranking">← Voltar</a></div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="header">
        <h1 class="logo">Painel Mobilizador</h1>
        <i id="menu-icon" class="fas fa-bars menu-icon"></i>
    </div>
    
    <div id="side-menu" class="side-menu">
        <div class="close-menu">
            <i id="close-menu-icon" class="fas fa-times"></i>
        </div>
        <a href="index.php" class="side-menu-link">
            <i class="fas fa-home"></i> Voltar
        </a>
        <a href="?logout=1" class="side-menu-link">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>

    <main class="main-content">
        <div class="ranking-container">
            <div class="ranking-header">
                <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['mobilizador_nome']); ?>!</h1>
                <p>Este é o seu painel de indicações.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number"><?php echo $total_indicacoes; ?></div><div class="stat-label">Total de Cadastros</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $total_cidades; ?></div><div class="stat-label">Cidades Alcançadas</div></div>
            </div>

            <?php if (!empty($cidades_data)): ?>
            <div class="ranking-table" style="padding: 20px;">
                <h3 style="padding: 0 0 20px 0; margin: 0; background: none; color: #147b0b;">Detalhes por Cidade</h3>
                <div class="city-details-container">
                    <div class="chart-container">
                        <h4>Top Cidades</h4>
                        <?php foreach (array_slice($cidades_data, 0, 5) as $cidade): 
                            $percentage = ($total_indicacoes > 0) ? ($cidade['total'] / $total_indicacoes) * 100 : 0;
                        ?>
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%; min-width: 25%;">
                                    <?php echo htmlspecialchars($cidade['cidade']); ?>
                                </div>
                                <span class="chart-bar-value"><?php echo $cidade['total']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="city-list-container">
                        <h4>Lista de Cidades</h4>
                        <div class="city-list-box">
                            <?php foreach ($cidades_data as $cidade): ?>
                                <div class="city-list-item">
                                    <span class="city-name"><?php echo htmlspecialchars($cidade['cidade']); ?></span>
                                    <span class="city-count"><?php echo $cidade['total']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="responsive-table-wrapper" style="margin-top: 30px;">
                <h3 style="padding: 20px; margin: 0; background: #147b0b; color: white;">Seus Indicados</h3>
                
                <div class="search-and-download">
                    <form action="portal.php" method="GET" class="search-form">
                        <input type="text" name="search" id="search-input" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Buscar (nome, WhatsApp, cidade)">
                    </form>
                    <a href="portal.php?download=filtered&search=<?php echo urlencode($search_term); ?>" class="btn download-btn"><i class="fas fa-download"></i> Download</a>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="responsive-table" id="indicados-table">
                        <thead><tr><th>Nome</th><th>WhatsApp</th><th>Cidade</th><th>Data</th></tr></thead>
                        <tbody>
                            <?php if (!empty($indicados_result)): ?>
                                <?php foreach($indicados_result as $row): ?>
                                    <tr>
                                        <td data-label="Nome"><?php echo htmlspecialchars($row['nome']); ?></td>
                                        <td data-label="WhatsApp"><?php echo htmlspecialchars($row['whatsapp']); ?></td>
                                        <td data-label="Cidade"><?php echo htmlspecialchars($row['cidade']); ?></td>
                                        <td data-label="Data"><?php echo formatar_data_br($row['data_indicacao']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; padding: 40px;">Nenhum resultado encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>" class="<?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="links-adicionais" style="margin-top: 30px;">
                <a href="index.php" class="link-ranking">← Voltar para cadastro</a>
            </div>
        </div>
    </main>

    <script src="grupos-portal.js"></script>
    <?php endif; ?>
</body>
</html>
