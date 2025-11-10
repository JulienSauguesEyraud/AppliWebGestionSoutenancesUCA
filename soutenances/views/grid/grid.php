<!DOCTYPE html>
<html>
<head>
    <title>Liste des modèles de grilles</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CTA band and small polish for better hierarchy */
        .cta-card{display:flex;align-items:center;justify-content:space-between;gap:16px;background:#f3f8ff;border:1px solid #e0e9f7;border-left:4px solid var(--uca-light-blue,#2f80ed);border-radius:10px;padding:12px 16px;margin:12px 0 18px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .cta-card .cta-text{color:#0b3d66}
        .cta-card .cta-title{display:block;font-weight:600;margin-bottom:2px}
        .cta-card .cta-sub{font-size:13px;color:#4a6076}
        .cta-actions{display:flex;align-items:center;gap:10px}
        .btn-add{display:inline-flex;align-items:center;gap:8px}
        .btn-add .fa{font-size:14px}
    </style>
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
                <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
        </div>
        <div class="page-header">
            <h1>Liste des modèles de grilles</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>

        <?php
        // Message de confirmation suppression
        if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="success-message">
                ✓ Le modèle de grille a été supprimé avec succès.
            </div>
        <?php endif; ?>

        <!-- CTA band: add + count -->
        <div class="cta-card">
            <div class="cta-text">
                <span class="cta-title">Gestion des modèles</span>
                <span class="cta-sub">Nombre de grilles&nbsp;: <strong><?php $grids = getAllGrids($pdo); echo count($grids); ?></strong></span>
            </div>
            <div class="cta-actions">
                <a href="index.php?action=add" class="btn btn-uca btn-add"><i class="fa fa-plus"></i>Ajouter une nouvelle grille</a>
            </div>
        </div>

        <div class="table-card" style="margin-top: 8px;">
            <div class="table-responsive">

        
        <!-- Tableau principal des grilles -->
        <?php if (!empty($grids) && is_array($grids)): ?>
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nom du Module</th>
                        <th>Nature</th>
                        <th>Note Maximum</th>
                        <th>Année</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grids as $grid): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($grid['nom'] ?? 'N/A'); ?>
                                <div class="margin-top">
                                    <!-- Boutons d'action par grille -->
                                    <a href="index.php?action=consulter&id=<?php echo urlencode($grid['id']); ?>&nom=<?php echo urlencode($grid['nom']); ?>"
                                       class="btn-small btn-consult">Consulter</a>
                                    <?php if (isset($grid['nb_utilisations']) && $grid['nb_utilisations'] == 0): ?>
                                        <a href="index.php?action=modify&id=<?php echo urlencode($grid['id']); ?>"
                                           class="btn-small btn-modify">Modifier/Supprimer</a>
                                        <a href="index.php?action=tester&id=<?php echo urlencode($grid['id']); ?>&nom=<?php echo urlencode($grid['nom']); ?>"
                                           class="btn-small btn-test">Tester</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($grid['nature'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($grid['note_max'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($grid['annee'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Message si aucune grille -->
            <div class="no-data-message">
                Aucune grille trouvée. <a href="index.php?action=add">Créer la première grille</a>
            </div>
        <?php endif; ?>
        </div>
        </div>
    </div>
</body>
</html> 