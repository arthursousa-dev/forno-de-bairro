<?php
/**
 * app/Config/config.php
 *
 * Configurações gerais da aplicação "Forno do Bairro".
 * Em produção (ex.: instância EC2), defina estas variáveis de ambiente no
 * servidor (via /etc/environment, systemd Environment=, ou Apache
 * SetEnv) ao invés de alterar os valores padrão abaixo.
 */

if (!defined('APP_ENV')) {

    define('APP_ENV', getenv('APP_ENV') ?: 'local');          // local | production
    define('APP_DEBUG', APP_ENV !== 'production');
    define('APP_NAME', 'Forno do Bairro');
    define('APP_BASE_URL', getenv('APP_BASE_URL') ?: '/');

    // ---- Banco de dados ----
    define('DB_DRIVER', 'pgsql');
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '5432');
    define('DB_NAME', getenv('DB_NAME') ?: 'forno_do_bairro');
    define('DB_USER', getenv('DB_USER') ?: 'postgres');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_CHARSET', 'utf8');

    // ---- Sessão / segurança ----
    define('SESSION_NAME', 'forno_session');

    // ---- Caminhos físicos ----
    define('BASE_PATH', dirname(__DIR__, 2));          // raiz do projeto
    define('PUBLIC_PATH', BASE_PATH . '/public');       // document root
    define('UPLOAD_PRODUTOS_PATH', PUBLIC_PATH . '/uploads/produtos');
    define('UPLOAD_PRODUTOS_URL', '/uploads/produtos');
    define('UPLOAD_PRODUTOS_MAX_BYTES', 3 * 1024 * 1024); // 3 MB

    date_default_timezone_set('America/Campo_Grande');

    if (APP_DEBUG) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    } else {
        error_reporting(0);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
    }
}
