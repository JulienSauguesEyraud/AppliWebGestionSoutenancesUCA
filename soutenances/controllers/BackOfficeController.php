<?php
require_once __DIR__ . '/../models/Auth.php';

function backoffice_showAlertes($db) {
    if (!auth_model_isLoggedIn() || $_SESSION['user_info']['type'] !== 'backoffice') {
        header('Location: index.php?action=login'); exit(); }
    $alertes = backoffice_getAlertesSoutenances($db);
    $pageTitle = "Alertes - Back Office"; ob_start();
    require_once dirname(__DIR__) . '/views/backoffice/alertes.php';
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/views/layouts/main.php';
}

function backoffice_getAlertesSoutenances($db) {
    $alertes = [];
    try {
        $query = "
                SELECT 
                    es.IdEvalSoutenance,
                    e.nom AS etudiant_nom, 
                    e.prenom AS etudiant_prenom,
                    mge.nomModuleGrilleEvaluation,
                    es.Statut,
                    'Il manque une note de rapport et/ou une note de soutenance' AS message
                FROM EvalSoutenance es
                JOIN EtudiantsBUT2ou3 e ON es.IdEtudiant = e.IdEtudiant
                JOIN ModelesGrilleEval mge ON es.IdModeleEval = mge.IdModeleEval
                WHERE es.Statut != 'VALIDEE'
                AND (es.note IS NULL OR es.note = 0)
            ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("Erreur de base de données: ".$e->getMessage()); }
    return $alertes;
}

function backoffice_showAnalytics($db) {
    if (!auth_model_isLoggedIn() || $_SESSION['user_info']['type'] !== 'backoffice') { header('Location: index.php?action=login'); exit(); }
    $repartitionRegion = backoffice_getRepartitionRegion($db);
    $pageTitle = "Analyses - Back Office"; ob_start();
    require_once dirname(__DIR__) . '/views/backoffice/analytics.php';
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/views/layouts/main.php';
}

function backoffice_getRepartitionRegion($db) {
    $repartition = [];
    try {
        $query = "
                SELECT 
                    e.villeE as region,
                    COUNT(DISTINCT asg.IdEtudiant) as nombre_stages,
                    asg.anneeDebut as annee
                FROM AnneeStage asg
                JOIN Entreprises e ON asg.IdEntreprise = e.IdEntreprise
                GROUP BY e.villeE, asg.anneeDebut
                ORDER BY asg.anneeDebut DESC, nombre_stages DESC
            ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            $annee = $row['annee']; $region = $row['region'];
            if (!isset($repartition[$annee])) { $repartition[$annee] = []; }
            $repartition[$annee][$region] = $row['nombre_stages'];
        }
    } catch (PDOException $e) { error_log("Erreur de base de données: ".$e->getMessage()); }
    return $repartition;
}

function backoffice_searchStudents($db) {
    if (!auth_model_isLoggedIn()) { header('Location: index.php?action=login'); exit(); }
    $searchResults = []; $searchTerm = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
        $searchTerm = trim($_POST['search']);
        if (!empty($searchTerm)) { $searchResults = backoffice_performStudentSearch($db, $searchTerm); }
    }
    $pageTitle = "Recherche d'étudiants"; ob_start();
    require_once dirname(__DIR__) . '/views/backoffice/search_students.php';
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/views/layouts/main.php';
}

function backoffice_performStudentSearch($db, $searchTerm) {
    $results = [];
    try {
        $query = "
                SELECT 
                    e.IdEtudiant,
                    e.nom,
                    e.prenom,
                    e.mail,
                    mge.nomModuleGrilleEvaluation,
                    es.Statut,
                    es.note
                FROM EtudiantsBUT2ou3 e
                LEFT JOIN EvalSoutenance es ON e.IdEtudiant = es.IdEtudiant
                LEFT JOIN ModelesGrilleEval mge ON es.IdModeleEval = mge.IdModeleEval
                WHERE e.nom LIKE :search OR e.prenom LIKE :search
                ORDER BY e.nom, e.prenom
            ";
        $stmt = $db->prepare($query);
        $stmt->execute(['search' => '%' . $searchTerm . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("Erreur de base de données: ".$e->getMessage()); }
    return $results;
}
?>
