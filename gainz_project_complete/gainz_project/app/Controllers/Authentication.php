<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use PDO;
use PDOException;

class Authentication
{
    private PDO $conn;

    public function __construct(?PDO $db = null)
    {
        if ($db instanceof PDO) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->connect();
        }
    }

    public function authenticateUser(string $email, string $password): array
    {
        try {
            $query = "SELECT UserID, FirstName, LastName, UserEmail, UserPassword_hash, MembershipType, MembershipExpiry, isAdmin
                      FROM `user`
                      WHERE UserEmail = :email
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':email' => trim($email)]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, (string)$user['UserPassword_hash'])) {
                return [
                    'status' => ((int)$user['isAdmin'] === 1) ? 'ADMIN' : 'CLIENT',
                    'user' => $user,
                ];
            }

            return ['status' => 'INVALID', 'user' => null];
        } catch (PDOException $e) {
            return ['status' => 'ERROR', 'error' => $e->getMessage(), 'user' => null];
        }
    }

    public static function requireAuth(bool $adminOnly = false): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['user_email']) || empty($_SESSION['user_id'])) {
            header('Location: ../login.php');
            exit();
        }

        if ($adminOnly && (int)($_SESSION['is_admin'] ?? 0) !== 1) {
            header('Location: ../member_portal/member_dashboard.php');
            exit();
        }
    }
}
