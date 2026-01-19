<?php
/**
 * Configuration de la base de données
 * Uses environment variables from Docker or falls back to defaults
 */

// Pour le dev local, utiliser un tunnel SSH : ssh -L 3306:127.0.0.1:3306 root@109.176.199.253
define('DB_HOST', getenv('MYSQL_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'xray_db');
define('DB_USER', getenv('MYSQL_USER') ?: 'xray_user');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: 'Gn*uTl7M*1TzaV');

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'error' => 'Database connection failed',
                'message' => $e->getMessage()
            ]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
