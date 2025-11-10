<!-- Liste des étudiants avec actions supprimer/modifier -->
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
            <h1>Supprimer ou modifier un étudiant</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="table-card">
            <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Mail</th>
                <!-- Empreinte supprimée de la liste -->
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etudiants as $etudiant): ?>
                <tr>
                    <td><?php echo htmlspecialchars($etudiant['nom']); ?></td>
                    <td><?php echo htmlspecialchars($etudiant['prenom']); ?></td>
                    <td><?php echo htmlspecialchars($etudiant['mail']); ?></td>
                    <!-- Empreinte masquée -->
                    <td>
                        <a href="index.php?action=destroyEtudiant&id=<?= $etudiant['IdEtudiant']; ?>">Supprimer</a>
                    </td>
                    <td>
                        <a href="index.php?action=editEtudiant&id=<?= $etudiant['IdEtudiant']; ?>">Modifier</a>
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
