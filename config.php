<?php
/**
 * MOBILIZA+ - Configurações do Sistema
 * * Arquivo central de configurações e funções auxiliares
 * Versão corrigida com detecção automática de caminhos e conexão PDO unificada
 */

// Definições do banco de dados
define('DB_HOST', 'X');
define('DB_USER', 'X');
define('DB_PASS', 'X');
define('DB_NAME', 'X');
define('DB_CHARSET', 'utf8mb4');

// Detectar URL base automaticamente
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . $host);


// Detectar caminho base do projeto
$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($script_path, '/admin/') !== false) {
    $base_path = substr($script_path, 0, strpos($script_path, '/admin/'));
} else {
    $base_path = dirname($script_path);
}
$base_path = rtrim($base_path, '/');

define('ADMIN_URL', SITE_URL . $base_path . '/admin');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . $base_path . '/uploads/');
define('UPLOAD_URL', SITE_URL . $base_path . '/uploads/');

// Configurações de sessão segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Iniciar sessão se não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Conecta ao banco de dados usando PDO
 * Esta é a única função de conexão usada para padronizar.
 */
function conectar_db() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, log o erro em vez de exibir
            error_log("Erro de conexão PDO: " . $e->getMessage());
            die("Erro de conexão com o banco de dados.");
        }
    }
    
    return $pdo;
}

/**
 * Formatar data para exibição em português
 */
function formatar_data_br($data, $incluir_hora = true) {
    if (!$data || $data === '0000-00-00 00:00:00') {
        return 'Não definida';
    }
    
    $timestamp = strtotime($data);
    if (!$timestamp) {
        return 'Data inválida';
    }
    
    if ($incluir_hora) {
        return date('d/m/Y \à\s H:i', $timestamp);
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Sanitizar dados de entrada
 */
function sanitizar($data, $tipo = 'texto') {
    switch ($tipo) {
        case 'email':
            return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var(trim($data), FILTER_SANITIZE_URL);
        case 'numero':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
        case 'telefone':
            return preg_replace('/[^0-9]/', '', $data);
        case 'slug':
            $slug = strtolower(trim($data));
            $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
            return preg_replace('/-+/', '-', trim($slug, '-'));
        default:
            return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Gerar slug único para URLs amigáveis
 */
function gerar_slug($texto, $tabela, $campo = 'slug', $id_exclusao = null) {
    $pdo = conectar_db();
    $slug_base = sanitizar($texto, 'slug');
    $slug = $slug_base;
    $contador = 1;
    
    do {
        $sql = "SELECT COUNT(*) FROM $tabela WHERE $campo = ?";
        $params = [$slug];
        
        if ($id_exclusao) {
            $sql .= " AND id != ?";
            $params[] = $id_exclusao;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $existe = $stmt->fetchColumn() > 0;
        
        if ($existe) {
            $slug = $slug_base . '-' . $contador++;
        }
    } while ($existe);
    
    return $slug;
}

/**
 * Validar upload de arquivo
 */
function validar_upload($arquivo, $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erro no upload do arquivo.');
    }
    
    // Verificar tamanho (máximo 5MB)
    if ($arquivo['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande. Máximo 5MB.');
    }
    
    // Verificar extensão
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $tipos_permitidos)) {
        throw new Exception('Tipo de arquivo não permitido.');
    }
    
    // Verificar MIME type se disponível
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);
        
        $mimes_permitidos = [
            'image/jpeg', 'image/jpg', 'image/png', 
            'image/gif', 'image/webp'
        ];
        
        if (!in_array($mime_type, $mimes_permitidos)) {
            throw new Exception('Tipo de arquivo não permitido.');
        }
    }
    
    return true;
}

/**
 * Função para fazer upload de arquivos
 * @param array $file Array do arquivo do $_FILES
 * @param string $folder Pasta onde salvar (dentro de uploads/)
 * @return string Caminho relativo do arquivo salvo
 */
function fazer_upload($file, $folder = 'geral') {
    // Diretório base de uploads
    // Usa __DIR__ para garantir que o caminho seja absoluto
    $base_dir = dirname(__FILE__) . '/uploads/';
    $upload_dir = $base_dir . $folder . '/';
    
    // Criar diretório se não existir
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Erro ao criar diretório de upload: ' . $upload_dir);
        }
    }
    
    // Verificar se é um upload válido
    // Esta verificação é crucial para garantir que apenas arquivos enviados pelo formulário sejam movidos
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Arquivo inválido ou não foi enviado via POST.');
    }
    
    // Validar tipo de arquivo
    $allowed_types = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    // Usar finfo_open para verificar o tipo MIME real do arquivo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types)) {
        throw new Exception('Tipo de arquivo não permitido. Use apenas imagens JPG, PNG, GIF ou WebP.');
    }
    
    // Verificar tamanho (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
    }
    
    // Gerar nome único
    $info = pathinfo($file['name']);
    $extension = strtolower($info['extension']);
    $filename = uniqid('img_') . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erro ao salvar arquivo no servidor.');
    }
    
    // Otimizar imagem se possível
    try {
        otimizar_imagem($filepath, $extension);
    } catch (Exception $e) {
        // Ignora erro de otimização
    }
    
    // Retornar caminho relativo
    // O caminho é relativo à raiz do projeto
    return 'uploads/' . $folder . '/' . $filename;
}


