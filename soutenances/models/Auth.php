<?php

function auth_model_login($pdo, $email, $password) {
    // Essai pour les enseignants
    $query = "SELECT IdEnseignant as id, nom, prenom, mail, mdp 
              FROM Enseignants 
              WHERE mail = :email AND mdp = SHA2(:password, 256)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($row['mdp']);
        $row['type'] = 'enseignant';
        return ['success' => true, 'user' => $row];
    }

    // Essai pour le back office
    $query = "SELECT Identifiant as id, nom, prenom, mail, mdp 
              FROM UtilisateursBackOffice 
              WHERE mail = :email AND mdp = SHA2(:password, 256)";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        unset($row['mdp']);
        $row['type'] = 'backoffice';
        return ['success' => true, 'user' => $row];
    }

    return ['success' => false, 'message' => 'Email ou mot de passe incorrect'];
}


function auth_model_isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function auth_model_logout() {
    session_unset();
    session_destroy();
    session_start();
}

?>