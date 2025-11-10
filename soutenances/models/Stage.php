<?php
// Modèle Stage : création et lecture de stages (anneestage)
function createStageEtudiant($pdo, $idEtudiant, $idEntreprise, $but3sinon2, $nomMaitreStageApp, $sujet, $alternanceBUT3) {
    // Insère une entrée d'année de stage pour l'étudiant
    $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
    $stmt = $pdo->prepare("INSERT INTO anneestage (IdEtudiant, IdEntreprise, but3sinon2, nomMaitreStageApp, sujet, alternanceBUT3, anneeDebut) VALUES (?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$idEtudiant, $idEntreprise, $but3sinon2, $nomMaitreStageApp, $sujet, $alternanceBUT3, $anneeDebut]);
}

function getStageByEtudiantId($pdo, $idEtudiant) {
    // Retourne l'entrée d'année de stage pour un étudiant
    $stmt = $pdo->prepare("SELECT * FROM anneestage WHERE IdEtudiant = ?");
    $stmt->execute([$idEtudiant]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
