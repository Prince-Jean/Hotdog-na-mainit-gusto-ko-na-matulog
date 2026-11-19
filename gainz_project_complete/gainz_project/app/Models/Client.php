<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;
use PDOException;

class Client
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db instanceof PDO ? $db : (new Database())->connect();
    }

    public function getAllClients(): array
    {
        $stmt = $this->db->query(
            "SELECT u.*, CONCAT(u.FirstName, ' ', u.LastName) AS FullName, m.MembershipDesc
             FROM `user` u
             LEFT JOIN `membership` m ON u.MembershipType = m.MembershipType
             ORDER BY u.UserID ASC"
        );
        return $stmt->fetchAll();
    }

    public function getClientById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, CONCAT(u.FirstName, ' ', u.LastName) AS FullName, m.MembershipDesc
             FROM `user` u
             LEFT JOIN `membership` m ON u.MembershipType = m.MembershipType
             WHERE u.UserID = :id LIMIT 1"
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getClientByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, CONCAT(u.FirstName, ' ', u.LastName) AS FullName, m.MembershipDesc
             FROM `user` u
             LEFT JOIN `membership` m ON u.MembershipType = m.MembershipType
             WHERE u.UserEmail = :email LIMIT 1"
        );
        $stmt->execute([':email' => trim($email)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function emailOrPhoneExists(string $email, string $phone): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `user` WHERE UserEmail = :email OR UserPhoneNo = :phone"
        );
        $stmt->execute([
            ':email' => trim($email),
            ':phone' => trim($phone),
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createClient(
        string $lastName,
        string $firstName,
        int $age,
        string $gender,
        float $weight,
        float $height,
        string $membershipType,
        string $email,
        string $passwordHash,
        string $phone,
        int $isAdmin = 0,
        ?string $expiryDate = null
    ): bool {
        try {
            $validPlans = ['BASIC', 'MID', 'PRO'];
            $plan = in_array(strtoupper($membershipType), $validPlans, true) ? strtoupper($membershipType) : 'BASIC';
            $genderCode = in_array(strtoupper($gender), ['M', 'F', 'O'], true) ? strtoupper($gender) : 'M';
            $expiry = $expiryDate ?? date('Y-m-d', strtotime('+30 days'));

            $stmt = $this->db->prepare(
                "INSERT INTO `user`
                 (LastName, FirstName, UserAge, UserGender, UserWeight, UserHeight, MembershipType, UserEmail, UserPassword_hash, UserPhoneNo, MembershipExpiry, isAdmin)
                 VALUES
                 (:ln, :fn, :age, :gender, :weight, :height, :plan, :email, :hash, :phone, :expiry, :is_admin)"
            );

            return $stmt->execute([
                ':ln' => trim($lastName),
                ':fn' => trim($firstName),
                ':age' => max(12, $age),
                ':gender' => $genderCode,
                ':weight' => max(20.0, $weight),
                ':height' => max(100.0, $height),
                ':plan' => $plan,
                ':email' => trim($email),
                ':hash' => $passwordHash,
                ':phone' => trim($phone),
                ':expiry' => $expiry,
                ':is_admin' => $isAdmin === 1 ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateClientById(
        int $userId,
        string $lastName,
        string $firstName,
        int $age,
        string $gender,
        float $weight,
        float $height,
        string $membershipType,
        string $email,
        string $phone,
        int $isAdmin
    ): bool {
        try {
            $validPlans = ['BASIC', 'MID', 'PRO'];
            $plan = in_array(strtoupper($membershipType), $validPlans, true) ? strtoupper($membershipType) : 'BASIC';
            $genderCode = in_array(strtoupper($gender), ['M', 'F', 'O'], true) ? strtoupper($gender) : 'M';

            $stmt = $this->db->prepare(
                "UPDATE `user`
                 SET LastName = :ln,
                     FirstName = :fn,
                     UserAge = :age,
                     UserGender = :gender,
                     UserWeight = :weight,
                     UserHeight = :height,
                     MembershipType = :plan,
                     UserEmail = :email,
                     UserPhoneNo = :phone,
                     isAdmin = :is_admin
                 WHERE UserID = :id"
            );

            return $stmt->execute([
                ':id' => $userId,
                ':ln' => trim($lastName),
                ':fn' => trim($firstName),
                ':age' => max(12, $age),
                ':gender' => $genderCode,
                ':weight' => max(20.0, $weight),
                ':height' => max(100.0, $height),
                ':plan' => $plan,
                ':email' => trim($email),
                ':phone' => trim($phone),
                ':is_admin' => $isAdmin === 1 ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateClientByEmail(
        string $currentEmail,
        string $lastName,
        string $firstName,
        int $age,
        string $gender,
        float $weight,
        float $height,
        string $membershipType,
        string $newEmail,
        string $phone
    ): bool {
        try {
            $validPlans = ['BASIC', 'MID', 'PRO'];
            $plan = in_array(strtoupper($membershipType), $validPlans, true) ? strtoupper($membershipType) : 'BASIC';
            $genderCode = in_array(strtoupper($gender), ['M', 'F', 'O'], true) ? strtoupper($gender) : 'M';

            $stmt = $this->db->prepare(
                "UPDATE `user`
                 SET LastName = :ln,
                     FirstName = :fn,
                     UserAge = :age,
                     UserGender = :gender,
                     UserWeight = :weight,
                     UserHeight = :height,
                     MembershipType = :plan,
                     UserEmail = :new_email,
                     UserPhoneNo = :phone
                 WHERE UserEmail = :current_email"
            );

            return $stmt->execute([
                ':current_email' => trim($currentEmail),
                ':ln' => trim($lastName),
                ':fn' => trim($firstName),
                ':age' => max(12, $age),
                ':gender' => $genderCode,
                ':weight' => max(20.0, $weight),
                ':height' => max(100.0, $height),
                ':plan' => $plan,
                ':new_email' => trim($newEmail),
                ':phone' => trim($phone),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updatePasswordByEmail(string $email, string $newPasswordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE `user` SET UserPassword_hash = :hash WHERE UserEmail = :email");
        return $stmt->execute([
            ':hash' => $newPasswordHash,
            ':email' => trim($email),
        ]);
    }

    public function deleteClient(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM `user` WHERE UserID = :id");
            return $stmt->execute([':id' => $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllMemberships(): array
    {
        $stmt = $this->db->query("SELECT * FROM `membership` ORDER BY MembershipRateMonthly ASC");
        return $stmt->fetchAll();
    }
}
