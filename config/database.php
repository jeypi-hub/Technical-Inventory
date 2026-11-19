<?php
require_once __DIR__ . '/../includes/i18n.php';

class Database
{
    private string $host    = 'localhost';
    private string $db_name = 'technical_inventory';
    private string $user    = 'root';
    private string $pass    = '';        
    private string $charset = 'utf8mb4';

    public ?PDO $conn = null;

    public function connect(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
        }
        return $this->conn;
    }
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = (new Database())->connect();
    }
    return $pdo;
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function start_session_once(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}
