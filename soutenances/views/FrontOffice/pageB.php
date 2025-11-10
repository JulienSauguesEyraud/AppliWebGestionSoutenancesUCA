<?php
/**
 * FrontOffice – Saisie des notes par enseignant.
 *
 * - Vérifie la session, prépare une connexion PDO locale.
 * - Expose quelques helpers (type d'étudiant, statut de grille, etc.).
 * - Liste les étudiants selon 3 rôles: tuteur, second, anglais (recherche par nom possible).
 *
 * Remarque: ce fichier établit sa propre connexion PDO (conservé à l'identique).
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?action=login');
    exit;
}
$enseignant_id = $_SESSION['user_id'];

try {
    $pdo = new PDO('mysql:host=localhost;dbname=soutenances;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur base de données : ' . $e->getMessage());
}

/** Retourne un libellé court (BUT2/BUT3 Classique/Alternance). */
function afficherType($but3sinon2, $alternance) {
    if ($but3sinon2) {
        return $alternance ? "BUT3 Alternance" : "BUT3 Classique";
    } else {
        return "BUT2";
    }
}
/** Récupère le statut dans la table d'évaluation ciblée. */
function getStatut($pdo, $table, $idEtudiant) {
    $sql = "SELECT Statut FROM $table WHERE IdEtudiant = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idEtudiant]);
    return $stmt->fetchColumn();
}
/** Rend un libellé coloré selon le statut et le rôle (tuteur/second). */
function statutColorText($statut, $libelle, $second) {
    if ($statut === 'SAISIE' && !$second) {
        return '<span style="color:red;">Saisir note ' . htmlspecialchars($libelle) . '</span>';
    } elseif($statut === 'SAISIE' && $second) {
        return '<span style="color:orange;">En attente du tuteur</span>';
    } elseif($statut != 'SAISIE' && $second) {
        return '<span style="color:green;">Afficher note ' . htmlspecialchars($libelle) . '</span>';
    } elseif($statut === 'VALIDEE' || $statut === 'BLOQUEE') {
        return '<span style="color:green;">Afficher note ' . htmlspecialchars($libelle) .' (modifiable)'.'</span>';
    } elseif($statut === 'REMONTEE' || $statut === 'DIFUSEE') {
        return '<span style="color:green;">Afficher note ' . htmlspecialchars($libelle) .' (non modifiable)'. '</span>';
    }
}
/** Date planifiée d'anglais pour un étudiant. */
function getDateAnglais($pdo, $idEtudiant) {
    $sql = "SELECT dateS FROM EvalAnglais WHERE IdEtudiant = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idEtudiant]);
    return $stmt->fetchColumn();
}
/** True si l'enseignant est évaluateur d'anglais de l'étudiant. */
function profEstEvaluateurAnglais($pdo, $idEtudiant, $enseignant_id) {
    $sql = "SELECT 1 FROM EvalAnglais WHERE IdEtudiant = :id AND IdEnseignant = :enseignant_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idEtudiant, 'enseignant_id' => $enseignant_id]);
    return $stmt->fetchColumn() !== false;
}

/**
 * Formatte la date de soutenance ou affiche un message si non planifiée.
 * @param string|null $date Chaîne date/time MySQL ou null
 * @param string $prefix Préfixe ajouté uniquement quand une date valide existe (ex: "Stage : ")
 * @return string Date au format d/m/Y H:i ou message par défaut
 */
function formatSoutenanceDate($date, $prefix = '') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return "La soutenance n'est pas encore planifiée";
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return "La soutenance n'est pas encore planifiée";
    }
    return $prefix . date('d/m/Y H:i', $ts);
}

// Recherche optionnelle par nom/prénom
$search_nom = isset($_GET['search_nom']) ? trim($_GET['search_nom']) : '';
$search_like = '%' . mb_strtolower($search_nom, 'UTF-8') . '%';

// Étudiants dont l'enseignant est tuteur principal (filtre nom/prénom)
$sql_tuteur = "
    SELECT e.IdEtudiant, e.nom, e.prenom, ent.nom AS entreprise, ast.alternanceBUT3, ast.but3sinon2, 
           es.date_h, es.statut
    FROM AnneeStage ast
    JOIN EtudiantsBUT2ou3 e ON ast.IdEtudiant = e.IdEtudiant
    LEFT JOIN Entreprises ent ON ast.IdEntreprise = ent.IdEntreprise
    LEFT JOIN EvalStage es ON es.IdEtudiant = e.IdEtudiant AND es.anneeDebut = ast.anneeDebut
    WHERE es.IdEnseignantTuteur = :enseignant_id
