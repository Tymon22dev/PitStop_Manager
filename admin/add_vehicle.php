<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number     = trim($_POST['number'] ?? '');
    $brand      = trim($_POST['brand'] ?? '');
    $model      = trim($_POST['model'] ?? '');
    $vin        = trim($_POST['vin'] ?? '');
    $year       = $_POST['year'] ?? null;
    $status     = $_POST['status'] ?? 'aktywny';
    $engine     = trim($_POST['engine'] ?? '');
    $weight     = $_POST['weight'] ?? null;
    $drive_type = trim($_POST['drive_type'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $debut_year = $_POST['debut_year'] ?? null;
    $description = trim($_POST['description'] ?? '');

    if (empty($brand) || empty($model)) {
        $error = "Marka i model są wymagane.";
    } else {
        if (!empty($vin)) {
            $check = $pdo->prepare("SELECT id FROM vehicles WHERE vin = ?");
            $check->execute([$vin]);
            if ($check->fetch()) {
                $error = "Pojazd o tym numerze VIN już istnieje.";
            }
        }

        if (empty($error)) {
            // Obsługa zdjęcia
            $photo_path = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
                $max_size      = 5 * 1024 * 1024;

                if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                    $error = "Dozwolone formaty zdjęcia: JPG, PNG, WEBP.";
                } elseif ($_FILES['photo']['size'] > $max_size) {
                    $error = "Zdjęcie nie może przekraczać 5MB.";
                } else {
                    $upload_dir = '../assets/uploads/vehicles/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $ext        = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename   = 'vehicle_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $photo_path = 'assets/uploads/vehicles/' . $filename;

                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                        $error = "Błąd podczas przesyłania zdjęcia.";
                    }
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("
                    INSERT INTO vehicles 
                        (number, brand, model, vin, year, status, photo,
                         engine, weight, drive_type, category, debut_year, description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([
                    $number ?: null,
                    $brand,
                    $model,
                    $vin ?: null,
                    $year ?: null,
                    $status,
                    $photo_path,
                    $engine ?: null,
                    $weight ?: null,
                    $drive_type ?: null,
                    $category ?: null,
                    $debut_year ?: null,
                    $description ?: null,
                ])) {
                    header("Location: vehicles.php?success=added");
                    exit;
                } else {
                    $error = "Wystąpił błąd podczas dodawania pojazdu.";
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Pojazdy</p>
            <h1 class="dashboard-title">Dodaj pojazd</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-plus"></i> Nowy pojazd</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form" enctype="multipart/form-data">

            <!-- Podstawowe dane -->
            <div class="form-row">
                <div class="form-group">
                    <label>Marka *</label>
                    <input type="text" name="brand" required placeholder="np. Subaru">
                </div>
                <div class="form-group">
                    <label>Model *</label>
                    <input type="text" name="model" required placeholder="np. Impreza WRC">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer startowy</label>
                    <input type="text" name="number" placeholder="np. 16">
                </div>
                <div class="form-group">
                    <label>Rok produkcji</label>
                    <input type="number" name="year"
                           min="1900" max="<?php echo date('Y'); ?>"
                           placeholder="<?php echo date('Y'); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer VIN</label>
                    <input type="text" name="vin" placeholder="opcjonalnie">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="aktywny">Aktywny</option>
                        <option value="w_naprawie">W naprawie</option>
                        <option value="wycofany">Wycofany</option>
                    </select>
                </div>
            </div>

            <!-- Specyfikacja techniczna -->
            <h3 class="form-section-title">
                <i class="fas fa-cogs"></i> Specyfikacja techniczna
            </h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Silnik</label>
                    <input type="text" name="engine" placeholder="np. 2.0 Turbo 300KM">
                </div>
                <div class="form-group">
                    <label>Napęd</label>
                    <select name="drive_type">
                        <option value="">— Nie określono —</option>
                        <option value="AWD">AWD (4x4)</option>
                        <option value="RWD">RWD (tył)</option>
                        <option value="FWD">FWD (przód)</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Masa (kg)</label>
                    <input type="number" name="weight" min="0" placeholder="np. 1200">
                </div>
                <div class="form-group">
                    <label>Kategoria wyścigowa</label>
                    <input type="text" name="category" placeholder="np. Rally2, GT3, P1">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Rok debiutu</label>
                    <input type="number" name="debut_year"
                           min="1900" max="<?php echo date('Y'); ?>"
                           placeholder="np. 2022">
                </div>
            </div>

            <!-- Zdjęcie -->
            <h3 class="form-section-title">
                <i class="fas fa-camera"></i> Zdjęcie pojazdu
            </h3>

            <div class="form-group">
                <div class="file-upload-area" id="file-upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Przeciągnij zdjęcie lub <span class="file-upload-link">kliknij aby wybrać</span></p>
                    <small>JPG, PNG, WEBP — max. 5MB</small>
                    <input type="file" name="photo" id="photo-input"
                           accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="file-preview" id="file-preview" style="display: none;">
                    <img id="preview-img" src="" alt="Podgląd">
                    <button type="button" class="file-remove" id="file-remove">
                        <i class="fas fa-times"></i> Usuń zdjęcie
                    </button>
                </div>
            </div>

            <!-- Opis -->
            <h3 class="form-section-title">
                <i class="fas fa-align-left"></i> Opis dla kibiców
            </h3>

            <div class="form-group">
                <textarea name="description" rows="4"
                          placeholder="Krótki opis pojazdu widoczny publicznie dla kibiców..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-plus"></i> Dodaj pojazd
                </button>
                <a href="vehicles.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>

        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>

<script>
const photoInput  = document.getElementById('photo-input');
const uploadArea  = document.getElementById('file-upload-area');
const filePreview = document.getElementById('file-preview');
const previewImg  = document.getElementById('preview-img');
const fileRemove  = document.getElementById('file-remove');

uploadArea.addEventListener('click', () => photoInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('drag-over');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('drag-over');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    if (e.dataTransfer.files[0]) showPreview(e.dataTransfer.files[0]);
});

photoInput.addEventListener('change', () => {
    if (photoInput.files[0]) showPreview(photoInput.files[0]);
});

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImg.src = e.target.result;
        uploadArea.style.display = 'none';
        filePreview.style.display = 'flex';
    };
    reader.readAsDataURL(file);
}

fileRemove.addEventListener('click', () => {
    photoInput.value = '';
    previewImg.src   = '';
    uploadArea.style.display = 'flex';
    filePreview.style.display = 'none';
});
</script>