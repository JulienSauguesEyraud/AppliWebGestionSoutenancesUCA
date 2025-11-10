<!-- Liste des entreprises avec actions supprimer/modifier -->
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
            <h1>Supprimer ou modifier une entreprise</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="table-card">
            <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Ville</th>
                <th>Code postal</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entreprises as $entreprise): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entreprise['nom']); ?></td>
                    <td><?php echo htmlspecialchars($entreprise['villeE']); ?></td>
                    <td><?php echo htmlspecialchars($entreprise['codePostal']); ?></td>
                    <td>
                        <a href="index.php?action=destroyEntreprise&id=<?= $entreprise['IdEntreprise']; ?>">Supprimer</a>
                    </td>
                    <td>
                        <a href="index.php?action=editEntreprise&id=<?= $entreprise['IdEntreprise']; ?>">Modifier</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
            </div>
        </div>
    </div>
</body>
</html>
