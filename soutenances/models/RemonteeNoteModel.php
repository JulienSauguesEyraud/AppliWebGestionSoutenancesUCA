<?php
require_once __DIR__ . '/../config/database.php';


function remontee_notes_getCandidatsRemontee(PDO $pdo): array {
    $stmt = $pdo->query('
            SELECT DISTINCT
                e.IdEtudiant,
                e.nom as nomEtudiant,
                e.prenom as prenomEtudiant,
                a.anneeDebut,
                a.but3sinon2,
                a.alternanceBUT3,
                ent.nom as entreprise_nom,
                a.sujet,
                a.nomMaitreStageApp
            FROM EtudiantsBUT2ou3 e
            JOIN AnneeStage a ON e.IdEtudiant = a.IdEtudiant
            LEFT JOIN Entreprises ent ON a.IdEntreprise = ent.IdEntreprise
            WHERE a.anneeDebut >= 2024
            AND EXISTS (
                -- Vérifier qu\'il y a une évaluation de stage BLOQUEE
                SELECT 1 FROM EvalStage es
                JOIN ModelesGrilleEval mge ON es.IdModeleEval = mge.IdModeleEval
                WHERE es.IdEtudiant = e.IdEtudiant 
                AND es.anneeDebut = a.anneeDebut
                AND mge.natureGrille LIKE \'%STAGE%\'
                AND es.Statut = \'BLOQUEE\'
            )
            AND EXISTS (
                -- Vérifier qu\'il y a une évaluation de portfolio BLOQUEE
                SELECT 1 FROM EvalPortFolio ep
                JOIN ModelesGrilleEval mge ON ep.IdModeleEval = mge.IdModeleEval
                WHERE ep.IdEtudiant = e.IdEtudiant 
                AND ep.anneeDebut = a.anneeDebut
                AND mge.natureGrille LIKE \'%PORTFOLIO%\'
                AND ep.Statut = \'BLOQUEE\'
            )
            AND (
                -- Si BUT2, pas besoin d\'anglais
                a.but3sinon2 = 0
                OR
                -- Si BUT3, vérifier qu\'il y a une évaluation d\'anglais BLOQUEE
                (a.but3sinon2 = 1 AND EXISTS (
                    SELECT 1 FROM EvalAnglais ea
                    JOIN ModelesGrilleEval mge ON ea.IdModeleEval = mge.IdModeleEval
                    WHERE ea.IdEtudiant = e.IdEtudiant 
                    AND ea.anneeDebut = a.anneeDebut
                    AND mge.natureGrille LIKE \'%ANGLAIS%\'
                    AND ea.Statut = \'BLOQUEE\'
                ))
            )
            ORDER BY a.anneeDebut, e.nom, e.prenom
        ');
    return $stmt->fetchAll();
}





function remontee_notes_getCandidatsSoutenancePasseeSaisie(PDO $pdo): array {
    $sql = "
            SELECT
                a.anneeDebut,
                etu.IdEtudiant,
                etu.nom AS nomEtudiant,
                etu.prenom AS prenomEtudiant,
                es.date_h AS dateStage,
                es.Statut AS StatutStage,
                ep.Statut AS StatutPortfolio,
                ea.dateS AS dateAnglais,
                ea.Statut AS StatutAnglais
            FROM AnneeStage a
            JOIN EtudiantsBUT2ou3 etu ON etu.IdEtudiant = a.IdEtudiant
            -- STAGE (pour récupérer la date de soutenance)
            LEFT JOIN EvalStage es ON es.IdEtudiant = a.IdEtudiant AND es.anneeDebut = a.anneeDebut
            LEFT JOIN ModelesGrilleEval ms ON ms.IdModeleEval = es.IdModeleEval AND ms.natureGrille LIKE '%STAGE%'
            -- PORTFOLIO
            LEFT JOIN EvalPortFolio ep ON ep.IdEtudiant = a.IdEtudiant AND ep.anneeDebut = a.anneeDebut
            LEFT JOIN ModelesGrilleEval mp ON mp.IdModeleEval = ep.IdModeleEval AND mp.natureGrille LIKE '%PORTFOLIO%'
            -- ANGLAIS (BUT3 seulement)
            LEFT JOIN EvalAnglais ea ON ea.IdEtudiant = a.IdEtudiant AND ea.anneeDebut = a.anneeDebut
            LEFT JOIN ModelesGrilleEval ma ON ma.IdModeleEval = ea.IdModeleEval AND ma.natureGrille LIKE '%ANGLAIS%'
            WHERE
                -- Date de soutenance passée
                es.date_h < NOW()
                -- Au moins une évaluation au statut 'SAISIE'
                AND (
                    es.Statut = 'SAISIE' 
                    OR ep.Statut = 'SAISIE' 
                    OR (a.but3sinon2 = 1 AND ea.Statut = 'SAISIE')
                )
            ORDER BY a.anneeDebut, etu.nom, etu.prenom
        ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function remontee_notes_getCandidatsAvecStatutRemontee(PDO $pdo): array {
    $sql = "
            SELECT DISTINCT
                a.anneeDebut,
                etu.IdEtudiant,
                etu.nom AS nomEtudiant,
                etu.prenom AS prenomEtudiant,
                es.Statut AS StatutStage,
                ep.Statut AS StatutPortfolio,
                ea.Statut AS StatutAnglais
            FROM AnneeStage a
            JOIN EtudiantsBUT2ou3 etu ON etu.IdEtudiant = a.IdEtudiant
            -- STAGE
            JOIN EvalStage es ON es.IdEtudiant = a.IdEtudiant AND es.anneeDebut = a.anneeDebut
            JOIN ModelesGrilleEval ms ON ms.IdModeleEval = es.IdModeleEval AND ms.natureGrille LIKE '%STAGE%'
            -- PORTFOLIO
            JOIN EvalPortFolio ep ON ep.IdEtudiant = a.IdEtudiant AND ep.anneeDebut = a.anneeDebut
            JOIN ModelesGrilleEval mp ON mp.IdModeleEval = ep.IdModeleEval AND mp.natureGrille LIKE '%PORTFOLIO%'
            -- ANGLAIS (BUT3 seulement)
            LEFT JOIN EvalAnglais ea ON ea.IdEtudiant = a.IdEtudiant AND ea.anneeDebut = a.anneeDebut
            LEFT JOIN ModelesGrilleEval ma ON ma.IdModeleEval = ea.IdModeleEval AND ma.natureGrille LIKE '%ANGLAIS%'
            WHERE 
                -- Toutes les évaluations sont au statut REMONTEE
                es.Statut = 'REMONTEE'
                AND ep.Statut = 'REMONTEE'
                AND (a.but3sinon2 = 0 OR (a.but3sinon2 = 1 AND ea.IdEtudiant IS NOT NULL AND ea.Statut = 'REMONTEE'))
            ORDER BY a.anneeDebut, etu.nom, etu.prenom
        ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function remontee_notes_remonterNotes(PDO $pdo, array $selections): array {
        $okCount = 0;
        $errors = [];
        if (!$selections) {
            return [$okCount, $errors];
        }
        $stmt = $pdo->prepare('CALL sp_remonter_notes(:id, :an)');
        foreach ($selections as $token) {
            list($idStr, $anStr) = array_pad(explode(':', (string)$token, 2), 2, '');
            $idEtudiant = (int)$idStr;
            $anneeDebut = (int)$anStr;
            if ($idEtudiant > 0 && $anneeDebut > 0) {
                try {
                    $stmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);
                    $okCount++;
                } catch (PDOException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        return [$okCount, $errors];
}


function remontee_notes_remettreEnSaisie(PDO $pdo, array $selections): array {
        $okCount = 0;
        $errors = [];
        if (!$selections) {
            return [$okCount, $errors];
        }
        
        $updateStageStmt = $pdo->prepare("UPDATE EvalStage SET Statut = 'SAISIE' WHERE IdEtudiant = :id AND anneeDebut = :an");
        $updatePortfolioStmt = $pdo->prepare("UPDATE EvalPortFolio SET Statut = 'SAISIE' WHERE IdEtudiant = :id AND anneeDebut = :an");
        $updateAnglaisStmt = $pdo->prepare("UPDATE EvalAnglais SET Statut = 'SAISIE' WHERE IdEtudiant = :id AND anneeDebut = :an");
        $updateRapportStmt = $pdo->prepare("UPDATE EvalRapport SET Statut = 'SAISIE' WHERE IdEtudiant = :id AND anneeDebut = :an");
        $updateSoutenanceStmt = $pdo->prepare("UPDATE EvalSoutenance SET Statut = 'SAISIE' WHERE IdEtudiant = :id AND anneeDebut = :an");


        foreach ($selections as $token) {
            list($idStr, $anStr) = array_pad(explode(':', (string)$token, 2), 2, '');
            $idEtudiant = (int)$idStr;
            $anneeDebut = (int)$anStr;
            
            if ($idEtudiant > 0 && $anneeDebut > 0) {
                try {
                    // Remettre en SAISIE
                    $updateStageStmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);
                    $updatePortfolioStmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);
                    $updateRapportStmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);
                    $updateSoutenanceStmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);

                    // Vérifier si c'est un BUT3 (avec évaluation d'anglais)
                    $checkBut3Stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM AnneeStage a 
                        WHERE a.IdEtudiant = :id AND a.anneeDebut = :an AND a.but3sinon2 = 1
                    ");
                    $checkBut3Stmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);
                    $isBut3 = (int)$checkBut3Stmt->fetchColumn() > 0;
                    
                    if ($isBut3) {
                        $updateAnglaisStmt->execute([':id' => $idEtudiant, ':an' => $anneeDebut]);
                    }
                    
                    $okCount++;
                } catch (PDOException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        
        return [$okCount, $errors];
}

function remontee_notes_getAllNotesRemonteesBut2(PDO $pdo): array {
    $stmt = $pdo->query('
            SELECT 
                es.anneeDebut,
                e.nom as nomEtudiant,
                e.prenom as prenomEtudiant,
                es.note as noteStage,
                ep.note as notePortfolio,
                CONCAT(ens.nom, " ", ens.prenom) as nomTuteur
            FROM EvalStage es
            JOIN EtudiantsBUT2ou3 e ON es.IdEtudiant = e.IdEtudiant
            JOIN AnneeStage a ON es.IdEtudiant = a.IdEtudiant AND es.anneeDebut = a.anneeDebut
            JOIN EvalPortFolio ep ON es.IdEtudiant = ep.IdEtudiant AND es.anneeDebut = ep.anneeDebut
            JOIN Enseignants ens ON es.IdEnseignantTuteur = ens.IdEnseignant
            WHERE es.statut = "REMONTEE" AND ep.statut = "REMONTEE"
            AND a.but3sinon2 = 0
            ORDER BY es.anneeDebut, e.nom, e.prenom
        ');
    return $stmt->fetchAll();
}

function remontee_notes_getAllNotesRemonteesBut3(PDO $pdo): array {
    $stmt = $pdo->query('
            SELECT 
                es.anneeDebut,
                e.nom as nomEtudiant,
                e.prenom as prenomEtudiant,
                es.note as noteStage,
                ep.note as notePortfolio,
                ea.note as noteAnglais,
                CONCAT(ens.nom, " ", ens.prenom) as nomTuteur
            FROM EvalStage es
            JOIN EtudiantsBUT2ou3 e ON es.IdEtudiant = e.IdEtudiant
            JOIN AnneeStage a ON es.IdEtudiant = a.IdEtudiant AND es.anneeDebut = a.anneeDebut
            JOIN EvalPortFolio ep ON es.IdEtudiant = ep.IdEtudiant AND es.anneeDebut = ep.anneeDebut
            JOIN EvalAnglais ea ON es.IdEtudiant = ea.IdEtudiant AND es.anneeDebut = ea.anneeDebut
            JOIN Enseignants ens ON es.IdEnseignantTuteur = ens.IdEnseignant
            WHERE es.statut = "REMONTEE" AND ep.statut = "REMONTEE" AND ea.statut = "REMONTEE"
            AND a.but3sinon2 = 1
            ORDER BY es.anneeDebut, e.nom, e.prenom
        ');
    return $stmt->fetchAll();
}

function remontee_notes_getEmailsTuteursEnRetard(PDO $pdo): array {
    $sql = "
            SELECT DISTINCT
                ens.mail,
                CONCAT(ens.nom, ' ', ens.prenom) as nomTuteur,
                COUNT(DISTINCT etu.IdEtudiant) as nbEtudiantsEnRetard
            FROM AnneeStage a
            JOIN EtudiantsBUT2ou3 etu ON etu.IdEtudiant = a.IdEtudiant
            JOIN EvalStage es ON es.IdEtudiant = a.IdEtudiant AND es.anneeDebut = a.anneeDebut
            JOIN ModelesGrilleEval ms ON ms.IdModeleEval = es.IdModeleEval AND ms.natureGrille LIKE '%STAGE%'
            JOIN Enseignants ens ON es.IdEnseignantTuteur = ens.IdEnseignant
            WHERE
                -- Date de soutenance passée
                es.date_h < NOW()
                -- Au moins une évaluation au statut 'SAISIE'
                AND (
                    es.Statut = 'SAISIE' 
                    OR EXISTS (
                        SELECT 1 FROM EvalPortFolio ep 
                        WHERE ep.IdEtudiant = a.IdEtudiant AND ep.anneeDebut = a.anneeDebut 
                        AND ep.Statut = 'SAISIE'
                    )
                    OR (a.but3sinon2 = 1 AND EXISTS (
                        SELECT 1 FROM EvalAnglais ea 
                        WHERE ea.IdEtudiant = a.IdEtudiant AND ea.anneeDebut = a.anneeDebut 
                        AND ea.Statut = 'SAISIE'
                    ))
                )
            GROUP BY ens.IdEnseignant, ens.mail, ens.nom, ens.prenom
            ORDER BY ens.nom, ens.prenom
        ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

    // Gestion de l'event quotidien
function remontee_notes_setEventStatus(PDO $pdo, $enabled = true): bool {
        $status = $enabled ? 'ENABLE' : 'DISABLE';
        $sql = "ALTER EVENT remonter_grilles_event $status;";
        try {
            $pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            return false;
        }
}

function remontee_notes_isEventEnabled(PDO $pdo): bool {
        $sql = "SELECT STATUS FROM information_schema.EVENTS WHERE EVENT_NAME = 'remonter_grilles_event'";
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch();
        return isset($row['STATUS']) && $row['STATUS'] === 'ENABLED';
}

    // Gestion des triggers automatiques
function remontee_notes_setTriggerStatus(PDO $pdo, $enabled = true): bool {
        $triggers = ['remonteeStage', 'remonteePortfolio', 'remonteeAnglais'];
        
        try {
            // DROP pour tous les triggers
            foreach ($triggers as $trigger) {
                $dropSql = "DROP TRIGGER IF EXISTS $trigger;";
                $pdo->exec($dropSql);
            }

            if ($enabled) {
                // Création des 3 triggers
                remontee_notes_createTriggers($pdo);
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
}

function remontee_notes_createTriggers(PDO $pdo): void {
        // Trigger remonteeStage
        $createStage = <<<SQL
CREATE TRIGGER remonteeStage
BEFORE UPDATE ON EvalStage
FOR EACH ROW
BEGIN
remontee_block: BEGIN
    DECLARE statutPortfolio VARCHAR(15);
    DECLARE statutAnglais VARCHAR(15);
    DECLARE but3 BOOLEAN;

    SELECT but3sinon2 INTO but3
    FROM AnneeStage
    WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    SELECT Statut INTO statutPortfolio
    FROM EvalPortFolio
    WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    IF but3 = 1 THEN
        SELECT Statut INTO statutAnglais
        FROM EvalAnglais
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;
    END IF;

    IF but3 = 0 AND NEW.Statut = 'BLOQUEE' AND statutPortfolio = 'BLOQUEE' THEN
        SET NEW.Statut = 'REMONTEE';
        UPDATE EvalPortFolio 
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    ELSEIF but3 = 1 AND NEW.Statut = 'BLOQUEE' AND statutPortfolio = 'BLOQUEE'AND statutAnglais = 'BLOQUEE'
    THEN
        SET NEW.Statut = 'REMONTEE';
        UPDATE EvalPortFolio 
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

        UPDATE EvalAnglais
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;
    END IF;
END;
END;
SQL;

        // Trigger remonteePortfolio
        $createPortfolio = <<<SQL
CREATE TRIGGER remonteePortfolio
BEFORE UPDATE ON EvalPortFolio
FOR EACH ROW
BEGIN
remontee_block: BEGIN
    DECLARE statutStage VARCHAR(15);
    DECLARE statutAnglais VARCHAR(15);
    DECLARE but3 BOOLEAN;

    SELECT but3sinon2 INTO but3
    FROM AnneeStage
    WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    SELECT Statut INTO statutStage
    FROM EvalStage
    WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    IF but3 = 1 THEN
        SELECT Statut INTO statutAnglais
        FROM EvalAnglais
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;
    END IF;

    IF but3 = 0 AND NEW.Statut = 'BLOQUEE' AND statutStage = 'BLOQUEE' THEN
        SET NEW.Statut = 'REMONTEE';
        UPDATE EvalStage 
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    ELSEIF but3 = 1 AND NEW.Statut = 'BLOQUEE' AND statutStage = 'BLOQUEE'AND statutAnglais = 'BLOQUEE'
    THEN
        SET NEW.Statut = 'REMONTEE';
        UPDATE EvalStage
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

        UPDATE EvalAnglais
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;
    END IF;
END;
END;
SQL;

        // Trigger remonteeAnglais
        $createAnglais = <<<SQL
CREATE TRIGGER remonteeAnglais
BEFORE UPDATE ON EvalAnglais
FOR EACH ROW
BEGIN
remontee_block: BEGIN
    DECLARE statutPortfolio VARCHAR(15);
    DECLARE statutStage VARCHAR(15);

    SELECT Statut INTO statutPortfolio
    FROM EvalPortFolio
    WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    SELECT Statut INTO statutStage
    FROM EvalStage
    WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

    IF NEW.Statut = 'BLOQUEE'AND statutPortfolio = 'BLOQUEE'AND statutStage = 'BLOQUEE' THEN
        SET NEW.Statut = 'REMONTEE';

        UPDATE EvalStage
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;

        UPDATE EvalPortFolio
        SET Statut = 'REMONTEE'
        WHERE anneeDebut = NEW.anneeDebut AND IdEtudiant = NEW.IdEtudiant;
    END IF;
END;
END;
SQL;

    $pdo->exec($createStage);
    $pdo->exec($createPortfolio);
    $pdo->exec($createAnglais);
}

function remontee_notes_areTriggersEnabled(PDO $pdo): bool {
        $triggers = ['remonteeStage', 'remonteePortfolio', 'remonteeAnglais'];
        $stmt = $pdo->query("SELECT DATABASE()");
        $dbName = $stmt->fetchColumn();
        
        foreach ($triggers as $trigger) {
            $sql = "SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_NAME = ? AND TRIGGER_SCHEMA = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$trigger, $dbName]);
            if ($stmt->fetchColumn() == 0) {
                return false;
            }
        }
        return true;
}
?>

