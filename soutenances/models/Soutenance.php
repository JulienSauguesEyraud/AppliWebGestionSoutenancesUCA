<?php
/**
 * Utilitaires et opérations CRUD liées aux soutenances (STAGE et ANGLAIS).
 * Fournit des helpers de planification, modification, suppression et lecture.
 */
/**
 * Retourne l'année de début de l'année scolaire en cours.
 */
function scolarite_annee_debut(): int {
    $m = (int)date('m');
    return ($m <= 8) ? ((int)date('Y') - 1) : (int)date('Y');
}

/**
 * Liste les soutenances planifiées (STAGE et ANGLAIS) pour une année donnée.
 */
function soutenance_getSoutenancesPlanifiees(PDO $pdo, int $anneeDebut, string $jourPlanning = null): array {
    $sql = "
        SELECT
            'STAGE' AS type,
            es.IdEvalStage AS Id,
            es.date_h AS date,
            es.IdSalle AS IdSalle,
            sa.description AS salle,
            CONCAT(e.prenom, ' ', e.nom) AS etudiant,
            CONCAT(t.nom, ' ', t.prenom) AS tuteur,
            CONCAT(t2.nom, ' ', t2.prenom) AS secondEnseignant,
            60 AS duree
        FROM evalstage es
        JOIN etudiantsbut2ou3 e ON e.IdEtudiant = es.IdEtudiant
        JOIN salles sa ON sa.IdSalle = es.IdSalle
        LEFT JOIN enseignants t ON t.IdEnseignant = es.IdEnseignantTuteur
        LEFT JOIN enseignants t2 ON t2.IdEnseignant = es.IdSecondEnseignant
        WHERE es.anneeDebut = :anneeDebut
        UNION ALL
        SELECT
            'ANGLAIS' AS type,
            ea.IdEvalAnglais AS Id,
            ea.dateS AS date,
            ea.IdSalle AS IdSalle,
            sa.description AS salle,
            CONCAT(e.prenom, ' ', e.nom) AS etudiant,
            CONCAT(t.nom, ' ', t.prenom) AS tuteur,
            NULL AS secondEnseignant,
            20 AS duree
        FROM evalanglais ea
        JOIN etudiantsbut2ou3 e ON e.IdEtudiant = ea.IdEtudiant
        JOIN salles sa ON sa.IdSalle = ea.IdSalle
        JOIN enseignants t ON t.IdEnseignant = ea.IdEnseignant
        WHERE ea.anneeDebut = :anneeDebut
        ORDER BY date
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['anneeDebut' => $anneeDebut]);
    $soutenances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filtrer par jour si demandé
    if ($jourPlanning) {
        $soutenances = array_filter($soutenances, function($soutenance) use ($jourPlanning) {
            $date = new DateTime($soutenance['date'] ?? $soutenance['date_h'] ?? $soutenance['dateS']);
            return $date->format('Y-m-d') === $jourPlanning;
        });
    }

    return $soutenances;
}

/**
 * Récupère une soutenance par identifiant et type (STAGE|ANGLAIS).
 */
