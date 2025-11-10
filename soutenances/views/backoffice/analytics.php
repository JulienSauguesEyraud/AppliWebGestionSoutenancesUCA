<?php
$totalStages = 0;
$regionsUniques = [];
foreach ($repartitionRegion as $annee => $regions) {
    foreach ($regions as $region => $count) {
        $totalStages += $count;
        $regionsUniques[$region] = true;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="index.php?action=accueil" class="btn btn-uca">
        <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
    </a>
    <h2 class="mb-0 text-primary">Analyses et Statistiques</h2>
    <div class="text-end">
        <span class="badge bg-info fs-6 p-2">
            <i class="fas fa-chart-bar me-1"></i><?= count($repartitionRegion) ?> année(s)
        </span>
    </div>
</div>

<!-- Cartes de statistiques -->
<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100 border-0 shadow">
            <div class="card-body text-center p-3">
                <i class="fas fa-users fa-2x mb-2"></i>
                <h3 class="mb-1"><?= $totalStages ?></h3>
                <p class="mb-0 opacity-75">Stages total</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white h-100 border-0 shadow">
            <div class="card-body text-center p-3">
                <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                <h3 class="mb-1"><?= count($repartitionRegion) ?></h3>
                <p class="mb-0 opacity-75">Années analysées</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white h-100 border-0 shadow">
            <div class="card-body text-center p-3">
                <i class="fas fa-map-marked-alt fa-2x mb-2"></i>
                <h3 class="mb-1"><?= count($regionsUniques) ?></h3>
                <p class="mb-0 opacity-75">Régions couvertes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100 border-0 shadow">
            <div class="card-body text-center p-3">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <h3 class="mb-1"><?= count($repartitionRegion) > 0 ? number_format($totalStages / count($repartitionRegion), 1) : 0 ?></h3>
                <p class="mb-0 opacity-75">Moyenne par an</p>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow">
    <div class="card-header bg-primary text-white py-3">
        <h3 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Répartition géographique des stages</h3>
    </div>
    <div class="card-body p-4">
        <?php if (!empty($repartitionRegion)): ?>
            <?php foreach ($repartitionRegion as $annee => $regions): ?>
                <div class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-primary mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Année <?= htmlspecialchars($annee) ?>
                        </h4>
                        <span class="badge bg-primary fs-6 p-2"><?= array_sum($regions) ?> stages</span>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th width="40%">
                                        <i class="fas fa-globe-europe me-2"></i>Région
                                    </th>
                                    <th width="20%" class="text-center">
                                        <i class="fas fa-chart-bar me-2"></i>Nombre
                                    </th>
                                    <th width="20%" class="text-center">
                                        <i class="fas fa-percentage me-2"></i>Pourcentage
                                    </th>
                                    <th width="20%">
                                        <i class="fas fa-chart-pie me-2"></i>Répartition
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalAnnee = array_sum($regions);
                                foreach ($regions as $region => $count): 
                                    $percentage = $totalAnnee > 0 ? round(($count / $totalAnnee) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                            <strong><?= htmlspecialchars($region) ?></strong>
                                        </td>
                                        <td class="text-center fw-bold fs-5"><?= $count ?></td>
                                        <td class="text-center fw-bold text-primary fs-5"><?= $percentage ?>%</td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-primary" 
                                                     style="width: <?= $percentage ?>%;"
                                                     role="progressbar">
                                                    <?= $percentage >= 10 ? $percentage . '%' : '' ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active fw-bold">
                                    <td><strong>Total</strong></td>
                                    <td class="text-center fs-5"><?= $totalAnnee ?></td>
                                    <td class="text-center fs-5">100%</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($annee !== array_key_last($repartitionRegion)): ?>
                    <hr class="my-4">
                <?php endif; ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-info-circle fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Aucune donnée disponible</h4>
                <p class="text-muted">Les données de répartition géographique apparaîtront après la saisie des stages.</p>
            </div>
        <?php endif; ?>
    </div>
</div>