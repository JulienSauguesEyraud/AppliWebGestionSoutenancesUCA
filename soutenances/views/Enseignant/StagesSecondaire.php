<!-- Stages où l'enseignant est second enseignant -->
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
            <a class="btn-back" href="index.php?action=viewEnseignant" onclick="history.back(); return false;">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Voir les stages de <?= htmlspecialchars($enseignant['nom']) ?> <?= htmlspecialchars($enseignant['prenom']) ?> en tant que second enseignant</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <h3><?= htmlspecialchars($enseignant['nom']) ?> <?= htmlspecialchars($enseignant['prenom']) ?> possède <?= count($StageSecondaire) ?> stage(s) en tant que second enseignant</h3>
        <div class="table-card"><div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>Nom de l'étudiant</th>
                <th>Prénom de l'étudiant</th>
                <th>Entreprise</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($StageSecondaire as $stage): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stage['nomEtudiant']); ?></td>
                    <td><?php echo htmlspecialchars($stage['prenomEtudiant']); ?></td>
                    <td><?php echo htmlspecialchars($stage['nomEntreprise']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
        </div></div>
    </div>
</body>
</html>
