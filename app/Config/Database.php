<?php
/**
 * app/Config/Database.php
 *
 * Encapsula a conexão PDO com o PostgreSQL em um Singleton, evitando múltiplas
 * conexões abertas durante o mesmo ciclo de requisição.
 */

namespace App\Config;

require_once __DIR__ . '/config.php';

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            DB_DRIVER,
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            error_log('[Database] Falha de conexão: ' . $e->getMessage());

            if (APP_DEBUG) {
                die('Erro de conexão com o banco de dados: ' . $e->getMessage());
            }

            http_response_code(500);
            die('Erro interno. Tente novamente mais tarde.');
        }

        return self::$instance;
    }
}
