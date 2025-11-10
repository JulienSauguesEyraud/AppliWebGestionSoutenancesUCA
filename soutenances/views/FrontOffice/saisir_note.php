<?php

// Vérification de session
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php?action=login');
    exit;
}
$enseignant_id = $_SESSION['user_id'];

// Connexion PDO
try {
    $pdo = new PDO('mysql:host=localhost;dbname=soutenances;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur base de données : ' . $e->getMessage());
}

// Récupération des paramètres GET
$id_etudiant = $_GET['id_etudiant'] ?? null;
$type_note = $_GET['type_note'] ?? null;
$mode = $_GET['mode'] ?? null; // 'affichage' pour lecture seule
$isTuteur=$_GET['isTuteur'] ?? null;

if (!$id_etudiant || !$type_note) {
    die('Paramètres manquants : id_etudiant ou type_note.');
}

// Mapping des types de notes aux tables correspondantes (respect des noms exacts du schéma)
$maps = [
    'portfolio' => [
        'eval_table' => 'EvalPortFolio',
        'notes_table' => 'LesCriteresNotesPortFolio',
        'nature' => 'PORTFOLIO',
        'id_field' => 'IdEvalPortfolio'
    ],
    'stage' => [
        'eval_table' => 'EvalStage',
        'notes_table' => 'LesCriteresNotesStage',
        'nature' => 'STAGE',
        'id_field' => 'IdEvalStage'
    ],
    'rapport' => [
        'eval_table' => 'EvalRapport',
        'notes_table' => 'LesCriteresNotesRapport',
        'nature' => 'RAPPORT',
        'id_field' => 'IdEvalRapport'
    ],
    'soutenance' => [
        'eval_table' => 'EvalSoutenance',
        'notes_table' => 'LesCriteresNotesSoutenance',
        'nature' => 'SOUTENANCE',
        'id_field' => 'IdEvalSoutenance'
    ],
    'anglais' => [
        'eval_table' => 'EvalAnglais',
        'notes_table' => 'LesCriteresNotesAnglais',
        'nature' => 'ANGLAIS',
        'id_field' => 'IdEvalAnglais'
    ],
];

if (!isset($maps[$type_note])) {
    die('Type de note invalide.');
}

$eval_table = $maps[$type_note]['eval_table'];
$notes_table = $maps[$type_note]['notes_table'];
$nature = $maps[$type_note]['nature'];
$id_field = $maps[$type_note]['id_field'];

// Récupération de l'évaluation pour cet étudiant et ce type
$sql_eval = "SELECT * FROM $eval_table WHERE IdEtudiant = :id_etudiant";
$stmt_eval = $pdo->prepare($sql_eval);
$stmt_eval->execute(['id_etudiant' => $id_etudiant]);
$eval = $stmt_eval->fetch(PDO::FETCH_ASSOC);

if (!$eval) {
    die('Évaluation non trouvée pour cet étudiant et ce type de note.');
}

$id_eval = $eval[$id_field];
$statut = $eval['Statut'];
$anneeDebut = $eval['anneeDebut'];
$id_modele = $eval['IdModeleEval'];
$commentaire = $eval['commentaireJury'] ?? '';

// Détermination du rôle de l'enseignant et si la grille est modifiable (respect des RG du PDF)
$is_tuteur = false;
$is_second = false;
$is_anglais_eval = false;
$editable = false;

if ($type_note === 'anglais') {
    if ($eval['IdEnseignant'] == $enseignant_id) {
        $is_anglais_eval = true;
    }
} else {
    // Pour les autres types, vérifier via EvalStage (car rôles définis là)
    $sql_role = "SELECT IdEnseignantTuteur, IdSecondEnseignant FROM EvalStage WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
    $stmt_role = $pdo->prepare($sql_role);
    $stmt_role->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
    $role = $stmt_role->fetch(PDO::FETCH_ASSOC);
    if ($role) {
        if ($role['IdEnseignantTuteur'] == $enseignant_id) {
            $is_tuteur = true;
        } elseif ($role['IdSecondEnseignant'] == $enseignant_id) {
            $is_second = true;
        }
    }
}

// Logique de modifiabilité : modifiable seulement pour le tuteur principal ou évaluateur anglais
if ($mode !== 'affichage' && !$is_second) { // Second tuteur ne peut jamais modifier
    if ($type_note === 'anglais') {
        if ($is_anglais_eval && in_array($statut, ['SAISIE', 'VALIDEE']) && !in_array($statut, ['REMONTEE', 'DIFFUSEE'])) {
            $editable = true;
        }
    } else {
        if ($is_tuteur && in_array($statut, ['SAISIE', 'VALIDEE']) && !in_array($statut, ['REMONTEE', 'DIFFUSEE'])) {
            $editable = true;
        }
    }
}

