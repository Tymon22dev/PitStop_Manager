<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php?error=no_access");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $track_name  = trim($_POST['track_name'] ?? '');
    $status      = $_POST['status'] ?? 'zaplanowane';
    $result      = trim($_POST['result'] ?? 'nieokreślony');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($event_date)) {
        $error = "Tytuł i data są wymagane.";
    } else {

        // Obsługa zdjęcia
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size      = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['photo']['type'], $allowed_types)) {
                $error = "Dozwolone formaty zdjęcia: JPG, PNG, WEBP.";
            } elseif ($_FILES['photo']['size'] > $max_size) {
                $error = "Zdjęcie nie może przekraczać 5MB.";
            } else {
                $upload_dir = '../assets/uploads/events/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext        = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename   = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $photo_path = 'assets/uploads/events/' . $filename;

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $filename)) {
                    $error = "Błąd podczas przesyłania zdjęcia.";
                }
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, event_date, track_name, status, result, description, photo)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$title, $event_date, $track_name, $status, $result, $description, $photo_path])) {
                $event_id = $pdo->lastInsertId();

                if (isset($_POST['vehicles']) && is_array($_POST['vehicles'])) {
                    $stmtV = $pdo->prepare("INSERT INTO event_vehicles (event_id, vehicle_id) VALUES (?, ?)");
                    foreach ($_POST['vehicles'] as $v_id) {
                        if (is_numeric($v_id)) {
                            $stmtV->execute([$event_id, (int)$v_id]);
                        }
                    }
                }
                if (isset($_POST['drivers']) && is_array($_POST['drivers'])) {
                    $stmtD = $pdo->prepare("INSERT INTO event_drivers (event_id, driver_id) VALUES (?, ?)");
                    foreach ($_POST['drivers'] as $d_id) {
                        if (is_numeric($d_id)) {
                            $stmtD->execute([$event_id, (int)$d_id]);
                        }
                    }
                }

                header("Location: events_manage.php?success=added");
                exit;
            } else {
                $error = "Wystąpił błąd podczas dodawania wydarzenia.";
            }
        }
    }
}

$vehicles = $pdo->query("
    SELECT * FROM vehicles 
    WHERE status = 'aktywny' 
    ORDER BY brand ASC, model ASC
")->fetchAll(PDO::FETCH_ASSOC);

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
            <h1 class="dashboard-title">Dodaj wydarzenie</h1>
        </div>
        <div class="current-date">
            <i class="far fa-calendar-alt"></i> <?php echo date('d.m.Y'); ?>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> Nowe wydarzenie</h2>

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
                           placeholder="np. Grand Prix Monako">
                </div>
                <div class="form-group">
                    <label>Nazwa toru</label>
                    <input type="text" name="track_name"
                           placeholder="np. Circuit de Monaco">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Data *</label>
                    <input type="date" name="event_date" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="status-select">
                        <option value="zaplanowane">Zaplanowane</option>
                        <option value="zakończone">Zakończone</option>
                        <option value="anulowane">Anulowane</option>
                    </select>
                </div>
            </div>

            <!-- Wynik — widoczny tylko gdy status = zakończone -->
            <div class="form-group" id="result-group" style="display: none;">
                <label>Wynik</label>
                <input type="text" name="result"
                       placeholder="np. 1. miejsce, DNF, P3">
                <small>Wpisz wynik zespołu w tym wydarzeniu.</small>
            </div>

            <!-- Zdjęcie -->
            <div class="form-group">
                <label>Zdjęcie wydarzenia</label>
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
                                           value="<?php echo $v['id']; ?>">
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
                                        value="<?php echo $d['id']; ?>">
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
                <textarea name="description" rows="3"
                          placeholder="Dodatkowe informacje techniczne lub logistyczne..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Dodaj wydarzenie
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
// --- Pokaż/ukryj pole wyniku zależnie od statusu ---
const statusSelect  = document.getElementById('status-select');
const resultGroup   = document.getElementById('result-group');

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

// Zamknij po kliknięciu poza dropdownem
document.addEventListener('click', (e) => {
    if (!document.getElementById('vehicle-dropdown').contains(e.target)) {
        dropdownMenu.classList.remove('open');
        dropdownToggle.classList.remove('open');
    }
});

// Aktualizacja etykiety
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
driverBoxes.forEach(cb => cb.addEventListener('change', updateDriverLabel));

// --- Upload zdjęcia ---
const photoInput    = document.getElementById('photo-input');
const uploadArea    = document.getElementById('file-upload-area');
const filePreview   = document.getElementById('file-preview');
const previewImg    = document.getElementById('preview-img');
const fileRemove    = document.getElementById('file-remove');

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
    const file = e.dataTransfer.files[0];
    if (file) showPreview(file);
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