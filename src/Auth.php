<?php
// src/Auth.php

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        // On démarre la session si elle n'existe pas
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Vérification du mot de passe (haché)
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function isLogged() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['user_id']);
    }

    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}