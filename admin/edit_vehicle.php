<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: vehicles.php");
    exit;
}

$vehicle_id = (int)$_GET['id'];
$error      = '';

$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    header("Location: vehicles.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = trim($_POST['number'] ?? '');
    $brand  = trim($_POST['brand'] ?? '');
    $model  = trim($_POST['model'] ?? '');
    $vin    = trim($_POST['vin'] ?? '');
    $year   = $_POST['year'] ?? null;
    $status = $_POST['status'] ?? 'aktywny';

    if (empty($brand) || empty($model)) {
        $error = "Marka i model są wymagane.";
    } else {
        // Sprawdzenie unikalności VIN (pomijając obecny pojazd)
        if (!empty($vin)) {
            $check = $pdo->prepare("SELECT id FROM vehicles WHERE vin = ? AND id != ?");
            $check->execute([$vin, $vehicle_id]);
            if ($check->fetch()) {
                $error = "Pojazd o tym numerze VIN już istnieje.";
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                UPDATE vehicles 
                SET number = ?, brand = ?, model = ?, vin = ?, year = ?, status = ?
                WHERE id = ?
            ");
            if ($stmt->execute([
                $number ?: null,
                $brand,
                $model,
                $vin ?: null,
                $year ?: null,
                $status,
                $vehicle_id
            ])) {
                header("Location: vehicles.php?success=edited");
                exit;
            } else {
                $error = "Wystąpił błąd podczas zapisywania zmian.";
            }
        }
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Pojazdy</p>
            <h1 class="dashboard-title">Edycja: <?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-edit"></i> Edytuj pojazd</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Marka</label>
                    <input type="text" name="brand" required
                           value="<?php echo htmlspecialchars($vehicle['brand']); ?>">
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" required
                           value="<?php echo htmlspecialchars($vehicle['model']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer startowy</label>
                    <input type="text" name="number"
                           value="<?php echo htmlspecialchars($vehicle['number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Rok produkcji</label>
                    <input type="number" name="year"
                           min="1900" max="<?php echo date('Y'); ?>"
                           value="<?php echo htmlspecialchars($vehicle['year'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer VIN</label>
                    <input type="text" name="vin"
                           value="<?php echo htmlspecialchars($vehicle['vin'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktywny"    <?php echo $vehicle['status'] === 'aktywny'    ? 'selected' : ''; ?>>Aktywny</option>
                        <option value="w_naprawie" <?php echo $vehicle['status'] === 'w_naprawie' ? 'selected' : ''; ?>>W naprawie</option>
                        <option value="wycofany"   <?php echo $vehicle['status'] === 'wycofany'   ? 'selected' : ''; ?>>Wycofany</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Zapisz zmiany
                </button>
                <a href="vehicles.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>

        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>