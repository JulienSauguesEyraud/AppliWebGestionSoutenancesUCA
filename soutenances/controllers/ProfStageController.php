<?php
require_once(__DIR__ . '/../models/ProfStage.php');
require_once(__DIR__ . '/../models/Etudiant.php');
require_once(__DIR__ . '/../models/Enseignant.php');

// Assigner deux enseignants (tuteur et secondaire) à un étudiant
function addEnseignantStage($pdo) {
    $idEtudiant = $_GET['idEtudiant'] ?? null;
    $etudiant = $idEtudiant ? getEtudiantById($pdo, $idEtudiant) : null;

    $anneeDebut = (date('m') <= 8) ? date('Y') - 1 : date('Y');

    $enseignants = getAllEnseignants($pdo);

    $erreur = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!$etudiant) {
            $erreur = "Étudiant introuvable.";
        } elseif (empty($_POST['IdEnseignantTuteur']) || empty($_POST['IdSecondEnseignant'])) {
            $erreur = "Deux enseignants requis.";
        } elseif ($_POST['IdEnseignantTuteur'] === $_POST['IdSecondEnseignant']) {
            $erreur = "Les deux enseignants doivent être différents.";
        } else {
            // Création de l'affectation et des grilles associées (transaction)
            try {
                $pdo->beginTransaction();
                createEnseignantsStage(
                    $pdo,
                    $_POST['idEtudiant'],
                    $_POST['IdEnseignantTuteur'],
                    $_POST['IdSecondEnseignant'],
                    $anneeDebut
                );
                createEvalPortfolio(
                    $pdo,
                    $_POST['idEtudiant'],
                    $anneeDebut
                );
                createEvalSoutenance(
                    $pdo,
                    $_POST['idEtudiant'],
                    $anneeDebut
                );
                createEvalRapport(
                    $pdo,
                    $_POST['idEtudiant'],
                    $anneeDebut
                );
                $pdo->commit();
                header("Location: index.php?action=listEnseignantStage");
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $title = "Ajout impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEnseignantStage';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
    }

    // Affiche le formulaire d'affectation
    require_once(__DIR__ . '/../views/ProfStage/form.php');
}

?>
