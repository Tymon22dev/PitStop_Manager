<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: drivers.php");
    exit;
}

$driver_id = (int)$_GET['id'];
$error     = '';

$stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

if (!$driver) {
    header("Location: drivers.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name    = trim($_POST['first_name'] ?? '');
    $last_name     = trim($_POST['last_name'] ?? '');
    $number        = $_POST['number'] ?? null;
    $nationality   = trim($_POST['nationality'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $joined_year   = $_POST['joined_year'] ?? null;
    $bio           = trim($_POST['bio'] ?? '');
    $is_active     = isset($_POST['is_active']) ? 1 : 0;

    if (empty($first_name) || empty($last_name)) {
        $error = "Imię i nazwisko są wymagane.";
    } else {
        $photo_path = $driver['photo'];

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
                $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'driver_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $new_path = 'assets/uploads/drivers/' . $filename;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    if ($driver['photo'] && file_exists('../' . $driver['photo'])) {
                        unlink('../' . $driver['photo']);
                    }
                    $photo_path = $new_path;
                } else {
                    $error = "Błąd podczas przesyłania zdjęcia.";
                }
            }
        }

        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
            if ($driver['photo'] && file_exists('../' . $driver['photo'])) {
                unlink('../' . $driver['photo']);
            }
            $photo_path = null;
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                UPDATE drivers
                SET first_name = ?, last_name = ?, number = ?, nationality = ?,
                    date_of_birth = ?, joined_year = ?, bio = ?, photo = ?, is_active = ?
                WHERE id = ?
            ");
            if ($stmt->execute([
                $first_name, $last_name,
                $number ?: null,
                $nationality ?: null,
                $date_of_birth ?: null,
                $joined_year ?: null,
                $bio ?: null,
                $photo_path,
                $is_active,
                $driver_id
            ])) {
                header("Location: drivers.php?success=edited");
                exit;
            } else {
                $error = "Wystąpił błąd podczas zapisywania zmian.";
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
            <h1 class="dashboard-title">Edycja: <?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-edit"></i> Edytuj kierowcę</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
            <input type="hidden" name="remove_photo" id="remove-photo" value="0">

            <h3 class="form-section-title">
                <i class="fas fa-user"></i> Dane osobowe
            </h3>

            <div class="form-row">
                <div class="form-group">
                    <label>Imię *</label>
                    <input type="text" name="first_name" required
                           value="<?php echo htmlspecialchars($driver['first_name']); ?>">
                </div>
                <div class="form-group">
                    <label>Nazwisko *</label>
                    <input type="text" name="last_name" required
                           value="<?php echo htmlspecialchars($driver['last_name']); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Numer startowy</label>
                    <input type="number" name="number" min="0"
                           value="<?php echo htmlspecialchars($driver['number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Narodowość</label>
                    <input type="text" name="nationality"
                           value="<?php echo htmlspecialchars($driver['nationality'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Data urodzenia</label>
                    <input type="date" name="date_of_birth"
                           value="<?php echo htmlspecialchars($driver['date_of_birth'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>W zespole od (rok)</label>
                    <input type="number" name="joined_year"
                           min="1900" max="<?php echo date('Y'); ?>"
                           value="<?php echo htmlspecialchars($driver['joined_year'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active"
                           <?php echo $driver['is_active'] ? 'checked' : ''; ?>
                           style="width: auto; accent-color: var(--primary);">
                    Kierowca aktywny w zespole
                </label>
            </div>

            <h3 class="form-section-title">
                <i class="fas fa-camera"></i> Zdjęcie
            </h3>

            <div class="form-group">
                <?php if (!empty($driver['photo'])): ?>
                    <div class="file-preview" id="file-preview" style="display: flex;">
                        <img id="preview-img"
                             src="../<?php echo htmlspecialchars($driver['photo']); ?>"
                             alt="Zdjęcie kierowcy">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <button type="button" class="file-remove" id="file-change">
                                <i class="fas fa-exchange-alt"></i> Zmień zdjęcie
                            </button>
                            <button type="button" class="file-remove" id="file-remove">
                                <i class="fas fa-times"></i> Usuń zdjęcie
                            </button>
                        </div>
                    </div>
                    <div class="file-upload-area" id="file-upload-area" style="display: none;">
                <?php else: ?>
                    <div class="file-upload-area" id="file-upload-area" style="display: flex;">
                <?php endif; ?>
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Przeciągnij zdjęcie lub <span class="file-upload-link">kliknij aby wybrać</span></p>
                        <small>JPG, PNG, WEBP — max. 5MB</small>
                        <input type="file" name="photo" id="photo-input"
                               accept="image/jpeg,image/png,image/webp">
                    </div>
                <?php if (empty($driver['photo'])): ?>
                    <div class="file-preview" id="file-preview" style="display: none;">
                        <img id="preview-img" src="" alt="Podgląd">
                        <button type="button" class="file-remove" id="file-remove">
                            <i class="fas fa-times"></i> Usuń zdjęcie
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <h3 class="form-section-title">
                <i class="fas fa-align-left"></i> Biografia
            </h3>

            <div class="form-group">
                <textarea name="bio" rows="4"><?php echo htmlspecialchars($driver['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Zapisz zmiany
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
const removePhoto = document.getElementById('remove-photo');

if (uploadArea) {
    uploadArea.addEventListener('click', () => photoInput.click());
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('drag-over'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        if (e.dataTransfer.files[0]) showPreview(e.dataTransfer.files[0]);
    });
    photoInput.addEventListener('change', () => { if (photoInput.files[0]) showPreview(photoInput.files[0]); });
}

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
        previewImg.src = e.target.result;
        uploadArea.style.display = 'none';
        filePreview.style.display = 'flex';
        removePhoto.value = '0';
    };
    reader.readAsDataURL(file);
}

const fileChange = document.getElementById('file-change');
if (fileChange) {
    fileChange.addEventListener('click', () => photoInput.click());
}

const fileRemoveBtn = document.getElementById('file-remove');
if (fileRemoveBtn) {
    fileRemoveBtn.addEventListener('click', () => {
        photoInput.value  = '';
        previewImg.src    = '';
        removePhoto.value = '1';
        filePreview.style.display = 'none';
        uploadArea.style.display  = 'flex';
    });
}
</script>