function soutenance_getSoutenanceById(PDO $pdo, int $id, string $type): ?array {
    if ($type === 'STAGE') {
        $stmt = $pdo->prepare("
            SELECT es.*,
                   CONCAT(e.prenom, ' ', e.nom) AS etudiant,
                   sa.description AS salle,
                   CONCAT(t.nom, ' ', t.prenom) AS tuteur,
                   CONCAT(t2.nom, ' ', t2.prenom) AS secondEnseignant
            FROM evalstage es
            JOIN etudiantsbut2ou3 e ON e.IdEtudiant = es.IdEtudiant
            JOIN salles sa ON sa.IdSalle = es.IdSalle
            LEFT JOIN enseignants t ON t.IdEnseignant = es.IdEnseignantTuteur
            LEFT JOIN enseignants t2 ON t2.IdEnseignant = es.IdSecondEnseignant
            WHERE es.IdEvalStage = ?
        ");
    } elseif ($type === 'ANGLAIS') {
        $stmt = $pdo->prepare("
            SELECT ea.*,
                   CONCAT(e.prenom, ' ', e.nom) AS etudiant,
                   sa.description AS salle,
                   CONCAT(t.nom, ' ', t.prenom) AS tuteur
            FROM evalanglais ea
            JOIN etudiantsbut2ou3 e ON e.IdEtudiant = ea.IdEtudiant
            JOIN salles sa ON sa.IdSalle = ea.IdSalle
            JOIN enseignants t ON t.IdEnseignant = ea.IdEnseignant
            WHERE ea.IdEvalAnglais = ?
        ");
    } else {
        return null;
    }
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Crée ou met à jour une soutenance selon le type fourni.
 * Pour STAGE, garantit l'existence des évaluations associées (Rapport, Portfolio, Soutenance).
 */
function soutenance_planifier(PDO $pdo, array $data): int {
    // Requiert a minima: type, IdEtudiant, anneeDebut, IdModeleEval, date_h (stage) ou dateS (anglais).
    $type = strtoupper(trim((string)($data['type'] ?? 'STAGE')));
    $idEtudiant = isset($data['IdEtudiant']) ? (int)$data['IdEtudiant'] : 0;
    $anneeDebut = isset($data['anneeDebut']) ? (int)$data['anneeDebut'] : (int)date('Y');
    $idModele = isset($data['IdModeleEval']) ? (int)$data['IdModeleEval'] : null;
    if ($idEtudiant <= 0 || $anneeDebut <= 0 || $idModele === null) {
        throw new InvalidArgumentException('Données incomplètes pour planifier une soutenance.');
    }

    try {
        $pdo->beginTransaction();

        if ($type === 'STAGE') {
            // Insertion (ou mise à jour) de la soutenance dans EvalStage
            $idSoutenance = !empty($data['IdSoutenance']) ? (int)$data['IdSoutenance'] : null;
            $date_h = $data['date_h'] ?? null;
            $tuteur = isset($data['IdTuteur']) ? (int)$data['IdTuteur'] : null;
            $second = isset($data['IdSecond']) ? (int)$data['IdSecond'] : null;
            $idSalle = isset($data['IdSalle']) ? trim((string)$data['IdSalle']) : null;
            $statut = $data['Statut'] ?? 'SAISIE';
            $presenceMaitre = (isset($data['presenceMaitreStageApp']) && (string)$data['presenceMaitreStageApp'] === '1') ? 1 : 0;
            $confidentiel = isset($data['confidentiel']) ? (bool)$data['confidentiel'] : null;
            $note = isset($data['note']) ? $data['note'] : null;
            $commentaire = isset($data['commentaireJury']) ? $data['commentaireJury'] : null;

            if ($idSoutenance === null) {
                $stmt = $pdo->prepare("
                    INSERT INTO EvalStage
                    (note, commentaireJury, presenceMaitreStageApp, confidentiel, date_h, IdEnseignantTuteur, Statut, IdSecondEnseignant, anneeDebut, IdModeleEval, IdEtudiant, IdSalle)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $note, $commentaire, $presenceMaitre, $confidentiel, $date_h, $tuteur, $statut, $second,
                    $anneeDebut, $idModele, $idEtudiant, $idSalle
                ]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare("
                    UPDATE EvalStage SET
                      note = ?, commentaireJury = ?, presenceMaitreStageApp = ?, confidentiel = ?, date_h = ?,
                      IdEnseignantTuteur = ?, Statut = ?, IdSecondEnseignant = ?, anneeDebut = ?, IdModeleEval = ?, IdEtudiant = ?, IdSalle = ?
                    WHERE IdEvalStage = ?
                ");
                $stmt->execute([
                    $note, $commentaire, $presenceMaitre, $confidentiel, $date_h, $tuteur, $statut, $second,
                    $anneeDebut, $idModele, $idEtudiant, $idSalle, $idSoutenance
                ]);
                $newId = $idSoutenance;
            }

            // Crée les évaluations associées si absentes pour l'étudiant/année/modèle
            $targets = [
                ['table' => 'EvalRapport', 'idcol' => 'IdEvalRapport'],
                ['table' => 'EvalPortFolio', 'idcol' => 'IdEvalPortfolio'],
                ['table' => 'EvalSoutenance', 'idcol' => 'IdEvalSoutenance']
            ];
            foreach ($targets as $t) {
                $existsStmt = $pdo->prepare("SELECT COUNT(*) FROM {$t['table']} WHERE IdEtudiant = ? AND anneeDebut = ? AND IdModeleEval = ?");
                $existsStmt->execute([$idEtudiant, $anneeDebut, $idModele]);
                $exists = (int)$existsStmt->fetchColumn();
                if ($exists === 0) {
                    // Insertion minimale avec Statut = SAISIE et clés requises.
                    if ($t['table'] === 'EvalRapport') {
                        $ins = $pdo->prepare("INSERT INTO EvalRapport (note, commentaireJury, Statut, anneeDebut, IdModeleEval, IdEtudiant) VALUES (?, ?, ?, ?, ?, ?)");
                        $ins->execute([null, null, 'SAISIE', $anneeDebut, getIdModeleEvalByName($pdo, 'RAPPORT'), $idEtudiant]);
                    } elseif ($t['table'] === 'EvalPortFolio') {
                        $ins = $pdo->prepare("INSERT INTO EvalPortFolio (note, commentaireJury, anneeDebut, IdModeleEval, IdEtudiant, Statut) VALUES (?, ?, ?, ?, ?, ?)");
                        $ins->execute([null, null, $anneeDebut, getIdModeleEvalByName($pdo, 'PORTFOLIO'), $idEtudiant, 'SAISIE']);
                    } elseif ($t['table'] === 'EvalSoutenance') {
                        $ins = $pdo->prepare("INSERT INTO EvalSoutenance (note, commentaireJury, anneeDebut, IdModeleEval, IdEtudiant, Statut) VALUES (?, ?, ?, ?, ?, ?)");
                        $ins->execute([null, null, $anneeDebut, getIdModeleEvalByName($pdo, 'SOUTENANCE'), $idEtudiant, 'SAISIE']);
                    }
                }
            }

            $pdo->commit();
            return $newId;
        } elseif ($type === 'ANGLAIS') {
            // Gestion ANGLAIS: insert/update dans EvalAnglais
            $idSoutenance = !empty($data['IdSoutenance']) ? (int)$data['IdSoutenance'] : null;
            $dateS = $data['date_h'] ?? $data['dateS'] ?? null;
            $idSalle = isset($data['IdSalle']) ? trim((string)$data['IdSalle']) : null;
            $idEns = isset($data['IdEnseignant']) ? (int)$data['IdEnseignant'] : null;
            $statut = $data['Statut'] ?? 'SAISIE';

            if ($idSoutenance === null) {
                $stmt = $pdo->prepare("
                    INSERT INTO EvalAnglais (dateS, note, commentaireJury, Statut, IdSalle, IdEnseignant, anneeDebut, IdModeleEval, IdEtudiant)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$dateS, null, null, $statut, $idSalle, $idEns, $anneeDebut, $idModele, $idEtudiant]);
                $newId = (int)$pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare("
                    UPDATE EvalAnglais SET dateS = ?, note = ?, commentaireJury = ?, Statut = ?, IdSalle = ?, IdEnseignant = ?, anneeDebut = ?, IdModeleEval = ?, IdEtudiant = ?
                    WHERE IdEvalAnglais = ?
                ");
                $stmt->execute([$dateS, null, null, $statut, $idSalle, $idEns, $anneeDebut, $idModele, $idEtudiant, $idSoutenance]);
                $newId = $idSoutenance;
            }

            $pdo->commit();
            return $newId;
        } else {
            // Type inconnu: rollback et erreur
            $pdo->rollBack();
            throw new InvalidArgumentException("Type de soutenance non supporté: {$type}");
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

/**
 * Met à jour une soutenance existante.
 */
function soutenance_modifier(PDO $pdo, int $id, string $type, array $data): void {
    if ($type === 'STAGE') {
            $stmt = $pdo->prepare("
                UPDATE evalstage
                SET IdSalle = ?, IdEnseignantTuteur = ?, IdSecondEnseignant = ?, date_h = ?, IdModeleEval = ?, presenceMaitreStageApp = ?
                WHERE IdEvalStage = ?
            ");
            $presence = (isset($data['presenceMaitreStageApp']) && (string)$data['presenceMaitreStageApp'] === '1') ? 1 : 0;
            $stmt->execute([
                $data['IdSalle'],
                $data['IdTuteur'] ?? ($data['IdEnseignant'] ?? null),
                $data['IdSecond'] ?? null,
                $data['date_h'],
                $data['IdModeleEval'] ?? null,
                $presence,
                $id
            ]);
    } elseif ($type === 'ANGLAIS') {
        $stmt = $pdo->prepare("
            UPDATE evalanglais
            SET IdSalle = ?, IdEnseignant = ?, dateS = ?, IdModeleEval = ?
            WHERE IdEvalAnglais = ?
        ");
        $stmt->execute([
            $data['IdSalle'],
            $data['IdEnseignant'] ?? ($data['IdTuteur'] ?? null),
            $data['date_h'],
            $data['IdModeleEval'] ?? null,
            $id
        ]);
    } else {
        throw new Exception('Type de soutenance non pris en charge');
    }
}

/**
 * Supprime une soutenance selon son type.
 */
function soutenance_supprimer(PDO $pdo, int $id, string $type): void {
    if ($type === 'STAGE') {
        $stmt = $pdo->prepare("DELETE FROM evalstage WHERE IdEvalStage = ?");
    } elseif ($type === 'ANGLAIS') {
        $stmt = $pdo->prepare("DELETE FROM evalanglais WHERE IdEvalAnglais = ?");
    } else {
        throw new Exception('Type de soutenance non pris en charge');
    }
    $stmt->execute([$id]);
}

/**
 * Vérifie si un créneau est libre pour une salle (et éventuellement exclut une soutenance en cours d'édition).
 */
function soutenance_estCreneauLibre(PDO $pdo, string $type, string $date_h, $idSalle, ?int $idEnseignant, ?int $idSecond = null, $idSoutenance = null): bool {
    if ($idSoutenance !== null && $idSoutenance !== '') {
        $idSoutenance = (int)$idSoutenance;
    }

    if ($type === 'STAGE') {
        $query = "SELECT COUNT(*) FROM EvalStage WHERE date_h = ? AND IdSalle = ?";
        $params = [$date_h, $idSalle];
        if ($idSoutenance !== null && $idSoutenance !== '') {
            $query .= " AND IdEvalStage != ?";
            $params[] = $idSoutenance;
        }
    } elseif ($type === 'ANGLAIS') {
        $query = "SELECT COUNT(*) FROM EvalAnglais WHERE dateS = ? AND IdSalle = ?";
        $params = [$date_h, $idSalle];
        if ($idSoutenance !== null && $idSoutenance !== '') {
            $query .= " AND IdEvalAnglais != ?";
            $params[] = $idSoutenance;
        }
    } else {
        return true;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return ((int)$stmt->fetchColumn()) === 0;
}


/**
 * Liste les étudiants sans soutenance de type STAGE pour l'année donnée.
 */
function soutenance_getEtudiantsSansSoutenance(PDO $pdo, int $anneeDebut): array {
    $stmt = $pdo->prepare("
        SELECT e.IdEtudiant, e.nom, e.prenom
        FROM etudiantsbut2ou3 e
        LEFT JOIN evalstage es ON e.IdEtudiant = es.IdEtudiant AND es.anneeDebut = ?
        WHERE es.IdEtudiant IS NULL
        ORDER BY e.nom, e.prenom
    ");
    $stmt->execute([$anneeDebut]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
