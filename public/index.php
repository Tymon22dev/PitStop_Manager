<?php session_start(); 
include '../includes/header.php';
?>

    <main class="home-wrapper">
        <section class="split-hero">
            <div class="hero-text">
                <span class="tag"><span class="status-dot"></span> System Ready</span>
                <h1>System operacyjny Twojego warsztatu.</h1>
                <p>Zarządzaj częściami, monitoruj serwis i optymalizuj pracę zespołu motorsportowego w jednej, zintegrowanej platformie.</p>
                <div class="hero-actions">
                    <a href="login.php" class="btn-main">Rozpocznij pracę <i class="fas fa-chevron-right"></i></a>
                    <a href="#stats" class="btn-outline">Raporty systemowe</a>
                </div>
            </div>
            
            <div class="hero-visual">
                <div class="stats-preview">
                    <div class="stat-card">
                        <i class="fas fa-car-side"></i>
                        <div class="stat-info">
                            <span class="value">12</span>
                            <span class="label">Pojazdy w gotowości</span>
                        </div>
                    </div>
                    <div class="stat-card pulse">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div class="stat-info">
                            <span class="value">03</span>
                            <span class="label">Krytyczne braki</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="stats" class="dashboard-grid">
            <div class="grid-item">
                <i class="fas fa-box-open"></i>
                <h3>Inteligentny Magazyn</h3>
                <p>Automatyczne powiadomienia o kończących się podzespołach i pełna historia wydań.</p>
                <ul class="mini-list">
                    <li><i class="fas fa-check"></i> Śledzenie kodów QR</li>
                    <li><i class="fas fa-check"></i> Powiadomienia SMS</li>
                </ul>
            </div>

            <div class="grid-item">
                <i class="fas fa-clipboard-list"></i>
                <h3>Harmonogram Serwisu</h3>
                <p>Planuj przeglądy dla każdego auta. System sam przypomni o zbliżającym się interwale.</p>
                <ul class="mini-list">
                    <li><i class="fas fa-check"></i> Historia napraw</li>
                    <li><i class="fas fa-check"></i> Kalendarz zespołu</li>
                </ul>
            </div>

            <div class="grid-item">
                <i class="fas fa-chart-line"></i>
                <h3>Analityka Wydajności</h3>
                <p>Monitoruj koszty eksploatacji i czas pracy mechaników w czasie rzeczywistym.</p>
                <ul class="mini-list">
                    <li><i class="fas fa-check"></i> Raporty PDF</li>
                    <li><i class="fas fa-check"></i> Eksport danych</li>
                </ul>
            </div>
        </section>
    </main>

    <footer class="simple-footer">
        <div class="footer-content">
            <p>&copy; 2026 PitStop Manager | <span class="highlight">Engineering Hub</span></p>
            <div class="footer-status">
                <small><i class="fas fa-circle" style="color: var(--success); font-size: 8px;"></i> Cloud Sync: Active</small>
            </div>
        </div>
    </footer>

</body>
</html>

<?php include '../includes/footer.php'; ?>