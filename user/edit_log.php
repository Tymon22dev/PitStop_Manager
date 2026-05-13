<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$user_id = $_SESSION['user_id'];
$log_id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

// Zabezpieczenie: Sprawdź czy log istnieje i czy należy do zalogowanego użytkownika!
if (!$log_id) {
    header("Location: logs_manage.php");
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ? AND user_id = ?");
$stmt->execute([$log_id, $user_id]);
$current_log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_log) {
    // Ktoś próbuje edytować cudzy log wpisując ID w pasek adresu!
    header("Location: logs_manage.php?error=Brak dostępu do tego raportu.");
    exit;
}

// --- ZAPISYWANIE ZMIAN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_log'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    $part_ids = $_POST['part_id'] ?? [];
    $part_qtys = $_POST['part_qty'] ?? [];

    if (!empty($vehicle_id) && !empty($title) && !empty($content)) {
        try {
            $pdo->beginTransaction();

            // 1. ODDANIE STARYCH CZĘŚCI DO MAGAZYNU (Revert)
            $stmtOldParts = $pdo->prepare("SELECT part_id, quantity_used FROM log_parts WHERE log_id = ?");
            $stmtOldParts->execute([$log_id]);
            $oldParts = $stmtOldParts->fetchAll();

            $stmtRestoreStock = $pdo->prepare("UPDATE parts SET quantity = quantity + ? WHERE id = ?");
            foreach ($oldParts as $op) {
                $stmtRestoreStock->execute([$op['quantity_used'], $op['part_id']]);
            }

            // 2. USUNIĘCIE STARYCH POWIĄZAŃ (Czyścimy stare zużycie dla tego loga)
            $pdo->prepare("DELETE FROM log_parts WHERE log_id = ?")->execute([$log_id]);

            // 3. AKTUALIZACJA DANYCH TEKSTOWYCH RAPORTU
            $stmtUpdateLog = $pdo->prepare("UPDATE logs SET vehicle_id = ?, title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmtUpdateLog->execute([$vehicle_id, $title, $content, $log_id, $user_id]);

            // 4. PRZYPISANIE NOWYCH CZĘŚCI I ZDJĘCIE ICH Z MAGAZYNU (jak w add_log.php)
            if (!empty($part_ids)) {
                $stmtLink = $pdo->prepare("INSERT INTO log_parts (log_id, part_id, quantity_used) VALUES (?, ?, ?)");
                $stmtCheckStock = $pdo->prepare("SELECT name, quantity FROM parts WHERE id = ?");
                $stmtUpdateStock = $pdo->prepare("UPDATE parts SET quantity = quantity - ? WHERE id = ?");

                for ($i = 0; $i < count($part_ids); $i++) {
                    $p_id = $part_ids[$i];
                    $qty = (int)$part_qtys[$i];

                    if (!empty($p_id) && $qty > 0) {
                        $stmtCheckStock->execute([$p_id]);
                        $partData = $stmtCheckStock->fetch();

                        if ($partData && $partData['quantity'] >= $qty) {
                            $stmtLink->execute([$log_id, $p_id, $qty]);
                            $stmtUpdateStock->execute([$qty, $p_id]);
                        } else {
                            $partName = $partData['name'] ?? 'Nieznana część';
                            throw new Exception("Brak wystarczającej ilości dla zaktualizowanej części: " . $partName . " (Dostępne: " . ($partData['quantity'] ?? 0) . ")");
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: logs_manage.php?success=Raport oraz stany magazynowe zostały pomyślnie zaktualizowane.");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "Wypełnij wszystkie wymagane pola (Pojazd, Tytuł, Opis).";
        $messageType = "danger";
    }
}

// Pobieranie danych do list rozwijanych i wypełnienia formularza
$vehicles = $pdo->query("SELECT id, brand, model, number FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);
// Pobieramy WSZYSTKIE części (żeby załadować nazwy tych, które były użyte)
$parts = $pdo->query("SELECT id, name, quantity FROM parts ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Pobieranie już przypisanych części do tego loga
$stmtCurrentParts = $pdo->prepare("SELECT part_id, quantity_used FROM log_parts WHERE log_id = ?");
$stmtCurrentParts->execute([$log_id]);
$current_log_parts = $stmtCurrentParts->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Dziennik serwisowy</p>
                <h1 class="dashboard-title">Edytuj raport techniczny</h1>
            </div>
            <div class="current-date date-outline">
                <i class="fas fa-pen" style="color: var(--primary);"></i> Tryb edycji
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="background: rgba(211,47,47,0.1); border-left: 4px solid var(--danger); padding: 15px; margin-bottom: 20px; color: #ff8a8a;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">
            <form action="" method="POST" class="admin-form">

                <div class="form-group">
                    <label class="label-sm">POJAZD *</label>
                    <select name="vehicle_id" required class="dark-input">
                        <?php foreach($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>" <?php echo $v['id'] == $current_log['vehicle_id'] ? 'selected' : ''; ?>>
                                #<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label-sm">TYTUŁ PRAC *</label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($current_log['title']); ?>" class="dark-input">
                </div>

                <div class="form-group parts-container-box">
                    <label class="label-sm">UŻYTE CZĘŚCI Z MAGAZYNU (Zmień, dodaj lub usuń)</label>

                    <div id="parts-container">
                        <!-- Wyświetlenie części, które zostały użyte wcześniej -->
                        <?php foreach($current_log_parts as $used_part): ?>
                            <div class="part-row">
                                <select name="part_id[]" class="dark-input" style="flex-grow: 1;">
                                    <option value="">Wybierz część...</option>
                                    <?php foreach($parts as $part): ?>
                                        <option value="<?php echo $part['id']; ?>" <?php echo $part['id'] == $used_part['part_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($part['name']); ?> (Dostępne: <?php echo $part['quantity']; ?> szt.)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="part_qty[]" value="<?php echo $used_part['quantity_used']; ?>" min="1" placeholder="Ilość" class="input-qty">
                                <button type="button" class="btn-remove-part">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" id="add-part-btn" class="btn-dashed">
                        <i class="fas fa-plus"></i> Dodaj kolejną część
                    </button>
                </div>

                <div class="form-group">
                    <label class="label-sm">OPIS / NOTATKI TECHNICZNE *</label>
                    <textarea name="content" required rows="5" class="dark-input"><?php echo htmlspecialchars($current_log['content']); ?></textarea>
                </div>

                <div class="form-actions-row">
                    <button type="submit" name="edit_log" class="btn-save">
                        <i class="fas fa-save"></i> ZAPISZ ZMIANY
                    </button>
                    <a href="logs_manage.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> ANULUJ
                    </a>
                </div>
            </form>
        </section>

    </main>

    <!-- Szablon dla nowej części (javascript) -->
    <template id="part-row-template">
        <div class="part-row">
            <select name="part_id[]" class="dark-input" style="flex-grow: 1;">
                <option value="">Wybierz część...</option>
                <?php foreach($parts as $part): ?>
                    <!-- Przy dodawaniu nowej pokazuj tylko te, które są faktycznie na magazynie (>0) -->
                    <?php if($part['quantity'] > 0): ?>
                        <option value="<?php echo $part['id']; ?>">
                            <?php echo htmlspecialchars($part['name']); ?> (Dostępne: <?php echo $part['quantity']; ?> szt.)
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <input type="number" name="part_qty[]" value="1" min="1" placeholder="Ilość" class="input-qty">
            <button type="button" class="btn-remove-part">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('parts-container');
            const addButton = document.getElementById('add-part-btn');
            const template = document.getElementById('part-row-template');

            addButton.addEventListener('click', function() {
                const clone = template.content.cloneNode(true);
                container.appendChild(clone);
            });

            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-part');
                if (btn) {
                    btn.closest('.part-row').remove();
                }
            });
        });
    </script>

<?php include '../includes/footer.php'; ?>