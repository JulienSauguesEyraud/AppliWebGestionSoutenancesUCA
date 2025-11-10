<?php
/**
 * Vue: Liste et planning des soutenances
 * - Sélecteur de date + navigation
 * - Tableaux de synthèse (à affecter / anglais sans date-salle / sans planification)
 * - Deux plannings: Stage (créneaux 1h) et Anglais (créneaux 20 min)
 * - Mode AJAX (ajax=planning) pour rafraîchir uniquement le planning
 */

// Récupération du jour de planning depuis l'URL ou par défaut la date actuelle
$jourPlanning = $_GET['jourPlanning'] ?? date('Y-m-d');
$displayJour = date('d/m/Y', strtotime($jourPlanning));
// Récupération de l'année de début depuis l'URL ou par défaut l'année scolaire en cours
$anneeDebut = $_GET['anneeDebut'] ?? scolarite_annee_debut();
// Récupération de toutes les soutenances pour l'année donnée
$soutenances = soutenance_getSoutenancesPlanifiees($pdo, $anneeDebut);
// Sous-ensemble pour le jour choisi (n'affecte pas les autres listes)
$soutenancesJour = [];
if (is_array($soutenances)) {
    foreach ($soutenances as $s) {
        $dateStr = $s['date'] ?? $s['dateS'] ?? $s['date_h'] ?? null;
        if (empty($dateStr)) continue;
        try { $d = new DateTime($dateStr); } catch (Throwable $e) { continue; }
        if ($d->format('Y-m-d') === $jourPlanning) $soutenancesJour[] = $s;
    }
}

// S'assure que trimId est défini avant usage (notamment dans la réponse AJAX)
if (!function_exists('trimId')) {
    function trimId($v): string {
        return $v === null ? '' : trim((string)$v);
    }
}

$isAjaxPlanning = isset($_GET['ajax']) && $_GET['ajax'] === 'planning';

