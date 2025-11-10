<!-- Formulaire d'affectation de l'enseignant d'anglais -->
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
            <a class="btn-back" href="index.php?action=listEnseignantAnglais">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Ajouter un enseignant d'anglais</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="form-container">
        <form action="index.php?action=addEnseignantAnglais&idEtudiant=<?= $etudiant['IdEtudiant'] ?>" method="POST">
            <input type="hidden" name="idEtudiant" value="<?= $etudiant['IdEtudiant'] ?>">

            <label for="IdEnseignant">Enseignant :</label>
            <select id="IdEnseignant" name="IdEnseignant" required>
                <?php foreach ($enseignants as $enseignant): ?>
                    <option value="<?= htmlspecialchars($enseignant['IdEnseignant']) ?>">
                        <?= htmlspecialchars($enseignant['prenom'] . ' ' . $enseignant['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <button type="submit">Ajouter</button>
        </form>
    </div>
    </div>
</body>
</html>