/**
 * Função auxiliar para otimizar imagens
 */
function otimizar_imagem($filepath, $extension) {
    // Verifica se as funções GD estão disponíveis
    if (!function_exists('imagecreatefrompng')) {
        return;
    }
    
    // Carregar imagem
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($filepath);
            break;
        case 'png':
            $image = imagecreatefrompng($filepath);
            break;
        case 'gif':
            $image = imagecreatefromgif($filepath);
            break;
        case 'webp':
            if (function_exists('imagecreatefromwebp')) {
                $image = imagecreatefromwebp($filepath);
            } else {
                return;
            }
            break;
        default:
            return;
    }
    
    if (!$image) {
        return;
    }
    
    // Obter dimensões
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Redimensionar se muito grande
    $max_width = 1200;
    $max_height = 1200;
    
    if ($width > $max_width || $height > $max_height) {
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = round($width * $ratio);
        $new_height = round($height * $ratio);
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preservar transparência para PNG e WEBP
        if ($extension == 'png' || $extension == 'webp') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        imagedestroy($image);
        $image = $new_image;
    }
    
    // Salvar imagem otimizada
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
            imagejpeg($image, $filepath, 85); // 85% de qualidade
            break;
        case 'png':
            imagepng($image, $filepath, 8); // Compressão 8
            break;
        case 'gif':
            imagegif($image, $filepath);
            break;
        case 'webp':
            if (function_exists('imagewebp')) {
                imagewebp($image, $filepath, 85);
            }
            break;
    }
    
    imagedestroy($image);
}

/**
 * Verificar se usuário está logado
 */
function verificar_login($tipo_requerido = null) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    if ($tipo_requerido && ($_SESSION['usuario_tipo'] ?? '') !== $tipo_requerido) {
        return false;
    }
    
    return true;
}

/**
 * Redirecionar se não estiver logado
 */
function requerer_login($tipo = null) {
    if (!verificar_login($tipo)) {
        $redirect_url = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? 
            substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '/admin/') + 7) . 'index.php' : 
            ADMIN_URL . "/index.php";
        header("Location: " . $redirect_url);
        exit();
    }
}

/**
 * Registrar log do sistema
 */
function registrar_log($acao, $tabela = null, $registro_id = null, $detalhes = null) {
    try {
        $pdo = conectar_db();
        $stmt = $pdo->prepare("
            INSERT INTO logs_sistema 
            (usuario_id, acao, tabela_afetada, registro_id, detalhes, ip, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $acao,
            $tabela,
            $registro_id,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Em caso de erro no log, não interromper o fluxo principal
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Obter configuração do sistema
 */
function obter_config($chave, $padrao = null) {
    static $configs = null;
    
    if ($configs === null) {
        try {
            $pdo = conectar_db();
            $stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
            $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $configs = [];
        }
    }
    
    return $configs[$chave] ?? $padrao;
}

/**
 * Definir configuração do sistema
 */
function definir_config($chave, $valor, $descricao = null) {
    $pdo = conectar_db();
    $stmt = $pdo->prepare("
        INSERT INTO configuracoes (chave, valor, descricao) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        valor = VALUES(valor), 
        descricao = COALESCE(VALUES(descricao), descricao)
    ");
    
    return $stmt->execute([$chave, $valor, $descricao]);
}

/**
 * Headers de segurança
 */
function definir_headers_seguranca() {
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Aplicar headers de segurança
definir_headers_seguranca();

/**
 * Função para debug (apenas em desenvolvimento)
 */
function debug($data, $die = false) {
    if (defined('DEBUG') && DEBUG) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) die();
    }
}

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Função auxiliar para detectar se estamos em um subdiretório
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Detectar subdiretório
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Se estamos em admin/, voltar um nível
    if (strpos($script_name, '/admin/') !== false) {
        $base_path = substr($script_name, 0, strpos($script_name, '/admin/'));
    } else {
        $base_path = dirname($script_name);
    }
    
    $base_path = rtrim($base_path, '/');
    
    return $protocol . $host . $base_path;
}

//Função para verificar - 
/**
 * Função para verificar autenticação
 */
function verificar_autenticacao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_tipo'])) {
        header('Location: /admin/');
        exit();
    }
    
    // Verificar se o usuário ainda está ativo no banco
    try {
        $pdo = conectar_db();
        $stmt = $pdo->prepare("SELECT ativo FROM usuarios_sistema WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();
        
        if (!$usuario || !$usuario['ativo']) {
            session_destroy();
            header('Location: /admin/');
            exit();
        }
    } catch (Exception $e) {
        // Em caso de erro, apenas continua
        error_log("Erro ao verificar autenticação: " . $e->getMessage());
    }
}
?>
