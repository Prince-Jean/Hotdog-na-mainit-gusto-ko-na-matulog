<?php
declare(strict_types=1);
session_start();
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\Authentication;
use App\Models\Trainer;
use App\Algorithms\SearchAndSort;

Authentication::requireAuth(true);

$trainerModel = new Trainer();
$notice = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_trainer'])) {
        $ok = $trainerModel->createTrainer(
            $_POST['last_name'] ?? '',
            $_POST['first_name'] ?? '',
            $_POST['specialization'] ?? 'STRENGTH',
            (float)($_POST['rate'] ?? 300)
        );
        $ok ? ($notice = "Trainer added.") : ($error = "Failed to add trainer.");
    } elseif (isset($_POST['update_trainer'])) {
        $ok = $trainerModel->updateTrainer(
            (int)$_POST['trainer_id'],
            $_POST['last_name'] ?? '',
            $_POST['first_name'] ?? '',
            $_POST['specialization'] ?? 'STRENGTH',
            (float)($_POST['rate'] ?? 300)
        );
        $ok ? ($notice = "Trainer updated.") : ($error = "Failed to update trainer.");
    } elseif (isset($_POST['delete_trainer'])) {
        $ok = $trainerModel->deleteTrainer((int)$_POST['trainer_id']);
        $ok ? ($notice = "Trainer removed.") : ($error = "Failed to remove trainer.");
    }
}

$sortBy = $_GET['sort_by'] ?? 'FullName';
$dir = strtolower($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$type = ($sortBy === 'RatePerHour' || $sortBy === 'TrainerID') ? 'number' : 'string';

$trainers = SearchAndSort::mergeSort($trainerModel->getAllTrainers(), $sortBy, $dir, $type);
$specs = $trainerModel->getAllSpecializations();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Trainers - BIG Gainz Fitness</title>
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
      <a href="admin_trainers.php" class="btn btn-sm btn-light fw-bold">Trainers</a>
      <a href="admin_schedules.php" class="btn btn-sm btn-outline-light">Schedules</a>
      <a href="admin_payments.php" class="btn btn-sm btn-outline-light">Renewals</a>
      <a href="../logout.php" class="btn btn-sm btn-danger">Logout</a>
    </div>
  </nav>

  <main class="container py-4">
    <h1 class="h3 fw-bold mb-3">TRAINER MANAGEMENT</h1>
    <?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="panel">
      <h2 class="h6 fw-bold mb-3">Add New Trainer</h2>
      <form method="POST" class="row g-2">
        <div class="col-md-3"><input type="text" name="first_name" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="First Name" required></div>
        <div class="col-md-3"><input type="text" name="last_name" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Last Name" required></div>
        <div class="col-md-2">
          <select name="specialization" class="form-select form-select-sm bg-dark text-white border-secondary">
            <?php foreach ($specs as $s): ?>
              <option value="<?= htmlspecialchars($s['SpecType']) ?>"><?= htmlspecialchars($s['SpecType']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.01" name="rate" class="form-control form-control-sm bg-dark text-white border-secondary" value="300.00" required></div>
        <div class="col-md-2"><button type="submit" name="add_trainer" class="btn btn-sm btn-accent fw-bold w-100">Add Trainer</button></div>
      </form>
    </div>

    <div class="panel">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h6 fw-bold mb-0">Sorted Trainers (Merge Sort)</h2>
        <div class="d-flex gap-2">
          <a href="?sort_by=FullName&dir=asc" class="btn btn-sm btn-outline-light">Name A-Z</a>
          <a href="?sort_by=RatePerHour&dir=desc" class="btn btn-sm btn-outline-light">Rate High-Low</a>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle small mb-0">
          <thead><tr><th>ID</th><th>Trainer</th><th>Specialization</th><th>Rate / Hr</th><th>Update / Remove</th></tr></thead>
          <tbody>
            <?php foreach ($trainers as $t): ?>
              <tr>
                <td><?= (int)$t['TrainerID'] ?></td>
                <td><?= htmlspecialchars($t['FirstName'] . ' ' . $t['LastName']) ?></td>
                <td><?= htmlspecialchars($t['SpecializationType']) ?></td>
                <td>&#8369;<?= number_format((float)$t['RatePerHour'], 2) ?></td>
                <td>
                  <form method="POST" class="d-inline-flex gap-1">
                    <input type="hidden" name="trainer_id" value="<?= (int)$t['TrainerID'] ?>">
                    <input type="hidden" name="first_name" value="<?= htmlspecialchars($t['FirstName'], ENT_QUOTES) ?>">
                    <input type="hidden" name="last_name" value="<?= htmlspecialchars($t['LastName'], ENT_QUOTES) ?>">
                    <select name="specialization" class="form-select form-select-sm bg-dark text-white border-secondary" style="width:130px;">
                      <?php foreach ($specs as $s): ?>
                        <option value="<?= htmlspecialchars($s['SpecType']) ?>" <?= $s['SpecType'] === $t['SpecializationType'] ? 'selected' : '' ?>><?= htmlspecialchars($s['SpecType']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="number" step="0.01" name="rate" value="<?= htmlspecialchars((string)$t['RatePerHour']) ?>" class="form-control form-control-sm bg-dark text-white border-secondary" style="width:95px;">
                    <button type="submit" name="update_trainer" class="btn btn-sm btn-outline-success">Save</button>
                    <button type="submit" name="delete_trainer" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete trainer?');">Del</button>
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
