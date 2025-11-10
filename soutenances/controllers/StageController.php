<?php
require_once(__DIR__ . '/../models/Stage.php');
require_once(__DIR__ . '/../models/Etudiant.php');
require_once(__DIR__ . '/../models/Entreprise.php');

// Ajouter un stage pour un étudiant : charge données nécessaires puis traite le formulaire
function addStageEtudiant($pdo) {
    $idEtudiant = $_GET['idEtudiant'] ?? null;
    $etudiant = null;
    if ($idEtudiant) {
        $etudiant = getEtudiantById($pdo, $idEtudiant);
    }

    $entreprises = getAllEntreprise($pdo);

    if (!empty($_POST['idEtudiant']) &&
        !empty($_POST['Entreprise']) &&
        isset($_POST['but3sinon2']) &&
        !empty($_POST['nomMaitreStageApp']) &&
        !empty($_POST['sujet'])) 
    {
        // Calcul de la valeur d'alternance si BUT3 et case cochée
        $alternanceBUT3 = ($_POST['but3sinon2'] == "1" && !empty($_POST['alternanceBUT3'])) ? $_POST['alternanceBUT3'] : 0;

        // Création de la ligne d'année de stage
        try {
            $ok = createStageEtudiant(
                $pdo,
                $_POST['idEtudiant'],
                $_POST['Entreprise'],
                $_POST['but3sinon2'],
                $_POST['nomMaitreStageApp'],
                $_POST['sujet'],
                $alternanceBUT3
            );
            if (!$ok) {
                $title = "Ajout impossible";
                $info = $pdo->errorInfo();
                $errorMessage = isset($info[2]) ? $info[2] : '';
                $backAction = 'listStage';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
            // Retour à la liste des stages
            header("Location: index.php?action=listStage");
            exit;
        } catch (Throwable $e) {
            $title = "Ajout impossible";
            $raw = $e->getMessage();
            if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                $errorMessage = trim($m[1]);
            } else {
                $errorMessage = $raw;
            }
            $backAction = 'listStage';
            require(__DIR__ . '/../views/Error/constraint.php');
            return;
        }
    }

    // Affiche le formulaire
    require_once(__DIR__ . '/../views/Stage/form.php');
}
?>
