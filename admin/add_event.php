<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $track_name  = trim($_POST['track_name'] ?? '');
    $status      = $_POST['status'] ?? 'zaplanowane';
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($event_date)) {
        $error = "Tytuł i data są wymagane.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO events (title, event_date, track_name, status, description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $event_date, $track_name, $status, $description])) {
            $event_id = $pdo->lastInsertId();
            if (isset($_POST['vehicles']) && is_array($_POST['vehicles'])) {
                $stmtVehicle = $pdo->prepare("INSERT INTO event_vehicles (event_id, vehicle_id) VALUES (?, ?)");
                foreach ($_POST['vehicles'] as $v_id) {
                    $stmtVehicle->execute([$event_id, $v_id]);
                }
            }
            header("Location: events_manage.php?success=added");
            exit;
        } else {
            $error = "Wystąpił błąd podczas dodawania wydarzenia.";
        }
    }
}

$vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'aktywny' ORDER BY brand ASC, model ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Kalendarz</p>
            <h1 class="dashboard-title">Dodaj wydarzenie</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Nowe wydarzenie</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form">
            <div class="form-group">
                <label>Tytuł wyścigu / wydarzenia *</label>
                <input type="text" name="title" required placeholder="np. Grand Prix Monako">
            </div>

            <div class="form-group">
                <label>Data *</label>
                <input type="date" name="event_date" required>
            </div>

            <div class="form-group">
                <label>Nazwa Toru</label>
                <input type="text" name="track_name" placeholder="np. Circuit de Monaco">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="zaplanowane">Zaplanowane</option>
                    <option value="zakończone">Zakończone</option>
                    <option value="anulowane">Anulowane</option>
                </select>
            </div>

            <div class="form-group">
                <label>Przypisz pojazdy (Opcjonalne)</label>
                <select name="vehicles[]" multiple class="form-select-multiple">
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>">
                            #<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Przytrzymaj CTRL aby zaznaczyć wiele pojazdów.</small>
            </div>

            <div class="form-group">
                <label>Opis / Notatki</label>
                <textarea name="description" rows="3" placeholder="Dodatkowe informacje techniczne lub logistyczne..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Dodaj wydarzenie
                </button>
                <a href="events_manage.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>
        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
