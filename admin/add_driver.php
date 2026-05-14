<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $number       = $_POST['number'] ?? null;
    $nationality  = trim($_POST['nationality'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $joined_year  = $_POST['joined_year'] ?? null;
    $bio          = trim($_POST['bio'] ?? '');
    $is_active    = isset($_POST['is_active']) ? 1 : 0;

    if (empty($first_name) || empty($last_name)) {
        $error = "Imię i nazwisko są wymagane.";
    } else {
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size      = 5 * 1024 * 1024;

            if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                $error = "Dozwolone formaty: JPG, PNG, WEBP.";
            } elseif ($_FILES['photo']['size'] > $max_size) {
                $error = "Zdjęcie nie może przekraczać 5MB.";
            } else {
                $upload_dir = '../assets/uploads/drivers/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext        = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename   = 'driver_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $photo_path = 'assets/uploads/drivers/' . $filename;

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    $error = "Błąd podczas przesyłania zdjęcia.";
                }
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                INSERT INTO drivers 
                    (first_name, last_name, number, nationality, date_of_birth, 
                     joined_year, bio, photo, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([
                $first_name, $last_name,
                $number ?: null,
                $nationality ?: null,
                $date_of_birth ?: null,
                $joined_year ?: null,
                $bio ?: null,
                $photo_path,
                $is_active
            ])) {
                header("Location: drivers.php?success=added");
                exit;
            } else {
                $error = "Wystąpił błąd podczas dodawania kierowcy.";
            }
        }
    }
}

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Kierowcy</p>
            <h1 class="dashboard-title">Dodaj kierowcę</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-plus"></i> Nowy kierowca</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form" enctype="multipart/form-data">

            <h3 class="form-section-title">
                <i class="fas fa-user"></i> Dane osobowe
            </h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Imię *</label>
                    <input type="text" name="first_name" required placeholder="np. Robert">
                </div>
                <div class="form-group">
                    <label>Nazwisko *</label>
                    <input type="text" name="last_name" required placeholder="np. Kubica">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer startowy</label>
                    <input type="number" name="number" min="0" placeholder="np. 88">
                </div>
                <div class="form-group">
                    <label>Narodowość</label>
                    <input type="text" name="nationality" placeholder="np. Polska">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Data urodzenia</label>
                    <input type="date" name="date_of_birth">
                </div>
                <div class="form-group">
                    <label>W zespole od (rok)</label>
                    <input type="number" name="joined_year"
                           min="1900" max="<?php echo date('Y'); ?>"
                           placeholder="<?php echo date('Y'); ?>">
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" checked
                           style="width: auto; accent-color: var(--primary);">
                    Kierowca aktywny w zespole
                </label>
            </div>

            <h3 class="form-section-title">
                <i class="fas fa-camera"></i> Zdjęcie
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

            <h3 class="form-section-title">
                <i class="fas fa-align-left"></i> Biografia
            </h3>

            <div class="form-group">
                <textarea name="bio" rows="4"
                          placeholder="Opis kierowcy widoczny publicznie dla kibiców..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-plus"></i> Dodaj kierowcę
                </button>
                <a href="drivers.php" class="btn-outline">
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
uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('drag-over');
    if (e.dataTransfer.files[0]) showPreview(e.dataTransfer.files[0]);
});
photoInput.addEventListener('change', () => { if (photoInput.files[0]) showPreview(photoInput.files[0]); });

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