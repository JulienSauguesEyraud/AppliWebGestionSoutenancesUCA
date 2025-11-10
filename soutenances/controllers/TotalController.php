<?php
require_once __DIR__ . '/../models/RemonteeNoteModel.php';
require_once __DIR__ . '/../src/Exception.php';
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function total_index(PDO $pdo): void {
        $vueSeule = isset($_GET['vue']) && $_GET['vue'] == '1';
        $ok = isset($_GET['ok']) ? (int)$_GET['ok'] : null;
        $errc = isset($_GET['errc']) ? (int)$_GET['errc'] : null;

        if (!$vueSeule && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'remettre_saisie') {
                // Action de remise en saisie
                $selections = isset($_POST['sel_remontee']) && is_array($_POST['sel_remontee']) ? $_POST['sel_remontee'] : [];
                list($okCount, $errors) = remontee_notes_remettreEnSaisie($pdo, total_formatSelections($selections));
                $ok = $okCount;
                $errc = count($errors);
            } elseif (isset($_POST['action']) && $_POST['action'] === 'export_but2') {
                // Export BUT2
                total_exportCSV($pdo, 'but2');
                return;
            } elseif (isset($_POST['action']) && $_POST['action'] === 'export_but3') {
                // Export BUT3
                total_exportCSV($pdo, 'but3');
                return;
            } elseif (isset($_POST['action']) && $_POST['action'] === 'send_email_export') {
                // Envoi d'email avec export CSV
                total_sendEmailWithCsv($pdo);
                return;
            } elseif (isset($_POST['action']) && $_POST['action'] === 'send_reminder_tutors') {
                // Envoi de rappels aux tuteurs
                total_sendReminderToTutors($pdo);
                return;
            } elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_event') {
                // Gestion de l'event quotidien
                total_toggleEvent($pdo);
                return;
            } elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_trigger') {
                // Gestion des triggers automatiques
                total_toggleTrigger($pdo);
                return;
            } else {
                // Action de remontée normale (sans champ action ou autres cas)
                $selections = isset($_POST['sel']) && is_array($_POST['sel']) ? $_POST['sel'] : [];
                
                // TEST: Afficher ce qui est reçu
                if (empty($selections)) {
                    // Aucune sélection
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=no_selection');
                    exit;
                }
                
                $formatted = total_formatSelections($selections);
                list($okCount, $errors) = remontee_notes_remonterNotes($pdo, $formatted);
                $ok = $okCount;
                $errc = count($errors);
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query(array_filter(['ok' => $ok, 'errc' => $errc])));
            exit;
        }

        try {
            $rowsCandidats = remontee_notes_getCandidatsRemontee($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur lors de la lecture des candidats: ' . htmlspecialchars($e->getMessage());
            return;
        }

        try {
            $rowsLate = remontee_notes_getCandidatsSoutenancePasseeSaisie($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur lors de la lecture de la seconde liste: ' . htmlspecialchars($e->getMessage());
            return;
        }

        try {
            $rowsRemontee = remontee_notes_getCandidatsAvecStatutRemontee($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur lors de la lecture de la liste des remontées: ' . htmlspecialchars($e->getMessage());
            return;
        }

        try {
            $notesBut2 = remontee_notes_getAllNotesRemonteesBut2($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur lors de la lecture des notes BUT2: ' . htmlspecialchars($e->getMessage());
            return;
        }

        try {
            $notesBut3 = remontee_notes_getAllNotesRemonteesBut3($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur lors de la lecture des notes BUT3: ' . htmlspecialchars($e->getMessage());
            return;
        }

        try {
            $emailsTuteursEnRetard = remontee_notes_getEmailsTuteursEnRetard($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            echo 'Erreur lors de la lecture des emails des tuteurs: ' . htmlspecialchars($e->getMessage());
            return;
        }

        // Vérifier les statuts des triggers et events
        $eventEnabled = remontee_notes_isEventEnabled($pdo);
        $triggersEnabled = remontee_notes_areTriggersEnabled($pdo);

    $data = compact('rowsCandidats', 'rowsRemontee', 'rowsLate', 'notesBut2', 'notesBut3', 'emailsTuteursEnRetard', 'vueSeule', 'ok', 'errc', 'eventEnabled', 'triggersEnabled');
    total_render(__DIR__ . '/../views/Remontee/total.view.php', $data);
}

function total_formatSelections(array $selections): array {
        $formattedSelections = [];
        foreach ($selections as $selection) {
            $parts = explode('_', $selection);
            if (count($parts) === 2) {
                $idEtudiant = $parts[0];
                $annee = $parts[1];
                $formattedSelections[] = $idEtudiant . ':' . $annee;
            }
        }
        return $formattedSelections;
}

function total_exportCSV(PDO $pdo, string $type): void {
        $tempFile = total_createCsvFile($pdo, $type);
        if (!$tempFile) {
            http_response_code(500);
            echo 'Erreur lors de la création du fichier CSV';
            return;
        }

        // Headers pour le téléchargement
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="notes_' . $type . '_' . date('Y') . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        // Lire et afficher le fichier (BOM déjà inclus dans le fichier)
    readfile($tempFile);
        unlink($tempFile);
        exit;
}

function total_sendEmailWithCsv(PDO $pdo): void {
        $exportType = $_POST['export_type'] ?? '';
        $destinataires = $_POST['destinataires'] ?? '';
        $sujet = $_POST['sujet'] ?? '';
        $message = $_POST['message'] ?? '';

        if (empty($exportType) || empty($destinataires) || empty($sujet) || empty($message)) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=missing_fields');
            exit;
        }

        // Créer le fichier CSV temporaire
        $tempFile = total_createCsvFile($pdo, $exportType);
        if (!$tempFile) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=csv_creation_failed');
            exit;
        }

        // Envoyer l'email
        $mail = total_createMailer();
        $destinatairesArray = array_map('trim', explode(',', $destinataires));
        
        try {
            foreach ($destinatairesArray as $dest) {
                $mail->addAddress($dest);
            }
            
            $mail->Subject = $sujet;
            $mail->Body = nl2br($message);
            $mail->addAttachment($tempFile, 'notes_' . $exportType . '_' . date('Y') . '.csv');
            
            $mail->send();
            unlink($tempFile);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?email_sent=1');
        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?email_error=' . urlencode($mail->ErrorInfo));
        }
        exit;
}

function total_sendReminderToTutors(PDO $pdo): void {
        $sujet = $_POST['sujet'] ?? '';
        $message = $_POST['message'] ?? '';

        if (empty($sujet) || empty($message)) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=missing_fields');
            exit;
        }

        $tuteurs = remontee_notes_getEmailsTuteursEnRetard($pdo);
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($tuteurs as $tuteur) {
            $mail = total_createMailer();
            
            try {
                $mail->addAddress($tuteur['mail']);
                $mail->Subject = $sujet;
                $mail->Body = nl2br($message);
                $mail->send();
                $successCount++;
            } catch (Exception $e) {
                $errorCount++;
            }
        }
        
        if ($successCount > 0) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?reminders_sent=' . $successCount . '&reminders_errors=' . $errorCount);
        } else {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?reminders_error=all_failed');
        }
    exit;
}

function total_createMailer(): PHPMailer {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'butsoutenance@gmail.com';
        $mail->Password = 'wqiinxlbjtrkqmoz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 20; // seconds
        $mail->SMTPKeepAlive = false;

        // Windows/XAMPP TLS cert handling
        $smtpOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];
        $envCa = getenv('SMTP_CAFILE');
        if ($envCa && @is_readable($envCa)) {
            $smtpOptions = [
                'ssl' => [
                    'verify_peer'       => true,
                    'verify_peer_name'  => true,
                    'allow_self_signed' => false,
                    'cafile'            => $envCa,
                ]
            ];
        }
        $mail->SMTPOptions = $smtpOptions;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('butsoutenance@gmail.com', 'Équipe Pédagogique');
        $mail->isHTML(true);
        return $mail;
}

