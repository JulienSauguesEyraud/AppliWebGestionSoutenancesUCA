<?php
    // Modèle Entreprise : fonctions CRUD de base
    function createEntreprise($pdo, $nom, $ville, $codePostal) {
        // Insère une entreprise
        $stmt = $pdo->prepare("INSERT INTO entreprises (nom, villeE, codePostal) VALUES (?, ?, ?)");
        return $stmt->execute([$nom, $ville, $codePostal]);
    }
    
    function deleteEntreprise($pdo, $id) {
        // Supprime une entreprise
        $stmt = $pdo->prepare("DELETE FROM entreprises WHERE IdEntreprise  = ?");
        return $stmt->execute([$id]);
    }

    function getAllEntreprise($pdo) {
        // Récupère toutes les entreprises
        $stmt = $pdo->query("SELECT * FROM entreprises");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function updateEntreprise($pdo, $id, $nom, $ville, $codePostal) {
        // Met à jour une entreprise
        $stmt = $pdo->prepare("UPDATE entreprises SET nom = ?, villeE = ?, codePostal = ? WHERE IdEntreprise = ?");
        return $stmt->execute([$nom, $ville, $codePostal, $id]);
    }

    function getEntrepriseById($pdo, $id) {
        // Récupère une entreprise par son identifiant
        $stmt = $pdo->prepare("SELECT * FROM entreprises WHERE IdEntreprise = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
?>