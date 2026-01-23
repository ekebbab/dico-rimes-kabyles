<?php
require_once __DIR__ . '/../src/Auth.php';
$auth = new Auth(null); // On n'a pas besoin de PDO juste pour logout
$auth->logout();