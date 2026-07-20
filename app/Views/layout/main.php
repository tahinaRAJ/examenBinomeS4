<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($this->renderSection('title', true) ?: 'MobileMoney') ?> - MobileMoney</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <!-- Police hébergée localement (pas de requête externe : chargement instantané
         et l'application fonctionne sans connexion internet) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/templatemo-crypto-style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/templatemo-crypto-dashboard.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/templatemo-crypto-pages.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">MM</div>
                <span class="logo-text">MobileMoney</span>
            </div>

            <nav class="nav-section">
                <div class="nav-label">Menu principal</div>
                <a href="<?= site_url('dashboard') ?>" class="nav-item <?= url_is('dashboard*') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="3" y="14" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard
                </a>
                <a href="<?= site_url('comptes') ?>" class="nav-item <?= url_is('comptes*') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                        <path d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                    Comptes clients
                </a>
                <a href="<?= site_url('prefixes') ?>" class="nav-item <?= url_is('prefixes*') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                    </svg>
                    Préfixes
                </a>
                <a href="<?= site_url('tarifs') ?>" class="nav-item <?= url_is('tarifs*') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                    Tarifs
                </a>
                <a href="<?= site_url('operateurs') ?>" class="nav-item <?= url_is('operateurs*') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                    </svg>
                    Opérateurs
                </a>
                <a href="<?= site_url('compensation') ?>" class="nav-item <?= url_is('compensation*') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="17 1 21 5 17 9"/>
                        <path d="M3 11V9a4 4 0 014-4h14"/>
                        <polyline points="7 23 3 19 7 15"/>
                        <path d="M21 13v2a4 4 0 01-4 4H3"/>
                    </svg>
                    Compensation
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="theme-toggle">
                    <div class="theme-toggle-label">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"/>
                            <line x1="12" y1="1" x2="12" y2="3"/>
                            <line x1="12" y1="21" x2="12" y2="23"/>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                            <line x1="1" y1="12" x2="3" y2="12"/>
                            <line x1="21" y1="12" x2="23" y2="12"/>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                        </svg>
                        Mode clair
                    </div>
                    <div class="theme-switch" id="themeSwitch"></div>
                </div>
                <?php // Simple lien : l'espace opérateur n'a pas de session à détruire ?>
                <a href="<?= site_url('login') ?>" class="logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Quitter l'espace opérateur
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1><?= esc($this->renderSection('title', true) ?: 'Dashboard') ?></h1>
                    <p class="header-subtitle"><?= esc($this->renderSection('subtitle', true)) ?></p>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">OP</div>
                        <div class="user-info">
                            <span class="user-name">Notre opérateur</span>
                            <span class="user-role">Administration</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (session()->getFlashdata('success')) : ?>
                <div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif ?>
            <?php if (session()->getFlashdata('error')) : ?>
                <div class="flash flash-error"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif ?>

            <?= $this->renderSection('content') ?>
        </main>
    </div>

    <script src="<?= base_url('assets/js/templatemo-crypto-script.js') ?>"></script>
</body>
</html>
