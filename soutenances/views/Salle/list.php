<!-- Liste des salles avec actions supprimer/modifier -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>soutenances</title>
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <h1>Supprimer ou modifier une salle</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="table-card">
            <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>Salle</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($salles as $salle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($salle['IdSalle']); ?></td>
                    <td><a href="index.php?action=destroySalle&id=<?= $salle['IdSalle']; ?>">Supprimer</a></td>
                    <td><a href="index.php?action=editSalle&id=<?= $salle['IdSalle']; ?>">Modifier</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            </div>
        </div>
    </div>
</body>
</html>
