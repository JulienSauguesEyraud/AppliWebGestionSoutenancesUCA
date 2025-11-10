<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des étudiants - Remontée</title>
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
            <h1>Étudiants avec statut "Remontée"</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>

    <!-- Liste BUT2 -->
    <h2>Deuxième année (BUT2)</h2>
    <form method="post" action="index.php?action=confirmSelection">
        <div class="table-card">
        <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
            <thead>
            <tr>
                <?php if ($showCheckboxBut2): ?>
                    <th>Sélection</th>
                <?php endif; ?>
                <th>Id Étudiant</th>
                <th>Nom</th>
                <th>Prénom</th>
                <?php if (!$showCheckboxBut2): ?>
                    <th>Note Stage</th>
                    <th>Note Portfolio</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($but2 as $etudiant): ?>
                <tr>
                    
                    <?php if ($showCheckboxBut2): ?>
                        <td><input type="checkbox" name="selection[]" 
                            value="<?= $etudiant['IdEtudiant'] . '-' . $etudiant['anneeDebut'] . '-' . $etudiant['nom'] . '-' . $etudiant['prenom'] ?>">
                    </td>
                <?php endif; ?>
                        
                    <td><?= htmlspecialchars($etudiant['IdEtudiant']) ?></td>
                    <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                    <?php if (!$showCheckboxBut2): ?>
                        <td><?= htmlspecialchars($etudiant['noteStage'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($etudiant['notePortfolio'] ?? '-') ?></td>
                <?php endif; ?>
                    
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        </div>
        <br>
        <?php if ($showCheckboxBut2): ?>
            <button type="submit" class="btn btn-uca"><i class="fa-solid fa-check me-1"></i>Valider la sélection BUT2</button>
        <?php endif; ?>
        
    </form>

    <!-- Liste BUT3 -->
    <h2>Troisième année (BUT3)</h2>
    <form method="post" action="index.php?action=confirmSelection">
        <div class="table-card">
        <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
            <thead>
            <tr>
                <?php if ($showCheckboxBut3): ?>
                    <th>Sélection</th>
                <?php endif; ?>
                <th>Id Étudiant</th>
                <th>Nom</th>
                <th>Prénom</th>
                <?php if (!$showCheckboxBut3): ?>
                    <th>Note Stage</th>
                    <th>Note Portfolio</th>
                    <th>Note Anglais</th>
                <?php endif; ?>
                
            </tr>
            </thead>
            <tbody>
            <?php foreach ($but3 as $etudiant): ?>
                <tr>
                <?php if ($showCheckboxBut3): ?>
                    <td>
                        <input type="checkbox" name="selection[]" 
                            value="<?= $etudiant['IdEtudiant'] . '-' . $etudiant['anneeDebut'] . '-' . $etudiant['nom'] . '-' . $etudiant['prenom'] ?>">
                    </td>
                <?php endif; ?>
                    
                    <td><?= htmlspecialchars($etudiant['IdEtudiant']) ?></td>
                    <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                    <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                    <?php if (!$showCheckboxBut3): ?>
                        <td><?= htmlspecialchars($etudiant['noteStage'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($etudiant['notePortfolio'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($etudiant['noteAnglais'] ?? '-') ?></td>
                    <?php endif; ?>
                    
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        </div>
        <br>
        <?php if ($showCheckboxBut3): ?>
            <button type="submit" class="btn btn-uca"><i class="fa-solid fa-check me-1"></i>Valider la sélection BUT3</button>
        <?php endif; ?>
        
    </form>
        </div>
    </div>
</body>
</html>
