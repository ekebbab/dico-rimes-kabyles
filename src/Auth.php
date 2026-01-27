<?php
/**
 * CLASSE Auth - VERSION SÉCURISÉE 2026
 * Gère l'authentification, la déconnexion et les rôles.
 * Note : La balise de fermeture ?> est omise volontairement pour éviter les espaces parasites.
 */

class Auth {
    /**
     * Initialise la session de manière sécurisée.
     * Doit être appelé au tout début des fichiers index.php, admin.php, etc.
     */
    public static function init() {
        if (headers_sent()) {
            return; // On ne tente pas de démarrer si du contenu a déjà été envoyé
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté et met à jour son activité.
     */
    public static function isLogged($db = null) {
        if (isset($_SESSION['user_id'])) {
            if ($db instanceof PDO) {
                try {
                    $stmt = $db->prepare("UPDATE users SET last_seen = datetime('now') WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                } catch (PDOException $e) {
                    error_log("Erreur tracking last_seen : " . $e->getMessage());
                }
            }
            return true;
        }
        return false;
    }

    public static function getRole() {
        return $_SESSION['role'] ?? 'guest';
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function logout() {
        self::init();
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function canManage($author_id, $author_role = 'user') {
        if (!self::isLogged()) return false;

        $myId = self::getUserId();
        $myRole = self::getRole();

        if ($myRole === 'superadmin') return true;
        if ($myRole === 'admin') return ($author_role !== 'superadmin');
        return ((int)$myId === (int)$author_id);
    }
}