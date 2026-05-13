<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// --- OBSŁUGA ZAPISU FORMULARZA I MAGAZYNU ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_log'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    $part_ids = $_POST['part_id'] ?? [];
    $part_qtys = $_POST['part_qty'] ?? [];

    if (!empty($vehicle_id) && !empty($title) && !empty($content)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO logs (user_id, vehicle_id, title, content) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $vehicle_id, $title, $content]);
            $log_id = $pdo->lastInsertId();

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
                            throw new Exception("Brak wystarczającej ilości w magazynie dla: " . $partName . " (Dostępne: " . ($partData['quantity'] ?? 0) . ")");
                        }
                    }
                }
            }

            $pdo->commit();
            header("Location: logs_manage.php?success=Raport zapisany, stany magazynowe zaktualizowane.");
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

$vehicles = $pdo->query("SELECT id, brand, model, number FROM vehicles WHERE status = 'aktywny'")->fetchAll(PDO::FETCH_ASSOC);
$parts = $pdo->query("SELECT id, name, quantity FROM parts WHERE quantity > 0 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

    <main class="home-wrapper wrapper-900">

        <div class="dashboard-header mb-40">
            <div>
                <p class="dashboard-sub">Dziennik serwisowy</p>
                <h1 class="dashboard-title">Dodaj raport techniczny</h1>
            </div>
            <div class="current-date date-outline">
                <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="card admin-panel-card">
            <h2 class="section-label"><i class="fas fa-tag"></i> Nowy raport i zużycie części</h2>

            <form action="" method="POST" class="admin-form">

                <div class="form-group">
                    <label class="label-sm">POJAZD *</label>
                    <select name="vehicle_id" required class="dark-input">
                        <option value="">Wybierz serwisowany pojazd...</option>
                        <?php foreach($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>">#<?php echo htmlspecialchars($v['number'] . ' - ' . $v['brand'] . ' ' . $v['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label-sm">TYTUŁ PRAC *</label>
                    <input type="text" name="title" required placeholder="np. Wymiana świec zapłonowych" class="dark-input">
                </div>

                <div class="form-group parts-container-box">
                    <label class="label-sm">ZUŻYTE CZĘŚCI Z MAGAZYNU</label>

                    <div id="parts-container">
                        <!-- Javascript generuje tutaj wiersze -->
                    </div>

                    <button type="button" id="add-part-btn" class="btn-dashed">
                        <i class="fas fa-plus"></i> Dodaj część do raportu
                    </button>
                </div>

                <div class="form-group">
                    <label class="label-sm">OPIS / NOTATKI TECHNICZNE *</label>
                    <textarea name="content" required rows="5" placeholder="Wprowadź szczegóły..." class="dark-input"></textarea>
                </div>

                <div class="form-actions-row">
                    <button type="submit" name="add_log" class="btn-save">
                        <i class="fas fa-plus"></i> DODAJ RAPORT
                    </button>
                    <a href="logs_manage.php" class="btn-cancel">
                        <i class="fas fa-arrow-left"></i> ANULUJ
                    </a>
                </div>
            </form>
        </section>

    </main>

    <template id="part-row-template">
        <div class="part-row">
            <select name="part_id[]" class="dark-input" style="flex-grow: 1;">
                <option value="">Wybierz część...</option>
                <?php foreach($parts as $part): ?>
                    <option value="<?php echo $part['id']; ?>">
                        <?php echo htmlspecialchars($part['name']); ?> (Dostępne: <?php echo $part['quantity']; ?> szt.)
                    </option>
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