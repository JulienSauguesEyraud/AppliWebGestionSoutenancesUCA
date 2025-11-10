<!-- Formulaire d'ajout/modification d'une entreprise -->
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
            <?php if (isset($entreprise)): ?>
                <a class="btn-back" href="index.php?action=listEntreprise" onclick="history.back(); return false;">Retour à la page précédente</a>
            <?php else: ?>
                <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
            <?php endif; ?>
        </div>
        <div class="page-header">
            <h1><?= isset($entreprise) ? 'Modifier' : 'Ajouter' ?> une Entreprise</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>

    <div class="form-container">
        <form action="index.php?action=<?= isset($entreprise) ? 'editEntreprise' : 'addEntreprise' ?>" method="POST">

            <?php if (isset($entreprise['IdEntreprise'])): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($entreprise['IdEntreprise']) ?>">
            <?php endif; ?>

            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" 
                   value="<?= isset($entreprise['nom']) ? htmlspecialchars($entreprise['nom']) : '' ?>" 
                   required>
            <br><br>

            <label for="ville">Ville :</label>
            <input type="text" id="villeE" name="villeE" 
                   value="<?= isset($entreprise['villeE']) ? htmlspecialchars($entreprise['villeE']) : '' ?>" 
                   required>
            <br><br>

         <label for="codePostal">Code Postal :</label>
         <input type="text" id="codePostal" name="codePostal"
             value="<?= isset($entreprise['codePostal']) ? htmlspecialchars($entreprise['codePostal']) : '' ?>"
             minlength="5" maxlength="5" pattern="^[0-9]{1,6}$" inputmode="numeric" autocomplete="postal-code" required>
            <br><br>

            <button type="submit"><?= isset($entreprise) ? 'Modifier' : 'Ajouter' ?></button>
            <br><br>

        </form>
    </div>

    
    </div>
</body>
</html>
