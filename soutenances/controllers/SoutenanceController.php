<?php
require_once __DIR__ . '/../models/Soutenance.php';
require_once __DIR__ . '/../models/Etudiant.php';
require_once __DIR__ . '/../models/Enseignant.php';
require_once __DIR__ . '/../models/Salle.php';

/**
 * Liste tous les étudiants et soutenances pour une année donnée.
 */
function soutenance_lister(PDO $pdo) {
    $anneeDebut = $_GET['anneeDebut'] ?? scolarite_annee_debut();
    $anneeDebut = (int)$anneeDebut;
    $etudiants = getAllEtudiants($pdo);
    $etudiantsNonCompletes = getEtudiantsSansSoutenance($pdo, $anneeDebut);
    $soutenances = soutenance_getSoutenancesPlanifiees($pdo, $anneeDebut);
    $salles = getAllSalles($pdo);
    $enseignants = getAllEnseignants($pdo);
    require __DIR__ . '/../views/Soutenance/ListeEtudiants.php';
}

/**
 * Affiche le formulaire pour planifier une nouvelle soutenance.
 */
function soutenance_planifier_form(PDO $pdo) {
    $idEtudiant = isset($_GET['idEtudiant']) ? (int)$_GET['idEtudiant'] : null;
    $type = $_GET['type'] ?? 'STAGE';
    $anneeDebut = (int)($_GET['anneeDebut'] ?? scolarite_annee_debut());
    $salles = getAllSalles($pdo);
    $enseignants = getAllEnseignants($pdo);
    $soutenances = soutenance_getSoutenancesPlanifiees($pdo, $anneeDebut);
    $soutenanceModifiee = null;
    require __DIR__ . '/../views/Soutenance/planifSoutenance.php';
}

/**
 * Affiche le formulaire pour modifier une soutenance existante.
 */
function soutenance_modifier_form(PDO $pdo) {
    $id = isset($_GET['idSoutenance']) ? (int)$_GET['idSoutenance'] : null;
    $type = $_GET['type'] ?? 'STAGE';
    if (!$id) {
        header('Location: index.php?action=listerSoutenances');
        exit;
    }
    $salles = getAllSalles($pdo);
    $enseignants = getAllEnseignants($pdo);
    $soutenanceModifiee = soutenance_getSoutenanceById($pdo, $id, $type);
    $anneeDebut = (int)($_GET['anneeDebut'] ?? ($soutenanceModifiee['anneeDebut'] ?? scolarite_annee_debut()));
    $idEtudiant = $soutenanceModifiee['IdEtudiant'] ?? null;
    $soutenances = soutenance_getSoutenancesPlanifiees($pdo, $anneeDebut);
    require __DIR__ . '/../views/Soutenance/planifSoutenance.php';
}

/**
 * Affiche le formulaire pour modifier uniquement la salle et l'heure d'une soutenance.
 */
function soutenance_modifier_restreint_form(PDO $pdo) {
    $id = isset($_GET['idSoutenance']) ? (int)$_GET['idSoutenance'] : null;
    $type = $_GET['type'] ?? 'STAGE';
    if (!$id) {
        header('Location: index.php?action=listerSoutenances');
        exit;
    }
    $salles = getAllSalles($pdo);
    $soutenanceModifiee = soutenance_getSoutenanceById($pdo, $id, $type);
    $anneeDebut = (int)($_GET['anneeDebut'] ?? ($soutenanceModifiee['anneeDebut'] ?? scolarite_annee_debut()));
    require __DIR__ . '/../views/Soutenance/modifierSoutenanceRestreinte.php';
}

/**
 * Enregistre la modification restreinte (salle et heure) d'une soutenance.
 */
/**
 * Enregistre la modification restreinte (salle et heure) d'une soutenance.
 */
