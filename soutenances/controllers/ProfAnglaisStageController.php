<?php
require_once(__DIR__ . '/../models/ProfAnglaisStage.php');
require_once(__DIR__ . '/../models/Etudiant.php');
require_once(__DIR__ . '/../models/Enseignant.php');

// Assigner un enseignant d'anglais à un étudiant
function addEnseignantAnglais($pdo) {
    $idEtudiant = $_GET['idEtudiant'] ?? null;
    $etudiant = $idEtudiant ? getEtudiantById($pdo, $idEtudiant) : null;

    $anneeDebut = (date('m') <= 8) ? date('Y') - 1 : date('Y');

    $enseignants = getAllEnseignants($pdo);

    $erreur = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$etudiant) {
            $erreur = "Étudiant introuvable.";
        } elseif (empty($_POST['IdEnseignant'])) {
            $erreur = "Un enseignant requis.";
        } else {
            // Création de l'affectation d'anglais
            try {
                $ok = createEnseignantAnglais(
                    $pdo,
                    $_POST['idEtudiant'],
                    $_POST['IdEnseignant'],
                    $anneeDebut
                );
                if (!$ok) {
                    $title = "Ajout impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEnseignantAnglais';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listEnseignantAnglais");
                exit;
            } catch (Throwable $e) {
                $title = "Ajout impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEnseignantAnglais';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
    }

    // Affiche le formulaire d'affectation d'anglais
    require_once(__DIR__ . '/../views/ProfAnglaisStage/form.php');
}
?>