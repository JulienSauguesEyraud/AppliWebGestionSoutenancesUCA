<?php
require_once(__DIR__ . '/../models/Enseignant.php');

    // Ajouter un enseignant : affiche le formulaire puis traite l'envoi
    function addEnseignant($pdo) {
        if (!empty($_POST['nom']) &&
            !empty($_POST['prenom']) &&
            !empty($_POST['mail']) &&
            !empty($_POST['mdp'])) 
        {
            try {
                $ok = createEnseignant($pdo, $_POST['nom'], $_POST['prenom'], $_POST['mail'], $_POST['mdp']);
                if (!$ok) {
                    $title = "Ajout impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEnseignant';
                        require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listEnseignant");
                exit;
            } catch (Throwable $e) {
                $title = "Ajout impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEnseignant';
                    require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
            require_once(__DIR__ . '/../views/Enseignant/form.php');
    }

    // Supprimer un enseignant : confirme puis supprime ; affiche le message SQL en cas d'erreur
    function destroyEnseignant($pdo) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            try {
                $ok = deleteEnseignant($pdo, $_POST['id']);
                if (!$ok) {
                    $title = "Suppression impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEnseignant';
                        require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listEnseignant");
                exit;
            } catch (Throwable $e) {
                // Extrait un message lisible depuis l'exception PDO
                $title = "Suppression impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEnseignant';
                    require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        } elseif (!empty($_GET['id'])) {
            // Affiche la page de confirmation
            $id = $_GET['id'];
                require(__DIR__ . '/../views/Enseignant/confirm_delete.php');
        } else {
            header("Location: index.php?action=listEnseignant");
            exit;
        }
    }
    
    // Modifier un enseignant : charge, valide, met à jour ; affiche le message SQL en cas d'erreur
    function editEnseignant($pdo) {
        $id = $_POST['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            header("Location: index.php?action=listEnseignant");
            exit;
        }

        $enseignant = getEnseignantById($pdo, $id);
        if (!$enseignant) {
            header("Location: index.php?action=listEnseignant");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['nom']) && !empty($_POST['prenom']) && !empty($_POST['mail'])) {
                try {
                    $ok = updateEnseignant($pdo, $id, $_POST['nom'], $_POST['prenom'], $_POST['mail'], $_POST['mdp']);
                    if (!$ok) {
                        $title = "Modification impossible";
                        $info = $pdo->errorInfo();
                        $errorMessage = isset($info[2]) ? $info[2] : '';
                        $backAction = 'listEnseignant';
                            require(__DIR__ . '/../views/Error/constraint.php');
                        return;
                    }
                    header("Location: index.php?action=listEnseignant");
                    exit;
                } catch (Throwable $e) {
                    // Extrait un message lisible depuis l'exception PDO
                    $title = "Modification impossible";
                    $raw = $e->getMessage();
                    if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                        $errorMessage = trim($m[1]);
                    } else {
                        $errorMessage = $raw;
                    }
                    $backAction = 'listEnseignant';
                        require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
            }
        }
            require_once(__DIR__ . '/../views/Enseignant/form.php');
    }
?>