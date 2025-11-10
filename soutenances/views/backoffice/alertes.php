<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="index.php?action=accueil" class="btn btn-uca">
        <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
    </a>
    <h2 class="mb-0 text-primary">Gestion des Alertes</h2>
    <div class="text-end">
        <span class="badge bg-warning fs-6 p-2">
            <i class="fas fa-bell me-1"></i><?= count($alertes) ?> alerte(s)
        </span>
    </div>
</div>

<div class="card border-0 shadow">
    <div class="card-header bg-primary text-white py-3">
        <h3 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Soutenances nécessitant une attention</h3>
    </div>
    <div class="card-body p-4">
        <?php if (empty($alertes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4 class="text-success">Aucune alerte en cours</h4>
                <p class="text-muted">Toutes les évaluations sont à jour et traitées.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning border-0 mb-4">
                <i class="fas fa-info-circle me-2"></i>
                <strong><?= count($alertes) ?> alerte(s) nécessite(nt) votre attention immédiate</strong>
            </div>
            
            <div class="row g-4">
                <?php foreach ($alertes as $alerte): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-warning border-2">
                        <div class="card-header bg-warning bg-opacity-10 py-2">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-graduate text-warning me-2"></i>
                                <strong class="text-dark"><?= htmlspecialchars($alerte['etudiant_prenom'] . ' ' . $alerte['etudiant_nom']) ?></strong>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted fw-bold">Grille d'évaluation:</small>
                                <p class="mb-0"><?= htmlspecialchars($alerte['nomModuleGrilleEvaluation']) ?></p>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted fw-bold">Statut:</small>
                                <div>
                                    <span class="badge bg-warning"><?= htmlspecialchars($alerte['Statut']) ?></span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted fw-bold">Problème:</small>
                                <p class="text-danger mb-0 small"><?= htmlspecialchars($alerte['message']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>