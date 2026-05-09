<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_event'])) {
        $title = trim($_POST['title']);
        $event_date = $_POST['event_date'];
        $track_name = trim($_POST['track_name']);
        $status = $_POST['status'];
        $description = trim($_POST['description']);

        if (!empty($title) && !empty($event_date)) {
            $stmt = $pdo->prepare("INSERT INTO events (title, event_date, track_name, status, description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $event_date, $track_name, $status, $description])) {
                $event_id = $pdo->lastInsertId();
                if (isset($_POST['vehicles']) && is_array($_POST['vehicles'])) {
                    $stmtVehicle = $pdo->prepare("INSERT INTO event_vehicles (event_id, vehicle_id) VALUES (?, ?)");
                    foreach ($_POST['vehicles'] as $v_id) {
                        $stmtVehicle->execute([$event_id, $v_id]);
                    }
                }
                $message = "Wydarzenie zostało pomyślnie dodane!";
                $messageType = "success";
            }
        } else {
            $message = "Tytuł i data są wymagane.";
            $messageType = "danger";
        }
    }

    if (isset($_POST['delete_event'])) {
        $id_to_delete = $_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        if ($stmt->execute([$id_to_delete])) {
            $message = "Wydarzenie zostało usunięte.";
            $messageType = "success";
        }
    }

    if (isset($_POST['update_result'])) {
        $id = $_POST['event_id'];
        $status = $_POST['status'];
        $result = trim($_POST['result']);

        $stmt = $pdo->prepare("UPDATE events SET status = ?, result = ? WHERE id = ?");
        $stmt->execute([$status, $result, $id]);
        $message = "Status i wynik zaktualizowane.";
        $messageType = "success";
    }
}

$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper">
        <div class="dashboard-header mb-40">
            <p class="dashboard-sub">Panel administracyjny</p>
            <h1 class="dashboard-title">Zarządzanie Kalendarzem</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="admin-layout-grid">

            <!-- Formularz dodawania -->
            <section class="card admin-panel-card">
                <h2><i class="fas fa-plus-circle"></i> Nowe wydarzenie</h2>
                <form action="" method="POST" class="admin-form">
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
                        <button type="submit" name="add_event" class="btn-save">
                            <i class="fas fa-save"></i> Zapisz do kalendarza
                        </button>
                    </div>
                </form>
            </section>

            <!-- Lista -->
            <section class="card admin-panel-card">
                <h2><i class="fas fa-calendar-alt"></i> Lista Wydarzeń</h2>
                <div class="table-container table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tytuł / Tor</th>
                            <th>Status</th>
                            <th>Wynik</th>
                            <th>Akcje</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($events)): ?>
                            <tr><td colspan="5" class="text-center text-muted">Brak wydarzeń.</td></tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d.m.Y', strtotime($event['event_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($event['track_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $bClass = 'badge-pending';
                                        if ($event['status'] == 'zakończone') $bClass = 'badge-success';
                                        if ($event['status'] == 'anulowane') $bClass = 'badge-danger';
                                        ?>
                                        <span class="badge <?php echo $bClass; ?>"><?php echo htmlspecialchars($event['status']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['result']); ?></td>
                                    <td>
                                        <div class="action-buttons-flex">
                                            <form method="POST" class="inline-form-flex" onsubmit="return confirm('Zaktualizować status i wynik?');">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <select name="status" class="inline-select">
                                                    <option value="zaplanowane" <?php echo $event['status'] == 'zaplanowane' ? 'selected' : ''; ?>>Zaplanowane</option>
                                                    <option value="zakończone" <?php echo $event['status'] == 'zakończone' ? 'selected' : ''; ?>>Zakończone</option>
                                                    <option value="anulowane" <?php echo $event['status'] == 'anulowane' ? 'selected' : ''; ?>>Anulowane</option>
                                                </select>
                                                <input type="text" name="result" value="<?php echo htmlspecialchars($event['result']); ?>" class="inline-input" placeholder="Wynik">
                                                <button type="submit" name="update_result" class="btn-save btn-sm"><i class="fas fa-check"></i></button>
                                            </form>

                                            <form method="POST" onsubmit="return confirm('Na pewno chcesz trwale usunąć to wydarzenie?');">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" name="delete_event" class="btn-sm btn-danger-outline">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

<?php include '../includes/footer.php'; ?>