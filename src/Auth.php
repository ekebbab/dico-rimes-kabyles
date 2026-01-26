<?php
/**
 * CLASSE Auth
 * Gère l'authentification, la déconnexion, les rôles et le tracking d'activité.
 */

class Auth {
    /**
     * Démarre la session de manière sécurisée
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     * Si une instance PDO est fournie, met à jour le champ 'last_seen'.
     */
    public static function isLogged($db = null) {
        self::init();
        
        if (isset($_SESSION['user_id'])) {
            // Si on a la connexion à la base, on marque l'utilisateur comme "vu"
            if ($db instanceof PDO) {
                try {
                    $stmt = $db->prepare("UPDATE users SET last_seen = datetime('now') WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                } catch (PDOException $e) {
                    // On échoue silencieusement pour ne pas bloquer l'utilisateur si la colonne n'existe pas encore
                    error_log("Erreur tracking last_seen : " . $e->getMessage());
                }
            }
            return true;
        }
        
        return false;
    }

    /**
     * Retourne le rôle de l'utilisateur (user, admin, superadmin)
     */
    public static function getRole() {
        self::init();
        return $_SESSION['role'] ?? 'guest';
    }

    /**
     * Retourne l'ID de l'utilisateur
     */
    public static function getUserId() {
        self::init();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Gère la déconnexion
     */
    public static function logout() {
        self::init();
        $_SESSION = array();
        session_destroy();
    }

    /**
     * Vérifie les permissions selon la hiérarchie
     */
    public static function canManage($author_id, $author_role = 'user') {
        if (!self::isLogged()) return false;

        $myId = self::getUserId();
        $myRole = self::getRole();

        // 1. Un superadmin peut tout gérer
        if ($myRole === 'superadmin') return true;
        
        // 2. Un admin peut gérer tout sauf les superadmins
        if ($myRole === 'admin') return ($author_role !== 'superadmin');
        
        // 3. Un utilisateur classique ne gère que ses propres contenus
        if ($myRole === 'user') return ($myId == $author_id);

        return false;
    }
}