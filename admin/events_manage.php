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
    if (isset($_POST['delete_event'])) {
        $id_to_delete = $_POST['event_id'];
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        if ($stmt->execute([$id_to_delete])) {
            header("Location: events_manage.php?success=deleted");
            exit;
        }
    }

    if (isset($_POST['update_result'])) {
        $id = $_POST['event_id'];
        $status = $_POST['status'];
        $result = trim($_POST['result']);

        $stmt = $pdo->prepare("UPDATE events SET status = ?, result = ? WHERE id = ?");
        $stmt->execute([$status, $result, $id]);
        header("Location: events_manage.php?success=updated");
        exit;
    }
}

$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Pobierz przypisane pojazdy dla każdego wydarzenia
$eventVehicles = [];
foreach ($events as $event) {
    $stmt = $pdo->prepare("SELECT v.id, v.number, v.brand, v.model FROM event_vehicles ev JOIN vehicles v ON ev.vehicle_id = v.id WHERE ev.event_id = ?");
    $stmt->execute([$event['id']]);
    $eventVehicles[$event['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../includes/header.php';
?>

    <main class="home-wrapper">
        
    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Kalendarz</p>
            <h1 class="dashboard-title">Wydarzenia</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php
                if ($_GET['success'] === 'deleted') echo "Wydarzenie zostało usunięte.";
                if ($_GET['success'] === 'added')   echo "Wydarzenie zostało dodane.";
                if ($_GET['success'] === 'edited')  echo "Wydarzenie zostało zaktualizowane.";
                if ($_GET['success'] === 'updated') echo "Status i wynik zostały zaktualizowane.";
            ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-calendar-alt"></i> Lista wydarzeń</h2>

                <div class="table-container table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tytuł / Tor</th>
                            <th>Status</th>
                            <th>Pojazdy</th>
                            <th>Wynik</th>
                            <th>Akcje</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($events)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Brak wydarzeń.</td></tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d.m.Y', strtotime($event['event_date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($event['track_name']); ?></small>
                                    </td>
                                    <td>
                                        <form method="POST" class="inline-form-flex" onsubmit="return confirm('Zaktualizować status wydarzenia?');">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <select name="status" class="inline-select">
                                                <option value="zaplanowane" <?php echo $event['status'] == 'zaplanowane' ? 'selected' : ''; ?>>Zaplanowane</option>
                                                <option value="zakończone" <?php echo $event['status'] == 'zakończone' ? 'selected' : ''; ?>>Zakończone</option>
                                                <option value="anulowane" <?php echo $event['status'] == 'anulowane' ? 'selected' : ''; ?>>Anulowane</option>
                                            </select>
                                            <button type="submit" name="update_result" class="btn-save btn-sm">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if (!empty($eventVehicles[$event['id']])): ?>
                                            <?php foreach ($eventVehicles[$event['id']] as $vehicle): ?>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($vehicle['number'] . ' - ' . $vehicle['brand'] . ' ' . $vehicle['model']); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Brak przypisanych</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['result']); ?></td>
                                    <td class="table-actions">
                                        <!-- Przycisk Edytuj -->
                                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" 
                                        class="btn-action btn-edit" title="Edytuj">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="events.php?delete=<?php echo $event['id']; ?>" 
                                        class="btn-action btn-deactivate" title="Usuń" 
                                        onclick="return confirm('Na pewno chcesz trwale usunąć to wydarzenie?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <a href="add_event.php" class="btn-save">
                        <i class="fas fa-plus"></i> Dodaj wydarzenie
                    </a>
                </div>
        </div>
    </main>

<?php include '../includes/footer.php'; ?>