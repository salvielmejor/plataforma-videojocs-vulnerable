<?php
// Configuración unificada y segura de la sesión

// Evitar reiniciar la sesión si ya está activa
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Asegurar que no se ha enviado salida antes de configurar las cookies
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 0,            // Sesión (hasta cerrar navegador)
            'path' => '/',              // Disponible para todo el sitio
            'domain' => '',             // Por defecto el host actual
            'secure' => $isSecure,      // Solo por HTTPS si está disponible
            'httponly' => true,         // No accesible desde JS
            'samesite' => 'Lax',        // Evita CSRF básico manteniendo flujos típicos
        ]);
        // Opciones adicionales de sesión
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        if ($isSecure) {
            ini_set('session.cookie_secure', '1');
        }
    }

    session_start();

    // Regenerar el id de sesión de manera periódica para mitigar fijación de sesión
    if (!isset($_SESSION['__created_at'])) {
        $_SESSION['__created_at'] = time();
        session_regenerate_id(true);
    } else if (time() - $_SESSION['__created_at'] > 900) { // 15 minutos
        $_SESSION['__created_at'] = time();
        session_regenerate_id(true);
    }
}
?>


