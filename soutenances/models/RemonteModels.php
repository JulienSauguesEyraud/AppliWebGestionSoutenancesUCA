<?php

// Activer ou désactiver l'event quotidien
function remonte_setEventStatus(PDO $pdo, $enabled = true) {
    $status = $enabled ? 'ENABLE' : 'DISABLE';
    $sql = "ALTER EVENT remonter_grilles_event $status;";
    return $pdo->exec($sql);
}

// Nouvelle gestion des triggers via DROP/CREATE pour les 3 triggers
function remonte_setTriggerStatus(PDO $pdo, $enabled = true) {
    // Noms des triggers à gérer
    $triggers = [
        'remonteeStage',
        'remonteePortfolio',
        'remonteeAnglais'
    ];

    // DROP pour tous les triggers
    foreach ($triggers as $trigger) {
        $dropSql = "DROP TRIGGER IF EXISTS $trigger;";
        $pdo->exec($dropSql);
    }

    if ($enabled) {
        // Création remonteeStage
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

        // Création remonteePortfolio
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

        // Création remonteeAnglais
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

        // Exécution des créations
        $pdo->exec($createStage);
        $pdo->exec($createPortfolio);
        $pdo->exec($createAnglais);
    }
    return true;
}

function remonte_isEventEnabled(PDO $pdo) {
    $sql = "SELECT STATUS FROM information_schema.EVENTS WHERE EVENT_NAME = 'remonter_grilles_event'";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();
    return isset($row['STATUS']) && $row['STATUS'] === 'ENABLED';
}

function remonte_areTriggersEnabled(PDO $pdo) {
    $triggers = [
        'remonteeStage',
        'remonteePortfolio',
        'remonteeAnglais'
    ];
    // Récupère le nom de la base courante
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