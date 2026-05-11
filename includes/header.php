<?php
$current_page = basename($_SERVER['PHP_SELF']);

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_user  = isset($_SESSION['role']) && $_SESSION['role'] === 'user';
$is_guest = !isset($_SESSION['user_id']);

// Wszystkie foldery (admin/, public/, user/) są na tym samym poziomie
// więc ścieżki względne są identyczne
$assets_path = '../assets/';
$logout_path = '../public/logout.php';
$login_path  = '../public/login.php';
$index_path  = '../public/index.php';

// Ścieżki do sekcji zależne od roli
$admin_base  = '../admin/';
$user_base   = '../user/';
$public_base = '../public/';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PitStop PRO</title>
    <link rel="stylesheet" href="<?php echo $assets_path; ?>style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<header class="modern-header">
    <nav>
        <div class="logo">
            <i class="fas fa-tools"></i> PitStop <span>PRO</span>
        </div>

        <div class="nav-links">
            <ul>
                <?php if ($is_admin): ?>
                    <li><a href="<?php echo $admin_base; ?>dashboard.php"
                        class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Strona główna</a></li>
                    <li><a href="<?php echo $admin_base; ?>users.php"
                        class="<?php echo in_array($current_page, ['users.php', 'add_user.php', 'edit_user.php']) ? 'active' : ''; ?>">Pracownicy</a></li>
                    <li><a href="<?php echo $admin_base; ?>vehicles.php"
                        class="<?php echo in_array($current_page, ['vehicles.php','add_vehicle.php','edit_vehicle.php']) ? 'active' : ''; ?>">Pojazdy</a></li>
                    <li><a href="<?php echo $admin_base; ?>categories.php"
                        class="<?php echo in_array($current_page, ['categories.php','add_category.php','edit_category.php']) ? 'active' : ''; ?>">Magazyn</a></li>
                    <li><a href="<?php echo $admin_base; ?>events_manage.php"
                        class="<?php echo $current_page === 'events_manage.php' ? 'active' : ''; ?>">Kalendarz</a></li>
                    <li><a href="<?php echo $admin_base; ?>logs.php"
                        class="<?php echo $current_page === 'logs.php' ? 'active' : ''; ?>">Logi</a></li>

                <?php elseif ($is_user): ?>
                    <li><a href="<?php echo $user_base; ?>dashboard.php"
                        class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Strona główna</a></li>
                    <li><a href="<?php echo $public_base; ?>events.php"
                        class="<?php echo $current_page === 'events.php' ? 'active' : ''; ?>">Kalendarz</a></li>
                    <li><a href="<?php echo $user_base; ?>parts_inventory.php"
                        class="<?php echo in_array($current_page, ['parts_inventory.php','add_part.php','edit_part.php']) ? 'active' : ''; ?>">Magazyn</a></li>
                    <li><a href="<?php echo $user_base; ?>vehicles.php"
                        class="<?php echo $current_page === 'vehicles.php' ? 'active' : ''; ?>">Pojazdy</a></li>
                    <li><a href="<?php echo $user_base; ?>logs.php"
                        class="<?php echo $current_page === 'logs.php' ? 'active' : ''; ?>">Logi</a></li>
                    <li><a href="<?php echo $user_base; ?>profile.php"
                        class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">Profil</a></li>

                <?php else: ?>
                    <li><a href="<?php echo $public_base; ?>index.php"
                        class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Strona główna</a></li>
                    <li><a href="<?php echo $public_base; ?>events.php"
                        class="<?php echo $current_page === 'events.php' ? 'active' : ''; ?>">Kalendarz</a></li>

                <?php endif; ?>
            </ul>
        </div>

        <div class="auth-zone">
            <?php if ($is_admin): ?>
                <span class="user-badge">
                    <i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="<?php echo $logout_path; ?>" class="btn-login-modern active">Wyloguj</a>

            <?php elseif ($is_user): ?>
                <span class="user-badge">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a href="<?php echo $logout_path; ?>" class="btn-login-modern active">Wyloguj</a>

            <?php else: ?>
                <a href="<?php echo $login_path; ?>" class="btn-login-modern active">
                    Logowanie <i class="fas fa-key"></i>
                </a>

            <?php endif; ?>
        </div>
    </nav>
</header>