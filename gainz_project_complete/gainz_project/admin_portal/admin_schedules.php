<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\Schedule;
use App\Models\Client;
use App\Models\Trainer;
use App\Algorithms\SearchAndSort;

Authentication::requireAuth(true);

$scheduleModel = new Schedule();
$clientModel = new Client();
$trainerModel = new Trainer();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_schedule'])) {
        $res = $scheduleModel->createScheduleByAdmin(
            (int)$_POST['user_id'],
            (int)$_POST['trainer_id'],
            $_POST['schedule_start'] ?? '',
            $_POST['schedule_end'] ?? '',
            $_POST['attendance_status'] ?? 'PENDING'
        );
        if ($res['ok']) {
            $notice = $res['message'];
        } else {
            $error = $res['message'];
        }
    } elseif (isset($_POST['update_attendance'])) {
        $scheduleModel->updateAttendanceStatus((int)$_POST['schedule_id'], $_POST['attendance_status'] ?? 'PENDING');
        $notice = "Attendance status updated.";
    } elseif (isset($_POST['delete_schedule'])) {
        $scheduleModel->deleteScheduleByAdmin((int)$_POST['schedule_id']);
        $notice = "Schedule deleted.";
    }
}

$sortBy = $_GET['sort_by'] ?? 'ScheduleStart';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$type = $sortBy === 'ScheduleStart' ? 'date' : 'string';

$schedules = SearchAndSort::mergeSort($scheduleModel->getAllSchedules(), $sortBy, $dir, $type);
$clients = $clientModel->getAllClients();
$trainers = $trainerModel->getAllTrainers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Workout Schedules - BIG Gainz Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@400;600;700;800&family=Outfit:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <nav class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3">
    <a href="admin_dashboard.php" class="text-white fw-bold text-decoration-none"><i class="fa-solid fa-dumbbell text-danger me-2"></i>BIG GainzFitness Admin</a>
    <div class="d-flex flex-wrap gap-2">
      <a href="admin_dashboard.php" class="btn btn-sm btn-outline-light">Overview</a>
      <a href="admin_clients.php" class="btn btn-sm btn-outline-light">Clients</a>
      <a href="admin_trainers.php" class="btn btn-sm btn-outline-light">Trainers</a>
      <a href="admin_schedules.php" class="btn btn-sm btn-light fw-bold">Schedules</a>
      <a href="admin_payments.php" class="btn btn-sm btn-outline-light">Renewals</a>
      <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </nav>

  <main class="container py-4">
    <h1 class="h3 fw-bold mb-3">WORKOUT SCHEDULES &amp; ATTENDANCE</h1>
    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel">
      <h2 class="h6 fw-bold mb-3">Assign Training Schedule</h2>
      <form method="POST" class="row g-2">
        <div class="col-md-3">
          <select name="user_id" class="form-select form-select-sm bg-dark text-white border-secondary" required>
            <?php foreach ($clients as $c): ?>
              <option value="<?= (int)$c['UserID'] ?>"><?= htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="trainer_id" class="form-select form-select-sm bg-dark text-white border-secondary" required>
            <?php foreach ($trainers as $t): ?>
              <option value="<?= (int)$t['TrainerID'] ?>"><?= htmlspecialchars($t['FirstName'] . ' ' . $t['LastName']) ?> (<?= htmlspecialchars($t['SpecializationType']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><input type="datetime-local" name="schedule_start" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-2"><input type="datetime-local" name="schedule_end" class="form-control form-control-sm bg-dark text-white border-secondary" required></div>
        <div class="col-md-2"><button type="submit" name="assign_schedule" class="btn btn-sm btn-accent fw-bold w-100">Assign</button></div>
      </form>
    </div>

    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0">Chronological Schedules (Merge Sort)</h2>
        <div class="d-flex gap-2">
          <a href="?sort_by=ScheduleStart&dir=asc" class="btn btn-sm btn-outline-light">Earliest First</a>
          <a href="?sort_by=ScheduleStart&dir=desc" class="btn btn-sm btn-outline-light">Latest First</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle small mb-0">
          <thead><tr><th>ID</th><th>Member</th><th>Trainer</th><th>Start</th><th>End</th><th>Attendance</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($schedules as $s): ?>
              <tr>
                <td><?= (int)$s['ScheduleID'] ?></td>
                <td><?= htmlspecialchars($s['MemberName']) ?></td>
                <td><?= htmlspecialchars($s['TrainerName']) ?></td>
                <td><?= htmlspecialchars($s['ScheduleStart']) ?></td>
                <td><?= htmlspecialchars($s['ScheduleEnd']) ?></td>
                <td>
                  <form method="POST" class="d-inline-flex gap-1">
                    <input type="hidden" name="schedule_id" value="<?= (int)$s['ScheduleID'] ?>">
                    <select name="attendance_status" class="form-select form-select-sm bg-dark text-white border-secondary">
                      <option value="PENDING" <?= $s['AttendanceStatus'] === 'PENDING' ? 'selected' : '' ?>>PENDING</option>
                      <option value="ATTENDED" <?= $s['AttendanceStatus'] === 'ATTENDED' ? 'selected' : '' ?>>ATTENDED</option>
                      <option value="ABSENT" <?= $s['AttendanceStatus'] === 'ABSENT' ? 'selected' : '' ?>>ABSENT</option>
                    </select>
                    <button type="submit" name="update_attendance" class="btn btn-sm btn-outline-light">Set</button>
                  </form>
                </td>
                <td>
                  <form method="POST" onsubmit="return confirm('Remove schedule?');">
                    <input type="hidden" name="schedule_id" value="<?= (int)$s['ScheduleID'] ?>">
                    <button type="submit" name="delete_schedule" class="btn btn-sm btn-outline-danger">Del</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</body>
</html>
