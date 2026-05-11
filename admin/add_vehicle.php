<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error = '';

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
        // Sprawdzenie unikalności VIN
        if (!empty($vin)) {
            $check = $pdo->prepare("SELECT id FROM vehicles WHERE vin = ?");
            $check->execute([$vin]);
            if ($check->fetch()) {
                $error = "Pojazd o tym numerze VIN już istnieje.";
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                INSERT INTO vehicles (number, brand, model, vin, year, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([
                $number ?: null,
                $brand,
                $model,
                $vin ?: null,
                $year ?: null,
                $status
            ])) {
                header("Location: vehicles.php?success=added");
                exit;
            } else {
                $error = "Wystąpił błąd podczas dodawania pojazdu.";
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
            <h1 class="dashboard-title">Dodaj pojazd</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-plus"></i> Nowy pojazd</h2>

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
                           placeholder="np. Ferrari">
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" required
                           placeholder="np. SF-23">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer startowy</label>
                    <input type="text" name="number"
                           placeholder="np. 16">
                </div>
                <div class="form-group">
                    <label>Rok produkcji</label>
                    <input type="number" name="year"
                           min="1900" max="<?php echo date('Y'); ?>"
                           placeholder="<?php echo date('Y'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer VIN</label>
                    <input type="text" name="vin"
                           placeholder="opcjonalnie">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktywny">Aktywny</option>
                        <option value="w_naprawie">W naprawie</option>
                        <option value="wycofany">Wycofany</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-plus"></i> Dodaj pojazd
                </button>
                <a href="vehicles.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>

        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>