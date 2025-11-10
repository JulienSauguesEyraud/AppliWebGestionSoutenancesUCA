<!-- Formulaire d'ajout/modification d'une salle -->
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
            <?php if (isset($salle)): ?>
                <a class="btn-back" href="index.php?action=listSalle">Retour à la page précédente</a>
            <?php else: ?>
                <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
            <?php endif; ?>
        </div>
        <div class="page-header">
            <h1><?= isset($salle) ? 'Modifier' : 'Ajouter' ?> une Salle</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="form-container">
        <form action="index.php?action=<?= isset($salle) ? 'editSalle' : 'addSalle' ?>" method="POST">
            <?php if (isset($salle['IdSalle'])): ?>
                <input type="hidden" name="original_id" value="<?= htmlspecialchars($salle['IdSalle']) ?>">
            <?php endif; ?>

            <label for="IdSalle">Nom de la Salle :</label>
            <input type="text" id="IdSalle" name="IdSalle" value="<?= isset($salle['IdSalle']) ? htmlspecialchars($salle['IdSalle']) : '' ?>" required>
            <br><br>

            <label for="description">Description :</label>
            <input type="text" id="description" name="description" value="<?= isset($salle['description']) ? htmlspecialchars($salle['description']) : '' ?>">
            <br><br>

            <button type="submit"><?= isset($salle) ? 'Modifier' : 'Ajouter' ?></button>
            <br><br>
        </form>
    </div>
    
    </div>
</body>
</html>
