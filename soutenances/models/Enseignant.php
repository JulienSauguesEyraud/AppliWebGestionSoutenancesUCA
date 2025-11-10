<?php
    // Modèle Enseignant : fonctions CRUD et requêtes liées aux stages par rôle
    function createEnseignant($pdo, $nom, $prenom, $mail, $mdp) {
        $stmt = $pdo->prepare("INSERT INTO enseignants (nom, prenom, mail, mdp) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nom, $prenom, $mail, $mdp]);
    }
    
    function deleteEnseignant($pdo, $id) {
        // Supprime un enseignant par son identifiant
        $stmt = $pdo->prepare("DELETE FROM enseignants WHERE IdEnseignant  = ?");
        return $stmt->execute([$id]);
    }

    function getAllEnseignants($pdo) {
        // Retourne tous les enseignants
        $stmt = $pdo->query("SELECT * FROM enseignants");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getEnseignantById($pdo, $id) {
        // Récupère un enseignant par son identifiant
        $stmt = $pdo->prepare("SELECT * FROM enseignants WHERE IdEnseignant = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function updateEnseignant($pdo, $id, $nom, $prenom, $mail, $mdp) {
        if ($mdp === '' || $mdp === null) {
            $stmt = $pdo->prepare("UPDATE enseignants SET nom = ?, prenom = ?, mail = ? WHERE IdEnseignant = ?");
            return $stmt->execute([$nom, $prenom, $mail, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE enseignants SET nom = ?, prenom = ?, mail = ?, mdp = ? WHERE IdEnseignant = ?");
            return $stmt->execute([$nom, $prenom, $mail, $mdp, $id]);
        }
    }

    function getStagesByEnseignantTuteurId($pdo, $idEnseignant) {
        // Liste des stages où l'enseignant est tuteur pour l'année en cours
        $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
        $stmt = $pdo->prepare("SELECT e.nom AS nomEntreprise, et.nom AS nomEtudiant, et.prenom AS prenomEtudiant
                               FROM evalstage es
                               JOIN anneestage a ON es.IdEtudiant = a.IdEtudiant
                               JOIN entreprises e ON a.IdEntreprise = e.IdEntreprise
                               JOIN etudiantsbut2ou3 et ON es.IdEtudiant = et.IdEtudiant
                               WHERE es.IdEnseignantTuteur = ? AND a.anneedebut = ?;");
        $stmt->execute([$idEnseignant, $anneeDebut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getStagesByEnseignantSecondaireId($pdo, $idEnseignant) {
        // Liste des stages où l'enseignant est secondaire pour l'année en cours
        $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
        $stmt = $pdo->prepare("SELECT e.nom AS nomEntreprise, et.nom AS nomEtudiant, et.prenom AS prenomEtudiant
                               FROM evalstage es
                               JOIN anneestage a ON es.IdEtudiant = a.IdEtudiant
                               JOIN entreprises e ON a.IdEntreprise = e.IdEntreprise
                               JOIN etudiantsbut2ou3 et ON es.IdEtudiant = et.IdEtudiant
                               WHERE es.IdSecondEnseignant = ? AND a.anneedebut = ?;");
        $stmt->execute([$idEnseignant, $anneeDebut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getStagesByEnseignantAnglaisId($pdo, $idEnseignant) {
        // Liste des stages où l'enseignant est référent d'anglais pour l'année en cours
        $anneeDebut = (date("m") <= 8) ? date("Y") - 1 : date("Y");
        $stmt = $pdo->prepare("SELECT e.nom AS nomEntreprise, et.nom AS nomEtudiant, et.prenom AS prenomEtudiant
                               FROM evalanglais ea
                               JOIN anneestage a ON ea.IdEtudiant = a.IdEtudiant
                               JOIN entreprises e ON a.IdEntreprise = e.IdEntreprise
                               JOIN etudiantsbut2ou3 et ON ea.IdEtudiant = et.IdEtudiant
                               WHERE ea.IdEnseignant = ? AND a.anneedebut = ?;");
        $stmt->execute([$idEnseignant, $anneeDebut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

?>