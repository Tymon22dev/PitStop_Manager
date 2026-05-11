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
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Nazwa kategorii jest wymagana.";
    } else {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $check->execute([$name]);

        if ($check->fetch()) {
            $error = "Kategoria o tej nazwie już istnieje.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                header("Location: categories.php?success=added");
                exit;
            } else {
                $error = "Wystąpił błąd podczas dodawania kategorii.";
            }
        }
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Magazyn / Kategorie</p>
            <h1 class="dashboard-title">Dodaj kategorię</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-tag"></i> Nowa kategoria</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form">
            <div class="form-group">
                <label>Nazwa kategorii</label>
                <input type="text" name="name" required placeholder="np. Silnik, Hamulce, Opony">
            </div>
            <div class="form-group">
                <label>Opis</label>
                <textarea name="description" rows="3" 
                          placeholder="Krótki opis kategorii (opcjonalnie)"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-plus"></i> Dodaj kategorię
                </button>
                <a href="categories.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>
        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>