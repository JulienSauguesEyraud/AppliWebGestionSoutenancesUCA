<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Charger PHPMailer depuis le dossier local "src"
require_once __DIR__ . '/../src/Exception.php';
require_once __DIR__ . '/../src/PHPMailer.php';
require_once __DIR__ . '/../src/SMTP.php';

// $pairs: tableau de chaînes "{IdEtudiant}-{anneeDebut}-{nom}-{prenom}" (au moins IdEtudiant-anneeDebut)
function evaluations_diffuserEvaluations(PDO $pdo, array $pairs, ?string $senderEmail, ?string $senderPassword): void {
    $tables = ['EvalStage', 'EvalPortFolio', 'EvalAnglais', 'EvalRapport', 'EvalSoutenance'];

    if (!$senderEmail) {
        throw new Exception('Email manquant.');
    }
    if (!$senderPassword) {
        throw new Exception('MDP manquant.');
    }

    // Collecte les erreurs d'envoi pour les afficher dans la vue de résultat
    $mailErrors = [];

    foreach ($pairs as $pair) {
        // Supporte les 2 formats: "id-annee" ou "id-annee-nom-prenom"
        $parts = explode('-', $pair);
        $idEtudiant = (int)($parts[0] ?? 0);
        $anneeDebut = (int)($parts[1] ?? 0);
        if (!$idEtudiant || !$anneeDebut) {
            // ignore entrée invalide
            continue;
        }

        // Récupération email/empreinte
        $sqlEmail = "SELECT mail, prenom, nom, empreinte FROM EtudiantsBUT2ou3 WHERE IdEtudiant = :idEtudiant";
        $stmtEmail = $pdo->prepare($sqlEmail);
        $stmtEmail->bindParam(':idEtudiant', $idEtudiant, PDO::PARAM_INT);
        $stmtEmail->execute();
        $etudiant = $stmtEmail->fetch(PDO::FETCH_ASSOC);

        if ($etudiant && !empty($etudiant['mail'])) {
            $destinataire = $etudiant['mail'];

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $senderEmail;
                $mail->Password = $senderPassword;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';

                // Réduit les délais et évite les connexions persistantes
                $mail->Timeout = 20; // secondes
                $mail->SMTPKeepAlive = false;

                // Gestion du certificat (Windows/XAMPP) :
                // - si une variable d'environnement SMTP_CAFILE est définie et lisible, on l'utilise
                // - sinon on assouplit la vérification en DEV (à éviter en prod)
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

                $mail->setFrom($senderEmail, 'Administration BUT');
                $mail->addAddress($destinataire, ($etudiant['prenom'] ?? '') . ' ' . ($etudiant['nom'] ?? ''));
                $mail->addCC($senderEmail);

                $mail->isHTML(true);
                $mail->Subject = 'Diffusion de vos évaluations';
                // Construit une URL absolue basée sur l'hôte et le dossier courant
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/\\');
                $empreinte = $etudiant['empreinte'] ?? '';
                if ($empreinte === '' || $empreinte === null) {
                    $mailErrors[] = "Empreinte manquante pour l'étudiant ID $idEtudiant (email non envoyé).";
                    continue;
                }
                $url = $scheme . '://' . $host . $basePath . '/index.php?action=etudiant&empreinte=' . urlencode($empreinte);
                $mail->Body = 'Bonjour ' . htmlspecialchars($etudiant['prenom'] ?? '') . ',<br><br>' .
                              'Vos évaluations pour l\'année ' . htmlspecialchars((string)$anneeDebut) . ' ont été diffusées.<br>' .
                              'Voici le lien pour consulter vos résultats : <a href="' . $url . '">' . $url . '</a>.<br><br>' .
                              'Cordialement,<br>L\'équipe pédagogique.';

                $mail->send();

                // Mise à jour des tables
                foreach ($tables as $table) {
                    $sql = "UPDATE $table SET Statut = 'DIFFUSEE' WHERE IdEtudiant = :idEtudiant AND anneeDebut = :anneeDebut";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':idEtudiant', $idEtudiant, PDO::PARAM_INT);
                    $stmt->bindParam(':anneeDebut', $anneeDebut, PDO::PARAM_INT);
                    $stmt->execute();
                }
            } catch (Exception $e) {
                // Ne pas émettre directement; stocker pour affichage propre dans la vue
                $mailErrors[] = "Erreur lors de l'envoi du mail à " . htmlspecialchars($destinataire) . ' : ' . htmlspecialchars($mail->ErrorInfo);
            }
        }
    }

    // Expose les erreurs à la vue de résultat (sans casser l'API actuelle)
    $GLOBALS['evaluations_last_errors'] = $mailErrors;
}