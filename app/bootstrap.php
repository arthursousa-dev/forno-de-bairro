<?php
/**
 * app/bootstrap.php
 *
 * Ponto único de inicialização da aplicação.
 * Toda página de entrada em public/ deve fazer:
 *
 *     require_once __DIR__ . '/../app/bootstrap.php';
 *
 * como primeira instrução.
 */

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

require_once __DIR__ . '/Config/config.php';
require_once __DIR__ . '/Config/Database.php';
require_once __DIR__ . '/Core/helpers.php';
