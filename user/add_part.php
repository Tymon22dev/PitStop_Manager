<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name          = trim($_POST['name'] ?? '');
    $category_id   = $_POST['category_id'] ?? null;
    $serial_number = trim($_POST['serial_number'] ?? '');
    $price         = $_POST['price'] ?? 0;
    $quantity      = $_POST['quantity'] ?? 1;
    $min_quantity  = $_POST['min_quantity'] ?? 5;
    $status        = $_POST['status'] ?? 'nowy';

    if (empty($name)) {
        $error = "Nazwa części jest wymagana.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Podaj poprawną cenę.";
    } elseif (!is_numeric($quantity) || $quantity < 0) {
        $error = "Podaj poprawną ilość.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO parts (name, category_id, serial_number, price, quantity, min_quantity, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([
            $name,
            $category_id ?: null,
            $serial_number ?: null,
            $price,
            $quantity,
            $min_quantity,
            $status
        ])) {
            header("Location: parts_inventory.php?success=added");
            exit;
        } else {
            $error = "Wystąpił błąd podczas dodawania części.";
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Magazyn</p>
            <h1 class="dashboard-title">Dodaj część</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-plus"></i> Nowa część</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form">

            <div class="form-row">
                <div class="form-group">
                    <label>Nazwa części</label>
                    <input type="text" name="name" required
                           placeholder="np. Klocki hamulcowe Brembo">
                </div>
                <div class="form-group">
                    <label>Kategoria</label>
                    <select name="category_id">
                        <option value="">— Brak kategorii —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer seryjny</label>
                    <input type="text" name="serial_number"
                           placeholder="np. BRE-2024-001 (opcjonalnie)">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="nowy">Nowy</option>
                        <option value="używany">Używany</option>
                        <option value="uszkodzony">Uszkodzony</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Cena (zł)</label>
                    <input type="number" name="price" step="0.01" min="0"
                           placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label>Ilość</label>
                    <input type="number" name="quantity" min="0"
                           value="1" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Minimalny stan magazynowy</label>
                    <input type="number" name="min_quantity" min="0" value="5">
                    <small>Alert pojawi się gdy ilość spadnie do lub poniżej tej wartości.</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-plus"></i> Dodaj część
                </button>
                <a href="parts_inventory.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>

        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>