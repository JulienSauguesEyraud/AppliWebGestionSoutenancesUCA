<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails étudiant</title>
</head>
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<body>
    <h1>Évaluations de <?= htmlspecialchars($etudiant['prenom'] . " " . $etudiant['nom']) ?></h1>
    <?php if (isset($_SESSION['user_info'])): ?>
    <div class="topbar">
        <span></span>
        <a href="index.php?action=logout" class="btn btn-uca"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
    </div>
    <?php endif; ?>

    <div class="container-main">
        <div class="page-actions">
            <a class="btn-back" href="javascript:void(0)" onclick="if (history.length > 1) { history.back(); } else { window.location='index.php?action=search_students'; }">Retour à la page précédente</a>
        </div>

        <div class="page-header">
            <h1>Fiche étudiant</h1>
            <?php if (isset($etudiant)): ?>
                <p class="subtitle"><i class="fa-solid fa-id-card-clip me-1"></i><?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?> — ID <?= htmlspecialchars($etudiant['IdEtudiant']) ?></p>
            <?php else: ?>
                <p class="subtitle">Informations indisponibles</p>
            <?php endif; ?>
        </div>

        <?php if (isset($etudiant)): ?>
        <div class="table-card" style="margin-bottom:16px;">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <tbody>
                        <tr>
                            <th style="width:220px;">Identifiant</th>
                            <td><?= htmlspecialchars($etudiant['IdEtudiant']) ?></td>
                        </tr>
                        <tr>
                            <th>Nom</th>
                            <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                        </tr>
                        <tr>
                            <th>Prénom</th>
                            <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                        </tr>
                        <?php if (!empty($etudiant['groupe'])): ?>
                        <tr>
                            <th>Groupe</th>
                            <td><?= htmlspecialchars($etudiant['groupe']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($etudiant['parcours'])): ?>
                        <tr>
                            <th>Parcours</th>
                            <td><?= htmlspecialchars($etudiant['parcours']) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="panel-card" style="margin-bottom: 16px;">
            <div class="panel-card-title"><i class="fa-solid fa-chart-line me-2"></i>Évaluations</div>
            <?php if (!empty($evaluations)): ?>
            <div class="table-card" style="margin:0;">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Année</th>
                                <th>Type</th>
                                <th>Note</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($evaluations as $eval): ?>
                                <tr>
                                    <td><?= htmlspecialchars($eval['annee']) ?></td>
                                    <td><?= htmlspecialchars($eval['type']) ?></td>
                                    <td><?= htmlspecialchars($eval['note']) ?></td>
                                    <td><?= htmlspecialchars($eval['commentaire']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
                <div class="banner-info" style="margin:0;">
                    <i class="fa-solid fa-circle-info me-2"></i>Aucune évaluation disponible.
                </div>
            <?php endif; ?>
        </div>

        <div class="page-actions">
            <a class="btn btn-secondary" href="javascript:void(0)" onclick="if (history.length > 1) { history.back(); } else { window.location='index.php?action=search_students'; }"><i class="fa-solid fa-arrow-left me-1"></i>Retour</a>
        </div>
    </div>
    <h2>Évaluations Stage</h2>
    <?php if (!empty($stages)): ?>
        <ul>
            <?php foreach ($stages as $st): ?>
                <li>
                    <strong>Année <?= htmlspecialchars($st['anneeDebut']) ?> :</strong><br>
                    Date : <?= htmlspecialchars($st['date_h']) ?><br>
                    Note : <?= htmlspecialchars($st['note']) ?><br>
                    Commentaire : <?= htmlspecialchars($st['commentaireJury']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun stage diffusé.</p>
    <?php endif; ?>

    <h2>Évaluations Soutenance</h2>
    <?php if (!empty($soutenances)): ?>
        <ul>
            <?php foreach ($soutenances as $s): ?>
                <li>
                    <strong>Année <?= htmlspecialchars($s['anneeDebut']) ?> :</strong><br>
                    Note : <?= htmlspecialchars($s['note']) ?><br>
                    Commentaire : <?= htmlspecialchars($s['commentaireJury']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucune soutenance diffusée.</p>
    <?php endif; ?>

    <h2>Évaluations Rapport</h2>
    <?php if (!empty($rapports)): ?>
        <ul>
            <?php foreach ($rapports as $r): ?>
                <li>
                    <strong>Année <?= htmlspecialchars($r['anneeDebut']) ?> :</strong><br>
                    Note : <?= htmlspecialchars($r['note']) ?><br>
                    Commentaire : <?= htmlspecialchars($r['commentaireJury']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun rapport diffusé.</p>
    <?php endif; ?>

</body>
</html>
