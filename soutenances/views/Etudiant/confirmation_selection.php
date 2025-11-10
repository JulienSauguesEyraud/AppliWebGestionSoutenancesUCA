<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation</title>
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirm-card{background:#fff;border:1px solid #e6eef8;border-radius:10px;padding:16px 18px;margin:14px 0;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .warn{display:flex;align-items:center;gap:10px;background:#fff7ed;border:1px solid #ffe7cc;border-left:4px solid #f59e0b;color:#7a4a00;padding:10px 12px;border-radius:8px;margin:10px 0 16px}
        .confirm-actions{display:flex;gap:10px;align-items:center}
        .confirm-actions input,.confirm-actions button{min-width:220px}
        .table-compact td,.table-compact th{padding:.5rem .75rem}
        /* Ensure inputs fill the card without overflowing; keep gutter via card padding */
        .confirm-card .form-group{ max-width: 100%; }
        .confirm-card input,
        .confirm-card select,
        .confirm-card textarea{
            width: calc(100% - 24px); /* leave a small right gutter so it stops before card edge */
            max-width: 100%;
            box-sizing: border-box;
            display:block;
        }
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
            <a class="btn-back" href="index.php?action=listeRemontee" onclick="if (history.length > 1) { history.back(); return false; }">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Confirmation de la sélection</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>

        <div class="warn"><i class="fa-solid fa-triangle-exclamation"></i> Êtes-vous sûr de vouloir valider ces étudiants ?</div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle table-compact mb-0">
                    <thead>
                        <tr>
                            <th>ID Étudiant</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Année début</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($selection as $item): 
                            list($idEtudiant, $anneeDebut, $nom, $prenom) = explode('-', $item);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($idEtudiant) ?></td>
                                <td><?= htmlspecialchars($nom) ?></td>
                                <td><?= htmlspecialchars($prenom) ?></td>
                                <td><?= htmlspecialchars($anneeDebut) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="confirm-card">
            <form method="post" action="index.php?action=validerSelection">
                <?php foreach ($selection as $item): ?>
                    <input type="hidden" name="selection[]" value="<?= htmlspecialchars($item) ?>">
                <?php endforeach; ?>

                <div class="form-group">
                    <label for="email">Votre email :</label><br>
                    <input type="email" id="email" name="email" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="password">Votre mot de passe d'application :</label><br>
                    <input type="password" id="password" name="password" required>
                </div>
                <br>
                <div class="confirm-actions">
                    <button type="submit" class="btn btn-uca" name="confirm" value="yes"><i class="fa-solid fa-check me-1"></i>Valider</button>
                    <a href="index.php?action=listeRemontee" class="btn btn-outline-primary"><i class="fa-solid fa-xmark me-1"></i>Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
