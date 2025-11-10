<?php
require_once(__DIR__ . '/../models/Entreprise.php');

    function addEntreprise($pdo) {
        if (!empty($_POST['nom']) &&
            !empty($_POST['villeE']) &&
            !empty($_POST['codePostal']))
        {
            try {
                $ok = createEntreprise($pdo, $_POST['nom'], $_POST['villeE'], $_POST['codePostal']);
                if (!$ok) {
                    $title = "Ajout impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEntreprise';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listEntreprise");
                exit;
            } catch (Throwable $e) {
                $title = "Ajout impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEntreprise';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
    require_once(__DIR__ . '/../views/Entreprise/form.php');
    }

    function destroyEntreprise($pdo) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            try {
                $ok = deleteEntreprise($pdo, $_POST['id']);
                if (!$ok) {
                    $title = "Suppression impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEntreprise';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listEntreprise");
                exit;
            } catch (Throwable $e) {
                $title = "Suppression impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEntreprise';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        } elseif (!empty($_GET['id'])) {
            $id = $_GET['id'];
            require(__DIR__ . '/../views/Entreprise/confirm_delete.php');
        } else {
            header("Location: index.php?action=listEntreprise");
            exit;
        }
    }

  function editEntreprise($pdo) {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;

    if (!$id) {
        header("Location: index.php?action=listEntreprise");
        exit;
    }

    $entreprise = getEntrepriseById($pdo, $id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['nom']) && !empty($_POST['villeE']) && !empty($_POST['codePostal'])) {
            try {
                $ok = updateEntreprise($pdo, $id, $_POST['nom'], $_POST['villeE'], $_POST['codePostal']);
                if (!$ok) {
                    $title = "Modification impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listEntreprise';
                    require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listEntreprise");
                exit;
            } catch (Throwable $e) {
                $title = "Modification impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listEntreprise';
                require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
    }
    require_once(__DIR__ . '/../views/Entreprise/form.php');
}

?>