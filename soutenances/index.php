<?php
/**
 * Routeur principal de l'application.
 * - Initialise la session et la connexion PDO
 * - Protège certaines routes via AuthController
 * - Dispatch vers les contrôleurs/vues selon l'action
 */
session_start();

require_once(__DIR__ . '/config/database.php');
require_once(__DIR__ . '/controllers/EnseignantController.php');
require_once(__DIR__ . '/controllers/EtudiantController.php');
require_once(__DIR__ . '/controllers/SalleController.php');
require_once(__DIR__ . '/controllers/StageController.php');
require_once(__DIR__ . '/controllers/EntrepriseController.php');
require_once(__DIR__ . '/controllers/ProfStageController.php');
require_once(__DIR__ . '/controllers/ProfAnglaisStageController.php');
require_once(__DIR__ . '/controllers/SoutenanceController.php');
require_once(__DIR__ . '/controllers/GridController.php');
require_once(__DIR__ . '/controllers/TachesController.php');
require_once(__DIR__ . '/controllers/EvalStageController.php');

// Connexion PDO (procédural) et action courante
$pdo = db_get_connection();
$action = $_GET['action'] ?? 'index';

// Actions protégées nécessitant une authentification
$protected_actions = ['backoffice-alertes', 'backoffice-analytics', 'search-students', 'fiche-notes', 'taches', 'listeRemontee', 'confirmSelection', 'validerSelection', 'Evaluation', 'remontee', 'remontee-notes'];

if (in_array($action, $protected_actions)) {
    require_once(__DIR__ . '/controllers/AuthController.php');
    auth_checkAccess($pdo);
}

