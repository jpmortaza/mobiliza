<?php
require_once '../config.php';

// Registrar log de logout se usuário estiver logado
if (isset($_SESSION['usuario_id'])) {
    registrar_log("logout_realizado", 'usuarios_sistema', $_SESSION['usuario_id'], "Logout realizado");
}

// Destruir sessão
session_unset();
session_destroy();

// Limpar cookies de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirecionar para login
header("Location: index.php");
exit();
?>