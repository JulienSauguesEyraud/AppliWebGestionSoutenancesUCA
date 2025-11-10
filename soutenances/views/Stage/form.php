<!-- Formulaire d'ajout de stage à un étudiant -->
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
            <a class="btn-back" href="index.php?action=listStage">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Ajouter un stage à <?= htmlspecialchars($etudiant['nom'].' '.$etudiant['prenom']) ?></h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="form-container">
        <form action="index.php?action=addStage&idEtudiant=<?= $etudiant['IdEtudiant'] ?>" method="POST">
            <input type="hidden" name="idEtudiant" value="<?= $etudiant['IdEtudiant'] ?>">

            <label for="Entreprise">Entreprise :</label>
            <select id="Entreprise" name="Entreprise" required>
                <?php foreach ($entreprises as $entreprise): ?>
                    <option value="<?= htmlspecialchars($entreprise['IdEntreprise']) ?>">
                        <?= htmlspecialchars($entreprise['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="but3sinon2">BUT3 (sinon 2) :</label>
            <select id="but3sinon2" name="but3sinon2" required>
                <option value="0">Non</option>
                <option value="1">Oui</option>
            </select>
            <br><br>

            <div id="alternance-container" style="display:none;">
                <label for="alternanceBUT3">Alternance en BUT3 :</label>
                <select id="alternanceBUT3" name="alternanceBUT3">
                    <option value="0">Non</option>
                    <option value="1">Oui</option>
                </select>
                <br><br>
            </div>

            <label for="nomMaitreStageApp">Nom du Maitre de Stage :</label>
            <input type="text" id="nomMaitreStageApp" name="nomMaitreStageApp" required>
            <br><br>

            <label for="sujet">Sujet :</label>
            <input type="text" id="sujet" name="sujet" required>
            <br><br>

            <button type="submit">Ajouter</button>
        </form>
    </div>
    <script>
        const but3Select = document.getElementById('but3sinon2');
        const alternanceContainer = document.getElementById('alternance-container');

        but3Select.addEventListener('change', () => {
            alternanceContainer.style.display = but3Select.value === "1" ? "block" : "none";
        });
    </script>
    </div>
</body>
</html>
