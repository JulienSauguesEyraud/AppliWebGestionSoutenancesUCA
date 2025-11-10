<?php
    // Modèle Étudiant : fonctions CRUD et requêtes liées aux stages/affectations
    function createEtudiant($pdo, $nom, $prenom, $mail, $empreinte = null) {
        // Insère un étudiant. Empreinte est optionnelle (NULL par défaut si non fournie).
        $stmt = $pdo->prepare("INSERT INTO etudiantsbut2ou3 (nom, prenom, mail, empreinte) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nom, $prenom, $mail, $empreinte]);
    }
    
    function deleteEtudiant($pdo, $id) {
        // Supprime un étudiant par son identifiant
        $stmt = $pdo->prepare("DELETE FROM etudiantsbut2ou3 WHERE IdEtudiant  = ?");
        return $stmt->execute([$id]);
    }

    function getAllEtudiants($pdo) {
        // Retourne tous les étudiants
        $stmt = $pdo->query("SELECT * FROM etudiantsbut2ou3");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function updateEtudiant($pdo, $id, $nom, $prenom, $mail, $empreinte = null) {
        // Met à jour un étudiant. N'actualise pas l'empreinte si elle n'est pas fournie.
        if ($empreinte === null || $empreinte === '') {
            $stmt = $pdo->prepare("UPDATE etudiantsbut2ou3 SET nom = ?, prenom = ?, mail = ? WHERE IdEtudiant = ?");
            return $stmt->execute([$nom, $prenom, $mail, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE etudiantsbut2ou3 SET nom = ?, prenom = ?, mail = ?, empreinte = ? WHERE IdEtudiant = ?");
            return $stmt->execute([$nom, $prenom, $mail, $empreinte, $id]);
        }
    }

    function getEtudiantById($pdo, $id) {
        // Récupère un étudiant par son identifiant
        $stmt = $pdo->prepare("SELECT * FROM etudiantsbut2ou3 WHERE IdEtudiant = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function getAllEtudiantsWithoutStage($pdo) {
    // Liste des étudiants sans stage pour l'année scolaire courante
    $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
    $stmt = $pdo->prepare("
        SELECT * FROM etudiantsbut2ou3 
        WHERE IdEtudiant NOT IN (
            SELECT IdEtudiant FROM anneestage WHERE anneeDebut = ?
        )
    ");
    $stmt->execute([$anneeDebut]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    function getAllEtudiantsWithStageAndNoEnseignant($pdo) {
    // Étudiants ayant un stage mais sans enseignant tuteur affecté pour l'année scolaire courante
    $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
            $sql = "
                SELECT e.*
                FROM etudiantsbut2ou3 e
                WHERE EXISTS (
                    SELECT 1 FROM anneestage a
                    WHERE a.IdEtudiant = e.IdEtudiant
                      AND a.anneeDebut = ?
                )
                AND NOT EXISTS (
                    SELECT 1 FROM evalstage es
                    WHERE es.IdEtudiant = e.IdEtudiant
                      AND es.anneeDebut = ?
                      AND es.IdEnseignantTuteur IS NOT NULL
                )
                ORDER BY e.nom, e.prenom
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$anneeDebut, $anneeDebut]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllEtudiantsWithStageAndNoEnseignantAnglais($pdo) {
// Étudiants ayant un stage mais sans enseignant d'anglais affecté pour l'année scolaire courante
$anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
        $sql = "
            SELECT e.*
            FROM etudiantsbut2ou3 e
            WHERE EXISTS (
                SELECT 1 FROM anneestage a
                WHERE a.IdEtudiant = e.IdEtudiant
                AND a.anneeDebut = ?
                AND a.but3sinon2 = 1
            )
            AND NOT EXISTS (
                SELECT 1 FROM evalanglais ea
                WHERE ea.IdEtudiant = e.IdEtudiant
                AND ea.anneeDebut = ?
                AND ea.IdEnseignant IS NOT NULL
            )
            ORDER BY e.nom, e.prenom
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$anneeDebut, $anneeDebut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php

function etudiant_getByEmpreinte(PDO $pdo, string $empreinte): ?array {
    $sql = "SELECT IdEtudiant, nom, prenom, mail FROM etudiantsbut2ou3 WHERE empreinte = :empreinte";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':empreinte' => $empreinte]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function etudiant_getSoutenancesDiffusees(PDO $pdo, int $idEtudiant): array {
    $sql = "SELECT note, commentaireJury, anneeDebut FROM evalsoutenance WHERE IdEtudiant = :id AND Statut = 'DIFFUSEE' ORDER BY anneeDebut";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idEtudiant]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function etudiant_getPortfoliosDiffuses(PDO $pdo, int $idEtudiant): array {
    $sql = "SELECT note, commentaireJury, anneeDebut FROM evalportfolio WHERE IdEtudiant = :id AND Statut = 'DIFFUSEE' ORDER BY anneeDebut";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idEtudiant]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function etudiant_getRapportsDiffuses(PDO $pdo, int $idEtudiant): array {
    $sql = "SELECT note, commentaireJury, anneeDebut FROM evalrapport WHERE IdEtudiant = :id AND Statut = 'DIFFUSEE' ORDER BY anneeDebut";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idEtudiant]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function etudiant_getAnglaisDiffuses(PDO $pdo, int $idEtudiant): array {
    $sql = "SELECT note, commentaireJury, anneeDebut, dateS FROM evalanglais WHERE IdEtudiant = :id AND Statut = 'DIFFUSEE' ORDER BY anneeDebut";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idEtudiant]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function etudiant_getStagesDiffuses(PDO $pdo, int $idEtudiant): array {
    $sql = "SELECT note, commentaireJury, anneeDebut, date_h FROM evalstage WHERE IdEtudiant = :id AND Statut = 'DIFFUSEE' ORDER BY anneeDebut";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idEtudiant]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEtudiantsSansSoutenance($pdo, $anneeDebut = null) {
    if ($anneeDebut === null) {
        $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
    }

    // Récupère étudiants sans date planifiée ; ramène champs evalstage / evalanglais utiles.
    $sql = "
        SELECT 
            e.*,
            es.IdEvalStage AS IdEvalStage,
            es.IdEnseignantTuteur AS IdEnseignantTuteur,
            es.IdSecondEnseignant AS IdSecondEnseignant,
            es.IdSalle AS IdSalle,
            es.date_h AS stage_date_h,
            ea.IdEvalAnglais AS IdEvalAnglais,
            ea.IdEnseignant AS IdEnseignantAnglais,
            ea.dateS AS anglais_dateS
        FROM etudiantsbut2ou3 e
        LEFT JOIN evalstage es
            ON es.IdEtudiant = e.IdEtudiant AND es.anneeDebut = ?
        LEFT JOIN evalanglais ea
            ON ea.IdEtudiant = e.IdEtudiant AND ea.anneeDebut = ?
        WHERE NOT EXISTS (
                SELECT 1 FROM evalstage es2
                WHERE es2.IdEtudiant = e.IdEtudiant
                  AND es2.anneeDebut = ?
                  AND es2.date_h IS NOT NULL AND es2.date_h <> ''
        )
        AND NOT EXISTS (
                SELECT 1 FROM evalanglais ea2
                WHERE ea2.IdEtudiant = e.IdEtudiant
                  AND ea2.anneeDebut = ?
                  AND ea2.dateS IS NOT NULL AND ea2.dateS <> ''
        )
        ORDER BY e.nom, e.prenom
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anneeDebut, $anneeDebut, $anneeDebut, $anneeDebut]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}