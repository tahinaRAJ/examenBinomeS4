<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($this->renderSection('title', true) ?: 'Espace client') ?> - MobileMoney</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
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

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon">MM</div>
                <span class="logo-text">MobileMoney</span>
            </div>

            <nav class="nav-section">
                <div class="nav-label">Mon compte</div>
                <a href="<?= site_url('profil') ?>" class="nav-item <?= url_is('profil') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Mon profil
                </a>
                <a href="<?= site_url('historique') ?>" class="nav-item <?= url_is('historique') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    Historique
                </a>
            </nav>

            <nav class="nav-section">
                <div class="nav-label">Opérations</div>
                <a href="<?= site_url('client/depot') ?>" class="nav-item <?= url_is('client/depot') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="19" x2="12" y2="5"/>
                        <polyline points="5 12 12 5 19 12"/>
                    </svg>
                    Dépôt
                </a>
                <a href="<?= site_url('client/retrait') ?>" class="nav-item <?= url_is('client/retrait') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <polyline points="19 12 12 19 5 12"/>
                    </svg>
                    Retrait
                </a>
                <a href="<?= site_url('client/transfert') ?>" class="nav-item <?= url_is('client/transfert') ? 'active' : '' ?>">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="17 1 21 5 17 9"/>
                        <path d="M3 11V9a4 4 0 014-4h14"/>
                        <polyline points="7 23 3 19 7 15"/>
                        <path d="M21 13v2a4 4 0 01-4 4H3"/>
                    </svg>
                    Transfert
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
                <form action="<?= site_url('logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="logout-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Se déconnecter
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-content">
            <div class="header">
                <div class="header-left">
                    <h1><?= esc($this->renderSection('title', true) ?: 'Espace client') ?></h1>
                    <p class="header-subtitle"><?= esc($this->renderSection('subtitle', true)) ?></p>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <?php $connecte = session()->get('user'); ?>
                        <div class="user-avatar"><?= esc(mb_strtoupper(mb_substr($connecte['nom'] ?? 'C', 0, 2))) ?></div>
                        <div class="user-info">
                            <span class="user-name"><?= esc($connecte['nom'] ?? '') ?></span>
                            <span class="user-role"><?= esc($connecte['numero'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php // Les controllers client utilisent 'erreur', ceux de l'opérateur 'error' ?>
            <?php if (session()->getFlashdata('success')) : ?>
                <div class="flash flash-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif ?>
            <?php if (session()->getFlashdata('erreur')) : ?>
                <div class="flash flash-error"><?= esc(session()->getFlashdata('erreur')) ?></div>
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