// Récupération des sections de la grille
$sql_sections = "
    SELECT se.IdSection, sce.titre, sce.description
    FROM SectionsEval se
    JOIN SectionCritereEval sce ON se.IdSection = sce.IdSection
    WHERE se.IdModeleEval = :id_modele
";
$stmt_sections = $pdo->prepare($sql_sections);
$stmt_sections->execute(['id_modele' => $id_modele]);
$sections = $stmt_sections->fetchAll(PDO::FETCH_ASSOC);

// Récupération des critères par section + notes existantes
$criteria = [];
$sum_notes = 0;
$sum_max = 0;
foreach ($sections as $section) {
    $sql_criteria = "
        SELECT ce.IdCritere, ce.descCourte, ce.descLongue, scc.ValeurMaxCritereEVal AS max_note
        FROM SectionContenirCriteres scc
        JOIN CriteresEval ce ON scc.IdCritere = ce.IdCritere
        WHERE scc.IdSection = :id_section
    ";
    $stmt_criteria = $pdo->prepare($sql_criteria);
    $stmt_criteria->execute(['id_section' => $section['IdSection']]);
    $crits = $stmt_criteria->fetchAll(PDO::FETCH_ASSOC);

    $section_criteria = [];
    foreach ($crits as $crit) {
        // Récupération de la note existante
        $sql_note = "SELECT noteCritere FROM $notes_table WHERE IdCritere = :id_crit AND $id_field = :id_eval";
        $stmt_note = $pdo->prepare($sql_note);
        $stmt_note->execute(['id_crit' => $crit['IdCritere'], 'id_eval' => $id_eval]);
        $note = $stmt_note->fetchColumn();
        $crit['note'] = $note !== false ? $note : '';

        // Cas spécial pour type 'stage' : auto-remplissage pour certains critères (respect RG PDF)
        $crit['auto'] = false;
        $crit['readonly'] = false;
        if ($type_note === 'stage') {
            if (stripos($crit['descCourte'], 'entreprise') !== false) {
                $sql_ent_note = "SELECT noteEntreprise FROM AnneeStage WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                $stmt_ent = $pdo->prepare($sql_ent_note);
                $stmt_ent->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
                $crit['note'] = $stmt_ent->fetchColumn() ?? '';
                $crit['auto'] = true;
                $crit['readonly'] = true; // Non modifiable (RG PDF)
            } elseif (stripos($crit['descCourte'], 'soutenance') !== false) {
                $sql_sout_note = "SELECT note FROM EvalSoutenance WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                $stmt_sout = $pdo->prepare($sql_sout_note);
                $stmt_sout->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
                $crit['note'] = $stmt_sout->fetchColumn() ?? '';
                $crit['auto'] = true;
                $crit['readonly'] = true;
            } elseif (stripos($crit['descCourte'], 'rapport') !== false) {
                $sql_rap_note = "SELECT note FROM EvalRapport WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                $stmt_rap = $pdo->prepare($sql_rap_note);
                $stmt_rap->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
                $crit['note'] = $stmt_rap->fetchColumn() ?? '';
                $crit['auto'] = true;
                $crit['readonly'] = true;
            } elseif (stripos($crit['descCourte'], 'tuteur') !== false) {
                // Note tuteur : modifiable par tuteur
                $crit['auto'] = false;
            }
        }

        // Calcul pour note finale (cumul pour affichage)
        if (is_numeric($crit['note'])) {
            $sum_notes += (float)$crit['note'];
        }
        $sum_max += (float)$crit['max_note'];

        $section_criteria[] = $crit;
    }

    $criteria[$section['IdSection']] = [
        'titre' => $section['titre'],
        'description' => $section['description'],
        'crits' => $section_criteria
    ];
}

// Récupération de noteMaxGrille
$sql_max_grille = "SELECT noteMaxGrille FROM ModelesGrilleEval WHERE IdModeleEval = :id_modele";
$stmt_max_grille = $pdo->prepare($sql_max_grille);
$stmt_max_grille->execute(['id_modele' => $id_modele]);
$note_max_grille = $stmt_max_grille->fetchColumn() ?? 20;

// Calcul note finale actuelle (RG PDF : sum notes / sum max * noteMaxGrille)
$note_finale = ($sum_max > 0) ? round(($sum_notes / $sum_max) * $note_max_grille, 2) : 0;

