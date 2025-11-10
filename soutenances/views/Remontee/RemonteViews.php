<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion remontée des grilles</title>
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .panel-card{background:#fff;border:1px solid #e6eef8;border-radius:10px;padding:16px 18px;margin:14px 0;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .panel-card-title{font-weight:600;margin-bottom:10px;color:var(--uca-blue);display:flex;align-items:center;gap:8px}
        .hint{color:#6b7280;font-size:.9rem}
        .form-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .form-row select{min-width:200px}
        .form-row .btn-apply{min-width:160px}
    </style>
    </head>
<body>
    <?php if (isset($_SESSION['user_info'])): ?>
    <div class="topbar">
        <span></span>
        <a href="index.php?action=logout" class="btn btn-uca"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
    </div>
    <?php endif; ?>

    <div class="container-main">
        <div class="page-actions">
            <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
        </div>
        <div class="page-header">
            <h1>Gestion remontée des grilles</h1>
            <p class="subtitle">Activez l'event quotidien ou les triggers automatiques</p>
        </div>

        <div class="panel-card">
            <div class="panel-card-title"><i class="fa-regular fa-clock"></i>Event quotidien</div>
            <form method="post" class="form-row">
                <label for="toggle_event">Mode :</label>
                <select name="toggle_event" id="toggle_event">
                    <option value="1" <?php if (!empty($eventEnabled)) echo 'selected'; ?> <?php if (!empty($triggersEnabled)) echo 'disabled'; ?>>Activer</option>
                    <option value="0" <?php if (empty($eventEnabled)) echo 'selected'; ?>>Désactiver</option>
                </select>
                <button type="submit" class="btn btn-uca btn-apply"><i class="fa-solid fa-check me-1"></i>Appliquer</button>
                <?php if (!empty($triggersEnabled)): ?>
                    <span class="hint"><i class="fa-solid fa-circle-info me-1"></i>Désactivez les triggers pour activer l'event</span>
                <?php endif; ?>
            </form>
        </div>

        <div class="panel-card">
            <div class="panel-card-title"><i class="fa-solid fa-bolt"></i>Triggers automatiques</div>
            <form method="post" class="form-row">
                <label for="toggle_trigger">Statut :</label>
                <select name="toggle_trigger" id="toggle_trigger">
                    <option value="1" <?php if (!empty($triggersEnabled)) echo 'selected'; ?> <?php if (!empty($eventEnabled)) echo 'disabled'; ?>>Activés</option>
                    <option value="0" <?php if (empty($triggersEnabled)) echo 'selected'; ?>>Désactivés</option>
                </select>
                <button type="submit" class="btn btn-uca btn-apply"><i class="fa-solid fa-check me-1"></i>Appliquer</button>
                <?php if (!empty($eventEnabled)): ?>
                    <span class="hint"><i class="fa-solid fa-circle-info me-1"></i>Désactivez l'event pour activer les triggers</span>
                <?php endif; ?>
            </form>
        </div>

        <?php if(isset($message) && $message): ?>
            <div class="panel-card" style="margin-top:16px;">
                <div class="hint"><i class="fa-solid fa-circle-check me-1"></i><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>