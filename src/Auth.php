<?php
/**
 * CLASSE Auth
 * Gère l'authentification, la déconnexion et les rôles.
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
     * Vérifie si l'utilisateur est connecté
     */
    public static function isLogged() {
        self::init();
        return isset($_SESSION['user_id']);
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

        if ($myRole === 'superadmin') return true;
        if ($myRole === 'admin') return ($author_role !== 'superadmin');
        if ($myRole === 'user') return ($myId == $author_id);

        return false;
    }
}