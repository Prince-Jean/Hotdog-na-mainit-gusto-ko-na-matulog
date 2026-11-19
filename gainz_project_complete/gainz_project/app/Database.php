<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class Database
{
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private ?PDO $connection = null;

    public function __construct(
        string $host = '127.0.0.1',
        string $dbName = 'gym_database',
        string $username = 'root',
        string $password = ''
    ) {
        $this->host = getenv('DB_HOST') ?: $host;
        $this->dbName = getenv('DB_NAME') ?: $dbName;
        $this->username = getenv('DB_USER') ?: $username;
        $this->password = getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : $password;
    }

    public function connect(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        // $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
        $dsn = "mysql:host={$this->host};port=3307;dbname={$this->dbName};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            return $this->connection;
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connect();
    }
}
