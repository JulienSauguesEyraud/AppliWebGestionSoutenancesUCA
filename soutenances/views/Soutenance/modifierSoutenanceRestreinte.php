<?php
/**
 * Vue: Modifier (restreint) une soutenance
 * - Formulaire pour changer date/heure/salle (et présence maître de stage pour STAGE)
 * - Planning en lecture seule pour le jour choisi
 * - Endpoint AJAX (ajax=planning) qui renvoie uniquement le tableau planning
 */
// Rafraîchir uniquement le planning via AJAX
if (isset($_GET['ajax']) && $_GET['ajax'] === 'planning') {
    $typeAjax = strtoupper(trim((string)($_GET['type'] ?? 'STAGE')));
    $jp = $_GET['jourPlanning'] ?? date('Y-m-d');
    $sallesAjax = $salles ?? [];
    $soutsAjax = $soutenances ?? [];
    // Fallback: charger les soutenances si le contrôleur ne les a pas fournies
    $anneeDebutAjax = $_GET['anneeDebut'] ?? date('Y');
    if ((empty($soutsAjax) || !is_array($soutsAjax)) && isset($pdo) && function_exists('soutenance_getSoutenancesPlanifiees')) {
        try { $soutsAjax = soutenance_getSoutenancesPlanifiees($pdo, $anneeDebutAjax); } catch (Throwable $e) { /* ignore */ }
    }
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
    // Dédupliquer les salles (clé: IdSalle trim)
    $uniqueSalles = [];
    foreach ($sallesAjax as $s) {
        $k = trimId($s['IdSalle'] ?? '');
        if ($k === '') continue;
        if (!isset($uniqueSalles[$k])) $uniqueSalles[$k] = $s;
    }
    $sallesAjax = array_values($uniqueSalles);

    // Sortie tableau simple (sans titre) – styled table
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
$soutenanceModifiee = $soutenanceModifiee ?? [];
// prefer GET idSoutenance when provided
$idSoutenance = $_GET['idSoutenance'] ?? $_GET['IdSoutenance'] ?? $soutenanceModifiee['Id'] ?? $soutenanceModifiee['IdEvalStage'] ?? $soutenanceModifiee['IdEvalAnglais'] ?? '';
$IdEtudiant = $soutenanceModifiee['IdEtudiant'] ?? $_GET['idEtudiant'] ?? '';
$type = $soutenanceModifiee['type'] ?? $_GET['type'] ?? '';
// use stored date or fallback to today
$dateSoutenance = $soutenanceModifiee['date'] ?? $soutenanceModifiee['date_h'] ?? $soutenanceModifiee['stage_date_h'] ?? '';
// Accepte jourPlanning (préféré) ou jour
$jourPlanning = date('Y-m-d', strtotime($dateSoutenance ?: ($_GET['jourPlanning'] ?? $_GET['jour'] ?? date('Y-m-d'))));
$IdSalleSelectionnee = $soutenanceModifiee['IdSalle'] ?? $_GET['IdSalle'] ?? '';
$error = $_GET['error'] ?? null;
// contexte de base
$anneeDebut = $_GET['anneeDebut'] ?? ($anneeDebut ?? date('Y'));
$soutenances = $soutenances ?? [];
$anneeDebut = $_GET['anneeDebut'] ?? ($anneeDebut ?? date('Y'));
// fallback: charger les soutenances si vide
if ((empty($soutenances) || !is_array($soutenances)) && isset($pdo) && function_exists('soutenance_getSoutenancesPlanifiees')) {
    try { $soutenances = soutenance_getSoutenancesPlanifiees($pdo, $anneeDebut); } catch (Throwable $e) { $soutenances = []; }
}
$salles = $salles ?? [];

// helpers
if (!function_exists('trimId')) {
    function trimId($v): string { return $v === null ? '' : trim((string)$v); }
}
if (!function_exists('safeH')) {
    function safeH($v){ return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

// Salles uniques
$uniqueSalles = [];
foreach ($salles as $s) {
    $k = trimId($s['IdSalle'] ?? '');
    if ($k === '') continue;
    if (!isset($uniqueSalles[$k])) $uniqueSalles[$k] = $s;
}
$salles = array_values($uniqueSalles);

// slotMap pour rendu initial
$slotMap = [];
foreach ($soutenances as $s) {
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
$renderedSoutenances = [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier la salle et l'heure de la soutenance</title>
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
            <h1>Modifier la soutenance</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
    <?php if ($error === 'duplicate'): ?>
        <p class="error">Erreur : La salle sélectionnée est déjà occupée à cette date et heure.</p>
    <?php endif; ?>

    <div class="form-container">
    <form action="index.php?action=enregistrerModificationRestreinte" method="post">
        <input type="hidden" name="IdSoutenance" value="<?= safeH($idSoutenance) ?>">
        <input type="hidden" name="IdEtudiant" value="<?= safeH($IdEtudiant) ?>">
        <input type="hidden" name="type" value="<?= safeH($type) ?>">
        <input type="hidden" name="anneeDebut" value="<?= safeH($anneeDebut) ?>">

        <label>Date et Heure :</label>
        <div class="form-group-inline">
        <input type="date" id="jourPlanning" name="jourPlanning" value="<?= safeH($jourPlanning) ?>">
        <?php if (strtoupper($type) === 'STAGE'): ?>
            <select name="date_h" required>
                <?php for ($h = 8; $h <= 17; $h++):
                    $value = $jourPlanning . ' ' . sprintf('%02d:00:00', $h);
                    $display = sprintf('%02d:00', $h);
                    $selected = '';
                    if (!empty($dateSoutenance)) {
                        $compare = date('Y-m-d H:00:00', strtotime($dateSoutenance));
                        if ($compare === $value) $selected = 'selected';
                    }
                ?>
                    <option value="<?= safeH($value) ?>" <?= $selected ?>><?= safeH($display) ?></option>
                <?php endfor; ?>
            </select>
        <?php else: ?>
            <select name="date_h" required>
                <?php
                $start = new DateTime("$jourPlanning 08:00");
                $end = new DateTime("$jourPlanning 17:40");
                while ($start <= $end) {
                    $value = $start->format('Y-m-d H:i:s');
                    $display = $start->format('H:i');
                    $selected = ($dateSoutenance && (date('Y-m-d H:i:s', strtotime($dateSoutenance)) === $value)) ? 'selected' : '';
                    echo "<option value='".safeH($value)."' {$selected}>".safeH($display)."</option>";
                    $start->modify('+20 minutes');
                }
                ?>
            </select>
        <?php endif; ?>
        </div>

    <br><br>
    <?php if (strtoupper($type) === 'STAGE'): ?>
        <label for="presenceMaitreStageApp">Présence du maître de stage :</label>
        <?php $presenceVal = isset($soutenanceModifiee['presenceMaitreStageApp']) ? (int)$soutenanceModifiee['presenceMaitreStageApp'] : 0; ?>
        <select name="presenceMaitreStageApp" id="presenceMaitreStageApp">
            <option value="1" <?= ($presenceVal === 1) ? 'selected' : '' ?>>Oui</option>
            <option value="0" <?= ($presenceVal === 0) ? 'selected' : '' ?>>Non</option>
        </select>
        <br><br>
    <?php endif; ?>
    <label>Salle :</label>
        <select name="IdSalle" required>
            <?php foreach ($salles as $salle): ?>
                <option value="<?= safeH($salle['IdSalle'] ?? '') ?>"
                    <?= ($IdSalleSelectionnee == ($salle['IdSalle'] ?? '')) ? 'selected' : '' ?>>
                    <?= safeH(($salle['IdSalle'] ?? '') . ' - ' . ($salle['description'] ?? '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <div class="form-actions">
            <button type="submit">Enregistrer les modifications</button>
        </div>
    </form>
    </div>

    <h2>Planning <?= safeH(strtoupper($type) === 'ANGLAIS' ? 'ANGLAIS' : 'STAGE') ?> - <span id="planning-date-label"><?= safeH($jourPlanning) ?></span></h2>
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
                <?php if (strtoupper($type) === 'STAGE'):
                    for ($h=8; $h<=17; $h++):
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
                                <?php } ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; else:
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
                                    <?php } ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <script>
    // Met à jour le planning et la liste des heures lorsque la date change
    (function(){
        const dateInput = document.getElementById('jourPlanning');
        const type = '<?= safeH($type) ?>';
        const container = document.getElementById('planning-container');
        const label = document.getElementById('planning-date-label');
        const anneeDebut = '<?= safeH($anneeDebut) ?>';
        const idSoutenance = '<?= safeH($idSoutenance) ?>';

        async function refreshPlanning(dateStr){
            try {
                const params = new URLSearchParams({
                    action: 'modifierSoutenanceRestreinte',
                    ajax: 'planning',
                    type,
                    jourPlanning: dateStr,
                    anneeDebut,
                    idSoutenance
                });
                const resp = await fetch('index.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const html = await resp.text();
                container.innerHTML = html;
                if (label) label.textContent = dateStr;

                // mettre à jour les options d'heure
                const hourSelect = document.querySelector('select[name="date_h"]');
                if (hourSelect) {
                    hourSelect.innerHTML = '';
                    if (type === 'STAGE') {
                        for (let h=8; h<=17; h++) {
                            const hh = String(h).padStart(2,'0');
                            const value = `${dateStr} ${hh}:00:00`;
                            const opt = document.createElement('option');
                            opt.value = value;
                            opt.textContent = `${hh}:00`;
                            hourSelect.appendChild(opt);
                        }
                    } else {
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
                console.error('Refresh planning failed', e);
            }
        }
        if (dateInput) {
            dateInput.addEventListener('change', (e)=>{
                const val = e.target.value;
                if (val) refreshPlanning(val);
            });
        }
    })();
    </script>

    </div>
</body>
</html>
