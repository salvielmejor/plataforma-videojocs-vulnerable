<?php
require_once __DIR__ . '/session.php';

// Vaciar datos de sesión
$_SESSION = [];

// Borrar cookie de sesión en el navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Destruir la sesión en el servidor
session_destroy();

// Redirigir a la portada
header('Location: ../index.php');
exit();
?>


