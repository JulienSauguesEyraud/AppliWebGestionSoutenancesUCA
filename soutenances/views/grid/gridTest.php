<!DOCTYPE html>
<html>
<head>
    <title>Tester la grille d'évaluation - <?php echo htmlspecialchars($grid['nom'] ?? 'Grille'); ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .panel-card{background:#fff;border:1px solid #e6eef8;border-radius:10px;padding:16px 18px;margin:14px 0;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .stats-title{display:flex;align-items:center;gap:8px;color:#0b3d66;margin:0 0 10px}
        .stats-title .fa{color:var(--uca-blue,#1b4e9b)}
        .stats-container{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
        .stat-label{font-size:14px;color:#374b63}
        .stat-number{font-weight:600;color:#1f3347}
        .note-input {
            width: 60px;
            padding: 4px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
        }
        .note-input:focus {
            border-color: var(--secondary-blue);
            outline: none;
        }
        .total-row {
            background-color: var(--very-light-blue);
            font-weight: bold;
        }
        .test-actions {
            margin: 20px 0;
            text-align: center;
        }
        .note-column {
            background-color: #f8f9fa;
        }
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
            <h1>Test de la grille d'évaluation</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>
        
        <!-- Informations de la grille -->
        <div class="panel-card">
            <h3 class="stats-title"><i class="fa-regular fa-rectangle-list"></i> Informations de la grille</h3>
            <div class="stats-container">
                <div>
                    <div class="stat-label">Nom de la grille</div>
                    <div class="stat-number"><?php echo htmlspecialchars($grid['nom'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <div class="stat-label">Nature</div>
                    <div class="stat-number"><?php echo htmlspecialchars($grid['nature'] ?? 'N/A'); ?></div>
                </div>
                <div>
                    <div class="stat-label">Note maximale</div>
                    <div class="stat-number criteria"><?php echo htmlspecialchars($grid['note_max'] ?? '0'); ?></div>
                </div>
                <div>
                    <div class="stat-label">Année</div>
                    <div class="stat-number"><?php echo htmlspecialchars($grid['annee'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <!-- Formulaire de test avec tableau -->
        <form id="testForm" onsubmit="return false;">
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
                                <th class="note-column">Note attribuée</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalMax = 0;
                            foreach ($sections as $index => $section): 
                                $totalMax += floatval($section['valeur_max']);
                            ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($section['section']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($section['description']); ?></td>
                                    <td><?php echo htmlspecialchars($section['critere']); ?></td>
                                    <td><?php echo htmlspecialchars($section['description_critere']); ?></td>
                                    <td><?php echo htmlspecialchars($section['valeur_max']); ?></td>
                                    <td class="note-column">
                                        <input type="number" 
                                               class="note-input" 
                                               name="note_<?php echo $index; ?>"
                                               id="note_<?php echo $index; ?>"
                                               min="0" 
                                               max="<?php echo htmlspecialchars($section['valeur_max']); ?>" 
                                               step="0.5"
                                               placeholder="0"
                                               onchange="calculerTotal()">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <!-- Ligne de total -->
                            <tr class="total-row">
                                <td colspan="4"><strong>TOTAL</strong></td>
                                <td><strong><?php echo number_format($totalMax, 1); ?></strong></td>
                                <td class="note-column">
                                    <strong><span id="totalNote">0</span> / <?php echo number_format($totalMax, 1); ?></strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data-message" style="padding: 16px;">
                        Aucune section trouvée pour cette grille d'évaluation.
                    </div>
                <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions du test -->
            <div class="test-actions">
                <button type="button" class="button button-secondary" onclick="resetNotes()">
                    Réinitialiser
                </button>
            </div>
        </form>
        
    </div>
    
    <!-- Script unifié -->
    <script src="assets/js/grid-unified.js?v=<?php echo time(); ?>"></script>
    
    <!-- Variables et initialisation spécifiques à gridTest -->
    <script>
        // Variable PHP nécessaire pour le calcul des pourcentages
        window.totalMaxValue = <?php echo $totalMax ?? 0; ?>;
    </script>
</body>
</html>