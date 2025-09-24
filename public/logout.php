<?php
require_once __DIR__ . '/../src/auth/functions.php';
auth_logout();
header('Location: /cardsigob/public/login.php');
exit;