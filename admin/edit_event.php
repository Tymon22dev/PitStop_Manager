<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events_manage.php");
    exit;
}

$event_id = (int)$_GET['id'];
$error    = '';
$success  = '';

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: events_manage.php");
    exit;
}

// Pobierz przypisane pojazdy
$stmtVehicles = $pdo->prepare("SELECT vehicle_id FROM event_vehicles WHERE event_id = ?");
$stmtVehicles->execute([$event_id]);
$assignedVehicles = array_column($stmtVehicles->fetchAll(PDO::FETCH_ASSOC), 'vehicle_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $track_name  = trim($_POST['track_name'] ?? '');
    $status      = $_POST['status'] ?? 'zaplanowane';
    $description = trim($_POST['description'] ?? '');
    $vehicles    = isset($_POST['vehicles']) && is_array($_POST['vehicles']) ? $_POST['vehicles'] : [];

    if (empty($title) || empty($event_date)) {
        $error = "Tytuł i data są wymagane.";
    } else {
        // Aktualizuj wydarzenie
        $stmt = $pdo->prepare("UPDATE events SET title = ?, event_date = ?, track_name = ?, status = ?, description = ? WHERE id = ?");
        if ($stmt->execute([$title, $event_date, $track_name, $status, $description, $event_id])) {
            // Usuń stare przypisania pojazdów
            $pdo->prepare("DELETE FROM event_vehicles WHERE event_id = ?")->execute([$event_id]);

            // Dodaj nowe przypisania
            if (!empty($vehicles)) {
                $stmtVehicle = $pdo->prepare("INSERT INTO event_vehicles (event_id, vehicle_id) VALUES (?, ?)");
                foreach ($vehicles as $v_id) {
                    $stmtVehicle->execute([$event_id, $v_id]);
                }
            }

            header("Location: events_manage.php?success=edited");
            exit;
        } else {
            $error = "Wystąpił błąd podczas zapisywania zmian.";
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
            <h1 class="dashboard-title">Edycja: <?php echo htmlspecialchars($event['title']); ?></h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-edit"></i> Edytuj wydarzenie</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form">
            <div class="form-group">
                <label>Tytuł wyścigu / wydarzenia *</label>
                <input type="text" name="title" required
                       value="<?php echo htmlspecialchars($event['title']); ?>">
            </div>

            <div class="form-group">
                <label>Data *</label>
                <input type="date" name="event_date" required
                       value="<?php echo htmlspecialchars($event['event_date']); ?>">
            </div>

            <div class="form-group">
                <label>Nazwa Toru</label>
                <input type="text" name="track_name"
                       value="<?php echo htmlspecialchars($event['track_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="zaplanowane" <?php echo $event['status'] === 'zaplanowane' ? 'selected' : ''; ?>>Zaplanowane</option>
                    <option value="zakończone" <?php echo $event['status'] === 'zakończone' ? 'selected' : ''; ?>>Zakończone</option>
                    <option value="anulowane" <?php echo $event['status'] === 'anulowane' ? 'selected' : ''; ?>>Anulowane</option>
                </select>
            </div>

            <div class="form-group">
                <label>Przypisz pojazdy</label>
                <select name="vehicles[]" multiple class="form-select-multiple">
                    <?php foreach ($vehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>" <?php echo in_array($v['id'], $assignedVehicles) ? 'selected' : ''; ?>>
                            #<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Przytrzymaj CTRL aby zaznaczyć wiele pojazdów. Aktualne przypisania są zaznaczone.</small>
            </div>

            <div class="form-group">
                <label>Opis / Notatki</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Zapisz zmiany
                </button>
                <a href="events_manage.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>
        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>
