<?php
    require_once(__DIR__ . '/ProfStage.php');
    // Modèle ProfAnglaisStage : affectation enseignant d'anglais
    function createEnseignantAnglais($pdo, $idEtudiant, $idEnseignant, $anneedebut) {
        $IdModeleEval = getIdModeleEvalByName($pdo, 'ANGLAIS');
        $Statut = 'SAISIE';
        $stmt = $pdo->prepare("INSERT INTO evalanglais (IdEtudiant, IdEnseignant, anneedebut, IdModeleEval, Statut) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$idEtudiant, $idEnseignant, $anneedebut, $IdModeleEval, $Statut]);
    }
?>