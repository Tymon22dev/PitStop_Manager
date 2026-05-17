<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events_manage.php");
    exit;
}

$event_id = (int)$_GET['id'];
$error    = '';

$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: events_manage.php");
    exit;
}

// Pobierz przypisane pojazdy
$stmtVehicles = $pdo->prepare("SELECT vehicle_id FROM event_vehicles WHERE event_id = ?");
$stmtVehicles->execute([$event_id]);
$assignedVehicles = array_column($stmtVehicles->fetchAll(PDO::FETCH_ASSOC), 'vehicle_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $track_name  = trim($_POST['track_name'] ?? '');
    $status      = $_POST['status'] ?? 'zaplanowane';
    $result      = trim($_POST['result'] ?? 'nieokreślony');
    $description = trim($_POST['description'] ?? '');
    $vehicles    = isset($_POST['vehicles']) && is_array($_POST['vehicles']) ? $_POST['vehicles'] : [];
    $pdo->prepare("DELETE FROM event_drivers WHERE event_id = ?")->execute([$event_id]);
    if (isset($_POST['drivers']) && is_array($_POST['drivers'])) {
        $stmtD = $pdo->prepare("INSERT INTO event_drivers (event_id, driver_id) VALUES (?, ?)");
        foreach ($_POST['drivers'] as $d_id) {
            if (is_numeric($d_id)) {
                $stmtD->execute([$event_id, (int)$d_id]);
            }
        }
    }
    if (empty($title) || empty($event_date)) {
        $error = "Tytuł i data są wymagane.";
    } else {

        // Obsługa zdjęcia
        $photo_path = $event['photo']; // zachowaj stare zdjęcie domyślnie
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size      = 5 * 1024 * 1024;

            if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                $error = "Dozwolone formaty zdjęcia: JPG, PNG, WEBP.";
            } elseif ($_FILES['photo']['size'] > $max_size) {
                $error = "Zdjęcie nie może przekraczać 5MB.";
            } else {
                $upload_dir = '../assets/uploads/events/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $new_path = 'assets/uploads/events/' . $filename;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    // Usuń stare zdjęcie jeśli istnieje
                    if ($event['photo'] && file_exists('../' . $event['photo'])) {
                        unlink('../' . $event['photo']);
                    }
                    $photo_path = $new_path;
                } else {
                    $error = "Błąd podczas przesyłania zdjęcia.";
                }
            }
        }

        // Obsługa usunięcia zdjęcia
        if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
            if ($event['photo'] && file_exists('../' . $event['photo'])) {
                unlink('../' . $event['photo']);
            }
            $photo_path = null;
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                UPDATE events 
                SET title = ?, event_date = ?, track_name = ?, 
                    status = ?, result = ?, description = ?, photo = ?
                WHERE id = ?
            ");
            if ($stmt->execute([$title, $event_date, $track_name, $status, $result, $description, $photo_path, $event_id])) {
                $pdo->prepare("DELETE FROM event_vehicles WHERE event_id = ?")->execute([$event_id]);

                if (!empty($vehicles)) {
                    $stmtV = $pdo->prepare("INSERT INTO event_vehicles (event_id, vehicle_id) VALUES (?, ?)");
                    foreach ($vehicles as $v_id) {
                        if (is_numeric($v_id)) {
                            $stmtV->execute([$event_id, (int)$v_id]);
                        }
                    }
                }

                header("Location: events_manage.php?success=edited");
                exit;
            } else {
                $error = "Wystąpił błąd podczas zapisywania zmian.";
            }
        }
    }
}

