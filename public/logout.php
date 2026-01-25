<?php
require_once __DIR__ . '/../src/Auth.php';

Auth::init();
Auth::logout();

header('Location: login.php');
exit;