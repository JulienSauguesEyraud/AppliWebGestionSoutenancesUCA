<!-- Formulaire d'ajout/modification d'un enseignant -->
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
            <?php if (isset($enseignant)): ?>
                <a class="btn-back" href="index.php?action=listEnseignant" onclick="history.back(); return false;">Retour à la page précédente</a>
            <?php else: ?>
                <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
            <?php endif; ?>
        </div>
        <div class="page-header">
            <h1><?= isset($enseignant) ? 'Modifier' : 'Ajouter' ?> un Enseignant</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="form-container">
        <form action="index.php?action=<?= isset($enseignant) ? 'editEnseignant' : 'addEnseignant' ?>" method="POST">
            <?php if (isset($enseignant['IdEnseignant'])): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($enseignant['IdEnseignant']) ?>">
            <?php endif; ?>

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" value="<?= isset($enseignant['nom']) ? htmlspecialchars($enseignant['nom']) : '' ?>" required>
            <br><br>

            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" value="<?= isset($enseignant['prenom']) ? htmlspecialchars($enseignant['prenom']) : '' ?>" required>
            <br><br>

            <label for="mail">Mail :</label>
            <input type="email" id="mail" name="mail" value="<?= isset($enseignant['mail']) ? htmlspecialchars($enseignant['mail']) : '' ?>" autocomplete="email" spellcheck="false" required>
            <br><br>

            <label for="mdp">Mot de passe :</label>
            <input type="password" id="mdp" name="mdp" <?= isset($enseignant) ? '' : 'required' ?>>
            <?php if (isset($enseignant)): ?>
                <p class="muted">Laissez vide pour conserver le mot de passe actuel, ou saisissez un nouveau mot de passe pour le changer.</p>
            <?php endif; ?>
            <br><br>

            <button type="submit"><?= isset($enseignant) ? 'Modifier' : 'Ajouter' ?></button>
            <br><br>
        </form>
    </div>
    </div>
</body>
</html>
