<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;
use PDOException;

class PaymentAndMessage
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db instanceof PDO ? $db : (new Database())->connect();
    }

    public function getAllPayments(): array
    {
        $stmt = $this->db->query(
            "SELECT pt.*, CONCAT(u.FirstName, ' ', u.LastName) AS MemberName, u.UserEmail AS MemberEmail
             FROM `payment_transaction` pt
             INNER JOIN `user` u ON pt.UserID = u.UserID
             ORDER BY pt.PaymentDate DESC, pt.PaymentID DESC"
        );
        return $stmt->fetchAll();
    }

    public function getPaymentsByEmail(string $email): array
    {
        $stmt = $this->db->prepare(
            "SELECT pt.*
             FROM `payment_transaction` pt
             INNER JOIN `user` u ON pt.UserID = u.UserID
             WHERE u.UserEmail = :email
             ORDER BY pt.PaymentDate DESC, pt.PaymentID DESC"
        );
        $stmt->execute([':email' => trim($email)]);
        return $stmt->fetchAll();
    }

    public function recordPaymentRenewal(
        int $userId,
        string $membershipType,
        string $billingCycle,
        float $amountPaid,
        string $notes = ''
    ): bool {
        try {
            $this->db->beginTransaction();

            $validPlans = ['BASIC', 'MID', 'PRO'];
            $plan = in_array(strtoupper($membershipType), $validPlans, true) ? strtoupper($membershipType) : 'BASIC';

            $cycle = strtoupper($billingCycle);
            $daysToAdd = match ($cycle) {
                'BIANNUAL' => 180,
                'ANNUAL' => 365,
                default => 30,
            };
            if (!in_array($cycle, ['MONTHLY', 'BIANNUAL', 'ANNUAL'], true)) {
                $cycle = 'MONTHLY';
            }

            $userStmt = $this->db->prepare("SELECT MembershipExpiry FROM `user` WHERE UserID = :id LIMIT 1");
            $userStmt->execute([':id' => $userId]);
            $currentExpiry = $userStmt->fetchColumn();

            $baseDate = ($currentExpiry && strtotime((string)$currentExpiry) > time())
                ? strtotime((string)$currentExpiry)
                : time();
            $validUntil = date('Y-m-d', strtotime("+{$daysToAdd} days", $baseDate));

            $insertStmt = $this->db->prepare(
                "INSERT INTO `payment_transaction` (UserID, MembershipType, BillingCycle, AmountPaid, ValidUntil, PaymentNotes)
                 VALUES (:uid, :plan, :cycle, :amount, :valid_until, :notes)"
            );
            $insertStmt->execute([
                ':uid' => $userId,
                ':plan' => $plan,
                ':cycle' => $cycle,
                ':amount' => max(0, $amountPaid),
                ':valid_until' => $validUntil,
                ':notes' => trim($notes),
            ]);

            $updateStmt = $this->db->prepare(
                "UPDATE `user`
                 SET MembershipType = :plan, MembershipExpiry = :valid_until
                 WHERE UserID = :uid"
            );
            $updateStmt->execute([
                ':plan' => $plan,
                ':valid_until' => $validUntil,
                ':uid' => $userId,
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function createContactMessage(string $firstName, string $lastName, string $email, string $phone, string $messageBody): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO `contact_message` (FirstName, LastName, Email, PhoneNumber, MessageBody)
                 VALUES (:fn, :ln, :email, :phone, :body)"
            );
            return $stmt->execute([
                ':fn' => trim($firstName),
                ':ln' => trim($lastName),
                ':email' => trim($email),
                ':phone' => trim($phone),
                ':body' => trim($messageBody),
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllContactMessages(): array
    {
        $stmt = $this->db->query("SELECT * FROM `contact_message` ORDER BY SubmittedAt DESC, MessageID DESC");
        return $stmt->fetchAll();
    }

    public function deleteContactMessage(int $messageId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `contact_message` WHERE MessageID = :id");
        return $stmt->execute([':id' => $messageId]);
    }
}
