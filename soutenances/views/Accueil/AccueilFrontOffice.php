<?php?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Gestion des Soutenances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
    /* Titre d'accueil comme sur pageB */
    .welcome-title{ color: #1976d2; font-size: 2.2rem; font-weight: 700; margin: 0 0 18px 0; }
    .welcome-bar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 18px; }
    .welcome-wrapper{ padding: 0; background: transparent; box-shadow: none; border-radius: 0; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .deconnexion {
            position: absolute;
            top: 50px;
            right: 20px;
        }
        /* Harmonisation du bouton Déconnexion avec le thème global (.btn-uca) */
        .deconnexion button {
            padding: .65rem 1.1rem;
            background: var(--uca-blue);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            box-shadow: var(--card-shadow);
            transition: all .2s ease;
        }
        .deconnexion button:hover { background: var(--uca-dark-blue); color:#fff; }
        .deconnexion button::before{
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            content: "\f2f5"; /* fa-sign-out-alt */
            margin-right: .5rem;
        }
        /* Le bouton Evaluer est désormais dans la barre d'accueil, stylé comme le reste */
        .btn-evaluer{ background: var(--uca-blue); border:none; color:#fff; padding:.6rem 1rem; border-radius:10px; font-weight:500; transition: all .2s ease; text-decoration:none; display:inline-flex; align-items:center; gap:.5rem; }
        .btn-evaluer:hover{ background: var(--uca-dark-blue); color:#fff; text-decoration:none; }
        .prenom {
            position: absolute;
            top: 20px;
            left: 20px;
        }
        .tuteur { background-color: #ffe599; }
        .second { background-color: #d9ead3; }
    </style>
</head>
<body>
    <div class="welcome-wrapper">
        <div class="welcome-bar">
            <h1 class="welcome-title">Bienvenue, <?= htmlspecialchars($_SESSION['user_info']['prenom'] . ' ' . $_SESSION['user_info']['nom']) ?></h1>
            <a href="index.php?action=Evaluation" class="btn-evaluer"><i class="fas fa-clipboard-check"></i> Evaluer</a>
        </div>
    </div>
    <?php if ($_SESSION['user_info']['type'] === 'enseignant'): ?>
        <h2>Tableau de soutenances à venir</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Rôle</th>
                    <th>Étudiant</th>
                    <th>Type de Soutenance</th>
                    <th>Entreprise</th>
                    <th>Maitre de stage( nom et présence)</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Salle</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($soutenances_avenir)): ?>
                <?php foreach($soutenances_avenir as $s): ?>
                    <tr class="<?= strtolower($s['role']) ?>">
                        <td><?= htmlspecialchars($s['role']) ?></td>
                        <td><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></td>
                        <td><?= htmlspecialchars($s['type_stage']) ?></td>
                        <td><?= htmlspecialchars($s['entreprise']) ?></td>
                        <td>
                            <?php if (isset($s['present'])): ?>
                            <?= htmlspecialchars($s['maitre_stage']) ?>
                                <span style="color:<?= $s['present'] ? 'green' : 'red' ?>">
                                    <?= $s['present'] ? ' : OUI' : ' : NON' ?>
                                </span>
                            <?php endif; ?>
                        </td>


                        <td>
                            <?= $s['confidentiel'] === 1
                                ? "<span style='color:red'>" . htmlspecialchars($s['date_soutenance']) . "</span>"
                                : htmlspecialchars($s['date_soutenance']) ?>
                        </td>
                        <td>
                            <?= $s['confidentiel'] === 1
                                ? "<span style='color:red'>" . htmlspecialchars($s['heure']) . "</span>"
                                : htmlspecialchars($s['heure']) ?>
                        </td>
                        <td>
                            <?= $s['confidentiel'] === 1
                                ? "<span style='color:red'>" . htmlspecialchars($s['salle']) . "</span>"
                                : htmlspecialchars($s['salle']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Aucune soutenance à venir.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>


        <h2>Tableau de soutenances passées</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Entreprise</th>
                    <th>Statut d'évaluation</th>
                    <th>Soutenance effectuée le</th>
                    <th>Heure</th>

                </tr>
            </thead>
            <tbody>
                <?php if (!empty($soutenances_passees)): ?>
                    <?php foreach($soutenances_passees as $s): ?>
                        <tr class="<?= strtolower($s['date_soutenance']) ?>">
                            <td><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></td>
                            <td><?= htmlspecialchars($s['entreprise']) ?></td>
                            <td>
                                <?php if ($s['statut'] === 'Grilles validées'): ?>
                                    <span style="color:green"><?= htmlspecialchars($s['statut']) ?></span>
                                <?php elseif ($s['statut'] === 'Saisie en cours / À VALIDER'): ?>
                                    <span style="color:red"><?= htmlspecialchars($s['statut']) ?></span>
                                <?php elseif ($s['statut'] === 'NOTES remontées'): ?>
                                    <span style="color:blue"><?= htmlspecialchars($s['statut']) ?></span>
                                <?php elseif ($s['statut'] === 'NOTES diffusées'): ?>
                                    <span style="color:orange"><?= htmlspecialchars($s['statut']) ?></span>
                                <?php elseif ($s['statut'] === 'Grilles bloquées'): ?>
                                    <span style="color:purple"><?= htmlspecialchars($s['statut']) ?></span>
                                <?php endif; ?>
                            </td>

                            <td><?= htmlspecialchars($s['date_soutenance']) ?></td>
                            <td><?= htmlspecialchars($s['heure']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Aucune soutenance effectuée</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <h2>Espace administrateur</h2>
        <p>Vous avez accès à toutes les fonctionnalités d'administration.</p>
    <?php endif; ?>
</body>
</html>