// Réponse partielle AJAX pour rafraîchir seulement le planning
if ($isAjaxPlanning) {
    // On reconstruit le sous-ensemble pour le jour demandé au cas où
    $jp = $_GET['jourPlanning'] ?? $jourPlanning;
    $disp = date('d/m/Y', strtotime($jp));
    $sJour = [];
    if (is_array($soutenances)) {
        foreach ($soutenances as $s) {
            $dateStr = $s['date'] ?? $s['dateS'] ?? $s['date_h'] ?? null;
            if (empty($dateStr)) continue;
            try { $d = new DateTime($dateStr); } catch (Throwable $e) { continue; }
            if ($d->format('Y-m-d') === $jp) $sJour[] = $s;
        }
    }
    $renderedSoutenances = [];
    // Sortie HTML minimal du bloc planning (identique à la page principale)
    ?>
    <h2>Planning Stages (1h) - <?= htmlspecialchars($disp) ?></h2>
    <div class="table-card"><div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
    <thead>
    <tr>
        <th>Heure</th>
        <?php foreach($salles as $salle): ?>
            <th><?= htmlspecialchars($salle['IdSalle'] ?? '') ?> : <?= htmlspecialchars($salle['description'] ?? '') ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php for($h=8; $h<18; $h++): $heure = sprintf('%02d:00', $h); ?>
    <tr>
        <td style="width:80px;"><?= $heure ?></td>
        <?php foreach($salles as $salle): ?>
        <td style="position:relative; height:120px;">
            <?php foreach($sJour as $soutenance):
                if(($soutenance['type'] ?? '') !== 'STAGE') continue;
                $dateStr = $soutenance['date'] ?? $soutenance['date_h'] ?? null;
                if (!$dateStr) continue;
                $date = new DateTime($dateStr);
                $heureDebut = (int)$date->format('H');
                $jourSoutenance = $date->format('Y-m-d');
                $salleSoutenance = isset($soutenance['IdSalle']) ? trim((string)$soutenance['IdSalle']) : '';
                $salleLigne      = isset($salle['IdSalle'])      ? trim((string)$salle['IdSalle'])      : '';
                if ($heureDebut == $h && $jourSoutenance === $jp && $salleSoutenance !== '' && $salleSoutenance === $salleLigne): ?>
                <div class="soutenance stage">
                    <strong>Étudiant:</strong> <?= htmlspecialchars($soutenance['etudiant'] ?? '?') ?><br>
                    <?php if(!empty($soutenance['tuteur'])): ?>
                        <strong>Tuteur:</strong> <?= htmlspecialchars($soutenance['tuteur']) ?><br>
                    <?php endif; ?>
                    <?php if(!empty($soutenance['secondEnseignant'])): ?>
                        <strong>Second:</strong> <?= htmlspecialchars($soutenance['secondEnseignant']) ?><br>
                    <?php endif; ?>
                    <?php if(!empty($soutenance['Id'])): ?>
                        <a href='index.php?action=modifierSoutenanceRestreinte&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=STAGE&anneeDebut=<?= urlencode($_GET['anneeDebut'] ?? date('Y')) ?>&jourPlanning=<?= urlencode($jp) ?>'>Modifier</a> |
                        <a href='index.php?action=supprimerSoutenance&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=STAGE' onclick="return confirm('Supprimer ?');">Supprimer</a>
                    <?php endif; ?>
                </div>
            <?php endif; endforeach; ?>
        </td>
        <?php endforeach; ?>
    </tr>
    <?php endfor; ?>
    </tbody>
    </table></div></div>

    <h2>Planning Anglais (20 min) - <?= htmlspecialchars($disp) ?></h2>
    <div class="table-card"><div class="table-responsive">
    <table class="table table-striped table-bordered align-middle mb-0">
    <thead>
    <tr>
        <th>Heure</th>
        <?php foreach($salles as $salle): ?>
            <th><?= htmlspecialchars($salle['IdSalle'] ?? '') ?> : <?= htmlspecialchars($salle['description'] ?? '') ?></th>
        <?php endforeach; ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $start = new DateTime('08:00');
    $end = new DateTime('17:40');
    $interval = new DateInterval('PT20M');
    $period = new DatePeriod($start, $interval, (clone $end)->add(new DateInterval('PT1M')));
    foreach($period as $heureSlot):
        $heure = $heureSlot->format('H:i'); ?>
    <tr>
        <td style='width:60px;'><?= $heure ?></td>
        <?php foreach($salles as $salle): ?>
        <td style='position:relative; height:50px;'>
            <?php foreach($sJour as $soutenance):
                if(($soutenance['type'] ?? '') !== 'ANGLAIS') continue;
                $dateStr = $soutenance['date'] ?? $soutenance['dateS'] ?? $soutenance['date_h'] ?? null;
                if (!$dateStr) continue;
                $date = new DateTime($dateStr);
                $soutenanceHeure = $date->format('H:i');
                $jourSoutenance = $date->format('Y-m-d');
                $salleSoutenance = isset($soutenance['IdSalle']) ? trimId($soutenance['IdSalle']) : '';
                $salleLigne      = isset($salle['IdSalle'])      ? trimId($salle['IdSalle'])      : '';
                $soutenanceId = null;
                foreach (['IdEvalAnglais','IdEval','Id','IdSoutenance'] as $k) {
                    if (!empty($soutenance[$k])) { $soutenanceId = (string)$soutenance[$k]; break; }
                }
                if ($soutenanceHeure === $heure && $jourSoutenance === $jp && $salleSoutenance !== '' && $salleSoutenance === $salleLigne && $soutenanceId !== null && !in_array($soutenanceId, $renderedSoutenances, true)):
                    $renderedSoutenances[] = $soutenanceId;
            ?>
                <div class='soutenance anglais'>
                    <strong>Étudiant:</strong> <?= htmlspecialchars($soutenance['etudiant'] ?? '?') ?><br>
                    <?php if(!empty($soutenance['tuteur'])): ?>
                        <strong>Enseignant:</strong> <?= htmlspecialchars($soutenance['tuteur']) ?><br>
                    <?php endif; ?>
                    <?php if(!empty($soutenance['Id'])): ?>
                        <a href='index.php?action=modifierSoutenanceRestreinte&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=ANGLAIS&anneeDebut=<?= urlencode($_GET['anneeDebut'] ?? date('Y')) ?>&jourPlanning=<?= urlencode($jp) ?>'>Modifier</a> |
                        <a href='index.php?action=supprimerSoutenance&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=ANGLAIS' onclick="return confirm('Supprimer ?');">Supprimer</a>
                    <?php endif; ?>
                </div>
            <?php endif; endforeach; ?>
        </td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table></div></div>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
        <meta charset="UTF-8">
        <title>Liste des étudiants et soutenances</title>
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
            <h1>Liste des étudiants et soutenances</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
<?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="notif success">Soutenance enregistrée avec succès</div>
    <script>setTimeout(()=>{document.querySelector('.notif.success').remove()}, 3000)</script>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    <div class="notif error"><?= htmlspecialchars($_GET['error']) ?></div>
    <script>setTimeout(()=>{document.querySelector('.notif.error').remove()}, 5000)</script>
<?php endif; ?>

<!-- Sélecteur de date pour le planning -->
<form method="get" action="" class="planning-filter" style="margin: 12px 0; display:flex; align-items:center; gap:8px;">
    <?php if (!empty($_GET['action'])): ?>
        <input type="hidden" name="action" value="<?= htmlspecialchars($_GET['action']) ?>">
    <?php endif; ?>
    <input type="hidden" name="anneeDebut" value="<?= htmlspecialchars($anneeDebut) ?>">
    <label for="jourPlanning">Voir le planning du jour :</label>
    <input type="date" id="jourPlanning" name="jourPlanning" value="<?= htmlspecialchars($jourPlanning) ?>">
    <?php
    // Liens pratique J-1 / J+1
    $dobj = DateTime::createFromFormat('Y-m-d', $jourPlanning) ?: new DateTime();
    $prev = (clone $dobj)->modify('-1 day')->format('Y-m-d');
    $next = (clone $dobj)->modify('+1 day')->format('Y-m-d');
    ?>
    <a href="#" class="date-nav" data-dir="prev" style="margin-left:8px;">◀ Jour précédent</a>
    <a href="#" class="date-nav" data-dir="next">Jour suivant ▶</a>
    <a href="#" class="date-nav" data-dir="today" style="margin-left:8px;">Aujourd'hui</a>
</form>
<!-- Emplois du temps en premier: Planning Stages et Anglais -->
<div id="planning-container">
<h2>Planning Stages (1h) - <?= htmlspecialchars($displayJour) ?></h2>
<div class="table-card"><div class="table-responsive">
<table class="table table-striped table-bordered align-middle mb-0">
<thead>
<tr>
    <th>Heure</th>
    <?php foreach($salles as $salle): ?>
        <th><?= htmlspecialchars($salle['IdSalle'] ?? '') ?> : <?= htmlspecialchars($salle['description'] ?? '') ?></th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php for($h=8; $h<18; $h++): $heure = sprintf('%02d:00', $h); ?>
<tr>
    <td style="width:80px;"><?= $heure ?></td>
    <?php foreach($salles as $salle): ?>
    <td style="position:relative; height:120px;">
        <?php foreach($soutenancesJour as $soutenance):
            if(($soutenance['type'] ?? '') !== 'STAGE') continue;
            $dateStr = $soutenance['date'] ?? $soutenance['date_h'] ?? null;
            if (!$dateStr) continue;
            $date = new DateTime($dateStr);
            $heureDebut = (int)$date->format('H');
            $jourSoutenance = $date->format('Y-m-d');
            // comparer l'IdSalle exact (string) après trim — évite les casts en int qui rendent "AmphiB1" -> 0
            $salleSoutenance = isset($soutenance['IdSalle']) ? trim((string)$soutenance['IdSalle']) : '';
            $salleLigne      = isset($salle['IdSalle'])      ? trim((string)$salle['IdSalle'])      : '';
            if ($heureDebut == $h && $jourSoutenance === $jourPlanning && $salleSoutenance !== '' && $salleSoutenance === $salleLigne): ?>
            <div class="soutenance stage">
                <strong>Étudiant:</strong> <?= htmlspecialchars($soutenance['etudiant'] ?? '?') ?><br>
                <?php if(!empty($soutenance['tuteur'])): ?>
                    <strong>Tuteur:</strong> <?= htmlspecialchars($soutenance['tuteur']) ?><br>
                <?php endif; ?>
                <?php if(!empty($soutenance['secondEnseignant'])): ?>
                    <strong>Second:</strong> <?= htmlspecialchars($soutenance['secondEnseignant']) ?><br>
                <?php endif; ?>
                <?php if(!empty($soutenance['Id'])): ?>
                    <a href='index.php?action=modifierSoutenanceRestreinte&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=STAGE&anneeDebut=<?= urlencode($_GET['anneeDebut'] ?? date('Y')) ?>&jourPlanning=<?= urlencode($jourPlanning) ?>'>Modifier</a> |
                    <a href='index.php?action=supprimerSoutenance&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=STAGE' onclick="return confirm('Supprimer ?');">Supprimer</a>
                <?php endif; ?>
            </div>
        <?php endif; endforeach; ?>
    </td>
    <?php endforeach; ?>
    </tr>
<?php endfor; ?>
</tbody>
</table></div></div>

<h2>Planning Anglais (20 min) - <?= htmlspecialchars($displayJour) ?></h2>
<div class="table-card"><div class="table-responsive">
<table class="table table-striped table-bordered align-middle mb-0">
<thead>
<tr>
    <th>Heure</th>
    <?php foreach($salles as $salle): ?>
        <th><?= htmlspecialchars($salle['IdSalle'] ?? '') ?> : <?= htmlspecialchars($salle['description'] ?? '') ?></th>
    <?php endforeach; ?>
</tr>
</thead>
<tbody>
<?php
$start = new DateTime('08:00');
$end = new DateTime('17:40');
$interval = new DateInterval('PT20M');
$period = new DatePeriod($start, $interval, (clone $end)->add(new DateInterval('PT1M')));
foreach($period as $heureSlot):
    $heure = $heureSlot->format('H:i'); ?>
<tr>
    <td style='width:60px;'><?= $heure ?></td>
    <?php foreach($salles as $salle): ?>
    <td style='position:relative; height:50px;'>
        <?php foreach($soutenancesJour as $soutenance):
            if(($soutenance['type'] ?? '') !== 'ANGLAIS') continue;
            $dateStr = $soutenance['date'] ?? $soutenance['dateS'] ?? $soutenance['date_h'] ?? null;
            if (!$dateStr) continue;
            $date = new DateTime($dateStr);
            $soutenanceHeure = $date->format('H:i');
            $jourSoutenance = $date->format('Y-m-d');

            // Définitions sûres des IdSalle (évite warnings si clé absente)
            $salleSoutenance = isset($soutenance['IdSalle']) ? trimId($soutenance['IdSalle']) : '';
            $salleLigne      = isset($salle['IdSalle'])      ? trimId($salle['IdSalle'])      : '';

            // obtenir un identifiant unique de la soutenance (plusieurs noms possibles dans le dataset)
            $soutenanceId = null;
            foreach (['IdEvalAnglais','IdEval','Id','IdSoutenance'] as $k) {
                if (!empty($soutenance[$k])) { $soutenanceId = (string)$soutenance[$k]; break; }
            }
            if ($soutenanceHeure === $heure
                && $jourSoutenance === $jourPlanning
                && $salleSoutenance !== ''
                && $salleSoutenance === $salleLigne
                && $soutenanceId !== null
                && !in_array($soutenanceId, $renderedSoutenances, true)
            ):
                $renderedSoutenances[] = $soutenanceId;
        ?>
            <div class='soutenance anglais'>
                <strong>Étudiant:</strong> <?= htmlspecialchars($soutenance['etudiant'] ?? '?') ?><br>
                <?php if(!empty($soutenance['tuteur'])): ?>
                    <strong>Enseignant:</strong> <?= htmlspecialchars($soutenance['tuteur']) ?><br>
                <?php endif; ?>
                <?php if(!empty($soutenance['Id'])): ?>
                    <a href='index.php?action=modifierSoutenanceRestreinte&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=ANGLAIS&anneeDebut=<?= urlencode($_GET['anneeDebut'] ?? date('Y')) ?>&jourPlanning=<?= urlencode($jourPlanning) ?>'>Modifier</a> |
                    <a href='index.php?action=supprimerSoutenance&idSoutenance=<?= urlencode($soutenance['Id']) ?>&type=ANGLAIS' onclick="return confirm('Supprimer ?');">Supprimer</a>
                <?php endif; ?>
            </div>
        <?php endif; endforeach; ?>
    </td>
    <?php endforeach; ?>
</tr>
<?php endforeach; ?>
                </tbody>
                </table></div></div>
</div>
<?php
// s'assurer que les tableaux fournis par le contrôleur existent
$etudiants = $etudiants ?? [];
$soutenances = $soutenances ?? [];
$salles = $salles ?? [];
$enseignants = $enseignants ?? [];

// --- FALLBACK : construire $etudiantsNonCompletes si le contrôleur ne l'a pas fourni ---
if (!isset($etudiantsNonCompletes) || !is_array($etudiantsNonCompletes) || empty($etudiantsNonCompletes)) {
    // 1) si le modèle fournit une fonction pour ça, l'utiliser (plus fiable)
    if (isset($pdo) && function_exists('soutenance_getEtudiantsSansSoutenance')) {
        try {
            $etudiantsNonCompletes = soutenance_getEtudiantsSansSoutenance($pdo, (int)($anneeDebut ?? date('Y')));
        } catch (Throwable $e) {
            $etudiantsNonCompletes = [];
        }
    }

    // 2) sinon reconstruire depuis $etudiants + $soutenances (détecte absence de date ou de salle)
    if (!isset($etudiantsNonCompletes) || !is_array($etudiantsNonCompletes) || empty($etudiantsNonCompletes)) {
        $byStudent = [];
        // indexer soutenances par IdEtudiant pour tests rapides
        foreach ($soutenances as $s) {
            $idE = $s['IdEtudiant'] ?? $s['idEtudiant'] ?? $s['IdEtudiant_Rel'] ?? null;
            if (!$idE) continue;
            $byStudent[(string)$idE][] = $s;
        }

        $etudiantsNonCompletes = [];
        foreach ($etudiants as $etu) {
            $idEtu = $etu['IdEtudiant'] ?? $etu['id'] ?? null;
            if (!$idEtu) continue;

            $souts = $byStudent[(string)$idEtu] ?? [];
            $hasComplete = false;
            foreach ($souts as $s) {
                $date = $s['date'] ?? $s['date_h'] ?? $s['dateS'] ?? null;
                $salle = $s['IdSalle'] ?? null;
                $statut = strtoupper(trim((string)($s['Statut'] ?? $s['StatutSoutenance'] ?? '')));
                if (!empty($date) && !empty($salle) && $statut !== 'SAISIE') {
                    $hasComplete = true;
                    break;
                }
            }
            if (!$hasComplete) {
                // étendre l'objet étudiant avec champs de soutenance s'il en existe (utile pour affichage)
                if (!empty($souts)) {
                    $etu = array_merge($etu, $souts[0]);
                }
                $etudiantsNonCompletes[] = $etu;
            }
        }
    }
}

// helper utilitaire pour normaliser un IdSalle en string trimée
if (!function_exists('trimId')) {
    function trimId($v): string {
        return $v === null ? '' : trim((string)$v);
    }
}

// <<< AJOUT — initialiser le tableau des soutenances déjà rendues pour éviter warnings/fatal
$renderedSoutenances = [];

// supprimer panneau DEBUG et commentaires précédents (remplacé par collecte DB explicite)
try {
    if (isset($pdo)) {
        // récupérer EvalAnglais incomplets (dateS null/empty OR IdSalle null/empty OR Statut = 'SAISIE')
        $stmtAng = $pdo->prepare("
            SELECT ea.*, e.IdEtudiant, e.nom, e.prenom
            FROM EvalAnglais ea
            JOIN EtudiantsBUT2ou3 e ON e.IdEtudiant = ea.IdEtudiant
            WHERE ea.anneeDebut = ?
              AND (ea.IdSalle IS NULL OR ea.IdSalle = '' OR ea.dateS IS NULL OR ea.dateS = '' OR ea.Statut = 'SAISIE')
        ");
        $stmtAng->execute([ (int)($anneeDebut ?? date('Y')) ]);
        $anglaisRows = $stmtAng->fetchAll(PDO::FETCH_ASSOC);

        // merge sans dupliquer (par IdEtudiant) dans $etudiantsNonCompletes
        $existingIds = [];
        foreach ($etudiantsNonCompletes as $e) {
            $existingIds[(string)($e['IdEtudiant'] ?? $e['id'] ?? '')] = true;
        }
        foreach ($anglaisRows as $r) {
            $idE = (string)($r['IdEtudiant'] ?? '');
            if ($idE === '' || isset($existingIds[$idE])) continue;
            // normaliser structure comme attendu par la vue (garde champs anglais)
            $et = [
                'IdEtudiant' => $r['IdEtudiant'],
                'nom' => $r['nom'] ?? '',
                'prenom' => $r['prenom'] ?? '',
                // inclure champs EvalAnglais utiles pour affichage/links
                'IdEvalAnglais' => $r['IdEvalAnglais'] ?? $r['Id'] ?? null,
                'IdEnseignant' => $r['IdEnseignant'] ?? null,
                'IdSalle' => $r['IdSalle'] ?? null,
                'dateS' => $r['dateS'] ?? null,
                'StatutSoutenance' => $r['Statut'] ?? ($r['StatutSoutenance'] ?? null)
            ];
            $etudiantsNonCompletes[] = $et;
            $existingIds[$idE] = true;
        }
    }
} catch (Throwable $e) {
    // en debug, log si besoin : error_get_last() ou file_put_contents(...) ; ici on ignore silencieusement
}

// Construire une liste unique de salles indexée par IdSalle (trim + string) et remplacer $salles
$uniqueSalles = [];
foreach ($salles as $s) {
    $key = isset($s['IdSalle']) ? trim((string)$s['IdSalle']) : '';
    if ($key === '') continue;            // ignorer IdSalle vides
    if (!isset($uniqueSalles[$key])) $uniqueSalles[$key] = $s;
}
$salles = array_values($uniqueSalles); // ré-écrit $salles pour que l'entête et le corps utilisent la même liste

/*
 * ✅ Récupération des étudiants "non complétés" via le modèle Etudiant
 */
require_once __DIR__ . '/../../models/Etudiant.php';
// supporte soit une classe Etudiant avec méthode, soit une fonction procedural
$etudiantsNonCompletes = [];
if (class_exists('Etudiant')) {
    try {
        $etudiantModel = new Etudiant();
        if (method_exists($etudiantModel, 'getEtudiantsSansSoutenance')) {
            $etudiantsNonCompletes = $etudiantModel->getEtudiantsSansSoutenance();
        }
    } catch (Throwable $e) {
        $etudiantsNonCompletes = $etudiantsNonCompletes ?? [];
    }
} elseif (function_exists('getEtudiantsSansSoutenance')) {
    // si le modèle expose une fonction globale
    $etudiantsNonCompletes = getEtudiantsSansSoutenance($pdo ?? null);
}
// helpers
function getStage($studentId, array $soutenances) {
    foreach ($soutenances as $s) {
        if (($s['type'] ?? '') === 'STAGE' && ((int)($s['IdEtudiant'] ?? $s['Id'] ?? 0) === (int)$studentId)) return $s;
    }
    return null;
}
function getAnglais($studentId, array $soutenances) {
    foreach ($soutenances as $s) {
        if (($s['type'] ?? '') === 'ANGLAIS' && ((int)($s['IdEtudiant'] ?? $s['Id'] ?? 0) === (int)$studentId)) return $s;
    }
    return null;
}

// helper to check if a student is en BUT3 for the given anneeDebut
function isBut3($pdo, $idEtudiant, $anneeDebut) {
    if (empty($pdo) || empty($idEtudiant) || empty($anneeDebut)) return false;
    try {
        $stmt = $pdo->prepare('SELECT but3sinon2 FROM AnneeStage WHERE IdEtudiant = ? AND anneeDebut = ? LIMIT 1');
        $stmt->execute([$idEtudiant, $anneeDebut]);
        $val = $stmt->fetchColumn();
        return $val !== false && intval($val) === 1;
    } catch (Throwable $e) {
        return false;
    }
}

// helper to check if a student has a stage record for the given year
function hasStageThisYear(PDO $pdo, $idEtudiant, $anneeDebut): bool {
    if (empty($pdo) || empty($idEtudiant) || empty($anneeDebut)) return false;
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM AnneeStage WHERE IdEtudiant = ? AND anneeDebut = ? LIMIT 1');
        $stmt->execute([$idEtudiant, $anneeDebut]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

// Présence d'une ligne dans EvalStage (peu importe statut)
function hasEvalStageRow(PDO $pdo, $idEtudiant, $anneeDebut): bool {
    if (empty($pdo) || empty($idEtudiant) || empty($anneeDebut)) return false;
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM EvalStage WHERE IdEtudiant = ? AND anneeDebut = ? LIMIT 1');
        $stmt->execute([$idEtudiant, $anneeDebut]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

// Présence d'une ligne dans EvalAnglais (peu importe statut)
function hasEvalAnglaisRow(PDO $pdo, $idEtudiant, $anneeDebut): bool {
    if (empty($pdo) || empty($idEtudiant) || empty($anneeDebut)) return false;
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM EvalAnglais WHERE IdEtudiant = ? AND anneeDebut = ? LIMIT 1');
        $stmt->execute([$idEtudiant, $anneeDebut]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$elevesInfos = [];
foreach ($etudiants as $etudiant) {
    $idE = $etudiant['IdEtudiant'] ?? $etudiant['id'] ?? null;
    $stage = getStage($idE, $soutenances);
    $anglais = getAnglais($idE, $soutenances);
    $elevesInfos[] = [
        'data' => $etudiant,
        'stage' => $stage,
        'anglais' => $anglais
    ];
}
?>
<!-- Nouveau tableau : étudiants sans salle et date de soutenance (à affecter — stage) -->
<h2>Étudiants sans salle et date de soutenance (à affecter — stage)</h2>
<div class="table-card"><div class="table-responsive">
<table class="table table-striped table-bordered align-middle mb-0">
  <thead>
    <tr>
      <th>Nom</th>
      <th>Prénom</th>
      <th>Tuteur / Professeur</th>
      <th>Second encadrant</th>
      <th>Salle</th>
      <th>Statut Soutenance</th>
    </tr>
  </thead>
  <tbody>
    <?php
    // inclure ici :
    // - les étudiants qui ont tuteur+second mais pas de date/salle (ou statut SAISIE)
    // - les étudiants "anglais only" (seulement prof anglais) qui n'ont pas de date ou de salle (ou statut SAISIE)
    $nonPlanifies = array_filter($etudiantsNonCompletes, function($etu) {
        $hasSalle   = !empty($etu['IdSalle']);
        $hasDate    = !empty($etu['date'] ?? $etu['date_h'] ?? $etu['dateS'] ?? null);
        $hasTuteur  = !empty($etu['IdEnseignantTuteur']) || !empty($etu['tuteur']);
        $hasSecond  = !empty($etu['IdSecondEnseignant']) || !empty($etu['secondEnseignant']);
        $hasEnglish = !empty($etu['IdEnseignant']) || !empty($etu['enseignantAnglais'] ?? null);
        $statut     = strtoupper(trim((string)($etu['StatutSoutenance'] ?? $etu['statut'] ?? '')));
        $hasRval    = !empty($etu['IdRval'] ?? $etu['IdEvalStage'] ?? $etu['IdEvalAnglais'] ?? $etu['IdSoutenance'] ?? $etu['Id'] ?? null);

        // Cas A : anglais-only -> inclure si manque salle/date ou statut SAISIE (on conserve le prof anglais)
        if ($hasEnglish && !$hasTuteur && !$hasSecond) {
            if (!$hasSalle || !$hasDate || $statut === 'SAISIE' || $hasRval) return true;
            return false;
        }

        // Cas B : déjà tuteur+second -> inclure si manque salle/date ou statut SAISIE
        if ($hasTuteur && $hasSecond) {
            if (!$hasSalle || !$hasDate || $statut === 'SAISIE' || $hasRval) {
                return true;
            }
            return false;
        }

        return false;
    });

    // Tri alphabétique des étudiants
    usort($nonPlanifies, function($a, $b) {
        $result = strcasecmp($a['nom'] ?? '', $b['nom'] ?? '');
        if ($result === 0) {
            return strcasecmp($a['prenom'] ?? '', $b['prenom'] ?? '');
        }
        return $result;
    });

    // IDs des étudiants présents dans le tableau du haut (pour bloquer le lien "Stage" en bas)
    $idsNonPlanifies = array_values(array_filter(array_map(function($e) {
        return (string)($e['IdEtudiant'] ?? $e['id'] ?? '');
    }, $nonPlanifies), function($id) {
        return $id !== '';
    }));

    if (empty($nonPlanifies)): ?>
      <tr><td colspan="6">Aucun étudiant correspondant (à affecter) ✅</td></tr>
    <?php else: ?>
      <?php foreach ($nonPlanifies as $etu):
          // Prioriser tuteur de stage ; si absent prendre professeur d'anglais pour l'affichage
          $tuteurId = $etu['IdEnseignantTuteur'] ?? $etu['IdEnseignant'] ?? null;
          $secondId = $etu['IdSecondEnseignant'] ?? null;
          $tuteurNom = '-';
          $secondNom = '-';
          foreach ($enseignants as $enseignant) {
              if (($enseignant['IdEnseignant'] ?? $enseignant['Id']) == $tuteurId) {
                  $tuteurNom = htmlspecialchars(($enseignant['nom'] ?? '') . ' ' . ($enseignant['prenom'] ?? ''));
                  break;
              }
          }
          foreach ($enseignants as $enseignant) {
              if (($enseignant['IdEnseignant'] ?? $enseignant['Id']) == $secondId) {
                  $secondNom = htmlspecialchars(($enseignant['nom'] ?? '') . ' ' . ($enseignant['prenom'] ?? ''));
                  break;
              }
          }
      ?>
        <tr>
          <td><?= htmlspecialchars($etu['nom'] ?? '') ?></td>
          <td><?= htmlspecialchars($etu['prenom'] ?? '') ?></td>
          <td><?= $tuteurNom ?></td>
          <td><?= $secondNom ?></td>
          <td><?= htmlspecialchars($etu['IdSalle'] ?? 'Non affectée') ?></td>
          <td>
            <?= htmlspecialchars($etu['StatutSoutenance'] ?? 'Non commencée') ?>
            <?php
                $idEtu = $etu['IdEtudiant'] ?? $etu['id'] ?? null;
                $typeSoutenance = 'STAGE';
                $idEvalStage = $etu['IdEvalStage'] ?? $etu['IdRval'] ?? $etu['IdEval'] ?? $etu['Id'] ?? '';
                if ($idEtu):
            ?>
                <br>
                <a href="index.php?action=modifierSoutenanceRestreinte&idEtudiant=<?= urlencode($idEtu) ?>&type=<?= urlencode($typeSoutenance) ?>&idSoutenance=<?= urlencode($idEvalStage) ?>&anneeDebut=<?= urlencode($_GET['anneeDebut'] ?? date('Y')) ?>">
                    Modifier horaire / salle
                </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table></div></div>
<!-- Nouveau tableau : étudiants ANGLAIS avec professeur mais SANS date ET SANS salle -->
<h2>Étudiants anglais avec professeur mais sans date et sans salle</h2>
<div class="table-card"><div class="table-responsive">
<table class="table table-striped table-bordered align-middle mb-0">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Professeur d'anglais</th>
            <th>Salle</th>
            <th>Statut</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Récupère explicitement les lignes EvalAnglais avec professeur mais sans date et sans salle
        $rowsAngSans = [];
        try {
                if (isset($pdo)) {
                        $stmt = $pdo->prepare("\n                SELECT ea.*, etu.nom, etu.prenom\n                FROM EvalAnglais ea\n                JOIN EtudiantsBUT2ou3 etu ON etu.IdEtudiant = ea.IdEtudiant\n                WHERE ea.anneeDebut = ?\n                  AND ea.IdEnseignant IS NOT NULL AND ea.IdEnseignant <> ''\n                  AND (ea.dateS IS NULL OR ea.dateS = '')\n                  AND (ea.IdSalle IS NULL OR ea.IdSalle = '')\n            ");
                        $stmt->execute([ (int)($anneeDebut ?? date('Y')) ]);
                        $rowsAngSans = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
        } catch (Throwable $e) {
                $rowsAngSans = [];
        }

        // Tri alphabétique
        usort($rowsAngSans, function($a, $b){
                $r = strcasecmp($a['nom'] ?? '', $b['nom'] ?? '');
                if ($r === 0) $r = strcasecmp($a['prenom'] ?? '', $b['prenom'] ?? '');
                return $r;
        });

        if (empty($rowsAngSans)):
        ?>
            <tr><td colspan="6">Aucun étudiant correspondant (anglais à affecter) ✅</td></tr>
        <?php else:
                foreach ($rowsAngSans as $row):
                        $idEtu = $row['IdEtudiant'] ?? null;
                        $idEvalAng = $row['IdEvalAnglais'] ?? $row['Id'] ?? $row['IdEval'] ?? '';
                        // retrouver le nom de l'enseignant d'anglais
                        $nomProf = '-';
                        $idProf = $row['IdEnseignant'] ?? null;
                        if (!empty($idProf)) {
                                foreach ($enseignants as $ens) {
                                        if (($ens['IdEnseignant'] ?? $ens['Id'] ?? null) == $idProf) {
                                                $nomProf = htmlspecialchars(($ens['nom'] ?? '') . ' ' . ($ens['prenom'] ?? ''));
                                                break;
                                        }
                                }
                        }
        ?>
            <tr>
                <td><?= htmlspecialchars($row['nom'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['prenom'] ?? '') ?></td>
                <td><?= $nomProf ?></td>
                <td><?= htmlspecialchars($row['IdSalle'] ?? 'Non affectée') ?></td>
                <td><?= htmlspecialchars($row['Statut'] ?? $row['StatutSoutenance'] ?? 'Non commencée') ?></td>
                <td>
                    <?php if ($idEtu && $idEvalAng): ?>
                        <a href="index.php?action=modifierSoutenanceRestreinte&idEtudiant=<?= urlencode($idEtu) ?>&type=ANGLAIS&idSoutenance=<?= urlencode($idEvalAng) ?>&anneeDebut=<?= urlencode($_GET['anneeDebut'] ?? date('Y')) ?>&jourPlanning=<?= urlencode($jourPlanning) ?>">Modifier horaire / salle</a>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table></div></div>
<h2>Étudiants sans soutenance planifiée</h2>
<div class="table-card"><div class="table-responsive">
<table class="table table-striped table-bordered align-middle mb-0">
    <tr><th>Nom</th><th>Prénom</th><th>Planifier</th></tr>
    <?php
    // Tri alphabétique des étudiants sans soutenance planifiée
    usort($etudiants, function($a, $b) {
        $result = strcasecmp($a['nom'], $b['nom']);
        if ($result === 0) {
            return strcasecmp($a['prenom'], $b['prenom']);
        }
        return $result;
    });
    ?>
    <?php if (!empty($etudiants)): foreach($etudiants as $etudiant):
        $idE = $etudiant['IdEtudiant'] ?? $etudiant['id'] ?? null;
        $stage = getStage($idE, $soutenances);
        $anglais = getAnglais($idE, $soutenances);
        if (empty($stage) && empty($anglais)): ?>
        <tr>
            <td><?= htmlspecialchars($etudiant['nom']) ?></td>
            <td><?= htmlspecialchars($etudiant['prenom']) ?></td>
            <td>
                <?php
                $hasStage = hasStageThisYear($pdo, $idE, $anneeDebut);
                $blockedByTop = isset($idsNonPlanifies) && in_array((string)$idE, $idsNonPlanifies, true);
                // presence lignes et statut complet
                $hasEvalStage = hasEvalStageRow($pdo, $idE, $anneeDebut);
                $hasEvalAnglais = hasEvalAnglaisRow($pdo, $idE, $anneeDebut);
                ?>
                <?php if (!$hasStage): ?>
                    <button type="button" disabled class="btn-disabled" title="Pas de stage enregistré pour cette année">Stage</button>
                <?php elseif ($blockedByTop): ?>
                    <button type="button" disabled class="btn-disabled" title="Présent dans la liste du haut (à compléter avant planification)">Stage</button>
                <?php elseif ($hasEvalStage): ?>
                    <button type="button" disabled class="btn-disabled" title="Déjà présent dans EvalStage pour cette année">Stage</button>
                <?php else: ?>
                    <a class="btn" href="index.php?action=planifierSoutenance&idEtudiant=<?= urlencode($idE) ?>&anneeDebut=<?= urlencode($anneeDebut) ?>&type=STAGE&jourPlanning=<?= urlencode($jourPlanning) ?>">Stage</a>
                <?php endif; ?>

                <?php if (isBut3($pdo, $idE, $anneeDebut)): ?>
                    |
                    <?php if ($hasEvalAnglais): ?>
                        <button type="button" disabled class="btn-disabled" title="Déjà présent dans EvalAnglais pour cette année">Anglais</button>
                    <?php else: ?>
                        <a href="index.php?action=planifierSoutenance&idEtudiant=<?= urlencode($idE) ?>&anneeDebut=<?= urlencode($anneeDebut) ?>&type=ANGLAIS&jourPlanning=<?= urlencode($jourPlanning) ?>">Anglais</a>
                    <?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; endforeach; else: ?>
        <tr><td colspan="3">Aucun étudiant trouvé.</td></tr>
    <?php endif; ?>
</table></div></div>
 

<script>
// Rafraîchir le planning via AJAX sans recharger toute la page
(function(){
    const form = document.querySelector('form[method="get"]');
    const inputDate = document.querySelector('#jourPlanning');
    const dateLinks = document.querySelectorAll('a.date-nav');
    const container = document.getElementById('planning-container');
    if (!form || !inputDate || !container) return;

    async function refreshPlanning(dateStr){
        try {
            const params = new URLSearchParams(new FormData(form));
            params.set('jourPlanning', dateStr);
            params.set('ajax', 'planning');
            const resp = await fetch('?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const html = await resp.text();
            container.innerHTML = html;
        } catch(e) {
            console.error('Refresh planning failed', e);
        }
    }

    inputDate.addEventListener('change', (e)=>{
        const val = e.target.value;
        if (val) refreshPlanning(val);
    });

    // Intercepter la soumission (Enter) pour éviter reload
    form.addEventListener('submit', (e)=>{
        e.preventDefault();
        const val = inputDate.value;
        if (val) refreshPlanning(val);
    });

    function fmt(d){
        const y = d.getFullYear();
        const m = String(d.getMonth()+1).padStart(2,'0');
        const day = String(d.getDate()).padStart(2,'0');
        return `${y}-${m}-${day}`;
    }

    // Clics sur les liens de navigation (précédent/suivant/aujourd'hui)
    dateLinks.forEach(a => {
        a.addEventListener('click', (e) => {
            e.preventDefault();
            const dir = a.getAttribute('data-dir');
            let base = inputDate.value ? new Date(inputDate.value) : new Date();
            if (isNaN(base.getTime())) base = new Date();
            if (dir === 'prev') base.setDate(base.getDate() - 1);
            else if (dir === 'next') base.setDate(base.getDate() + 1);
            else if (dir === 'today') base = new Date();
            const newVal = fmt(base);
            inputDate.value = newVal;
            refreshPlanning(newVal);
        });
    });
})();
</script>

    </div>


</body>
</html>
