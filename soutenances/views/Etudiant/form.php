<!-- Formulaire d'ajout/modification d'un étudiant -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= isset($etudiant) ? 'Modifier' : 'Ajouter' ?> un Étudiant</title>
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
            <?php if (isset($etudiant)): ?>
                <a class="btn-back" href="index.php?action=listEtudiant">Retour à la page précédente</a>
            <?php else: ?>
                <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
            <?php endif; ?>
        </div>
        <div class="page-header">
            <h1><?= isset($etudiant) ? 'Modifier' : 'Ajouter' ?> un Étudiant</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="form-container">
        <form action="index.php?action=<?= isset($etudiant) ? 'editEtudiant' : 'addEtudiant' ?>" method="POST">

            <?php if (isset($etudiant['IdEtudiant'])): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($etudiant['IdEtudiant']) ?>">
            <?php endif; ?>

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" value="<?= isset($etudiant['nom']) ? htmlspecialchars($etudiant['nom']) : '' ?>" required>
            <br><br>

            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" value="<?= isset($etudiant['prenom']) ? htmlspecialchars($etudiant['prenom']) : '' ?>" required>
            <br><br>

            <label for="mail">Mail :</label>
            <input type="email" id="mail" name="mail" value="<?= isset($etudiant['mail']) ? htmlspecialchars($etudiant['mail']) : '' ?>" autocomplete="email" spellcheck="false" required>
            <br><br>

            <!-- Empreinte supprimée de l'IHM (non demandée à l'ajout/modification) -->

            <button type="submit"><?= isset($etudiant) ? 'Modifier' : 'Ajouter' ?></button>
            <br><br>
        </form>
    </div>
    </div>
</body>
</html>
