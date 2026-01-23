<?php
// src/Auth.php

class Auth {
    
    public static function login($username, $password) {
        // Simulation d'identifiants (à remplacer par une requête BDD plus tard)
        $admin_user = "admin";
        $admin_pass = "admin"; 

        if ($username === $admin_user && $password === $admin_pass) {
            // On démarre la session si elle n'est pas déjà lancée
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            return true;
        }
        
        return false;
    }

    public static function isLogged() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }
}