$vehicles = $pdo->query("
    SELECT * FROM vehicles 
    WHERE status = 'aktywny' 
    ORDER BY brand ASC, model ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Pobierz przypisanych kierowców
$stmtDrivers = $pdo->prepare("SELECT driver_id FROM event_drivers WHERE event_id = ?");
$stmtDrivers->execute([$event_id]);
$assignedDrivers = array_column($stmtDrivers->fetchAll(PDO::FETCH_ASSOC), 'driver_id');

$drivers = $pdo->query("
    SELECT * FROM drivers 
    WHERE is_active = 1 
    ORDER BY last_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<main class="home-wrapper">

    <div class="dashboard-header">
        <div>
            <p class="dashboard-sub">Kalendarz</p>
            <h1 class="dashboard-title">Edycja: <?php echo htmlspecialchars($event['title']); ?></h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-edit"></i> Edytuj wydarzenie</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="admin-form" enctype="multipart/form-data">

            <div class="form-row">
                <div class="form-group">
                    <label>Tytuł wyścigu / wydarzenia *</label>
                    <input type="text" name="title" required
                           value="<?php echo htmlspecialchars($event['title']); ?>">
                </div>
                <div class="form-group">
                    <label>Nazwa toru</label>
                    <input type="text" name="track_name"
                           value="<?php echo htmlspecialchars($event['track_name'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Data *</label>
                    <input type="date" name="event_date" required
                           value="<?php echo htmlspecialchars($event['event_date']); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status-select">
                        <option value="zaplanowane" <?php echo $event['status'] === 'zaplanowane' ? 'selected' : ''; ?>>Zaplanowane</option>
                        <option value="zakończone"  <?php echo $event['status'] === 'zakończone'  ? 'selected' : ''; ?>>Zakończone</option>
                        <option value="anulowane"   <?php echo $event['status'] === 'anulowane'   ? 'selected' : ''; ?>>Anulowane</option>
                    </select>
                </div>
            </div>

            <!-- Wynik — widoczny tylko gdy status = zakończone -->
            <div class="form-group" id="result-group">
                <label>Wynik</label>
                <input type="text" name="result"
                       value="<?php echo htmlspecialchars($event['result'] ?? ''); ?>"
                       placeholder="np. 1. miejsce, DNF, P3">
                <small>Wpisz wynik zespołu w tym wydarzeniu.</small>
            </div>

            <!-- Zdjęcie -->
            <div class="form-group">
                <label>Zdjęcie wydarzenia</label>

                <?php if (!empty($event['photo'])): ?>
                    <!-- Podgląd istniejącego zdjęcia -->
                    <div class="file-preview" id="file-preview" style="display: flex;">
                        <img id="preview-img"
                             src="<?php echo htmlspecialchars('../' . $event['photo']); ?>"
                             alt="Zdjęcie wydarzenia">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <button type="button" class="file-remove" id="file-change">
                                <i class="fas fa-exchange-alt"></i> Zmień zdjęcie
                            </button>
                            <button type="button" class="file-remove" id="file-remove">
                                <i class="fas fa-times"></i> Usuń zdjęcie
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="remove_photo" id="remove-photo" value="0">
                <?php else: ?>
                    <input type="hidden" name="remove_photo" id="remove-photo" value="0">
                <?php endif; ?>

                <div class="file-upload-area" id="file-upload-area"
                     style="<?php echo !empty($event['photo']) ? 'display: none;' : 'display: flex;' ?>">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Przeciągnij zdjęcie lub <span class="file-upload-link">kliknij aby wybrać</span></p>
                    <small>JPG, PNG, WEBP — max. 5MB</small>
                    <input type="file" name="photo" id="photo-input"
                           accept="image/jpeg,image/png,image/webp">
                </div>
            </div>

            <!-- Pojazdy — dropdown z checkboxami -->
            <div class="form-group">
                <label>Przypisz pojazdy</label>
                <div class="checkbox-dropdown" id="vehicle-dropdown">
                    <div class="checkbox-dropdown-toggle" id="dropdown-toggle">
                        <span id="dropdown-label">Wybierz pojazdy...</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="checkbox-dropdown-menu" id="dropdown-menu">
                        <?php if (empty($vehicles)): ?>
                            <p class="dropdown-empty">Brak aktywnych pojazdów.</p>
                        <?php else: ?>
                            <?php foreach ($vehicles as $v): ?>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="vehicles[]"
                                           value="<?php echo $v['id']; ?>"
                                           <?php echo in_array($v['id'], $assignedVehicles) ? 'checked' : ''; ?>>
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">
                                        #<?php echo htmlspecialchars($v['number'] ?? '?'); ?>
                                        — <?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kierowcy — dropdown z checkboxami -->
            <div class="form-group">
                <label>Przypisz kierowców</label>
                <div class="checkbox-dropdown" id="driver-dropdown">
                    <div class="checkbox-dropdown-toggle" id="driver-toggle">
                        <span id="driver-label">Wybierz kierowców...</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="checkbox-dropdown-menu" id="driver-menu">
                        <?php if (empty($drivers)): ?>
                            <p class="dropdown-empty">Brak aktywnych kierowców.</p>
                        <?php else: ?>
                            <?php foreach ($drivers as $d): ?>
                                <label class="checkbox-option">
                                    <input type="checkbox" name="drivers[]"
                                        value="<?php echo $d['id']; ?>"
                                        <?php echo in_array($d['id'], $assignedDrivers) ? 'checked' : ''; ?>>
                                    <span class="checkbox-custom"></span>
                                    <span class="checkbox-label">
                                        <?php if (!empty($d['number'])): ?>
                                            #<?php echo htmlspecialchars($d['number']); ?> —
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Opis / Notatki</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Zapisz zmiany
                </button>
                <a href="events_manage.php" class="btn-outline">
                    <i class="fas fa-arrow-left"></i> Anuluj
                </a>
            </div>

        </form>
    </div>

</main>

<?php include '../includes/footer.php'; ?>

<script>
// --- Pokaż/ukryj pole wyniku ---
const statusSelect = document.getElementById('status-select');
const resultGroup  = document.getElementById('result-group');

function toggleResult() {
    resultGroup.style.display = statusSelect.value === 'zakończone' ? 'flex' : 'none';
}
statusSelect.addEventListener('change', toggleResult);
toggleResult();

// --- Dropdown z checkboxami ---
const dropdownToggle = document.getElementById('dropdown-toggle');
const dropdownMenu   = document.getElementById('dropdown-menu');
const dropdownLabel  = document.getElementById('dropdown-label');
const checkboxes     = dropdownMenu.querySelectorAll('input[type="checkbox"]');

dropdownToggle.addEventListener('click', () => {
    dropdownMenu.classList.toggle('open');
    dropdownToggle.classList.toggle('open');
});

document.addEventListener('click', (e) => {
    if (!document.getElementById('vehicle-dropdown').contains(e.target)) {
        dropdownMenu.classList.remove('open');
        dropdownToggle.classList.remove('open');
    }
});

function updateLabel() {
    const selected = [...checkboxes].filter(cb => cb.checked);
    if (selected.length === 0) {
        dropdownLabel.textContent = 'Wybierz pojazdy...';
    } else if (selected.length === 1) {
        dropdownLabel.textContent = selected[0].closest('label')
            .querySelector('.checkbox-label').textContent.trim();
    } else {
        dropdownLabel.textContent = `Wybrano ${selected.length} pojazdy`;
    }
}

// Ustaw etykietę na starcie (już zaznaczone pojazdy)
updateLabel();
checkboxes.forEach(cb => cb.addEventListener('change', updateLabel));

// --- Dropdown kierowców ---
const driverToggle = document.getElementById('driver-toggle');
const driverMenu   = document.getElementById('driver-menu');
const driverLabel  = document.getElementById('driver-label');
const driverBoxes  = driverMenu.querySelectorAll('input[type="checkbox"]');

driverToggle.addEventListener('click', () => {
    driverMenu.classList.toggle('open');
    driverToggle.classList.toggle('open');
});

document.addEventListener('click', (e) => {
    if (!document.getElementById('driver-dropdown').contains(e.target)) {
        driverMenu.classList.remove('open');
        driverToggle.classList.remove('open');
    }
});

function updateDriverLabel() {
    const selected = [...driverBoxes].filter(cb => cb.checked);
    if (selected.length === 0) {
        driverLabel.textContent = 'Wybierz kierowców...';
    } else if (selected.length === 1) {
        driverLabel.textContent = selected[0].closest('label')
            .querySelector('.checkbox-label').textContent.trim();
    } else {
        driverLabel.textContent = `Wybrano ${selected.length} kierowców`;
    }
}
updateDriverLabel();
driverBoxes.forEach(cb => cb.addEventListener('change', updateDriverLabel));

// --- Upload zdjęcia ---
const photoInput  = document.getElementById('photo-input');
const uploadArea  = document.getElementById('file-upload-area');
const filePreview = document.getElementById('file-preview');
const previewImg  = document.getElementById('preview-img');
const removePhoto = document.getElementById('remove-photo');

if (uploadArea) {
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

// Zmień zdjęcie
const fileChange = document.getElementById('file-change');
if (fileChange) {
    fileChange.addEventListener('click', () => photoInput.click());
    photoInput.addEventListener('change', () => {
        if (photoInput.files[0]) showPreview(photoInput.files[0]);
    });
}

// Usuń zdjęcie
const fileRemove = document.getElementById('file-remove');
if (fileRemove) {
    fileRemove.addEventListener('click', () => {
        photoInput.value = '';
        previewImg.src   = '';
        removePhoto.value = '1';
        filePreview.style.display = 'none';
        uploadArea.style.display  = 'flex';
    });
}
</script>