<?php
require_once(__DIR__ . '/../models/Salle.php');

    function addSalle($pdo) {
        if (!empty($_POST['IdSalle'])) 
        {
            $description = !empty($_POST['description']) ? $_POST['description'] : null;
            try {
                $ok = createSalle($pdo, $_POST['IdSalle'], $description);
                if (!$ok) {
                    $title = "Ajout impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listSalle';
                        require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listSalle");
                exit;
            } catch (Throwable $e) {
                $title = "Ajout impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listSalle';
                    require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        }
            require_once(__DIR__ . '/../views/Salle/form.php');
    }

    function destroySalle($pdo) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            try {
                $ok = deleteSalle($pdo, $_POST['id']);
                if (!$ok) {
                    $title = "Suppression impossible";
                    $info = $pdo->errorInfo();
                    $errorMessage = isset($info[2]) ? $info[2] : '';
                    $backAction = 'listSalle';
                        require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
                header("Location: index.php?action=listSalle");
                exit;
            } catch (Throwable $e) {
                $title = "Suppression impossible";
                $raw = $e->getMessage();
                if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                    $errorMessage = trim($m[1]);
                } else {
                    $errorMessage = $raw;
                }
                $backAction = 'listSalle';
                    require(__DIR__ . '/../views/Error/constraint.php');
                return;
            }
        } elseif (!empty($_GET['id'])) {
            $id = $_GET['id'];
                require(__DIR__ . '/../views/Salle/confirm_delete.php');
        } else {
            header("Location: index.php?action=listSalle");
            exit;
        }
    }
    
    function editSalle($pdo) {
        $originalId = $_POST['original_id'] ?? $_GET['id'] ?? null;
        if (!$originalId) {
            header("Location: index.php?action=listSalle");
            exit;
        }

        $salle = getSalleById($pdo, $originalId);
        if (!$salle) {
            header("Location: index.php?action=listSalle");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['IdSalle'])) {
                $newId = $_POST['IdSalle'];
                $description = $_POST['description'] !== '' ? $_POST['description'] : null;
                try {
                    $ok = updateSalle($pdo, $originalId, $newId, $description);
                    if (!$ok) {
                        $title = "Modification impossible";
                        $info = $pdo->errorInfo();
                        $errorMessage = isset($info[2]) ? $info[2] : '';
                        $backAction = 'listSalle';
                            require(__DIR__ . '/../views/Error/constraint.php');
                        return;
                    }
                    header("Location: index.php?action=listSalle");
                    exit;
                } catch (Throwable $e) {
                    $title = "Modification impossible";
                    $raw = $e->getMessage();
                    if (preg_match('/SQLSTATE\\[[0-9A-Z]+\\]:[^:]*: \\d+ (.*)/', $raw, $m)) {
                        $errorMessage = trim($m[1]);
                    } else {
                        $errorMessage = $raw;
                    }
                    $backAction = 'listSalle';
                        require(__DIR__ . '/../views/Error/constraint.php');
                    return;
                }
            }
        }
            require_once(__DIR__ . '/../views/Salle/form.php');
    }
?>