function enregistrerModificationRestreinte(PDO $pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?action=listerSoutenances');
        exit;
    }

    $data = $_POST;
    $IdSoutenance = isset($data['IdSoutenance']) ? (int)$data['IdSoutenance'] : null;
    $IdEtudiant = $data['IdEtudiant'] ?? null;
    $type = $data['type'] ?? 'STAGE';
    $date_h = $data['date_h'] ?? null;
    // IdSalle peut être alphanumérique (ex: AmphiB1) -> ne pas caster en int
    $IdSalle = isset($data['IdSalle']) ? (string)$data['IdSalle'] : '';
    $anneeDebut = $data['anneeDebut'] ?? null;
    $jourPlanning = $data['jourPlanning'] ?? null;

    // Vérifier si la nouvelle combinaison date_h + IdSalle est déjà utilisée par une autre soutenance
    $conflitExiste = soutenance_estCreneauLibre(
        $pdo,
        $type,
        $date_h,
        $IdSalle,
        null,
        null,
        $IdSoutenance
    );

    if (!$conflitExiste) {
        // Rediriger avec une erreur, en conservant le jourPlanning si fourni
        $qs = 'action=modifierSoutenanceRestreinte&error=duplicate&idSoutenance=' . urlencode((string)$IdSoutenance) . '&type=' . urlencode((string)$type);
        if (!empty($anneeDebut)) $qs .= '&anneeDebut=' . urlencode((string)$anneeDebut);
        if (!empty($jourPlanning)) $qs .= '&jourPlanning=' . urlencode((string)$jourPlanning);
        header('Location: index.php?' . $qs);
        exit;
    }

    // Mettre à jour la date/heure et la salle (et la présence du maître de stage pour STAGE)
    if ($type === 'STAGE') {
        $sql = "UPDATE EvalStage SET date_h = ?, IdSalle = ?, presenceMaitreStageApp = ? WHERE IdEvalStage = ?";
    } else {
        $sql = "UPDATE EvalAnglais SET dateS = ?, IdSalle = ? WHERE IdEvalAnglais = ?";
    }

    try {
        $stmt = $pdo->prepare($sql);
        if ($type === 'STAGE') {
            $presence = (isset($data['presenceMaitreStageApp']) && (string)$data['presenceMaitreStageApp'] === '1') ? 1 : 0;
            $stmt->execute([$date_h, $IdSalle, $presence, $IdSoutenance]);
        } else {
            $stmt->execute([$date_h, $IdSalle, $IdSoutenance]);
        }
    } catch (Throwable $e) {
        // Affiche une page d'erreur explicite avec le message SQL/trigger comme pour les ajouts
        error_log("Erreur lors de la mise à jour de la soutenance: " . $e->getMessage());
        $raw = $e->getMessage();
        if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
            $errorMessage = trim($m[1]);
        } else {
            $errorMessage = $raw;
        }
        $backAction = 'listerSoutenances';
        require __DIR__ . '/../views/Error/constraint.php';
        return;
    }

    header('Location: index.php?action=listerSoutenances&success=1');
    exit;
}




/**
 * Enregistre ou modifie une soutenance.
 */
function soutenance_enregistrer(PDO $pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: index.php?action=listerSoutenances');
        exit;
    }
    $data = $_POST;
    try {
        if (empty($data['IdEtudiant'])) {
            throw new Exception('IdEtudiant manquant');
        }
        $type = $data['type'] ?? 'STAGE';
        $creneauLibre = soutenance_estCreneauLibre(
            $pdo,
            $type,
            $data['date_h'],
            (int)$data['IdSalle'],
            isset($data['IdEnseignant']) ? (int)$data['IdEnseignant'] : (isset($data['IdTuteur']) ? (int)$data['IdTuteur'] : null),
            isset($data['IdSecond']) ? (int)$data['IdSecond'] : null,
            isset($data['IdSoutenance']) ? (int)$data['IdSoutenance'] : null
        );
        if (!$creneauLibre) {
            throw new Exception('Conflit de planning détecté');
        }
        if (!empty($data['IdSoutenance'])) {
            soutenance_modifier($pdo, (int)$data['IdSoutenance'], $type, $data);
        } else {
            soutenance_planifier($pdo, $data);
        }
        $annee = !empty($data['anneeDebut']) ? (int)$data['anneeDebut'] : scolarite_annee_debut();
        header('Location: index.php?action=listerSoutenances&anneeDebut=' . $annee . '&success=1');
        exit;
    } catch (Throwable $e) {
        $annee = !empty($data['anneeDebut']) ? (int)$data['anneeDebut'] : scolarite_annee_debut();
        header('Location: index.php?action=listerSoutenances&anneeDebut=' . $annee . '&error=' . urlencode($e->getMessage()));
        exit;
    }
}

/**
 * Supprime une soutenance.
 */
function soutenance_supprimer_action(PDO $pdo) {
    $id = $_GET['idSoutenance'] ?? $_POST['IdSoutenance'] ?? null;
    $type = $_GET['type'] ?? $_POST['type'] ?? 'STAGE';
    if (!$id) {
        header('Location: index.php?action=listerSoutenances');
        exit;
    }
    soutenance_supprimer($pdo, (int)$id, $type);
    header('Location: index.php?action=listerSoutenances');
    exit;
}
