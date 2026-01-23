<?php
require_once __DIR__ . '/../src/Auth.php';

// Appel de la méthode statique pour nettoyer la session
Auth::logout();

// Redirection immédiate vers la page de connexion ou l'accueil
header('Location: login.php');
exit;