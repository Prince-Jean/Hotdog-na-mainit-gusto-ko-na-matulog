SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS gym_database DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gym_database;

CREATE TABLE IF NOT EXISTS attendance (
  AttendanceStatus varchar(50) NOT NULL,
  AttendanceInfo text NOT NULL,
  PRIMARY KEY (AttendanceStatus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO attendance (AttendanceStatus, AttendanceInfo) VALUES
('ABSENT', 'Absent From Workout Schedule'),
('ATTENDED', 'Attended Workout Schedule'),
('PENDING', 'Pending Workout Schedule')
ON DUPLICATE KEY UPDATE AttendanceInfo = VALUES(AttendanceInfo);

CREATE TABLE IF NOT EXISTS membership (
  MembershipType varchar(50) NOT NULL,
  MembershipDesc varchar(100) NOT NULL,
  MembershipRateMonthly decimal(10,2) NOT NULL,
  MembershipRateBiannual decimal(10,2) NOT NULL DEFAULT 900.00,
  MembershipRateYearly decimal(10,2) NOT NULL,
  PRIMARY KEY (MembershipType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO membership (MembershipType, MembershipDesc, MembershipRateMonthly, MembershipRateBiannual, MembershipRateYearly) VALUES
('BASIC', 'Basic Package - Gym Access & Core Coaching', 1000.00, 900.00, 800.00),
('MID', 'Mid Package - Full Access & Extended Coaching', 1300.00, 1150.00, 1000.00),
('PRO', 'Pro Package - Unlimited Access & Priority Coaching', 1800.00, 1650.00, 1500.00)
ON DUPLICATE KEY UPDATE
  MembershipDesc = VALUES(MembershipDesc),
  MembershipRateMonthly = VALUES(MembershipRateMonthly),
  MembershipRateBiannual = VALUES(MembershipRateBiannual),
  MembershipRateYearly = VALUES(MembershipRateYearly);

CREATE TABLE IF NOT EXISTS specialization (
  SpecType varchar(50) NOT NULL,
  SpecDesc text NOT NULL,
  PRIMARY KEY (SpecType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO specialization (SpecType, SpecDesc) VALUES
('STRENGTH', 'Strength & Conditioning Coach'),
('CARDIO', 'Conditioning & Endurance Coach'),
('HYPERTROPHY', 'Hypertrophy & Physique Coach'),
('MOBILITY', 'Mobility & Recovery Coach')
ON DUPLICATE KEY UPDATE SpecDesc = VALUES(SpecDesc);

CREATE TABLE IF NOT EXISTS trainer (
  TrainerID int(11) NOT NULL AUTO_INCREMENT,
  LastName varchar(50) NOT NULL,
  FirstName varchar(50) NOT NULL,
  SpecializationType varchar(50) NOT NULL DEFAULT 'STRENGTH',
  RatePerHour decimal(10,2) NOT NULL DEFAULT 300.00,
  PRIMARY KEY (TrainerID),
  KEY fk_specialization (SpecializationType),
  CONSTRAINT fk_specialization FOREIGN KEY (SpecializationType) REFERENCES specialization (SpecType) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO trainer (TrainerID, LastName, FirstName, SpecializationType, RatePerHour) VALUES
(1, 'Ramos', 'Leonard', 'STRENGTH', 300.00),
(2, 'Cruz', 'Jean', 'STRENGTH', 300.00),
(3, 'Dela Cruz', 'Leon', 'STRENGTH', 300.00),
(4, 'Jugo', 'Jin', 'STRENGTH', 300.00),
(5, 'Pasana', 'Jillion', 'STRENGTH', 300.00),
(6, 'Bautista', 'Lij', 'STRENGTH', 300.00),
(7, 'Isla', 'Jeo', 'STRENGTH', 300.00),
(8, 'Pabuna', 'Prins', 'STRENGTH', 300.00),
(9, 'Lopez', 'Liam', 'STRENGTH', 300.00);

CREATE TABLE IF NOT EXISTS user (
  UserID int(11) NOT NULL AUTO_INCREMENT,
  LastName varchar(50) NOT NULL,
  FirstName varchar(50) NOT NULL,
  UserAge int(11) NOT NULL,
  UserGender varchar(1) NOT NULL,
  UserWeight decimal(10,2) NOT NULL,
  UserHeight decimal(10,2) NOT NULL,
  MembershipType varchar(50) NOT NULL DEFAULT 'BASIC',
  UserEmail varchar(100) NOT NULL,
  UserPassword_hash varchar(255) NOT NULL,
  UserPhoneNo varchar(50) NOT NULL,
  RegistrationDate timestamp NOT NULL DEFAULT current_timestamp(),
  MembershipExpiry date DEFAULT NULL,
  isAdmin tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (UserID),
  UNIQUE KEY UserEmail (UserEmail),
  UNIQUE KEY UserPhoneNo (UserPhoneNo),
  KEY fk_membershiptype (MembershipType),
  CONSTRAINT fk_membershiptype FOREIGN KEY (MembershipType) REFERENCES membership (MembershipType) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO user (UserID, LastName, FirstName, UserAge, UserGender, UserWeight, UserHeight, MembershipType, UserEmail, UserPassword_hash, UserPhoneNo, RegistrationDate, MembershipExpiry, isAdmin) VALUES
(1, 'Dela Cruz', 'Wally', 21, 'M', 70.00, 170.00, 'BASIC', 'juandelacruz@gmail.com', '$2y$10$hRxvEcLCGwVg8T8.EiEV0uxiwlAGdgdwIxe6zTWAKcMvNLdB285.u', '09911234567', '2026-11-13 12:39:08', '2026-12-19', 0),
(2, 'Ibarra', 'Crisostomo', 30, 'M', 80.00, 175.00, 'PRO', 'cristoibarra@gmail.com', '$2y$10$fcugKe/0xePPUgwOLn1INuHl3rYYvBQ2lMHApDV2Y2alKrKPmAQqe', '09918837462', '2026-11-13 12:40:31', '2027-11-19', 1);

CREATE TABLE IF NOT EXISTS workoutschedule (
  ScheduleID int(11) NOT NULL AUTO_INCREMENT,
  UserID int(11) NOT NULL,
  TrainerID int(11) NOT NULL,
  ScheduleStart datetime NOT NULL,
  ScheduleEnd datetime NOT NULL,
  AttendanceStatus varchar(50) NOT NULL DEFAULT 'PENDING',
  PRIMARY KEY (ScheduleID),
  KEY fk_user_schedule (UserID),
  KEY fk_trainer_schedule (TrainerID),
  KEY fk_attendance_schedule (AttendanceStatus),
  CONSTRAINT fk_trainer_schedule FOREIGN KEY (TrainerID) REFERENCES trainer (TrainerID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_schedule FOREIGN KEY (UserID) REFERENCES user (UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_attendance_schedule FOREIGN KEY (AttendanceStatus) REFERENCES attendance (AttendanceStatus) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO workoutschedule (ScheduleID, UserID, TrainerID, ScheduleStart, ScheduleEnd, AttendanceStatus) VALUES
(1, 1, 3, '2026-11-20 08:00:00', '2026-11-20 10:00:00', 'PENDING');

CREATE TABLE IF NOT EXISTS payment_transaction (
  PaymentID int(11) NOT NULL AUTO_INCREMENT,
  UserID int(11) NOT NULL,
  MembershipType varchar(50) NOT NULL,
  BillingCycle enum('MONTHLY','BIANNUAL','ANNUAL') NOT NULL DEFAULT 'MONTHLY',
  AmountPaid decimal(10,2) NOT NULL,
  PaymentDate datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ValidUntil date NOT NULL,
  PaymentNotes varchar(255) DEFAULT NULL,
  PRIMARY KEY (PaymentID),
  KEY fk_payment_user (UserID),
  KEY fk_payment_membership (MembershipType),
  CONSTRAINT fk_payment_user FOREIGN KEY (UserID) REFERENCES user (UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_payment_membership FOREIGN KEY (MembershipType) REFERENCES membership (MembershipType) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO payment_transaction (PaymentID, UserID, MembershipType, BillingCycle, AmountPaid, PaymentDate, ValidUntil, PaymentNotes) VALUES
(1, 1, 'BASIC', 'MONTHLY', 1000.00, '2026-11-19 09:00:00', '2026-12-19', 'Initial walk-in cash payment at Dagupan front desk'),
(2, 2, 'PRO', 'ANNUAL', 18000.00, '2026-11-19 10:00:00', '2027-11-19', 'Annual membership renewal recorded by admin');

CREATE TABLE IF NOT EXISTS contact_message (
  MessageID int(11) NOT NULL AUTO_INCREMENT,
  FirstName varchar(80) NOT NULL,
  LastName varchar(80) NOT NULL,
  Email varchar(120) NOT NULL,
  PhoneNumber varchar(40) NOT NULL,
  MessageBody text NOT NULL,
  SubmittedAt timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (MessageID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

COMMIT;