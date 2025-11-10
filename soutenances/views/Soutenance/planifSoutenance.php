<?php
/**
 * Vue: Planifier/Modifier une soutenance
 * - Formulaire complet (tuteurs/second/enseignant anglais, présence maître, salle)
 * - Planning du jour (Stage: 1h, Anglais: 20 min)
 * - Endpoint AJAX (ajax=planning) pour rafraîchir le tableau planning
 */
?>
<?php
// Rafraîchir uniquement le planning sans recharger toute la page
if (isset($_GET['ajax']) && $_GET['ajax'] === 'planning') {
    $typeAjax = strtoupper(trim((string)($_GET['type'] ?? 'STAGE')));
    $jp = $_GET['jourPlanning'] ?? date('Y-m-d');
    $sallesAjax = $salles ?? [];
    $soutsAjax = $soutenances ?? [];
    if (!function_exists('trimId')) {
        function trimId($v): string { return $v === null ? '' : trim((string)$v); }
    }
    // Map indexée par "YYYY-MM-DD|HH:MM|IdSalle|TYPE"
    $slotMap = [];
    foreach ($soutsAjax as $s) {
        $typeS = strtoupper(trim((string)($s['type'] ?? ($s['Type'] ?? ''))));
        $dateRaw = $s['date'] ?? $s['date_h'] ?? $s['dateS'] ?? null;
        if (!$dateRaw) continue;
        try { $dt = new DateTime($dateRaw); } catch (Throwable $e) { continue; }
        $dateY = $dt->format('Y-m-d');
        $timeHM = $dt->format('H:i');
        if ($typeS === 'STAGE') { $timeHM = $dt->format('H:00'); }
        $idSalle = trimId($s['IdSalle'] ?? '');
        if ($idSalle === '') continue;
        $key = implode('|', [$dateY, $timeHM, $idSalle, $typeS]);
        if (!isset($slotMap[$key])) $slotMap[$key] = [];
        $slotMap[$key][] = $s;
    }

    // Dédupliquer les salles (même logique que la page complète)
    $uniqueSalles = [];
    foreach ($sallesAjax as $s) {
        $k = trimId($s['IdSalle'] ?? '');
        if ($k === '') continue;
        if (!isset($uniqueSalles[$k])) $uniqueSalles[$k] = $s;
    }
    $sallesAjax = array_values($uniqueSalles);

    // Rendu du tableau planning seul (sans titre, pour éviter doublon côté client) — styled table
    echo '<table class="table table-striped table-bordered align-middle mb-0"><thead><tr><th>Heure</th>';
    foreach ($sallesAjax as $salle) {
        echo '<th>'.htmlspecialchars(($salle['IdSalle'] ?? '') . ' : ' . ($salle['description'] ?? '')).'</th>';
    }
    echo '</tr></thead><tbody>';

    if ($typeAjax === 'STAGE') {
        for ($h=8; $h<=17; $h++) {
            $heure = sprintf('%02d:00', $h);
            echo '<tr><td>'.htmlspecialchars($heure).'</td>'; 
            foreach ($sallesAjax as $salle) {
                $key = implode('|', [$jp, $heure, trimId($salle['IdSalle'] ?? ''), 'STAGE']);
                $cells = $slotMap[$key] ?? [];
                echo '<td>';
                foreach ($cells as $sout) {
                    echo '<div class="soutenance stage">';
                    echo '<strong>Étudiant:</strong> '.htmlspecialchars($sout['etudiant'] ?? $sout['nom'] ?? '?').'<br>';
                    if (!empty($sout['tuteur'])) echo '<strong>Tuteur:</strong> '.htmlspecialchars($sout['tuteur']).'<br>';
                    if (!empty($sout['secondEnseignant'])) echo '<strong>Second:</strong> '.htmlspecialchars($sout['secondEnseignant']).'<br>';
                    echo '</div>';
                }
                echo '</td>';
            }
            echo '</tr>';
        }
    } else { // ANGLAIS
        $start = new DateTime("$jp 08:00");
        $end = new DateTime("$jp 17:40");
        $interval = new DateInterval('PT20M');
        $period = new DatePeriod($start, $interval, (clone $end)->add(new DateInterval('PT1M')));
        foreach ($period as $heureSlot) {
            $heure = $heureSlot->format('H:i');
            echo '<tr><td>'.htmlspecialchars($heure).'</td>';
            foreach ($sallesAjax as $salle) {
                $key = implode('|', [$jp, $heure, trimId($salle['IdSalle'] ?? ''), 'ANGLAIS']);
                $cells = $slotMap[$key] ?? [];
                echo '<td>';
                foreach ($cells as $sout) {
                    echo '<div class="soutenance anglais">';
                    echo '<strong>Étudiant:</strong> '.htmlspecialchars($sout['etudiant'] ?? $sout['nom'] ?? '?').'<br>';
                    if (!empty($sout['tuteur'])) echo '<strong>Enseignant:</strong> '.htmlspecialchars($sout['tuteur']).'<br>';
                    echo '</div>';
                }
                echo '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planifier une soutenance</title>
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if (isset($_SESSION['user_info'])): ?>
    <div class="topbar">
        <span></span>
        <a href="index.php?action=logout" class="btn btn-uca"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
    </div>
    <?php endif; ?>
    <div class="container-main">
        <div class="page-actions">
            <a class="btn-back" href="index.php?action=accueil">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Planifier une soutenance</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
<?php
$soutenanceModifiee = $soutenanceModifiee ?? [];
$idEtudiant = $idEtudiant ?? ($soutenanceModifiee['IdEtudiant'] ?? '');
$type = $type ?? ($soutenanceModifiee['type'] ?? 'STAGE');
$anneeDebut = $anneeDebut ?? date('Y');
$jourPlanning = $jourPlanning ?? date('Y-m-d');
$soutenances = $soutenances ?? [];
$salles = $salles ?? [];
$enseignants = $enseignants ?? [];

// helper utilitaires
if (!function_exists('trimId')) {
    function trimId($v): string { return $v === null ? '' : trim((string)$v); }
}
if (!function_exists('safeH')) {
    function safeH($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

// construire liste unique de salles indexée par IdSalle (trimé)
$uniqueSalles = [];
foreach ($salles as $s) {
    $k = trimId($s['IdSalle'] ?? '');
    if ($k === '') continue;
    if (!isset($uniqueSalles[$k])) $uniqueSalles[$k] = $s;
}
$salles = array_values($uniqueSalles);

// Construire une map pour accès direct par clé "YYYY-MM-DD|HH:MM|IdSalle"
$slotMap = []; // key => array of soutenances
foreach ($soutenances as $s) {
    $typeS = strtoupper(trim((string)($s['type'] ?? ($s['Type'] ?? ''))));
    // choisir champ date selon le type
    $dateRaw = $s['date'] ?? $s['date_h'] ?? $s['dateS'] ?? null;
    if (!$dateRaw) continue;
    try {
        $dt = new DateTime($dateRaw);
    } catch (Throwable $e) {
        continue;
    }
    $dateY = $dt->format('Y-m-d');
    $timeHM = $dt->format('H:i'); // minutes included for anglais
    if ($typeS === 'STAGE') {
        // stages are on full hours in this UI; normalize to HH:00
        $timeHM = $dt->format('H:00');
    }
    $idSalle = trimId($s['IdSalle'] ?? '');
    if ($idSalle === '') continue;
    $key = implode('|', [$dateY, $timeHM, $idSalle, $typeS]); // include type to avoid cross-type collisions
    if (!isset($slotMap[$key])) $slotMap[$key] = [];
    $slotMap[$key][] = $s;
}

// évite duplications lors du rendu (marque les soutenances déjà affichées)
$renderedSoutenances = [];

// Pré-remplissages (GET/POST) + value sourcing
$prefilledTuteur = $soutenanceModifiee['IdEnseignantTuteur'] ?? ($_POST['IdTuteur'] ?? $_GET['IdTuteur'] ?? null);
$prefilledSecond = $soutenanceModifiee['IdSecondEnseignant'] ?? ($_POST['IdSecond'] ?? $_GET['IdSecond'] ?? null);
$prefilledEnseignantAnglais = $soutenanceModifiee['IdEnseignant'] ?? ($_POST['IdEnseignant'] ?? $_GET['IdEnseignant'] ?? null);
$soutenanceId = null;
if(!empty($soutenanceModifiee)){
    if(($type) === 'STAGE') $soutenanceId = $soutenanceModifiee['IdEvalStage'] ?? null;
    if(($type) === 'ANGLAIS') $soutenanceId = $soutenanceModifiee['IdEvalAnglais'] ?? null;
}
?>
<h1>
    <?= $soutenanceId ? 'Modifier' : 'Planifier' ?> la soutenance
    <?php if (!empty($idEtudiant)): ?>
        de l’étudiant #<?= safeH($idEtudiant) ?>
    <?php endif; ?>
    (<?= safeH($type ?: 'Type inconnu') ?>)
    - Année <?= safeH($anneeDebut) ?>
</h1>

<div class="form-container">
<form action="index.php?action=enregistrerSoutenance" method="post">
    <input type="hidden" name="IdEtudiant" value="<?= safeH($idEtudiant) ?>">
    <input type="hidden" name="anneeDebut" value="<?= safeH($anneeDebut) ?>">
    <input type="hidden" name="type" value="<?= safeH($type) ?>">
    <?php if($soutenanceId): ?>
        <input type="hidden" name="IdSoutenance" value="<?= safeH($soutenanceId) ?>">
    <?php endif; ?>

    <label>Date et Heure :</label>
    <div class="form-group-inline">
    <input type="date" id="jourPlanning" name="jourPlanning" value="<?= safeH($jourPlanning) ?>">
    <?php if($type === 'STAGE'): ?>
        <select name="date_h" required>
            <?php
            for ($h=8; $h<=17; $h++) {
                $value = $jourPlanning . ' ' . sprintf('%02d:00:00', $h);
                $display = sprintf('%02d:00', $h);
                $selected = (!empty($soutenanceModifiee['date']) && date('H:i', strtotime($soutenanceModifiee['date'])) === $display) ? 'selected' : '';
                echo "<option value='".safeH($value)."' {$selected}>".safeH($display)."</option>";
            }
            ?>
        </select>
    <?php else: // ANGLAIS ?>
        <select name="date_h" required>
            <?php
            $start = new DateTime("$jourPlanning 08:00");
            $end = new DateTime("$jourPlanning 17:40");
            while($start <= $end){
                $value = $start->format('Y-m-d H:i:s');
                $display = $start->format('H:i');
                $selected = (!empty($soutenanceModifiee['dateS']) && $soutenanceModifiee['dateS'] === $value) ? 'selected' : '';
                echo "<option value='".safeH($value)."' {$selected}>".safeH($display)."</option>";
                $start->modify('+20 minutes');
            }
            ?>
        </select>
    <?php endif; ?>
    </div>

    <br><br>
    <?php if($type === 'STAGE'): ?>
        <label>Enseignant Tuteur :</label>
        <?php if (!empty($prefilledTuteur)): ?>
            <select name="IdTuteur" id="IdEnseignantTuteur" disabled>
                <?php foreach($enseignants as $ens): ?>
                    <option value="<?= safeH($ens['IdEnseignant']) ?>"
                        <?= ($prefilledTuteur == $ens['IdEnseignant']) ? 'selected' : '' ?>>
                        <?= safeH($ens['nom'].' '.$ens['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="IdTuteur" value="<?= safeH($prefilledTuteur) ?>">
        <?php else: ?>
            <select name="IdTuteur" id="IdEnseignantTuteur" required>
                <?php foreach($enseignants as $ens): ?>
                    <option value="<?= safeH($ens['IdEnseignant']) ?>"
                        <?= (!empty($soutenanceModifiee) && ($soutenanceModifiee['IdEnseignantTuteur'] ?? '') == $ens['IdEnseignant']) ? 'selected' : ''?>>
                        <?= safeH($ens['nom'].' '.$ens['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <br><br>

        <label>Second Enseignant :</label>
        <?php if (!empty($prefilledSecond)): ?>
            <select name="IdSecond" id="IdSecondEnseignant" disabled>
                <?php foreach($enseignants as $ens): ?>
                    <option value="<?= safeH($ens['IdEnseignant']) ?>"
                        <?= ($prefilledSecond == $ens['IdEnseignant']) ? 'selected' : '' ?>>
                        <?= safeH($ens['nom'].' '.$ens['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="IdSecond" value="<?= safeH($prefilledSecond) ?>">
        <?php else: ?>
            <select name="IdSecond" id="IdSecondEnseignant" required>
                <?php foreach($enseignants as $ens): ?>
                    <option value="<?= safeH($ens['IdEnseignant']) ?>"
                        <?= (!empty($soutenanceModifiee) && ($soutenanceModifiee['IdSecondEnseignant'] ?? '') == $ens['IdEnseignant']) ? 'selected' : ''?>>
                        <?= safeH($ens['nom'].' '.$ens['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input type="hidden" name="IdModeleEval" value="2">
        <br><br>
    <label>Présence du maître de stage :</label>
        <?php
            $presenceVal = null;
            if (!empty($soutenanceModifiee)) {
                $presenceVal = (int)($soutenanceModifiee['presenceMaitreStageApp'] ?? 0);
            }
            if (isset($_POST['presenceMaitreStageApp'])) {
                $presenceVal = ((string)$_POST['presenceMaitreStageApp'] === '1') ? 1 : 0;
            }
            if ($presenceVal === null) { $presenceVal = 0; }
        ?>
        <select name="presenceMaitreStageApp" id="presenceMaitreStageApp">
            <option value="1" <?= ($presenceVal === 1) ? 'selected' : '' ?>>Oui</option>
            <option value="0" <?= ($presenceVal === 0) ? 'selected' : '' ?>>Non</option>
        </select>
    <?php else: ?>
        <label>Enseignant :</label>
        <?php if (!empty($prefilledEnseignantAnglais)): ?>
            <select name="IdEnseignant" disabled>
                <?php foreach($enseignants as $ens): ?>
                    <option value="<?= safeH($ens['IdEnseignant']) ?>"
                        <?= ($prefilledEnseignantAnglais == $ens['IdEnseignant']) ? 'selected' : ''?>>
                        <?= safeH($ens['nom'].' '.$ens['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="IdEnseignant" value="<?= safeH($prefilledEnseignantAnglais) ?>">
        <?php else: ?>
            <select name="IdEnseignant" required>
                <?php foreach($enseignants as $ens): ?>
                    <option value="<?= safeH($ens['IdEnseignant']) ?>"
                        <?= (!empty($soutenanceModifiee) && ($soutenanceModifiee['IdEnseignant'] ?? '') == $ens['IdEnseignant']) ? 'selected' : ''?>>
                        <?= safeH($ens['nom'].' '.$ens['prenom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <input type="hidden" name="IdModeleEval" value="1">
    <?php endif; ?>

    <br><br>
    <label>Salle :</label>
    <select name="IdSalle" required>
        <?php foreach($salles as $salle): ?>
            <option value="<?= safeH($salle['IdSalle']) ?>"
                <?= (!empty($soutenanceModifiee) && trimId($soutenanceModifiee['IdSalle'] ?? '') === trimId($salle['IdSalle'])) ? 'selected' : ''?>>
                <?= safeH(($salle['IdSalle'] ?? '') . ' - ' . ($salle['description'] ?? '')) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <div class="form-actions">
        <button type="submit"><?= $soutenanceId ? 'Modifier' : 'Planifier' ?></button>
    </div>
</form>
</div>

<?php if($soutenanceId): ?>
    <hr>
    <h2>Supprimer cette soutenance</h2>
    <form action="index.php?action=supprimerSoutenance" method="post">
        <input type="hidden" name="IdSoutenance" value="<?= safeH($soutenanceId) ?>">
        <input type="hidden" name="type" value="<?= safeH($type) ?>">
        <button type="submit" style="background:#c62828;color:#fff;">Supprimer</button>
    </form>
<?php endif; ?>

<h2>Planning <?= safeH($type) ?> - <span id="planning-date-label"><?= safeH($jourPlanning) ?></span></h2>
<div id="planning-container" class="table-card">
<div class="table-responsive">
<table class="table table-striped table-bordered align-middle mb-0">
    <thead>
        <tr>
            <th>Heure</th>
            <?php foreach($salles as $salle): ?>
                <th><?= safeH(($salle['IdSalle'] ?? '') . ' : ' . ($salle['description'] ?? '')) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php
        if($type === 'STAGE'){
            for($h=8; $h<=17; $h++):
                $heure = sprintf('%02d:00', $h);
                ?>
                <tr>
                    <td><?= safeH($heure) ?></td>
                    <?php foreach($salles as $salle): ?>
                        <td>
                            <?php
                            $key = implode('|', [$jourPlanning, $heure, trimId($salle['IdSalle'] ?? ''), 'STAGE']);
                            $cells = $slotMap[$key] ?? [];
                            foreach ($cells as $sout) {
                                $soutId = $sout['IdEvalStage'] ?? $sout['Id'] ?? null;
                                if ($soutId !== null) {
                                    $sid = (string)$soutId;
                                    if (in_array($sid, $renderedSoutenances, true)) continue;
                                    $renderedSoutenances[] = $sid;
                                }
                                ?>
                                <div class="soutenance stage">
                                    <strong>Étudiant:</strong> <?= safeH($sout['etudiant'] ?? $sout['nom'] ?? '?') ?><br>
                                    <?php if(!empty($sout['tuteur'])): ?><strong>Tuteur:</strong> <?= safeH($sout['tuteur']) ?><br><?php endif; ?>
                                    <?php if(!empty($sout['secondEnseignant'])): ?><strong>Second:</strong> <?= safeH($sout['secondEnseignant']) ?><br><?php endif; ?>
                                </div>
                                <?php
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endfor;
        } else { // ANGLAIS
            $start = new DateTime("$jourPlanning 08:00");
            $end = new DateTime("$jourPlanning 17:40");
            $interval = new DateInterval('PT20M');
            $period = new DatePeriod($start, $interval, (clone $end)->add(new DateInterval('PT1M')));
            foreach($period as $heureSlot):
                $heure = $heureSlot->format('H:i'); ?>
                <tr>
                    <td><?= safeH($heure) ?></td>
                    <?php foreach($salles as $salle): ?>
                        <td>
                            <?php
                            $key = implode('|', [$jourPlanning, $heure, trimId($salle['IdSalle'] ?? ''), 'ANGLAIS']);
                            $cells = $slotMap[$key] ?? [];
                            foreach ($cells as $sout) {
                                $soutId = $sout['IdEvalAnglais'] ?? $sout['Id'] ?? null;
                                if ($soutId !== null) {
                                    $sid = (string)$soutId;
                                    if (in_array($sid, $renderedSoutenances, true)) continue;
                                    $renderedSoutenances[] = $sid;
                                }
                                ?>
                                <div class="soutenance anglais">
                                    <strong>Étudiant:</strong> <?= safeH($sout['etudiant'] ?? $sout['nom'] ?? '?') ?><br>
                                    <?php if(!empty($sout['tuteur'])): ?><strong>Enseignant:</strong> <?= safeH($sout['tuteur']) ?><br><?php endif; ?>
                                </div>
                                <?php
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach;
        }
        ?>
    </tbody>
        </table>
        </div>
</div>
<script>
// Met à jour le planning sous le formulaire quand on change la date, sans reload
(function(){
    const dateInput = document.getElementById('jourPlanning');
    const type = '<?= safeH($type) ?>';
    const container = document.getElementById('planning-container');
    const label = document.getElementById('planning-date-label');
    const baseAction = '<?= htmlspecialchars($_GET['action'] ?? 'planifierSoutenance', ENT_QUOTES) ?>';
    const anneeDebut = '<?= safeH($anneeDebut) ?>';
    const idEtudiant = '<?= safeH($idEtudiant) ?>';
    if (!dateInput || !container) return;

    async function refreshPlanning(dateStr){
        try {
            const params = new URLSearchParams({
                action: baseAction,
                ajax: 'planning',
                type,
                jourPlanning: dateStr,
                anneeDebut,
                idEtudiant
            });
            const resp = await fetch('index.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const html = await resp.text();
            container.innerHTML = html;
            if (label) label.textContent = dateStr;
            // Met à jour aussi les options de l'heure selon la date sélectionnée
            const hourSelect = document.querySelector('select[name="date_h"]');
            if (hourSelect) {
                if (type === 'STAGE') {
                    hourSelect.innerHTML = '';
                    for (let h=8; h<=17; h++) {
                        const hh = String(h).padStart(2,'0');
                        const value = `${dateStr} ${hh}:00:00`;
                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = `${hh}:00`;
                        hourSelect.appendChild(opt);
                    }
                } else { // ANGLAIS 20 min slots
                    hourSelect.innerHTML = '';
                    let d = new Date(dateStr + 'T08:00:00');
                    const end = new Date(dateStr + 'T17:40:00');
                    while (d <= end) {
                        const hh = String(d.getHours()).padStart(2,'0');
                        const mm = String(d.getMinutes()).padStart(2,'0');
                        const value = `${dateStr} ${hh}:${mm}:00`;
                        const opt = document.createElement('option');
                        opt.value = value;
                        opt.textContent = `${hh}:${mm}`;
                        hourSelect.appendChild(opt);
                        d = new Date(d.getTime() + 20*60000);
                    }
                }
            }
        } catch(e) {
            console.error('Refresh planif planning failed', e);
        }
    }

    dateInput.addEventListener('change', (e)=>{
        const val = e.target.value;
        if (val) refreshPlanning(val);
    });
})();
</script>
<script>
// Empêche de sélectionner le même enseignant comme Tuteur et Second
(function(){
    const selectTuteur = document.getElementById('IdEnseignantTuteur');
    const selectSecond = document.getElementById('IdSecondEnseignant');
    if (!selectTuteur || !selectSecond) return;
    function filtrerSecond(){
        const tuteur = selectTuteur.value;
        Array.from(selectSecond.options).forEach(opt => {
            opt.hidden = (opt.value === tuteur && opt.value !== '');
        });
        // si la sélection actuelle est masquée, choisir la première option non masquée
        if (!selectSecond.value || selectSecond.options[selectSecond.selectedIndex]?.hidden) {
            let found = false;
            for (const opt of selectSecond.options) {
                if (!opt.hidden) { opt.selected = true; found = true; break; }
            }
            selectSecond.disabled = !found;
        } else {
            selectSecond.disabled = false;
        }
    }
    selectTuteur.addEventListener('change', filtrerSecond);
    // Initialiser au chargement
    filtrerSecond();
})();
</script>
    </div>
</body>
</html>
