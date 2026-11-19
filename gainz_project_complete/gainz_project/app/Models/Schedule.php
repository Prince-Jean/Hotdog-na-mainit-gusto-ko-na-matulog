<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;
use PDO;
use PDOException;

class Schedule
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db instanceof PDO ? $db : (new Database())->connect();
    }

    private function normalizeDateTime(string $dt): string
    {
        $ts = strtotime($dt);
        return $ts ? date('Y-m-d H:i:s', $ts) : $dt;
    }

    public function getAllSchedules(): array
    {
        $stmt = $this->db->query(
            "SELECT ws.*,
                    CONCAT(u.FirstName, ' ', u.LastName) AS MemberName,
                    u.UserEmail AS MemberEmail,
                    CONCAT(t.FirstName, ' ', t.LastName) AS TrainerName,
                    t.SpecializationType
             FROM `workoutschedule` ws
             INNER JOIN `user` u ON ws.UserID = u.UserID
             INNER JOIN `trainer` t ON ws.TrainerID = t.TrainerID
             ORDER BY ws.ScheduleStart ASC"
        );
        return $stmt->fetchAll();
    }

    public function getUserSchedules(string $userEmail): array
    {
        $stmt = $this->db->prepare(
            "SELECT ws.*,
                    CONCAT(t.FirstName, ' ', t.LastName) AS TrainerName,
                    t.SpecializationType,
                    t.RatePerHour
             FROM `workoutschedule` ws
             INNER JOIN `user` u ON ws.UserID = u.UserID
             INNER JOIN `trainer` t ON ws.TrainerID = t.TrainerID
             WHERE u.UserEmail = :email
             ORDER BY ws.ScheduleStart ASC"
        );
        $stmt->execute([':email' => trim($userEmail)]);
        return $stmt->fetchAll();
    }

    public function hasScheduleConflict(int $trainerId, string $start, string $end, ?int $excludeScheduleId = null): bool
    {
        $s = $this->normalizeDateTime($start);
        $e = $this->normalizeDateTime($end);

        $sql = "SELECT COUNT(*) FROM `workoutschedule`
                WHERE TrainerID = :tid
                  AND AttendanceStatus != 'ABSENT'
                  AND (:start < ScheduleEnd AND :end > ScheduleStart)";
        $params = [
            ':tid' => $trainerId,
            ':start' => $s,
            ':end' => $e,
        ];

        if ($excludeScheduleId !== null) {
            $sql .= " AND ScheduleID != :exclude_id";
            $params[':exclude_id'] = $excludeScheduleId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createScheduleByEmail(string $userEmail, int $trainerId, string $start, string $end): array
    {
        $s = $this->normalizeDateTime($start);
        $e = $this->normalizeDateTime($end);

        if (strtotime($e) <= strtotime($s)) {
            return ['ok' => false, 'message' => 'End time must be after start time.'];
        }

        if ($this->hasScheduleConflict($trainerId, $s, $e)) {
            return ['ok' => false, 'message' => 'Selected trainer already has a booking during that time slot.'];
        }

        try {
            $userStmt = $this->db->prepare("SELECT UserID FROM `user` WHERE UserEmail = :email LIMIT 1");
            $userStmt->execute([':email' => trim($userEmail)]);
            $userId = $userStmt->fetchColumn();

            if (!$userId) {
                return ['ok' => false, 'message' => 'Member account not found.'];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO `workoutschedule` (UserID, TrainerID, ScheduleStart, ScheduleEnd, AttendanceStatus)
                 VALUES (:uid, :tid, :start, :end, 'PENDING')"
            );
            $ok = $stmt->execute([
                ':uid' => (int)$userId,
                ':tid' => $trainerId,
                ':start' => $s,
                ':end' => $e,
            ]);

            return $ok
                ? ['ok' => true, 'message' => 'Session booked! View it under My Schedules.']
                : ['ok' => false, 'message' => 'Unable to book session.'];
        } catch (PDOException $ex) {
            return ['ok' => false, 'message' => 'Database error while booking session.'];
        }
    }

    public function createScheduleByAdmin(int $userId, int $trainerId, string $start, string $end, string $status = 'PENDING'): array
    {
        $s = $this->normalizeDateTime($start);
        $e = $this->normalizeDateTime($end);

        if (strtotime($e) <= strtotime($s)) {
            return ['ok' => false, 'message' => 'End time must be later than start time.'];
        }

        if ($this->hasScheduleConflict($trainerId, $s, $e)) {
            return ['ok' => false, 'message' => 'Trainer time slot conflict detected.'];
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO `workoutschedule` (UserID, TrainerID, ScheduleStart, ScheduleEnd, AttendanceStatus)
                 VALUES (:uid, :tid, :start, :end, :status)"
            );
            $ok = $stmt->execute([
                ':uid' => $userId,
                ':tid' => $trainerId,
                ':start' => $s,
                ':end' => $e,
                ':status' => strtoupper($status),
            ]);
            return ['ok' => $ok, 'message' => $ok ? 'Workout schedule assigned.' : 'Failed to assign schedule.'];
        } catch (PDOException $ex) {
            return ['ok' => false, 'message' => 'Database error while assigning schedule.'];
        }
    }

    public function updateClientSchedule(int $scheduleId, string $userEmail, int $trainerId, string $start, string $end): array
    {
        $s = $this->normalizeDateTime($start);
        $e = $this->normalizeDateTime($end);

        if (strtotime($e) <= strtotime($s)) {
            return ['ok' => false, 'message' => 'End time must be after start time.'];
        }

        if ($this->hasScheduleConflict($trainerId, $s, $e, $scheduleId)) {
            return ['ok' => false, 'message' => 'Selected trainer is already booked at that time.'];
        }

        try {
            $stmt = $this->db->prepare(
                "UPDATE `workoutschedule` ws
                 INNER JOIN `user` u ON ws.UserID = u.UserID
                 SET ws.TrainerID = :tid, ws.ScheduleStart = :start, ws.ScheduleEnd = :end
                 WHERE ws.ScheduleID = :sid AND u.UserEmail = :email"
            );
            $ok = $stmt->execute([
                ':tid' => $trainerId,
                ':start' => $s,
                ':end' => $e,
                ':sid' => $scheduleId,
                ':email' => trim($userEmail),
            ]);
            return ['ok' => $ok, 'message' => $ok ? 'Schedule updated.' : 'Failed to update schedule.'];
        } catch (PDOException $ex) {
            return ['ok' => false, 'message' => 'Database error updating schedule.'];
        }
    }

    public function updateAttendanceStatus(int $scheduleId, string $status): bool
    {
        $allowed = ['PENDING', 'ATTENDED', 'ABSENT'];
        $cleanStatus = in_array(strtoupper($status), $allowed, true) ? strtoupper($status) : 'PENDING';

        $stmt = $this->db->prepare("UPDATE `workoutschedule` SET AttendanceStatus = :status WHERE ScheduleID = :id");
        return $stmt->execute([
            ':status' => $cleanStatus,
            ':id' => $scheduleId,
        ]);
    }

    public function deleteUserSchedule(int $scheduleId, string $userEmail): bool
    {
        $stmt = $this->db->prepare(
            "DELETE ws FROM `workoutschedule` ws
             INNER JOIN `user` u ON ws.UserID = u.UserID
             WHERE ws.ScheduleID = :sid AND u.UserEmail = :email"
        );
        return $stmt->execute([
            ':sid' => $scheduleId,
            ':email' => trim($userEmail),
        ]);
    }

    public function deleteScheduleByAdmin(int $scheduleId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `workoutschedule` WHERE ScheduleID = :id");
        return $stmt->execute([':id' => $scheduleId]);
    }
}
