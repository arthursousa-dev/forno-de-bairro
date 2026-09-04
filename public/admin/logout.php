<?php
require_once __DIR__ . '/../../app/bootstrap.php';

$_SESSION = [];
session_destroy();

session_start();
header('Location: /admin/index.php');
exit;
