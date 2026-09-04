<?php
require_once __DIR__ . '/../app/bootstrap.php';

$_SESSION = [];
session_destroy();

session_start();
definirFlash('info', 'Você saiu da sua conta.');
header('Location: /index.php');
exit;