// Traitement POST : Enregistrement / Validation / Blocage (respect RG PDF)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $editable) {
    $pdo->beginTransaction();
    try {
        // Mise à jour des notes (seulement les non-auto)
        foreach ($criteria as $sec) {
            foreach ($sec['crits'] as $crit) {
                if ($crit['auto'] || $crit['readonly']) continue;
                $id_crit = $crit['IdCritere'];
                $note_post = $_POST["note_$id_crit"] ?? '';
                if ($note_post !== '' && is_numeric($note_post) && $note_post >= 0 && $note_post <= $crit['max_note']) {
                    // UPSERT (INSERT ou UPDATE)
                    $sql_upsert = "
                        INSERT INTO $notes_table (IdCritere, $id_field, noteCritere)
                        VALUES (:id_crit, :id_eval, :note)
                        ON DUPLICATE KEY UPDATE noteCritere = :note
                    ";
                    $stmt_upsert = $pdo->prepare($sql_upsert);
                    $stmt_upsert->execute(['id_crit' => $id_crit, 'id_eval' => $id_eval, 'note' => $note_post]);
                }
            }
        }

        // Mise à jour commentaire
        $comment_post = $_POST['commentaire'] ?? '';
        $sql_update_comm = "UPDATE $eval_table SET commentaireJury = :comm WHERE $id_field = :id_eval";
        $stmt_comm = $pdo->prepare($sql_update_comm);
        $stmt_comm->execute(['comm' => $comment_post, 'id_eval' => $id_eval]);

        // Recalcul note finale et mise à jour
        $new_sum_notes = 0;
        $new_sum_max = 0;
        foreach ($criteria as $sec) {
            foreach ($sec['crits'] as $crit) {
                $note_used = $_POST["note_{$crit['IdCritere']}"] ?? $crit['note'];
                if (is_numeric($note_used)) $new_sum_notes += (float)$note_used;
                $new_sum_max += (float)$crit['max_note'];
            }
        }
        $new_note_finale = ($new_sum_max > 0) ? ($new_sum_notes / $new_sum_max) * $note_max_grille : 0;
        $sql_update_note = "UPDATE $eval_table SET note = :note WHERE $id_field = :id_eval";
        $stmt_note = $pdo->prepare($sql_update_note);
        $stmt_note->execute(['note' => $new_note_finale, 'id_eval' => $id_eval]);

        // Logique validation / blocage (RG spécifiques PDF)
        if (isset($_POST['valider'])) {
            if ($type_note === 'stage') {
                // Vérifier si rapport et soutenance sont VALIDEE (RG PDF)
                $sql_check_rap = "SELECT Statut FROM EvalRapport WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                $stmt_check_rap = $pdo->prepare($sql_check_rap);
                $stmt_check_rap->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
                $stat_rap = $stmt_check_rap->fetchColumn();

                $sql_check_sout = "SELECT Statut FROM EvalSoutenance WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                $stmt_check_sout = $pdo->prepare($sql_check_sout);
                $stmt_check_sout->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
                $stat_sout = $stmt_check_sout->fetchColumn();

                if ($stat_rap === 'VALIDEE' && $stat_sout === 'VALIDEE') {
                    // Bloquer les 3 grilles
                    $sql_block_stage = "UPDATE EvalStage SET Statut = 'BLOQUEE' WHERE $id_field = :id_eval";
                    $pdo->prepare($sql_block_stage)->execute(['id_eval' => $id_eval]);

                    $sql_block_rap = "UPDATE EvalRapport SET Statut = 'BLOQUEE' WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                    $pdo->prepare($sql_block_rap)->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);

                    $sql_block_sout = "UPDATE EvalSoutenance SET Statut = 'BLOQUEE' WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
                    $pdo->prepare($sql_block_sout)->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
                } else {
                    throw new Exception('Impossible de valider : les grilles de rapport et soutenance doivent être validées.');
                }
            } else {
                // Pour les autres types
                $new_statut = ($type_note === 'portfolio') ? 'BLOQUEE' : 'VALIDEE';
                $sql_valider = "UPDATE $eval_table SET Statut = :new_statut WHERE $id_field = :id_eval";
                $stmt_valider = $pdo->prepare($sql_valider);
                $stmt_valider->execute(['new_statut' => $new_statut, 'id_eval' => $id_eval]);
            }
        }

        $pdo->commit();
        // Après sauvegarde/validation, décider de la redirection
        if (isset($_POST['valider'])) {
            // Après validation, retour à la liste (pageB)
            header('Location: ../../index.php?action=Evaluation');
        } else {
            // Après simple enregistrement, rester sur la page
            header("Location: {$_SERVER['REQUEST_URI']}");
        }
        exit;
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Erreur lors de la sauvegarde : ' . $e->getMessage();
    }
}

