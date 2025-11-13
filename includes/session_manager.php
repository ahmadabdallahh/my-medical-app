<?php
/**
 * Enhanced Session Management System
 * Provides secure session handling with CSRF protection and user tracking
 */

class SessionManager {
    private $session_name = 'MEDICAL_BOOKING_SESSION';
    private $session_lifetime = 3600; // 1 hour
    private $regenerate_interval = 300; // 5 minutes

    public function __construct() {
        $this->init_session();
    }

    /**
     * Initialize secure session
     */
    private function init_session() {
        // Set session name
        session_name($this->session_name);

        // Configure session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');

        // Set session lifetime
        ini_set('session.gc_maxlifetime', $this->session_lifetime);

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Regenerate session ID periodically
        $this->regenerate_session_id();

        // Validate session
        $this->validate_session();

        // Set security headers
        $this->set_security_headers();
    }

    /**
     * Regenerate session ID to prevent fixation
     */
    private function regenerate_session_id() {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        }

        if (time() - $_SESSION['last_regeneration'] > $this->regenerate_interval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }

    /**
     * Validate session integrity
     */
    private function validate_session() {
        if (isset($_SESSION['user_id'])) {
            // Check IP address
            if (!isset($_SESSION['ip_address'])) {
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
            } elseif ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                $this->destroy_session();
                return false;
            }

            // Check user agent
            if (!isset($_SESSION['user_agent'])) {
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            } elseif ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                $this->destroy_session();
                return false;
            }

            // Check session expiration
            if (isset($_SESSION['last_activity']) &&
                (time() - $_SESSION['last_activity'] > $this->session_lifetime)) {
                $this->destroy_session();
                return false;
            }

            $_SESSION['last_activity'] = time();
        }

        return true;
    }

    /**
     * Set security headers
     */
    private function set_security_headers() {
        if (!headers_sent()) {
            header('X-Frame-Options: DENY');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Set user session data
     */
    public function set_user_session($user_data) {
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['full_name'] = $user_data['full_name'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        // Generate CSRF token
        $this->generate_csrf_token();
    }

    /**
     * Generate CSRF token
     */
    public function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        // Regenerate token every 30 minutes
        if (time() - $_SESSION['csrf_token_time'] > 1800) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public function validate_csrf_token($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Check if user is logged in
     */
    public function is_logged_in() {
        return isset($_SESSION['user_id']) && $this->validate_session();
    }

    /**
     * Get current user data
     */
    public function get_user_data() {
        if ($this->is_logged_in()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role'],
                'login_time' => $_SESSION['login_time']
            ];
        }

        return null;
    }

    /**
     * Check user role
     */
    public function has_role($role) {
        return $this->is_logged_in() && $_SESSION['role'] === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function has_any_role($roles) {
        if (!$this->is_logged_in()) {
            return false;
        }

        return in_array($_SESSION['role'], $roles);
    }

    /**
     * Get session flash message
     */
    public function get_flash_message($key) {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }

        return null;
    }

    /**
     * Set session flash message
     */
    public function set_flash_message($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get all flash messages
     */
    public function get_all_flash_messages() {
        $messages = $_SESSION['flash'] ?? [];
        $_SESSION['flash'] = [];
        return $messages;
    }

    /**
     * Destroy session
     */
    public function destroy_session() {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Logout user
     */
    public function logout() {
        $this->destroy_session();

        // Clear any remember me tokens
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, '/');
        }
    }

    /**
     * Get session statistics
     */
    public function get_session_stats() {
        return [
            'session_id' => session_id(),
            'session_name' => session_name(),
            'user_agent' => $_SESSION['user_agent'] ?? null,
            'ip_address' => $_SESSION['ip_address'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'login_time' => $_SESSION['login_time'] ?? null,
            'csrf_token_time' => $_SESSION['csrf_token_time'] ?? null
        ];
    }
}

/**
 * Global session manager instance
 */
$session_manager = new SessionManager();

/**
 * Helper functions for easy access
 */
function is_logged_in() {
    global $session_manager;
    return $session_manager->is_logged_in();
}

function get_current_user() {
    global $session_manager;
    return $session_manager->get_user_data();
}

function has_role($role) {
    global $session_manager;
    return $session_manager->has_role($role);
}

function has_any_role($roles) {
    global $session_manager;
    return $session_manager->has_any_role($roles);
}

function get_csrf_token() {
    global $session_manager;
    return $session_manager->generate_csrf_token();
}

function validate_csrf_token($token) {
    global $session_manager;
    return $session_manager->validate_csrf_token($token);
}

function set_flash_message($key, $message) {
    global $session_manager;
    return $session_manager->set_flash_message($key, $message);
}

function get_flash_message($key) {
    global $session_manager;
    return $session_manager->get_flash_message($key);
}

function logout_user() {
    global $session_manager;
    return $session_manager->logout();
}
?>
