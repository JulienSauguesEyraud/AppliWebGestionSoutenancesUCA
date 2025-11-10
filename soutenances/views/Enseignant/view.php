<!-- Vue récapitulative des stages suivis par chaque enseignant -->
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
            <h1>Voir les stages suivis par un enseignant</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        <div class="card border-0 shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Mail</th>
                <th>Enseignant Tuteur</th>
                <th>Enseignant Secondaire</th>
                <th>Enseignant Anglais</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($enseignants as $enseignant): ?>
                <?php
                    $stagesTuteur    = getStagesByEnseignantTuteurId($pdo, $enseignant['IdEnseignant']);
                    $stagesSecond    = getStagesByEnseignantSecondaireId($pdo, $enseignant['IdEnseignant']);
                    $stagesAnglais   = getStagesByEnseignantAnglaisId($pdo, $enseignant['IdEnseignant']);
                ?>
                <tr>
                    <td><?= htmlspecialchars($enseignant['nom']); ?></td>
                    <td><?= htmlspecialchars($enseignant['prenom']); ?></td>
                    <td><?= htmlspecialchars($enseignant['mail']); ?></td>
                    <td>
                        <?php if (!empty($stagesTuteur)): ?>
                            <a href="index.php?action=listStageTuteur&id=<?= $enseignant['IdEnseignant']; ?>">Stages en tant que tuteur (<?= count($stagesTuteur); ?>)</a>
                        <?php else: ?>
                            <span>Aucun stage</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($stagesSecond)): ?>
                            <a href="index.php?action=listStageSecondaire&id=<?= $enseignant['IdEnseignant']; ?>">Stages en tant que second (<?= count($stagesSecond); ?>)</a>
                        <?php else: ?>
                            <span>Aucun stage</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($stagesAnglais)): ?>
                            <a href="index.php?action=listStageAnglais&id=<?= $enseignant['IdEnseignant']; ?>">Stages en tant qu'enseignant d'anglais (<?= count($stagesAnglais); ?>)</a>
                        <?php else: ?>
                            <span>Aucun stage</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
