<?php
session_start();
require_once '../config/db.php';

// Zabezpieczenie: dostęp tylko dla zalogowanego użytkownika (mechanika)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// --- OBSŁUGA AKCJI CRUD (ZAPIS, EDYCJA, USUWANIE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. DODAWANIE NOWEGO LOGU
    if (isset($_POST['add_log'])) {
        $vehicle_id = $_POST['vehicle_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        $stmt = $pdo->prepare("INSERT INTO logs (user_id, vehicle_id, title, content) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $vehicle_id, $title, $content])) {
            $message = "Nowy raport został pomyślnie dodany do dziennika.";
            $messageType = "success";
        } else {
            $message = "Wystąpił błąd podczas dodawania raportu.";
            $messageType = "danger";
        }
    }

    // 2. EDYCJA ISTNIEJĄCEGO LOGU
    if (isset($_POST['edit_log'])) {
        $log_id = $_POST['log_id'];
        $vehicle_id = $_POST['vehicle_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);

        // Upewniamy się, że mechanik edytuje TYLKO SWÓJ wpis (warunek user_id = ?)
        $stmt = $pdo->prepare("UPDATE logs SET vehicle_id = ?, title = ?, content = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$vehicle_id, $title, $content, $log_id, $user_id])) {
            $message = "Raport został pomyślnie zaktualizowany.";
            $messageType = "success";
        }
    }

    // 3. USUWANIE LOGU
    if (isset($_POST['delete_log'])) {
        $log_id = $_POST['log_id'];
        // Ponownie weryfikacja user_id - mechanik nie usunie logu kolegi
        $stmt = $pdo->prepare("DELETE FROM logs WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$log_id, $user_id])) {
            $message = "Raport został trwale usunięty ze względów bezpieczeństwa.";
            $messageType = "success";
        }
    }
}

// --- POBIERANIE DANYCH DO WIDOKU ---

// Jeśli w URL jest parametre ?edit_id=X, pobieramy dane tego logu do formularza
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit_id'], $user_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Pobieramy listę aktywnych pojazdów (do listy rozwijanej w formularzu)
$vehicles = $pdo->query("SELECT id, brand, model, number FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);

// Pobieramy całą historię logów TYLKO tego użytkownika
$my_logs = $pdo->prepare("
    SELECT l.*, v.brand, v.model, v.number 
    FROM logs l 
    LEFT JOIN vehicles v ON l.vehicle_id = v.id 
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC
");
$my_logs->execute([$user_id]);
$logs = $my_logs->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper">
        <!-- Nagłówek panelu -->
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Zarządzanie pracą</p>
                <h1 class="dashboard-title">Dziennik Serwisowy</h1>
            </div>
        </div>

        <!-- Wyświetlanie powiadomień -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="admin-layout-grid">

            <!-- KOLUMNA LEWA: Formularz Dodawania/Edycji -->
            <section class="card">
                <h2>
                    <i class="fas <?php echo $edit_data ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                    <?php echo $edit_data ? 'Edytuj raport serwisowy' : 'Dodaj nowy raport'; ?>
                </h2>

                <form action="logs_manage.php" method="POST" class="admin-form">
                    <?php if($edit_data): ?>
                        <!-- Ukryte pole z ID edytowanego logu -->
                        <input type="hidden" name="log_id" value="<?php echo $edit_data['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Pojazd *</label>
                        <select name="vehicle_id" required>
                            <option value="">Wybierz serwisowany pojazd...</option>
                            <?php foreach($vehicles as $v): ?>
                                <!-- Sprawdzamy czy to tryb edycji i zaznaczamy właściwy pojazd -->
                                <option value="<?php echo $v['id']; ?>" <?php echo ($edit_data && $edit_data['vehicle_id'] == $v['id']) ? 'selected' : ''; ?>>
                                    #<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tytuł prac *</label>
                        <input type="text" name="title" required placeholder="np. Wymiana klocków hamulcowych" value="<?php echo $edit_data ? htmlspecialchars($edit_data['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Szczegółowy opis techniczny *</label>
                        <textarea name="content" required rows="6" placeholder="Opisz dokładnie jakie prace zostały wykonane, jakie części zużyto..."><?php echo $edit_data ? htmlspecialchars($edit_data['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="submit" name="<?php echo $edit_data ? 'edit_log' : 'add_log'; ?>" class="btn-save">
                            <i class="fas fa-save"></i> <?php echo $edit_data ? 'Zapisz zmiany' : 'Zapisz raport'; ?>
                        </button>

                        <?php if($edit_data): ?>
                            <!-- Przycisk powrotu/anulowania jeśli jesteśmy w trybie edycji -->
                            <a href="logs_manage.php" class="btn-outline" style="text-decoration:none; padding:12px 20px;">Anuluj</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <!-- KOLUMNA PRAWA: Tabela historii wpisów -->
            <section class="card">
                <h2><i class="fas fa-history"></i> Twoja historia prac</h2>
                <div class="table-container table-responsive">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Data</th>
                            <th>Pojazd</th>
                            <th>Tytuł prac</th>
                            <th>Akcje</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if(empty($logs)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Brak wpisów. Wykonaj swoją pierwszą naprawę!</td></tr>
                        <?php else: ?>
                            <?php foreach($logs as $log): ?>
                                <tr>
                                    <td class="text-nowrap"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>

                                    <td>
                                        <strong>#<?php echo htmlspecialchars($log['number']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['brand'] . ' ' . $log['model']); ?></small>
                                    </td>

                                    <td><?php echo htmlspecialchars($log['title']); ?></td>

                                    <td>
                                        <!-- Przyciski akcji wykorzystujące wbudowane funkcje JS dla potwierdzenia kasowania -->
                                        <div class="action-buttons-flex">
                                            <!-- Edycja (przekierowuje na tę samą stronę dodając ID do URL) -->
                                            <a href="logs_manage.php?edit_id=<?php echo $log['id']; ?>" class="btn-sm btn-outline" style="text-decoration:none;">
                                                <i class="fas fa-pen"></i>
                                            </a>

                                            <!-- Usuwanie (Kasowanie z potwierdzeniem - wymóg projektu) -->
                                            <form method="POST" onsubmit="return confirm('Czy na pewno chcesz usunąć ten raport? Tej akcji nie można cofnąć.');">
                                                <input type="hidden" name="log_id" value="<?php echo $log['id']; ?>">
                                                <button type="submit" name="delete_log" class="btn-sm btn-danger-outline">
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