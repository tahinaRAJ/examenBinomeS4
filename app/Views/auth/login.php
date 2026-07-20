<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - MobileMoney</title>
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/templatemo-crypto-style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/templatemo-crypto-login.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="auth-page">
    <!-- Colonne gauche : présentation -->
    <div class="auth-branding">
        <div class="branding-content">
            <div class="branding-logo">MM</div>
            <h1 class="branding-title">MobileMoney</h1>
            <p class="branding-subtitle">Accédez à votre compte pour déposer, retirer et transférer de l'argent en toute simplicité.</p>

            <div class="branding-features">
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1c1c1e" stroke-width="2">
                            <line x1="12" y1="19" x2="12" y2="5"/>
                            <polyline points="5 12 12 5 19 12"/>
                        </svg>
                    </div>
                    Dépôt et retrait immédiats
                </div>
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1c1c1e" stroke-width="2">
                            <polyline points="17 1 21 5 17 9"/>
                            <path d="M3 11V9a4 4 0 014-4h14"/>
                            <polyline points="7 23 3 19 7 15"/>
                            <path d="M21 13v2a4 4 0 01-4 4H3"/>
                        </svg>
                    </div>
                    Transfert vers n'importe quel numéro
                </div>
                <div class="branding-feature">
                    <div class="feature-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1c1c1e" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    Historique complet de vos opérations
                </div>
            </div>
        </div>
    </div>

    <!-- Colonne droite : formulaire -->
    <div class="auth-form-container">
        <div class="auth-form-wrapper">
            <div class="form-header">
                <h1>Bienvenue</h1>
                <p>Entrez votre numéro de compte pour vous connecter</p>
            </div>

            <?php if (session()->getFlashdata('erreur')) : ?>
                <div class="flash flash-error"><?= esc(session()->getFlashdata('erreur')) ?></div>
            <?php endif ?>

            <form class="auth-form active" action="<?= site_url('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="numero">Numéro de compte</label>
                    <div class="form-input-wrapper">
                        <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        <input type="text" class="form-input" id="numero" name="numero"
                               placeholder="Ex. 0341112233"
                               value="<?= esc(old('numero')) ?>" required autofocus>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Se connecter</button>
            </form>

            <div class="form-footer">
                <p class="muted">Espace opérateur : <a href="<?= site_url('/') ?>" class="forgot-link">accéder au dashboard</a></p>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/js/templatemo-crypto-script.js') ?>"></script>
</body>
</html>