// Dispatch selon l'action
switch ($action) {
    // ===== AUTHENTIFICATION =====
    case 'login':
        require_once(__DIR__ . '/controllers/AuthController.php');
        auth_showLogin($pdo);
        break;
        
    case 'login-process':
        require_once(__DIR__ . '/controllers/AuthController.php');
        auth_processLogin($pdo);
        break;
        
    case 'logout':
        require_once(__DIR__ . '/controllers/AuthController.php');
        auth_logout($pdo);
        break;
        
    // ===== BACKOFFICE =====
    case 'backoffice-alertes':
        require_once(__DIR__ . '/controllers/BackOfficeController.php');
        backoffice_showAlertes($pdo);
        break;
    
    case 'backoffice-analytics':
        require_once(__DIR__ . '/controllers/BackOfficeController.php');
        backoffice_showAnalytics($pdo);
        break;
    
    case 'search-students':
        require_once(__DIR__ . '/controllers/BackOfficeController.php');
        backoffice_searchStudents($pdo);
        break;
    
    case 'fiche-notes':
        showFicheNotes($pdo);
        break;

    // ===== DIFFUSION (interne) =====
    case 'listeRemontee':
        require_once __DIR__ . '/controllers/EvalStageController.php';
        evalstage_listeEtudiantsRemontee($pdo);
        break;

    case 'confirmSelection':
        require_once __DIR__ . '/controllers/EvalStageController.php';
        evalstage_confirmSelection($pdo);
        break;

    case 'validerSelection':
        require_once __DIR__ . '/controllers/EvalStageController.php';
        evalstage_validerSelection($pdo);
        break;

    case 'etudiant':
        require_once __DIR__ . '/controllers/EtudiantController.php';
        etudiant_showByEmpreinte($pdo);
        break;
        
    // ===== SOUTENANCES =====
    case 'listerSoutenances':
        soutenance_lister($pdo);
        break;
    case 'planifierSoutenance':
        soutenance_planifier_form($pdo);
        break;
    
    case 'taches':
        taches_index($pdo);
        break;
    case 'enregistrerSoutenance':
        soutenance_enregistrer($pdo);
        break;
    case 'modifierSoutenance':
        soutenance_modifier_form($pdo);
        break;
    case 'modifierSoutenanceRestreinte':
        soutenance_modifier_restreint_form($pdo);
        break;
    case 'supprimerSoutenance':
        soutenance_supprimer_action($pdo);
        break;
    case 'enregistrerModificationRestreinte':
        enregistrerModificationRestreinte($pdo);
        break;
        
    // ===== ENSEIGNANTS =====
    case 'addEnseignant':
        addEnseignant($pdo);
        break;
    case 'listEnseignant':
    $enseignants = getAllEnseignants($pdo);
    require_once(__DIR__ . '/views/Enseignant/list.php');
        break;
    case 'destroyEnseignant':
        destroyEnseignant($pdo);
        break;
    case 'editEnseignant':
        editEnseignant($pdo);
        break;
    case 'viewEnseignant':
    $enseignants = getAllEnseignants($pdo);
    require_once(__DIR__ . '/views/Enseignant/view.php');
        break;
    case 'listStageTuteur':
        $id = $_GET['id'] ?? null;
        if ($id === null) { header('Location: index.php?action=viewEnseignant'); exit; }
        $enseignant = getEnseignantById($pdo, $id);
        $StageTuteur = getStagesByEnseignantTuteurId($pdo, $id);
    require_once(__DIR__ . '/views/Enseignant/StagesTuteur.php');
        break;
    case 'listStageSecondaire':
        $id = $_GET['id'] ?? null;
        if ($id === null) { header('Location: index.php?action=viewEnseignant'); exit; }
        $enseignant = getEnseignantById($pdo, $id);
        $StageSecondaire = getStagesByEnseignantSecondaireId($pdo, $id);
    require_once(__DIR__ . '/views/Enseignant/StagesSecondaire.php');
        break;
    case 'listStageAnglais':
        $id = $_GET['id'] ?? null;
        if ($id === null) { header('Location: index.php?action=viewEnseignant'); exit; }
        $enseignant = getEnseignantById($pdo, $id);
        $StageAnglais = getStagesByEnseignantAnglaisId($pdo, $id);
    require_once(__DIR__ . '/views/Enseignant/StagesAnglais.php');
        break;
        
    // ===== SALLES =====
    case 'addSalle':
        addSalle($pdo);
        break;
    case 'listSalle':
    $salles = getAllSalles($pdo);
    require_once(__DIR__ . '/views/Salle/list.php');
        break;
    case 'destroySalle':
        destroySalle($pdo);
        break;
    case 'editSalle':
        editSalle($pdo);
        break;
        
    // ===== ENTREPRISES =====
    case 'addEntreprise':
        addEntreprise($pdo);
        break;
    case 'listEntreprise':
    $entreprises = getAllEntreprise($pdo);
    require_once(__DIR__ . '/views/Entreprise/list.php');
        break;
    case 'destroyEntreprise':
        destroyEntreprise($pdo);
        break;
    case 'editEntreprise':
        editEntreprise($pdo);
        break;
        
    // ===== ETUDIANTS =====
    case 'addEtudiant':
        addEtudiant($pdo);
        break;
    case 'listEtudiant':
    $etudiants = getAllEtudiants($pdo);
    require_once(__DIR__ . '/views/Etudiant/list.php');
        break;
    case 'destroyEtudiant':
        destroyEtudiant($pdo);
        break;  
    case 'editEtudiant':
        editEtudiant($pdo);
        break;
        
    // ===== STAGES =====
    case 'addStage':
        $entreprises = getAllEntreprise($pdo);
        addStageEtudiant($pdo);
        break;
    case 'listStage':
    $etudiants = getAllEtudiantsWithoutStage($pdo);
    require_once(__DIR__ . '/views/Stage/list.php');
        break;
    case 'addEnseignantStage':
        $entreprises = getAllEntreprise($pdo);
        $enseignants = getAllEnseignants($pdo);
        addEnseignantStage($pdo);
        break;
    case 'listEnseignantStage':
    $etudiants = getAllEtudiantsWithStageAndNoEnseignant($pdo);
    require_once(__DIR__ . '/views/ProfStage/list.php');
        break;
    case 'addEnseignantAnglais':
        $entreprises = getAllEntreprise($pdo);
        $enseignants = getAllEnseignants($pdo);
        addEnseignantAnglais($pdo);
        break;
    case 'listEnseignantAnglais':
    $etudiants = getAllEtudiantsWithStageAndNoEnseignantAnglais($pdo);
    require_once(__DIR__ . '/views/ProfAnglaisStage/list.php');
        break;
        
    // ===== GRILLES =====
    case 'viewEnseignant':
    $enseignants = getAllEnseignants($pdo);
    require_once(__DIR__ . '/views/Enseignant/view.php');
        break;
    case 'listStageTuteur':
        $id = $_GET['id'] ?? null;
        if ($id === null) { header('Location: index.php?action=viewEnseignant'); exit; }
        $enseignant = getEnseignantById($pdo, $id);
        $StageTuteur = getStagesByEnseignantTuteurId($pdo, $id);
    require_once(__DIR__ . '/views/Enseignant/StagesTuteur.php');
        break;
    case 'listStageSecondaire':
        $id = $_GET['id'] ?? null;
        if ($id === null) { header('Location: index.php?action=viewEnseignant'); exit; }
        $enseignant = getEnseignantById($pdo, $id);
        $StageSecondaire = getStagesByEnseignantSecondaireId($pdo, $id);
    require_once(__DIR__ . '/views/Enseignant/StagesSecondaire.php');
        break;
    case 'listStageAnglais':
        $id = $_GET['id'] ?? null;
        if ($id === null) { header('Location: index.php?action=viewEnseignant'); exit; }
        $enseignant = getEnseignantById($pdo, $id);
        $StageAnglais = getStagesByEnseignantAnglaisId($pdo, $id);
    require_once(__DIR__ . '/views/Enseignant/StagesAnglais.php');
        break;
    case 'grids':
        indexGrid($pdo);
        break;
    case 'consulter':
        consulterGrid($pdo, $_GET['id']);
        break;
    case 'tester':
        testerGrid($pdo, $_GET['id']);
        break;
    case 'add':
        ajouterGrid($pdo);
        break;
    case 'store':
        storeGrid($pdo);
        break;
    case 'copy':
        copyGrid($pdo);
        break;
    case 'modify':
        modifyGrid($pdo, $_GET['id']);
        break;
    case 'update':
        updateGrid($pdo);
        break;
    case 'delete':
        deleteGridController($pdo);
        break;
        
    // ===== ACCUEIL =====
    case 'accueil':
        require_once(__DIR__ . '/controllers/AccueilController.php');
        showAccueilAccueil($pdo);
        break;

    case 'Evaluation':
        require_once(__DIR__ . '/controllers/AuthController.php');
        auth_processEvaluation($pdo);
        break;

    // ===== REMONTEE (grilles) =====
    case 'remontee':
        // Version procédurale
        require_once(__DIR__ . '/controllers/RemonteController.php');
        require_once(__DIR__ . '/models/RemonteModels.php');

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['toggle_event'])) {
                $enable = $_POST['toggle_event'] === '1';
                $message = remonte_toggleEvent($pdo, $enable);
            }
            if (isset($_POST['toggle_trigger'])) {
                $enable = $_POST['toggle_trigger'] === '1';
                $message = remonte_toggleTrigger($pdo, $enable);
            }
        }

        // Etats pour la vue
        $eventEnabled = remonte_isEventEnabled($pdo);
        $triggersEnabled = remonte_areTriggersEnabled($pdo);

        require_once(__DIR__ . '/views/Remontee/RemonteViews.php');
        break;

    // ===== REMONTEE NOTES (mini-MVC local) =====
    case 'remontee-notes':
        // Dispatch procédural local (TotalController converti en fonctions total_*)
        $route = isset($_GET['r']) ? $_GET['r'] : 'total/index';
        [$controllerName, $action] = array_pad(explode('/', $route), 2, 'index');
        $controllerFile = __DIR__ . '/controllers/' . ucfirst($controllerName) . 'Controller.php';
        if (is_file($controllerFile)) {
            require_once $controllerFile;
        }

        // Pour 'total/index' appeler total_index($pdo)
        if ($controllerName === 'total' && $action === 'index' && function_exists('total_index')) {
            total_index($pdo);
            break;
        }

        http_response_code(404);
        echo 'Route introuvable';
        break;
        
    default:
        // Fallback : router vers accueil (protégé) ou login
        require_once(__DIR__ . '/controllers/AccueilController.php');
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            showAccueilAccueil($pdo);
        } else {
            header('Location: index.php?action=login');
        }
        break;
}
?>
