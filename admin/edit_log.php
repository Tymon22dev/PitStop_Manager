<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$log_id = $_GET['id'] ?? null;
$message = '';
$messageType = '';

if (!$log_id) {
    header("Location: logs_manage.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM logs WHERE id = ?");
$stmt->execute([$log_id]);
$current_log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_log) {
    header("Location: logs_manage.php");
    exit;
}

// --- PROCES AKTUALIZACJI I PRZELICZANIA MAGAZYNU ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_log'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    $part_ids = $_POST['part_id'] ?? [];
    $part_qtys = $_POST['part_qty'] ?? [];

    if (!empty($vehicle_id) && !empty($title) && !empty($content)) {
        try {
            $pdo->beginTransaction();

            // 1. Wyzerowanie poprzedniego stanu zużycia części (revert na magazyn)
            $stmtOldParts = $pdo->prepare("SELECT part_id, quantity_used FROM log_parts WHERE log_id = ?");
            $stmtOldParts->execute([$log_id]);
            $oldParts = $stmtOldParts->fetchAll();

            $stmtRestoreStock = $pdo->prepare("UPDATE parts SET quantity = quantity + ? WHERE id = ?");
            foreach ($oldParts as $op) {
                $stmtRestoreStock->execute([$op['quantity_used'], $op['part_id']]);
            }

            // Usunięcie starych relacji logów i części
            $pdo->prepare("DELETE FROM log_parts WHERE log_id = ?")->execute([$log_id]);

            // 2. Aktualizacja głównych danych tekstowych wpisu
            $stmtUpdateLog = $pdo->prepare("UPDATE logs SET vehicle_id = ?, title = ?, content = ? WHERE id = ?");
            $stmtUpdateLog->execute([$vehicle_id, $title, $content, $log_id]);

            // 3. Zapisanie nowo przypisanego zużycia i pobranie z magazynu
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
                            throw new Exception("Brak wystarczającej ilości dla części: " . ($partData['name'] ?? 'Nieznana'));
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: logs_manage.php?success=Poprawnie skorygowano raport w dzienniku serwisowym.");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

$vehicles = $pdo->query("SELECT id, brand, model, number FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);
$parts = $pdo->query("SELECT id, name, quantity FROM parts ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmtCurrentParts = $pdo->prepare("SELECT part_id, quantity_used FROM log_parts WHERE log_id = ?");
$stmtCurrentParts->execute([$log_id]);
$current_log_parts = $stmtCurrentParts->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">
        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Korekta danych</p>
                <h1 class="dashboard-title">Edytuj raport</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
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
                    <label class="label-sm">UŻYTE CZĘŚCI Z MAGAZYNU</label>
                    <div id="parts-container">
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
                                <input type="number" name="part_qty[]" value="<?php echo $used_part['quantity_used']; ?>" min="1" class="input-qty">
                                <button type="button" class="btn-remove-part"><i class="fas fa-times"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-part-btn" class="btn-dashed"><i class="fas fa-plus"></i> Dodaj kolejną część</button>
                </div>

                <div class="form-group">
                    <label class="label-sm">OPIS / NOTATKI TECHNICZNE *</label>
                    <textarea name="content" required rows="5" class="dark-input"><?php echo htmlspecialchars($current_log['content']); ?></textarea>
                </div>

                <div class="form-actions-row">
                    <button type="submit" name="edit_log" class="btn-save"><i class="fas fa-save"></i> ZAPISZ KOREKTĘ</button>
                    <a href="logs_manage.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> ANULUJ</a>
                </div>
            </form>
        </section>
    </main>

    <template id="part-row-template">
        <div class="part-row">
            <select name="part_id[]" class="dark-input" style="flex-grow: 1;">
                <option value="">Wybierz część...</option>
                <?php foreach($parts as $part): ?>
                    <?php if($part['quantity'] > 0): ?>
                        <option value="<?php echo $part['id']; ?>"><?php echo htmlspecialchars($part['name']); ?> (Dostępne: <?php echo $part['quantity']; ?> szt.)</option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <input type="number" name="part_qty[]" value="1" min="1" class="input-qty">
            <button type="button" class="btn-remove-part"><i class="fas fa-times"></i></button>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('parts-container');
            const addButton = document.getElementById('add-part-btn');
            const template = document.getElementById('part-row-template');

            addButton.addEventListener('click', function() {
                container.appendChild(template.content.cloneNode(true));
            });

            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-part');
                if (btn) btn.closest('.part-row').remove();
            });
        });
    </script>

<?php include '../includes/footer.php'; ?>