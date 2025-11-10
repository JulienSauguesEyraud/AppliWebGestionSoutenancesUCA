<!-- Formulaire d'affectation des enseignants tuteur/secondaire -->
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
            <a class="btn-back" href="index.php?action=listEnseignantStage" onclick="history.back(); return false;">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Ajouter des enseignants au stage</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="form-container">
        <form action="index.php?action=addEnseignantStage&idEtudiant=<?= $etudiant['IdEtudiant'] ?>" method="POST">
            <input type="hidden" name="idEtudiant" value="<?= $etudiant['IdEtudiant'] ?>">

            <label for="IdEnseignantTuteur">Enseignant Tuteur :</label>
            <select id="IdEnseignantTuteur" name="IdEnseignantTuteur" required>
                <?php foreach ($enseignants as $enseignant): ?>
                    <option value="<?= htmlspecialchars($enseignant['IdEnseignant']) ?>">
                        <?= htmlspecialchars($enseignant['prenom'] . ' ' . $enseignant['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label for="IdSecondEnseignant">Enseignant Secondaire :</label>
            <select id="IdSecondEnseignant" name="IdSecondEnseignant" required>
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

    <script>
        const selectTuteur = document.getElementById('IdEnseignantTuteur');
        const selectSecond = document.getElementById('IdSecondEnseignant');

        function filtrerSecond() {
            const tuteur = selectTuteur.value;
            Array.from(selectSecond.options).forEach(opt => {
                opt.hidden = (opt.value === tuteur);
            });
            if (!selectSecond.value || selectSecond.options[selectSecond.selectedIndex]?.hidden) {
                let found = false;
                for (const opt of selectSecond.options) {
                    if (!opt.hidden) {
                        opt.selected = true;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    selectSecond.selectedIndex = -1;
                    selectSecond.disabled = true;
                } else {
                    selectSecond.disabled = false;
                }
            }
        }
        selectTuteur.addEventListener('change', filtrerSecond);
        filtrerSecond();
    </script>
    </div>
</body>
</html>
