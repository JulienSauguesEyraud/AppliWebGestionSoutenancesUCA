<?php
require_once __DIR__ . '/../models/EvalStageModel.php';

// Liste des étudiants en "remontée" et fallback par niveau
function evalstage_listeEtudiantsRemontee(PDO $pdo) {
    $etudiants = evalstage_getEtudiantsRemontee($pdo);

    // Affichage: cases à cocher visibles si remontées
    $showCheckboxBut2 = true;
    $showCheckboxBut3 = true;

    $but2 = array_filter($etudiants, fn($e) => $e['but3sinon2'] == 0);
    if (empty($but2)) {
    $but2 = evalstage_getAllEtudiantsBUT2($pdo);
    $showCheckboxBut2 = false;
    }

    $but3 = array_filter($etudiants, fn($e) => $e['but3sinon2'] == 1);
    if (empty($but3)) {
    $but3 = evalstage_getAllEtudiantsBUT3($pdo);
    $showCheckboxBut3 = false;
    }

    // Vue
    $showCheckboxBut2 = (bool)$showCheckboxBut2;
    $showCheckboxBut3 = (bool)$showCheckboxBut3;
    require __DIR__ . '/../views/Etudiant/liste_etudiants_remontee.php';
}

// Confirmation de la sélection
function evalstage_confirmSelection(PDO $pdo) {
    if (empty($_POST['selection'])) {
        require __DIR__ . '/../views/Etudiant/aucune_selection.php';
        return;
    }
    $selection = $_POST['selection'];
    require __DIR__ . '/../views/Etudiant/confirmation_selection.php';
}

// Validation de la sélection: diffusion + affichage récap
function evalstage_validerSelection(PDO $pdo) {
    if (isset($_POST['confirm']) && $_POST['confirm'] === 'no') {
        evalstage_listeEtudiantsRemontee($pdo);
        return;
    }

    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes' && isset($_POST['selection'])) {
        $selection = $_POST['selection'];
        $email     = $_POST['email'] ?? null;
        $password  = $_POST['password'] ?? null;

        require_once __DIR__ . '/../models/EvaluationsModel.php';
        try {
            evaluations_diffuserEvaluations($pdo, $selection, $email, $password);
        } catch (Throwable $e) {
            echo "Erreur lors de la diffusion: " . htmlspecialchars($e->getMessage());
            return;
        }

        require __DIR__ . '/../views/Etudiant/diffusion_result.php';
    }
}