// Traitement déblocage séparé (peut être fait même si non editable, si BLOQUEE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['debloquer']) && $is_tuteur && $statut === 'BLOQUEE' && !in_array($statut, ['REMONTEE', 'DIFFUSEE'])) {
    $pdo->beginTransaction();
    try {
        if ($type_note === 'stage') {
            // Débloquer stage + rapport + soutenance (RG PDF)
            $sql_deblock_stage = "UPDATE EvalStage SET Statut = 'SAISIE' WHERE $id_field = :id_eval";
            $pdo->prepare($sql_deblock_stage)->execute(['id_eval' => $id_eval]);

            $sql_deblock_rap = "UPDATE EvalRapport SET Statut = 'SAISIE' WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
            $pdo->prepare($sql_deblock_rap)->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);

            $sql_deblock_sout = "UPDATE EvalSoutenance SET Statut = 'SAISIE' WHERE IdEtudiant = :id_etudiant AND anneeDebut = :annee";
            $pdo->prepare($sql_deblock_sout)->execute(['id_etudiant' => $id_etudiant, 'annee' => $anneeDebut]);
        } elseif ($type_note === 'portfolio') {
            $sql_deblock_port = "UPDATE EvalPortFolio SET Statut = 'SAISIE' WHERE $id_field = :id_eval";
            $pdo->prepare($sql_deblock_port)->execute(['id_eval' => $id_eval]);
        }
    $pdo->commit();
    // Après déblocage, retour à la liste (pageB)
    header('Location: ../../index.php?action=Evaluation');
    exit;
    } catch (Exception $e) {
        $pdo->rollback();
        $error = 'Erreur lors du déblocage : ' . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisir/Afficher Note - <?= ucfirst($type_note) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/style/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f9fa; }
        .container { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,0.07); padding: 32px; }
        h1 { color: #1976d2; font-size: 2rem; margin-bottom: 24px; }
        h2 { color: #333; font-size: 1.4rem; margin-top: 32px; }
        .form-group { margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 8px; }
        input[type="number"], textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        textarea { height: 100px; }
        .note-finale { font-size: 1.2rem; color: #1976d2; margin-top: 24px; }
        .error { color: red; margin-bottom: 16px; }
    /* Harmonisation boutons avec charte UCA (sans supprimer les styles existants) */
    button { padding: 10px 20px; background-color: var(--uca-blue); color: white; border: none; border-radius: 8px; cursor: pointer; margin-right: 10px; }
    button:hover { background-color: var(--uca-dark-blue); }
        button:disabled { background-color: #ccc; cursor: not-allowed; }
        .readonly { color: #555; font-style: italic; }
        .retour-btn { background-color: #6c757d; }
        .retour-btn:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Saisie/Affichage des notes pour <?= ucfirst($type_note) ?> - Étudiant <?= $id_etudiant ?></h1>
        <p>Statut actuel : <?= htmlspecialchars($statut) ?></p>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($criteria as $sec): ?>
                <h2><?= htmlspecialchars($sec['titre']) ?></h2>
                <p><?= htmlspecialchars($sec['description'] ?? '') ?></p>
                <?php foreach ($sec['crits'] as $crit): ?>
                    <div class="form-group">
                        <label><?= htmlspecialchars($crit['descCourte']) ?> (max : <?= $crit['max_note'] ?>)</label>
                        <p class="readonly"><?= htmlspecialchars($crit['descLongue'] ?? '') ?></p>
                        <?php if ($editable && !$crit['readonly']): ?>
                            <input type="number" step="0.5" min="0" max="<?= $crit['max_note'] ?>" name="note_<?= $crit['IdCritere'] ?>" value="<?= htmlspecialchars($crit['note']) ?>">
                        <?php else: ?>
                            <p class="readonly">Note : <?= htmlspecialchars($crit['note'] !== '' ? $crit['note'] : 'Non saisie') ?> <?= $crit['auto'] ? '(auto-rempli)' : '' ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="form-group">
                <label>Commentaire du jury</label>
                <?php if ($editable): ?>
                    <textarea name="commentaire"><?= htmlspecialchars($commentaire) ?></textarea>
                <?php else: ?>
                    <p class="readonly"><?= htmlspecialchars($commentaire ?: 'Aucun commentaire') ?></p>
                <?php endif; ?>
            </div>

            <?php if ($editable): ?>
                <button type="submit">Enregistrer</button>
                <button type="submit" name="valider" value="1">Valider</button>
            <?php endif; ?>
            <?php if ($statut === 'BLOQUEE' && $is_tuteur && ($type_note === 'stage' || $type_note === 'portfolio') && !in_array($statut, ['REMONTEE', 'DIFFUSEE'])): ?>
                <button type="submit" name="debloquer" value="1">Débloquer</button>
            <?php endif; ?>
            <a href="../../index.php?action=Evaluation"><button type="button" class="retour-btn">Retour</button></a>
        </form>

        <p class="note-finale">Note finale calculée : <?= $note_finale ?> / <?= $note_max_grille ?></p>
    </div>
</body>
</html>