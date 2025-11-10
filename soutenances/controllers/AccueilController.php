<?php
require_once __DIR__ . '/../models/Auth.php';

/**
 * Affiche la page d'accueil selon le type d'utilisateur connecté.
 * - Redirige vers le login si l'utilisateur n'est pas authentifié.
 * - Pour un enseignant: charge les soutenances à venir et passées via procédures SQL.
 * - Rend la vue FrontOffice (enseignant) ou BackOffice (admin) dans le layout principal.
 */
function showAccueilAccueil($db) {
    if (!auth_model_isLoggedIn()) {
        header('Location: index.php?action=login');
        exit();
    }
    $user_type = $_SESSION['user_info']['type'];
    $user_id = $_SESSION['user_id'];
    $pageTitle = "Accueil - Gestion des Soutenances";

    $soutenances_avenir = [];
    $soutenances_passees = [];

    if ($user_type === 'enseignant') {
        try {
            $stmt = $db->prepare("CALL v_soutenances_avenir(:id_enseignant)");
            $stmt->bindParam(':id_enseignant', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $soutenances_avenir = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $stmt->closeCursor();
        } catch (PDOException $e) {
            error_log('Erreur v_soutenances_avenir: ' . $e->getMessage());
            $soutenances_avenir = [];
        }

        try {
            $stmt2 = $db->prepare("CALL v_soutenances_finies(:id_enseignant)");
            $stmt2->bindParam(':id_enseignant', $user_id, PDO::PARAM_INT);
            $stmt2->execute();
            $soutenances_passees = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $stmt2->closeCursor();
        } catch (PDOException $e) {
            error_log('Erreur v_soutenances_finies: ' . $e->getMessage());
            $soutenances_passees = [];
        }
    }
    ob_start();
    if ($user_type === 'enseignant') {
        require_once dirname(__DIR__) . '/views/Accueil/AccueilFrontOffice.php';
    } else {
        // Hide the layout's global logout to render a page-local one (consistent with other pages)
        $hideGlobalLogout = true;
        require_once dirname(__DIR__) . '/views/Accueil/AccueilBackOffice.php';
    }
    $content = ob_get_clean();
    require_once dirname(__DIR__) . '/views/layouts/main.php';
}
?>
