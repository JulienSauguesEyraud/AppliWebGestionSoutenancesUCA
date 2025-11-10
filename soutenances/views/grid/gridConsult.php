<!DOCTYPE html>
<html>
<head>
    <title>Consultation de la grille d'évaluation</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Polished stats card */
        .panel-card{background:#fff;border:1px solid #e6eef8;border-radius:10px;padding:16px 18px;margin:14px 0;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .stats-title{display:flex;align-items:center;gap:8px;color:#0b3d66;margin:0 0 10px}
        .stats-title .fa{color:var(--uca-blue,#1b4e9b)}
        .stats-container{display:grid;grid-template-columns:1fr;gap:8px}
        .stats-container>div{display:flex;align-items:center;gap:10px;color:#1f3347}
        .stat-number{display:inline-block;min-width:28px;text-align:center;border-radius:999px;background:#eaf2ff;color:#1b4e9b;font-weight:700;padding:2px 10px}
        .stat-label{font-size:14px;color:#374b63}
        @media (min-width: 700px){.stats-container{grid-template-columns:repeat(2,1fr)}}
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
            <a class="btn-back" href="index.php?action=grids" onclick="if (history.length > 1) { history.back(); return false; }">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Grille d'évaluation - <?php echo htmlspecialchars($_GET['nom']); ?></h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>

        <?php
        // Calcul des statistiques de la grille
        $sections_distinctes = []; $criteres_distincts = [];
        
        if (!empty($sections) && is_array($sections)) {
            foreach ($sections as $section) {
                if (!empty($section['section'])) $sections_distinctes[] = $section['section'];
                if (!empty($section['critere'])) $criteres_distincts[] = $section['critere'];
            }
            // Élimination des doublons
            $sections_distinctes = array_unique($sections_distinctes);
            $criteres_distincts = array_unique($criteres_distincts);
        }
        
        $nb_sections = count($sections_distinctes);
        $nb_criteres = count($criteres_distincts);
        ?>

        <!-- Panneau de statistiques -->
        <div class="panel-card">
            <h3 class="stats-title"><i class="fa-regular fa-chart-bar"></i> Statistiques de la grille</h3>
            <div class="stats-container">
                <div>
                    <span class="stat-number"><?php echo $nb_sections; ?></span>
                    <span class="stat-label"><i class="fa-regular fa-folder-open" style="margin-right:6px;color:#1b4e9b"></i>section<?php echo $nb_sections > 1 ? 's' : ''; ?> distincte<?php echo $nb_sections > 1 ? 's' : ''; ?></span>
                </div>
                <div>
                    <span class="stat-number criteria"><?php echo $nb_criteres; ?></span>
                    <span class="stat-label"><i class="fa-regular fa-square-check" style="margin-right:6px;color:#1b4e9b"></i>critère<?php echo $nb_criteres > 1 ? 's' : ''; ?> distinct<?php echo $nb_criteres > 1 ? 's' : ''; ?></span>
                </div>
            </div>
        </div>
    
    <!-- Tableau détaillé de la grille -->
    <div class="table-card">
        <div class="table-responsive">
        <?php if (!empty($sections) && is_array($sections)): ?>
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Titre de la Section</th>
                        <th>Description de la Section</th>
                        <th>Critère</th>
                        <th>Description du critère</th>
                        <th>Valeur Max</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $section): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($section['section']); ?></strong></td>
                            <td><?php echo htmlspecialchars($section['description']); ?></td>
                            <td><?php echo htmlspecialchars($section['critere']); ?></td>
                            <td><?php echo htmlspecialchars($section['description_critere']); ?></td>
                            <td><?php echo htmlspecialchars($section['valeur_max']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Message si aucune section -->
            <div class="no-data-message" style="padding: 16px;">
                Aucune section trouvée pour cette grille d'évaluation.
            </div>
        <?php endif; ?>
    </div>
        </div>
    </div>
</body>
</html>
 