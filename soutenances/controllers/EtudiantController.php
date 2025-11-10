<?php
require_once(__DIR__ . '/../models/Etudiant.php');
require_once(__DIR__ . '/../models/Auth.php');

// ================== FICHE DE NOTES / CONSULTATION ==================
function showFicheNotes($pdo) {
    if (!auth_model_isLoggedIn()) {
        header('Location: index.php?action=login');
        exit();
    }
    $etudiantId = $_GET['id'] ?? null;
    if (!$etudiantId) { header('Location: index.php?action=listEtudiant'); exit; }
    $etudiant = getEtudiantInfo($pdo, $etudiantId);
    if (!$etudiant) { header('Location: index.php?action=listEtudiant'); exit; }
    $notes = getAllNotes($pdo, $etudiantId);
    $moyennes = calculateAverages($notes);
    require_once(__DIR__ . '/../views/Etudiant/fiche_notes.php');
}

// ================== CONSULTATION PAR EMPREINTE (Lien email étudiant) ==================
function etudiant_showByEmpreinte($pdo) {
    // Lien public: pas d'auth requise. Empreinte obligatoire.
    $empreinte = $_GET['empreinte'] ?? '';
    if ($empreinte === '') {
        http_response_code(400);
        echo "Empreinte manquante.";
        return;
    }

    // Récupérer l'étudiant via l'empreinte
    $etu = etudiant_getByEmpreinte($pdo, $empreinte);
    if (!$etu) {
        http_response_code(404);
        echo "Lien invalide ou expiré.";
        return;
    }

    // Charger les notes puis ne garder que celles DIFFUSEE
    $etudiantId = (int)$etu['IdEtudiant'];
    $notesAll = getAllNotes($pdo, $etudiantId);
    $notes = [];
    foreach ($notesAll as $type => $evaluations) {
        $notes[$type] = array_values(array_filter($evaluations, function($e){
            return isset($e['Statut']) && $e['Statut'] === 'DIFFUSEE';
        }));
    }

    // Calcul des moyennes uniquement sur les notes retenues
    $moyennes = calculateAverages($notes);

    // Exposer $etudiant au format attendu par la vue fiche_notes
    $etudiant = [
        'IdEtudiant' => $etudiantId,
        'nom' => $etu['nom'] ?? '',
        'prenom' => $etu['prenom'] ?? '',
        'mail' => $etu['mail'] ?? ''
    ];

    require_once(__DIR__ . '/../views/Etudiant/fiche_notes.php');
}

