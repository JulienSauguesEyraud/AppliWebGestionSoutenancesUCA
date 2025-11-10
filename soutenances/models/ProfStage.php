<?php
    // Modèle ProfStage : affectations de tuteur/secondaire et création des grilles
    function createEnseignantsStage($pdo, $idEtudiant, $idEnseignantTuteur, $idSecondEnseignant, $anneedebut) {
        $IdModeleEval = getIdModeleEvalByName($pdo, 'STAGE');
        $Statut = 'SAISIE';
        $stmt = $pdo->prepare("INSERT INTO evalstage (IdEtudiant, IdEnseignantTuteur, IdSecondEnseignant, anneedebut, IdModeleEval, Statut) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$idEtudiant, $idEnseignantTuteur, $idSecondEnseignant, $anneedebut, $IdModeleEval, $Statut]);
    }

    // Récupère le nom de l'entreprise du stage de l'étudiant
    function getNomEntrepriseByEtudiantId($pdo, $idEtudiant) {
        $stmt = $pdo->prepare("SELECT e.nom FROM entreprises e JOIN anneestage a ON e.IdEntreprise = a.IdEntreprise WHERE a.IdEtudiant = ?");
        $stmt->execute([$idEtudiant]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['nom'] ?? 'N/A';
    }

    // Récupère l'id du dernier modèle de grille pour une nature donnée
    function getIdModeleEvalByName($pdo, $natureGrille) {
        $sql = "SELECT me.IdModeleEval
                FROM modelesgrilleeval me
                WHERE me.natureGrille = ?
                ORDER BY me.anneeDebut DESC, me.IdModeleEval DESC
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$natureGrille]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['IdModeleEval'] ?? null;
    }

    // Crée une grille de Portfolio
    function createEvalPortfolio($pdo, $idEtudiant, $anneedebut) {
        $IdModeleEval = getIdModeleEvalByName($pdo, 'PORTFOLIO');
        $Statut = 'SAISIE';
        $stmt = $pdo->prepare("INSERT INTO evalportfolio (IdEtudiant, anneedebut, IdModeleEval, Statut) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$idEtudiant, $anneedebut, $IdModeleEval, $Statut]);
    }

    // Crée une grille de Soutenance
    function createEvalSoutenance($pdo, $idEtudiant, $anneedebut) {
        $IdModeleEval = getIdModeleEvalByName($pdo, 'SOUTENANCE');
        $Statut = 'SAISIE';
        $stmt = $pdo->prepare("INSERT INTO evalsoutenance (IdEtudiant, anneedebut, IdModeleEval, Statut) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$idEtudiant, $anneedebut, $IdModeleEval, $Statut]);
    }

    // Crée une grille de Rapport
    function createEvalRapport($pdo, $idEtudiant, $anneedebut) {
        $IdModeleEval = getIdModeleEvalByName($pdo, 'RAPPORT');
        $Statut = 'SAISIE';
        $stmt = $pdo->prepare("INSERT INTO evalrapport (IdEtudiant, anneedebut, IdModeleEval, Statut) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$idEtudiant, $anneedebut, $IdModeleEval, $Statut]);
    }

?>