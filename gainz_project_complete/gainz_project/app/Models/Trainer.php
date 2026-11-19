<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;
use PDOException;

class Trainer
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db instanceof PDO ? $db : (new Database())->connect();
    }

    public function getAllTrainers(): array
    {
        $stmt = $this->db->query(
            "SELECT t.*, CONCAT(t.FirstName, ' ', t.LastName) AS FullName, s.SpecDesc
             FROM `trainer` t
             LEFT JOIN `specialization` s ON t.SpecializationType = s.SpecType
             ORDER BY t.TrainerID ASC"
        );
        return $stmt->fetchAll();
    }

    public function getAllSpecializations(): array
    {
        $stmt = $this->db->query("SELECT * FROM `specialization` ORDER BY SpecType ASC");
        return $stmt->fetchAll();
    }

    public function createTrainer(string $lastName, string $firstName, string $specialization, float $rate): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO `trainer` (LastName, FirstName, SpecializationType, RatePerHour)
                 VALUES (:ln, :fn, :spec, :rate)"
            );
            return $stmt->execute([
                ':ln' => trim($lastName),
                ':fn' => trim($firstName),
                ':spec' => strtoupper(trim($specialization)),
                ':rate' => max(0, $rate),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateTrainer(int $trainerId, string $lastName, string $firstName, string $specialization, float $rate): bool
    {
        try {
            $stmt = $this->db->prepare(
                "UPDATE `trainer`
                 SET LastName = :ln, FirstName = :fn, SpecializationType = :spec, RatePerHour = :rate
                 WHERE TrainerID = :id"
            );
            return $stmt->execute([
                ':id' => $trainerId,
                ':ln' => trim($lastName),
                ':fn' => trim($firstName),
                ':spec' => strtoupper(trim($specialization)),
                ':rate' => max(0, $rate),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteTrainer(int $trainerId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM `trainer` WHERE TrainerID = :id");
            return $stmt->execute([':id' => $trainerId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