function total_createCsvFile(PDO $pdo, string $type): ?string {
        $data = $type === 'but2' ? remontee_notes_getAllNotesRemonteesBut2($pdo) : remontee_notes_getAllNotesRemonteesBut3($pdo);
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $handle = fopen($tempFile, 'w');
        
        if (!$handle) {
            return null;
        }
        
        // BOM UTF-8 pour Excel
        fwrite($handle, "\xEF\xBB\xBF");
        
        if ($type === 'but2') {
            fputcsv($handle, ['Année', 'Étudiant', 'Note Stage', 'Note Portfolio', 'Tuteur'], ';');
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['anneeDebut'],
                    $row['nomEtudiant'] . ' ' . $row['prenomEtudiant'],
                    $row['noteStage'],
                    $row['notePortfolio'],
                    $row['nomTuteur']
                ], ';');
            }
        } else {
            fputcsv($handle, ['Année', 'Étudiant', 'Note Stage', 'Note Portfolio', 'Note Anglais', 'Tuteur'], ';');
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['anneeDebut'],
                    $row['nomEtudiant'] . ' ' . $row['prenomEtudiant'],
                    $row['noteStage'],
                    $row['notePortfolio'],
                    $row['noteAnglais'],
                    $row['nomTuteur']
                ], ';');
            }
        }
        fclose($handle);
    return $tempFile;
}

function total_toggleEvent(PDO $pdo): void {
        $enable = isset($_POST['toggle_event']) && $_POST['toggle_event'] == '1';
        
        if ($enable && remontee_notes_areTriggersEnabled($pdo)) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=triggers_conflict');
            exit;
        }
        
        $success = remontee_notes_setEventStatus($pdo, $enable);
        $message = $enable ? 'event_enabled' : 'event_disabled';
        
        if ($success) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . $message . '=1');
        } else {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=event_failed');
        }
        exit;
}

function total_toggleTrigger(PDO $pdo): void {
        $enable = isset($_POST['toggle_trigger']) && $_POST['toggle_trigger'] == '1';
        
        if ($enable && remontee_notes_isEventEnabled($pdo)) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=event_conflict');
            exit;
        }
        
        $success = remontee_notes_setTriggerStatus($pdo, $enable);
        $message = $enable ? 'triggers_enabled' : 'triggers_disabled';
        
        if ($success) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . $message . '=1');
        } else {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?error=triggers_failed');
        }
        exit;
}

function total_render(string $viewPath, array $data): void {
        extract($data);
        require $viewPath;
}
?>