function getEtudiantInfo($pdo, $etudiantId) {
    try {
        $query = "SELECT * FROM EtudiantsBUT2ou3 WHERE IdEtudiant = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['id' => $etudiantId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log('DB getEtudiantInfo: '.$e->getMessage()); return null; }
}

function getAllNotes($pdo, $etudiantId) {
    $notes = [];
    try {
        $queries = [
            'soutenances' => "SELECT es.note, mge.nomModuleGrilleEvaluation, es.Statut, es.commentaireJury FROM EvalSoutenance es JOIN ModelesGrilleEval mge ON es.IdModeleEval = mge.IdModeleEval WHERE es.IdEtudiant = :id",
            'rapports'    => "SELECT er.note, mge.nomModuleGrilleEvaluation, er.Statut, er.commentaireJury FROM EvalRapport er JOIN ModelesGrilleEval mge ON er.IdModeleEval = mge.IdModeleEval WHERE er.IdEtudiant = :id",
            'portfolios'  => "SELECT ep.note, mge.nomModuleGrilleEvaluation, ep.Statut, ep.commentaireJury FROM EvalPortFolio ep JOIN ModelesGrilleEval mge ON ep.IdModeleEval = mge.IdModeleEval WHERE ep.IdEtudiant = :id",
            'stages'      => "SELECT es.note, mge.nomModuleGrilleEvaluation, es.Statut, es.commentaireJury FROM EvalStage es JOIN ModelesGrilleEval mge ON es.IdModeleEval = mge.IdModeleEval WHERE es.IdEtudiant = :id",
            'anglais'     => "SELECT ea.note, mge.nomModuleGrilleEvaluation, ea.Statut, ea.commentaireJury FROM EvalAnglais ea JOIN ModelesGrilleEval mge ON ea.IdModeleEval = mge.IdModeleEval WHERE ea.IdEtudiant = :id",
        ];
        foreach ($queries as $type => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $etudiantId]);
            $notes[$type] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { error_log('DB getAllNotes: '.$e->getMessage()); }
    return $notes;
}

function calculateAverages($notes) {
    $moyennes = [];
    $total = 0; $count = 0;
    foreach ($notes as $type => $evaluations) {
        $sum = 0; $c = 0;
        foreach ($evaluations as $evaluation) {
            if ($evaluation['note'] !== null && $evaluation['note'] !== '') {
                $sum += $evaluation['note'];
                $c++; $total += $evaluation['note']; $count++;
            }
        }
        $moyennes[$type] = $c ? $sum / $c : null;
    }
    $moyennes['generale'] = $count ? $total / $count : null;
    return $moyennes;
}

    // Ajouter un étudiant (affiche le formulaire, puis traite l'envoi)
    function addEtudiant($pdo) {
        // Si on a reçu des données valides par POST, on crée l'étudiant
        if (!empty($_POST['nom']) &&
            !empty($_POST['prenom']) &&
            !empty($_POST['mail'])) 
        {
            try {
                // Empreinte n'est plus saisie via le formulaire; on n'envoie rien (NULL implicite)
                $ok = createEtudiant($pdo, $_POST['nom'], $_POST['prenom'], $_POST['mail']);
                if (!$ok) {
                    $title = "Ajout impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEtudiant';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                // Après l'ajout, on revient à la liste des étudiants
                header("Location: index.php?action=listEtudiant");
                exit;
            } catch (Throwable $e) {
                // Affiche le message SQL/trigger uniquement
                $title = "Ajout impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEtudiant';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
        // Sinon, on affiche le formulaire d'ajout
    require_once(__DIR__ . '/../views/Etudiant/form.php');
    }

    // Supprimer un étudiant (confirmation puis suppression)
    function destroyEtudiant($pdo) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $id = $_POST['id'];
            try {
                $ok = deleteEtudiant($pdo, $id);
                if (!$ok) {
                    // Si la suppression échoue (trigger/contrainte), on affiche le message en clair
                    $title = "Suppression impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEtudiant';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                // Succès : retour à la liste
                header("Location: index.php?action=listEtudiant");
                exit;
            } catch (Throwable $e) {
                // En cas d'exception PDO, on extrait le message SQL lisible
                $title = "Suppression impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEtudiant';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        } elseif (!empty($_GET['id'])) {
            // Si on arrive par GET avec un id, on affiche la page de confirmation
            $id = $_GET['id'];
            require(__DIR__ . '/../views/Etudiant/confirm_delete.php');
        } else {
            // Sans id, on retourne à la liste
            header("Location: index.php?action=listEtudiant");
            exit;
        }
    }


// Modifier un étudiant (affiche le formulaire pré-rempli puis traite l'envoi)
function editEtudiant($pdo) {
    // Récupère l'id depuis POST (soumission) ou GET (ouverture du formulaire)
    $id = $_POST['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        // Sans id, on retourne à la liste
        header("Location: index.php?action=listEtudiant");
        exit;
    }

    // On récupère l'étudiant à modifier
    $etudiant = getEtudiantById($pdo, $id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Vérifie que les champs nécessaires sont remplis
        if (!empty($_POST['nom']) && !empty($_POST['prenom']) && !empty($_POST['mail'])) {
            try {
                // Empreinte n'est plus modifiée via le formulaire; on la laisse inchangée
                $ok = updateEtudiant($pdo, $id, $_POST['nom'], $_POST['prenom'], $_POST['mail']);
                if (!$ok) {
                    // Si la modification échoue (trigger/contrainte), on affiche le message en clair
                    $title = "Modification impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEtudiant';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                // Succès : retour à la liste
                header("Location: index.php?action=listEtudiant");
                exit;
            } catch (Throwable $e) {
                // En cas d'exception PDO, on extrait le message SQL lisible
                $title = "Modification impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEtudiant';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
    }

    // Affiche le formulaire de modification
    require_once(__DIR__ . '/../views/Etudiant/form.php');
}
?>