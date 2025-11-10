<?php
    // Modèle Salle : fonctions CRUD de base
    function createSalle($pdo, $id, $description) {
        // Insère une salle
        $stmt = $pdo->prepare("INSERT INTO salles (IdSalle, description) VALUES (?, ?)");
        return $stmt->execute([$id, $description]);
    }
    
    function deleteSalle($pdo, $id) {
        // Supprime une salle
        $stmt = $pdo->prepare("DELETE FROM salles WHERE IdSalle = ?");
        return $stmt->execute([$id]);
    }

    function getAllSalles($pdo) {
        // Récupère toutes les salles
        $stmt = $pdo->query("SELECT * FROM salles");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getSalleById($pdo, $id) {
        // Récupère une salle par son identifiant
        $stmt = $pdo->prepare("SELECT * FROM salles WHERE IdSalle = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function updateSalle($pdo, $originalId, $newId, $description) {
        // Met à jour une salle (y compris son identifiant)
        $stmt = $pdo->prepare("UPDATE salles SET IdSalle = ?, description = ? WHERE IdSalle = ?");
        return $stmt->execute([$newId, $description, $originalId]);
    }
?>