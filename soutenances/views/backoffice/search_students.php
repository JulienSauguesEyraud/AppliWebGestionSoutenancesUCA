<div class="d-flex justify-content-between align-items-center mb-4">
    <a href="index.php?action=accueil" class="btn btn-uca">
        <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
    </a>
    <h2 class="mb-0">Recherche d'étudiants</h2>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="POST" action="index.php?action=search-students">
            <div class="input-group">
                <input type="text" class="form-control" name="search" 
                       placeholder="Rechercher un étudiant par nom ou prénom" 
                       value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="btn btn-uca">Rechercher</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($searchTerm)): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Résultats pour "<?= htmlspecialchars($searchTerm) ?>"</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($searchResults)): ?>
                <?php foreach ($searchResults as $student): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5><?= htmlspecialchars($student['prenom'] . ' ' . $student['nom']) ?></h5>
                        <p><strong>Email:</strong> <?= htmlspecialchars($student['mail']) ?></p>
                        <?php if (!empty($student['nomModuleGrilleEvaluation'])): ?>
                            <p><strong>Dernière évaluation:</strong> <?= htmlspecialchars($student['nomModuleGrilleEvaluation']) ?></p>
                            <p><strong>Statut:</strong> <?= htmlspecialchars($student['Statut']) ?></p>
                            <p><strong>Note:</strong> <?= htmlspecialchars($student['note'] ?? 'Non noté') ?></p>
                        <?php endif; ?>
                        <a href="index.php?action=fiche-notes&id=<?= $student['IdEtudiant'] ?>" class="btn btn-uca btn-sm">Voir la fiche complète</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">Aucun étudiant trouvé pour votre recherche.</p>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle me-2"></i>
        Utilisez le champ de recherche pour trouver des étudiants.
    </div>
<?php endif; ?>