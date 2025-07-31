<?php
/**
 * MIDDLEWARE DE AUTENTICACIÓN UNIVERSAL
 * Incluir este archivo en cualquier página que necesite autenticación
 * 
 * Uso: include_once 'auth_middleware.php';
 */

// Configurar sesión para dominios compartidos
ini_set('session.cookie_domain', '.conlineweb.com');
ini_set('session.cookie_path', '/');
ini_set('session.cookie_secure', true); // Solo si usas HTTPS
ini_set('session.cookie_httponly', true);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthMiddleware {
    
    private static $LOGIN_URL = 'https://cliente.conlineweb.com/ingreso.php';
    private static $SESSION_TIMEOUT = 3600; // 1 hora en segundos
    
    /**
     * Validar sesión actual
     */
    public static function validateCurrentSession() {
        // Verificar si hay datos básicos de sesión
        if (!isset($_SESSION['uid']) || !isset($_SESSION['login']) || $_SESSION['login'] !== true) {
            return false;
        }
        
        // Verificar timeout de sesión
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > self::$SESSION_TIMEOUT) {
                self::destroySession();
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validar sesión desde cookie (para dominios cruzados)
     */
    public static function validateFromCookie() {
        $session_id = $_COOKIE['PHPSESSID'] ?? $_COOKIE['shared_session_id'] ?? '';
        
        if (empty($session_id)) {
            return false;
        }
        
        // Si el session_id es diferente, intentar cargarlo
        if (session_id() !== $session_id) {
            session_write_close();
            session_id($session_id);
            session_start();
        }
        
        // Validar datos de cookie personalizada si existe
        if (isset($_COOKIE['user_session_data'])) {
            $sessionData = json_decode($_COOKIE['user_session_data'], true);
            
            if ($sessionData && isset($sessionData['uid'], $sessionData['login'])) {
                if (time() - $sessionData['timestamp'] <= self::$SESSION_TIMEOUT) {
                    // Recrear sesión desde cookie
                    $_SESSION['uid'] = $sessionData['uid'];
                    $_SESSION['login'] = $sessionData['login'];
                    $_SESSION['tipo'] = $sessionData['tipo'] ?? null;
                    $_SESSION['last_activity'] = time();
                    
                    // Actualizar cookie
                    self::updateSessionCookie();
                    return true;
                }
            }
        }
        
        return self::validateCurrentSession();
    }
    
    /**
     * Actualizar cookie de sesión
     */
    public static function updateSessionCookie() {
        if (isset($_SESSION['uid'])) {
            setcookie('user_session_data', json_encode([
                'uid' => $_SESSION['uid'],
                'tipo' => $_SESSION['tipo'] ?? null,
                'login' => true,
                'timestamp' => time()
            ]), time() + self::$SESSION_TIMEOUT, '/', '.conlineweb.com', true, true);
        }
    }
    
    /**
     * Destruir sesión completamente
     */
    public static function destroySession() {
        // Limpiar variables de sesión
        $_SESSION = array();
        
        // Eliminar cookies
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Eliminar cookie personalizada
        setcookie('user_session_data', '', time() - 3600, '/', '.conlineweb.com', true, true);
        setcookie('shared_session_id', '', time() - 3600, '/', '.conlineweb.com', true, true);
        
        // Destruir sesión
        session_destroy();
    }
    
    /**
     * Redirigir al login
     */
    public static function redirectToLogin($message = '') {
        $url = self::$LOGIN_URL;
        if (!empty($message)) {
            $url .= '?error=' . urlencode($message);
        }
        
        header("Location: $url");
        exit();
    }
    
    /**
     * Validación principal - usar en páginas protegidas
     */
    public static function requireAuth($redirect_on_fail = true) {
        $isValid = false;
        
        // Primero intentar validar sesión actual
        if (self::validateCurrentSession()) {
            // Actualizar última actividad
            $_SESSION['last_activity'] = time();
            self::updateSessionCookie();
            $isValid = true;
        } 
        // Si no funciona, intentar desde cookie
        else if (self::validateFromCookie()) {
            $isValid = true;
        }
        
        if (!$isValid && $redirect_on_fail) {
            self::redirectToLogin('Sesión expirada');
        }
        
        return $isValid;
    }
    
    /**
     * Obtener información del usuario autenticado
     */
    public static function getUserInfo() {
        if (!self::requireAuth(false)) {
            return null;
        }
        
        return [
            'uid' => $_SESSION['uid'] ?? null,
            'tipo' => $_SESSION['tipo'] ?? null,
            'login' => $_SESSION['login'] ?? false,
            'last_activity' => $_SESSION['last_activity'] ?? null
        ];
    }
    
    /**
     * Verificar si el usuario tiene un tipo específico
     */
    public static function checkUserType($required_type) {
        $user = self::getUserInfo();
        return $user && $user['tipo'] == $required_type;
    }
    
    /**
     * Logout manual
     */
    public static function logout() {
        self::destroySession();
        self::redirectToLogin('Sesión cerrada');
    }
}

// Auto-ejecutar validación si se incluye directamente
if (!defined('AUTH_MIDDLEWARE_INCLUDED')) {
    define('AUTH_MIDDLEWARE_INCLUDED', true);
    
    // Solo validar si no estamos en la página de login
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    $is_login_page = strpos($current_url, 'ingreso.php') !== false;
    
    if (!$is_login_page) {
        AuthMiddleware::requireAuth();
    }
}
?>