";
if ($search_nom !== '') {
    $sql_tuteur .= " AND (LOWER(e.nom) LIKE :search_like OR LOWER(e.prenom) LIKE :search_like)";
}
// Tri: dossiers en saisie d'abord, puis par date
$sql_tuteur .= " ORDER BY (CASE WHEN es.statut = 'saisie' THEN 0 ELSE 1 END), es.date_h ASC";

$stmt = $pdo->prepare($sql_tuteur);
$params = ['enseignant_id' => $enseignant_id];
if ($search_nom !== '') $params['search_like'] = $search_like;
$stmt->execute($params);
$etudiants_tuteur = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Étudiants dont l'enseignant est second évaluateur (filtre nom/prénom)
$sql_second = "
    SELECT e.IdEtudiant, e.nom, e.prenom, ent.nom AS entreprise, ast.alternanceBUT3, ast.but3sinon2, 
           es.date_h, es.statut
    FROM AnneeStage ast
    JOIN EtudiantsBUT2ou3 e ON ast.IdEtudiant = e.IdEtudiant
    LEFT JOIN Entreprises ent ON ast.IdEntreprise = ent.IdEntreprise
    LEFT JOIN EvalStage es ON es.IdEtudiant = e.IdEtudiant AND es.anneeDebut = ast.anneeDebut
    WHERE es.IdSecondEnseignant = :enseignant_id
";
if ($search_nom !== '') {
    $sql_second .= " AND (LOWER(e.nom) LIKE :search_like OR LOWER(e.prenom) LIKE :search_like)";
}
$sql_second .= " ORDER BY (CASE WHEN es.statut = 'saisie' THEN 0 ELSE 1 END), es.date_h ASC";

$stmt2 = $pdo->prepare($sql_second);
$params2 = ['enseignant_id' => $enseignant_id];
if ($search_nom !== '') $params2['search_like'] = $search_like;
$stmt2->execute($params2);
$etudiants_second = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Étudiants pour lesquels l'enseignant est évaluateur d'anglais (filtre nom/prénom)
$sql_anglais = "
    SELECT e.IdEtudiant, e.nom, e.prenom, ent.nom AS entreprise, ast.alternanceBUT3, ast.but3sinon2, 
           ea.dateS, ea.statut
    FROM AnneeStage ast
    JOIN EtudiantsBUT2ou3 e ON ast.IdEtudiant = e.IdEtudiant
    LEFT JOIN Entreprises ent ON ast.IdEntreprise = ent.IdEntreprise
    LEFT JOIN evalanglais ea ON ea.IdEtudiant = e.IdEtudiant AND ea.anneeDebut = ast.anneeDebut
    WHERE ea.IdEnseignant = :enseignant_id
";
if ($search_nom !== '') {
    $sql_anglais .= " AND (LOWER(e.nom) LIKE :search_like OR LOWER(e.prenom) LIKE :search_like)";
}
$sql_anglais .= " ORDER BY (CASE WHEN ea.statut = 'saisie' THEN 0 ELSE 1 END), ea.dateS ASC";

