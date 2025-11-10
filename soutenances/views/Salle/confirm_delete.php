<!-- Confirmation de suppression d'une salle -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>soutenances</title>
    <link rel="stylesheet" href="assets/style/style.css">
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
            <a class="btn-back" href="index.php?action=listSalle">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Confirmer la suppression</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="confirm-box">
            <p>Voulez-vous vraiment supprimer cette salle ?</p>
            <form method="post" action="index.php?action=destroySalle&id=<?= htmlspecialchars($id) ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <div class="confirm-actions">
                    <button type="submit">Supprimer</button>
                    <a class="action-link" href="index.php?action=listSalle">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>