$stmt_anglais = $pdo->prepare($sql_anglais);
$params_anglais = ['enseignant_id' => $enseignant_id];
if ($search_nom !== '') $params_anglais['search_like'] = $search_like;
$stmt_anglais->execute($params_anglais);
$etudiants_anglais = $stmt_anglais->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisir les notes - Gestion des Soutenances</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f9fa; }
        .container-main { max-width: 1200px; margin: 40px auto 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 32px 32px 48px 32px; }
        h1 { color: #1976d2; font-size: 2.2rem; margin-bottom: 32px; text-align: center; }
        .nav-menu { display: flex; justify-content: center; gap: 24px; margin-bottom: 32px; }
        .nav-menu a { color: #1976d2; font-weight: bold; text-decoration: none; font-size: 1.1rem; padding: 8px 18px; border-radius: 6px; transition: background 0.2s, color 0.2s; }
        .nav-menu a.active, .nav-menu a:hover { background: #e3f2fd; color: #0d47a1; text-decoration: underline; }
        .section-title { font-size: 1.3rem; color: #333; margin-top: 40px; margin-bottom: 18px; display: flex; align-items: center; gap: 12px; }
        .section-title .goto-link { font-size: 0.95rem; color: #1976d2; text-decoration: underline; cursor: pointer; margin-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fafcff; }
        th, td { border: 1px solid #e0e0e0; padding: 8px; text-align: left; }
        th { background-color: #e3f2fd; color: #1976d2; }
        tr:nth-child(even) { background: #f5f7fa; }
    .deconnexion { position: absolute; top: 20px; right: 40px; }
    /* Harmonisation bouton Déconnexion */
    .deconnexion button{ padding:.65rem 1.1rem; background:var(--uca-blue); color:#fff; border:none; border-radius:10px; font-weight:500; cursor:pointer; box-shadow:var(--card-shadow); transition:all .2s ease; }
    .deconnexion button:hover{ background:var(--uca-dark-blue); color:#fff; }
    .deconnexion button::before{ font-family:"Font Awesome 6 Free"; font-weight:900; content:"\f2f5"; margin-right:.5rem; }
        a.action-link {
            display: inline-block;              /* pour que padding et background s’appliquent bien */
            color: #fff;                        /* texte blanc */
            background-color: #d8e5f3ff;          /* fond bleu */
            text-decoration: none;
            font-weight: bold;
            padding: 6px 12px;                  /* un peu plus de padding pour l’effet bouton */
            border-radius: 6px;                 /* coins arrondis */
            border: 1px solid #1565c0;          /* bordure légèrement plus foncée */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* petite ombre */
            transition: background 0.2s, transform 0.1s;
            cursor: pointer;                    /* curseur main */
        }

        a.action-link:hover {
            background-color: #dadcdeff;          /* bleu plus foncé au hover */
            transform: translateY(-2px);        /* petit effet “lift” */
            text-decoration: none;              /* pas de soulignement */
        }

    .input-group { margin-bottom: 0; }
    /* Harmoniser les boutons principaux avec la charte */
    button { background: var(--uca-blue); }
    button:hover { background: var(--uca-dark-blue); }
        @media (max-width: 900px) { .container-main { padding: 12px; } th, td { font-size: 0.95rem; } }
    </style>
</head>
<body>
    <div class="deconnexion">
        <a href="index.php?action=logout"><button type="button">Déconnexion</button></a>
    </div>

    <h1>Bienvenue, <?= htmlspecialchars($_SESSION['user_info']['prenom'] . ' ' . $_SESSION['user_info']['nom']) ?></h1>

    
    <div class="container-main">
        <div class="page-actions">
            <a class="btn-back" href="index.php">Retour à la page précédente</a>
        </div>
        <h1>Saisir les notes de quels étudiants?</h1>
        <nav class="nav-menu">
            <a href="#tuteur-section" class="active" id="nav-tuteur">Étudiants dont vous êtes enseignant tuteur</a>
            <a href="#anglais-section" id="nav-anglais">Étudiants dont vous êtes enseignant d'anglais</a>
            <a href="#second-section" id="nav-second">Étudiants dont vous êtes enseignant second</a>
        </nav>

    <!-- Zone de résultats de recherche (remplie dynamiquement) -->
        <div id="searchResults" style="display:none; max-width:1100px; margin: 0 auto;"></div>

    <!-- Tuteur principal -->
        <div id="tuteur-section">
            <div class="section-title">
                Étudiants dont vous êtes enseignant tuteur
                <span class="goto-link" onclick="document.getElementById('second-section').scrollIntoView({behavior:'smooth'})">Aller aux étudiants second enseignant ↓</span>
                <span class="goto-link" onclick="document.getElementById('anglais-section').scrollIntoView({behavior:'smooth'})">Aller aux étudiants d'anglais ↓</span>
            </div>
            <table id="tuteur-table">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Entreprise</th>
                    <th>Type</th>
                    <th>Date de soutenance</th>
                    <th>Portfolio</th>
                    <th>Stage</th>
                    <th>Rapport</th>
                    <th>Soutenance</th>
                </tr>
                <?php foreach ($etudiants_tuteur as $etudiant): ?>
                    <?php
                        $id = $etudiant['IdEtudiant'];
                        $statut_portfolio = getStatut($pdo, 'EvalPortFolio', $id);
                        $statut_stage = getStatut($pdo, 'EvalStage', $id);
                        $statut_rapport = getStatut($pdo, 'EvalRapport', $id);
                        $statut_soutenance = getStatut($pdo, 'EvalSoutenance', $id);
                        $date_classique = isset($etudiant['date_h']) ? $etudiant['date_h'] : null;
                        $date_anglais = profEstEvaluateurAnglais($pdo, $id, $enseignant_id) ? getDateAnglais($pdo, $id) : null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['entreprise']) ?></td>
                        <td><?= afficherType($etudiant['but3sinon2'], $etudiant['alternanceBUT3']) ?></td>
                        <td>
                            <?php echo formatSoutenanceDate($date_classique); ?>
                        </td>
                        <td><a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=portfolio&isTuteur=True" class="action-link"><?= statutColorText($statut_portfolio, 'portfolio', false) ?></a></td>
                        <td><a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=stage&isTuteur=True" class="action-link"><?= statutColorText($statut_stage, 'stage', false) ?></a></td>
                        <td><a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=rapport&isTuteur=True" class="action-link"><?= statutColorText($statut_rapport, 'rapport', false) ?></a></td>
                        <td><a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=soutenance&isTuteur=True" class="action-link"><?= statutColorText($statut_soutenance, 'soutenance', false) ?></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($etudiants_tuteur)): ?>
                    <tr><td colspan="10">Aucun étudiant trouvé.</td></tr>
                <?php endif; ?>
            </table>
        </div>

    <!-- Anglais -->
        <div id="anglais-section">
            <div class="section-title">
                Étudiants dont vous êtes enseignant d'anglais 
                <span class="goto-link" onclick="document.getElementById('tuteur-section').scrollIntoView({behavior:'smooth'})">Aller aux étudiants tuteur principal ↑</span>
                <span class="goto-link" onclick="document.getElementById('second-section').scrollIntoView({behavior:'smooth'})">Aller aux étudiants second enseignant ↓</span>
            </div>
            <table id="anglais-table">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Entreprise</th>
                    <th>Type</th>
                    <th>Date de soutenance</th>
                    <th>Anglais</th>
                </tr>
                <?php foreach ($etudiants_anglais as $etudiant): ?>
                    <?php
                        $id = $etudiant['IdEtudiant'];
                        $date_anglais = $etudiant['dateS'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['entreprise']) ?></td>
                        <td><?= afficherType($etudiant['but3sinon2'], $etudiant['alternanceBUT3']) ?></td>
                        <td><?php echo formatSoutenanceDate($date_anglais); ?></td>    
                        <td>
                            <?php $statut_anglais = getStatut($pdo, 'EvalAnglais', $id); ?>
                            <a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=anglais" class="action-link"><?= statutColorText($statut_anglais, 'anglais', false) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($etudiants_anglais)): ?>
                    <tr><td colspan="6">Aucun étudiant trouvé.</td></tr>
                <?php endif; ?>
            </table>
        </div>

    <!-- Second enseignant -->
        <div id="second-section">
            <div class="section-title">
                Étudiants dont vous êtes enseignant second
                <span class="goto-link" onclick="document.getElementById('anglais-section').scrollIntoView({behavior:'smooth'})">Aller aux étudiants d'anglais ↑</span>
                <span class="goto-link" onclick="document.getElementById('tuteur-section').scrollIntoView({behavior:'smooth'})">Aller aux étudiants tuteur principal ↑</span>
            </div>
            <table id="second-table">
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Entreprise</th>
                    <th>Type</th>
                    <th>Date de soutenance</th>
                    <th>Portfolio</th>
                    <th>Stage</th>
                    <th>Rapport</th>
                    <th>Soutenance</th>
                </tr>
                <?php foreach ($etudiants_second as $etudiant): ?>
                    <?php
                        $id = $etudiant['IdEtudiant'];
                        $statut_portfolio = getStatut($pdo, 'EvalPortFolio', $id);
                        $statut_stage = getStatut($pdo, 'EvalStage', $id);
                        $statut_rapport = getStatut($pdo, 'EvalRapport', $id);
                        $statut_soutenance = getStatut($pdo, 'EvalSoutenance', $id);
                        $date_classique = isset($etudiant['date_h']) ? $etudiant['date_h'] : null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($etudiant['nom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
                        <td><?= htmlspecialchars($etudiant['entreprise']) ?></td>
                        <td><?= afficherType($etudiant['but3sinon2'], $etudiant['alternanceBUT3']) ?></td>
                        <td><?= formatSoutenanceDate($date_classique, 'Stage : ') ?></td>
                        <td>
                            <?php if ($statut_portfolio !== 'SAISIE'): ?>
                                <a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=portfolio&mode=affichage" class="action-link"><?= statutColorText($statut_portfolio, 'portfolio', true) ?></a>
                            <?php else: ?>
                                <?= statutColorText($statut_portfolio, 'portfolio', true) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($statut_stage !== 'SAISIE'): ?>
                                <a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=stage&mode=affichage" class="action-link"><?= statutColorText($statut_stage, 'stage', true) ?></a>
                            <?php else: ?>
                                <?= statutColorText($statut_stage, 'stage', true) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($statut_rapport !== 'SAISIE'): ?>
                                <a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=rapport&mode=affichage" class="action-link"><?= statutColorText($statut_rapport, 'rapport', true) ?></a>
                            <?php else: ?>
                                <?= statutColorText($statut_rapport, 'rapport', true) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($statut_soutenance !== 'SAISIE'): ?>
                                <a href="views/FrontOffice/saisir_note.php?id_etudiant=<?= $id ?>&type_note=soutenance&mode=affichage" class="action-link"><?= statutColorText($statut_soutenance, 'soutenance', true) ?></a>
                            <?php else: ?>
                                <?= statutColorText($statut_soutenance, 'soutenance', true) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($etudiants_second)): ?>
                    <tr><td colspan="10">Aucun étudiant trouvé.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>


</body>
</html>