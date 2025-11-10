<?php
// Préparation des données sections/critères pour le formulaire
$sectionsDataForForm = [];

// Organisation des données par section
if (isset($sections) && is_array($sections)) {
    $sectionsGrouped = [];
    
    // Groupement par section avec critères associés
    foreach ($sections as $row) {
        $sectionTitre = $row['section'];
        if (!isset($sectionsGrouped[$sectionTitre])) {
            $sectionsGrouped[$sectionTitre] = [
                'titre' => $sectionTitre, 
                'description' => $row['description'], 
                'criteres' => [],
                'idSection' => $row['idSection'] ?? null // AJOUTER l'ID de section
            ];
        }
        $sectionsGrouped[$sectionTitre]['criteres'][] = [
            'id' => $row['idCritere'], 
            'description_courte' => $row['critere'],
            'description_longue' => $row['description_critere'], 
            'valeur_max' => $row['valeur_max']
        ];
    }
    $sectionsDataForForm = array_values($sectionsGrouped);
}

// Garantie de 3 sections minimum avec 5 critères chacune
while (count($sectionsDataForForm) < 3) {
    $sectionsDataForForm[] = ['titre' => '', 'description' => '', 'criteres' => [], 'idSection' => null];
}

for ($i = 0; $i < count($sectionsDataForForm); $i++) {
    if (!isset($sectionsDataForForm[$i]['criteres']) || !is_array($sectionsDataForForm[$i]['criteres'])) {
        $sectionsDataForForm[$i]['criteres'] = [];
    }
    while (count($sectionsDataForForm[$i]['criteres']) < 5) {
        $sectionsDataForForm[$i]['criteres'][] = ['description_courte' => '', 'description_longue' => '', 'valeur_max' => ''];
    }
}

// AJOUTER : Fonction pour vérifier si une section existe dans la base
function sectionExisteDansBase($titre, $sectionsName) {
    if (empty($sectionsName) || empty($titre)) return false;
    
    foreach ($sectionsName as $section) {
        if ($section['titre'] === $titre) {
            return $section['idSection'];
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Créer un nouveau modèle de grille d'évaluation</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Accent styles to improve readability, aligned with UCA look */
        .banner-info{display:flex;align-items:center;gap:10px;background:#f3f8ff;border:1px solid #e0e9f7;border-left:4px solid var(--uca-light-blue, #2f80ed);color:#0b3d66;padding:12px 14px;border-radius:8px;margin:12px 0 20px}
        .banner-info .icon{color:var(--uca-blue, #1b4e9b);font-size:18px}
        .form-section{background:#fff;border:1px solid #e6eef8;border-radius:8px;padding:16px;margin:16px 0;box-shadow:0 1px 2px rgba(16,24,40,.04)}
        .form-section-title{display:flex;align-items:center;gap:8px;margin:0 0 12px;padding:8px 12px;background:#f7fbff;border-left:4px solid var(--uca-light-blue, #2f80ed);border-radius:6px;color:#0b3d66}
        .form-section-title .fa,.form-section-title .fas,.form-section-title .far{color:var(--uca-blue, #1b4e9b)}
        .radio-group label{display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:999px;padding:6px 10px;margin:6px 8px 0 0}
        .radio-group input[type="radio"]{accent-color:var(--uca-blue, #1b4e9b)}
        .criteria-item-title{display:flex;align-items:center;gap:8px;margin:10px 0;color:#0b3d66}
        .criteria-item-title .fa{color:var(--uca-blue, #1b4e9b)}
        /* Layout helpers for copy model row */
    .flex-row{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap}
    .flex-grow{flex:1;min-width:280px}
    /* Space between select and Copy button (row or wrapped) */
    #btn-copier{margin-top:10px}
    .flex-row > div + div{margin-left:12px}
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
            <a class="btn-back" href="index.php?action=grids">Retour à la page précédente</a>
        </div>
        <div class="page-header">
            <h1>Créer un nouveau modèle de grille d'évaluation</h1>
            <p class="subtitle">Université Clermont Auvergne</p>
        </div>

        <div class="banner-info"><i class="fa-solid fa-pen-ruler icon"></i><div><strong>Création de modèle</strong><br><span style="font-weight:normal">Vous pouvez copier un modèle existant ou réutiliser des sections/critères pour gagner du temps.</span></div></div>
        <div class="form-container">
            <form method="POST" action="index.php?action=store">

            <!-- Copie de modèle existant (optionnel) -->
                <div class="form-section">
                    <h3>Copier un modèle existant (optionnel)</h3>
                    <p>Vous pouvez partir d'un modèle existant :</p>
                    
                    <div class="flex-row">
                        <div class="flex-grow">
                            <label for="modele_source" class="form-label">Sélectionner un modèle :</label>
                            <select id="modele_source" name="modele_source" class="form-control">
                                <option value="">-- Choisir un modèle --</option>
                                <?php if (!empty($noms)): foreach ($noms as $modele): ?>
                                    <option value="<?php echo htmlspecialchars($modele['idModeleEval'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($modele['nom'] ?? 'Nom non défini'); ?>
                                    </option>
                                <?php endforeach; else: ?>
                                    <option value="">Aucun modèle disponible</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <!-- Bouton de copie avec validation JavaScript -->
                            <button type="button" id="btn-copier" onclick="
                            var modeleId = document.getElementById('modele_source').value;
                            if(modeleId) window.location.href='index.php?action=copy&modele_id=' + modeleId;
                            else alert('Veuillez sélectionner un modèle');
                            " class="button button-secondary">Copier</button>
                        </div>
                    </div>
                </div>
                
                <!-- Informations de base de la grille -->
            <div class="form-section">
                <h3 class="form-section-title"><i class="fa-solid fa-circle-info"></i> 1. Informations de base</h3>
                
                <label for="nom">Nom du modèle :</label>
                <input type="text" id="nom" name="nom" placeholder="Ex: Grille BUT2 d'évaluation du portfolio 2026" value="<?php echo isset($grid['nom']) ? htmlspecialchars($grid['nom']) : ''; ?>" required>
                
                <label for="annee">Année :</label>
                <input type="number" id="annee" name="annee" value="<?php echo isset($grid['annee']) ? htmlspecialchars($grid['annee']) : date('Y'); ?>" min="<?php echo date('Y'); ?>" required>
                
                <label for="note_max">Note maximale (calculée automatiquement) :</label>
                <input type="number" id="note_max" name="note_max" value="<?php echo isset($grid['note_max']) ? htmlspecialchars($grid['note_max']) : '0'; ?>" readonly 
                       style="background-color: #f8f9fa; cursor: not-allowed;">
            </div>

            <!-- Section 2: Nature de la grille -->
            <div class="form-section">
                <h3 class="form-section-title"><i class="fa-solid fa-shapes"></i> 2. Nature de la grille</h3>
                <p>Sélectionnez la nature de la grille :</p>
                
                <div class="radio-group">
                    <label class="radio-option" for="anglais">
                        <input type="radio" id="anglais" name="nature" value="anglais" <?php echo (isset($grid['nature']) && $grid['nature'] == 'anglais') ? 'checked' : ''; ?> required>
                        Anglais
                    </label>
                    
                    <label class="radio-option" for="rapport">
                        <input type="radio" id="rapport" name="nature" value="rapport" <?php echo (isset($grid['nature']) && $grid['nature'] == 'rapport') ? 'checked' : ''; ?> required>
                        Rapport
                    </label>
                    
                    <label class="radio-option" for="soutenance">
                        <input type="radio" id="soutenance" name="nature" value="soutenance" <?php echo (isset($grid['nature']) && $grid['nature'] == 'soutenance') ? 'checked' : ''; ?> required>
                        Soutenance
                    </label>
                    
                    <label class="radio-option" for="stage">
                        <input type="radio" id="stage" name="nature" value="stage" <?php echo (isset($grid['nature']) && $grid['nature'] == 'stage') ? 'checked' : ''; ?> required>
                        Stage
                    </label>
                    
                    <label class="radio-option" for="portfolio">
                        <input type="radio" id="portfolio" name="nature" value="portfolio" <?php echo (isset($grid['nature']) && $grid['nature'] == 'portfolio') ? 'checked' : ''; ?> required>
                        Portfolio
                    </label>
                </div>
            </div>

            <!-- Section 3: Sections -->
            <div class="form-section">
                <h3 class="form-section-title"><i class="fa-solid fa-layer-group"></i> 3. Sections</h3>
                <p>Configurez les 3 sections de votre grille :</p>
                
                <?php for ($sectionIndex = 1; $sectionIndex <= 3; $sectionIndex++): ?>
                    <!-- Section <?php echo $sectionIndex; ?> -->
                    <div class="form-section">
                        <h4 class="form-section-title"><i class="fa-regular fa-folder-open"></i> Section <?php echo $sectionIndex; ?></h4>
                        
                        <!-- Choix nouveau/existant pour la section -->
                        <div class="radio-group">
                            <?php 
                            // Vérifier si la section est pré-remplie ET existe dans la base
                            $isExistingSection = !empty($sectionsDataForForm[$sectionIndex-1]['titre']) && 
                                                 sectionExisteDansBase($sectionsDataForForm[$sectionIndex-1]['titre'], $sectionsName);
                            ?>
                            <label style="font-weight: normal; font-size: 13px;">
                                <input type="radio" name="section<?php echo $sectionIndex; ?>_type" value="nouvelle" <?php echo !$isExistingSection ? 'checked' : ''; ?> onchange="toggleSectionType(<?php echo $sectionIndex; ?>, this.value)"> Nouvelle
                            </label>
                            <label style="font-weight: normal; font-size: 13px;">
                                <input type="radio" name="section<?php echo $sectionIndex; ?>_type" value="existante" <?php echo $isExistingSection ? 'checked' : ''; ?> onchange="toggleSectionType(<?php echo $sectionIndex; ?>, this.value)"> Existante
                            </label>
                        </div>
                        
                        <!-- Sélection de section existante -->
                        <div id="section<?php echo $sectionIndex; ?>_existante" style="<?php echo $isExistingSection ? 'display: block;' : 'display: none;'; ?> margin-bottom: 10px;">
                            <label>Sélectionner une section existante :</label>
                            <select id="section<?php echo $sectionIndex; ?>_select" name="section<?php echo $sectionIndex; ?>_existing_id" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="loadExistingSection(<?php echo $sectionIndex; ?>)">
                                <option value="">-- Choisir une section --</option>
                                <?php if (!empty($sectionsName)): ?>
                                    <?php foreach ($sectionsName as $sectionExistante): ?>
                                        <option value="<?php echo htmlspecialchars($sectionExistante['idSection']); ?>"
                                                <?php 
                                                // Pré-sélectionner la section si elle correspond à celle actuellement affichée
                                                if ($isExistingSection && 
                                                    $sectionsDataForForm[$sectionIndex-1]['titre'] == $sectionExistante['titre']) {
                                                    echo 'selected';
                                                }
                                                ?>>
                                            <?php echo htmlspecialchars($sectionExistante['titre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Champs de section (toujours visibles) -->
                        <div id="section<?php echo $sectionIndex; ?>_champs">
                            <label>Titre de la section :</label>
                            <input type="text" id="section<?php echo $sectionIndex; ?>_titre"
                                   name="sections[<?php echo $sectionIndex; ?>][titre]" 
                                   value="<?php echo isset($sectionsDataForForm[$sectionIndex-1]['titre']) ? htmlspecialchars($sectionsDataForForm[$sectionIndex-1]['titre']) : ''; ?>" 
                                   placeholder="Ex: Contenu et qualité du travail" 
                                   <?php echo ($sectionIndex == 1 && !$isExistingSection) ? 'required' : ''; ?>
                                   <?php echo $isExistingSection ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                            
                            <label>Description de la section :</label>
                            <textarea id="section<?php echo $sectionIndex; ?>_description"
                                      name="sections[<?php echo $sectionIndex; ?>][description]" rows="3" 
                                      placeholder="Description détaillée de ce qui est évalué dans cette section" 
                                      <?php echo ($sectionIndex == 1 && !$isExistingSection) ? 'required' : ''; ?>
                                      style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;<?php echo $isExistingSection ? ' background-color: #f8f9fa; cursor: not-allowed;' : ''; ?>"
                                      <?php echo $isExistingSection ? 'readonly' : ''; ?>><?php echo isset($sectionsDataForForm[$sectionIndex-1]['description']) ? htmlspecialchars($sectionsDataForForm[$sectionIndex-1]['description']) : ''; ?></textarea>
                        </div>
                        
                        <!-- Critères de la section -->
                        <h5 class="criteria-item-title"><i class="fa-regular fa-square-check"></i> Critères de la section <?php echo $sectionIndex; ?> :</h5>
                        
                        <?php for ($critereIndex = 1; $critereIndex <= 5; $critereIndex++): ?>
                            <!-- Critère <?php echo $sectionIndex; ?>.<?php echo $critereIndex; ?> -->
                            <div class="critere-section">
                                <h6 class="criteria-item-title">Critère <?php echo $critereIndex; ?></h6>
                                
                                <!-- Choix nouveau/existant pour le critère -->
                                <div class="radio-group">
                                    <?php 
                                    // Vérifier si le critère est pré-rempli (existant)
                                    $isExistingCritere = !empty($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['description_courte']);
                                    ?>
                                    <label style="font-weight: normal; font-size: 13px;">
                                        <input type="radio" name="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_type" value="nouveau" <?php echo !$isExistingCritere ? 'checked' : ''; ?> onchange="toggleCritereType(<?php echo $sectionIndex; ?>, <?php echo $critereIndex; ?>, this.value)"> Nouveau
                                    </label>
                                    <label style="font-weight: normal; font-size: 13px;">
                                        <input type="radio" name="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_type" value="existant" <?php echo $isExistingCritere ? 'checked' : ''; ?> onchange="toggleCritereType(<?php echo $sectionIndex; ?>, <?php echo $critereIndex; ?>, this.value)"> Existant
                                    </label>
                                </div>
                                
                                <!-- Sélection de critère existant -->
                                <div id="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_existant" style="<?php echo $isExistingCritere ? 'display: block;' : 'display: none;'; ?> margin-bottom: 10px;">
                                    <label>Sélectionner un critère existant :</label>
                                    <select name="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_existing_id" style="width: 100%; padding: 5px; border: 1px solid #ddd; border-radius: 3px;" onchange="loadExistingCritere(<?php echo $sectionIndex; ?>, <?php echo $critereIndex; ?>)">
                                        <option value="">-- Choisir un critère --</option>
                                        <?php if (!empty($criteresName)): ?>
                                            <?php foreach ($criteresName as $critereExistant): ?>
                                                <option value="<?php echo htmlspecialchars($critereExistant['idCritere']); ?>"
                                                        <?php 
                                                        // Pré-sélectionner le critère s'il existe déjà
                                                        if ($isExistingCritere && isset($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['id']) && 
                                                            $sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['id'] == $critereExistant['idCritere']) {
                                                            echo 'selected';
                                                        }
                                                        ?>>
                                                    <?php echo htmlspecialchars($critereExistant['descCourte']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                
                                <!-- Champs de critère (toujours visibles) -->
                                <div id="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_champs">
                                    <label>Description courte :</label>
                                    <input type="text" id="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_desc_courte"
                                           name="sections[<?php echo $sectionIndex; ?>][criteres][<?php echo $critereIndex; ?>][description_courte]" 
                                           value="<?php echo isset($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['description_courte']) ? htmlspecialchars($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['description_courte']) : ''; ?>" 
                                           placeholder="Ex: Clarté" 
                                           <?php echo ($sectionIndex == 1 && $critereIndex == 1) ? 'required' : ''; ?>
                                           <?php echo $isExistingCritere ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                                    
                                    <label>Description longue :</label>
                                    <textarea id="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_desc_longue"
                                              name="sections[<?php echo $sectionIndex; ?>][criteres][<?php echo $critereIndex; ?>][description_longue]" 
                                              rows="2" placeholder="Description détaillée du critère" 
                                              style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;<?php echo $isExistingCritere ? ' background-color: #f8f9fa; cursor: not-allowed;' : ''; ?>" 
                                              <?php echo ($sectionIndex == 1 && $critereIndex == 1) ? 'required' : ''; ?>
                                              <?php echo $isExistingCritere ? 'readonly' : ''; ?>><?php echo isset($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['description_longue']) ? htmlspecialchars($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['description_longue']) : ''; ?></textarea>
                                </div>
                                
                                <!-- Valeur maximale (toujours visible) -->
                                <label>Valeur maximale :</label>
                                <input type="number" id="critere<?php echo $sectionIndex; ?>_<?php echo $critereIndex; ?>_valeur_max"
                                       name="sections[<?php echo $sectionIndex; ?>][criteres][<?php echo $critereIndex; ?>][valeur_max]" 
                                       value="<?php echo isset($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['valeur_max']) ? htmlspecialchars($sectionsDataForForm[$sectionIndex-1]['criteres'][$critereIndex-1]['valeur_max']) : ''; ?>" 
                                       placeholder="Ex: 5" min="0" max="100" step="0.5" 
                                       class="critere-valeur-max"
                                       style="width: 100px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;<?php echo $isExistingCritere ? ' background-color: #f8f9fa; cursor: not-allowed;' : ''; ?>"
                                       <?php echo $isExistingCritere ? 'readonly' : ''; ?>
                                       onchange="updateNoteMax()">
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Boutons d'action -->
            <div class="form-actions">
                <button type="submit" class="button button-primary">Créer le modèle</button>
                <button type="button" class="button button-secondary" onclick="window.location.href='index.php?action=grids'">Annuler</button>
            </div>
        </form>
    </div>
    
    

    <!-- Script unifié -->
    <script src="assets/js/grid-unified.js?v=<?php echo time(); ?>"></script>
    
    <!-- Variables et fonctions spécifiques à gridAdd -->
    <script>
        // Variables PHP injectées en JavaScript
        window.sectionsDisponibles = <?php echo json_encode($sectionsData ?? []); ?>;
        window.criteresDisponibles = <?php echo json_encode($criteresData ?? []); ?>;
        window.criteresValeurMaxDisponibles = <?php echo json_encode($criteresValeurMax ?? []); ?>;
        
        // Fonction spécifique pour l'initialisation de gridAdd
        function initializePageSpecificFields() {
            // Si on vient d'une copie, initialiser automatiquement les sections existantes
            for (let sectionNum = 1; sectionNum <= 3; sectionNum++) {
                const sectionTypeExistante = document.querySelector(`input[name="section${sectionNum}_type"][value="existante"]`);
                const sectionSelect = document.getElementById('section' + sectionNum + '_select');
                
                if (sectionTypeExistante && sectionTypeExistante.checked) {
                    // La section est marquée comme existante dans le PHP
                    
                    // 1. Trouver l'ID de la section dans le dropdown
                    const titreSection = document.getElementById('section' + sectionNum + '_titre').value;
                    if (titreSection && sectionSelect) {
                        // Chercher l'option correspondante dans le select
                        const options = sectionSelect.options;
                        for (let i = 0; i < options.length; i++) {
                            const optionText = options[i].textContent;
                            if (optionText === titreSection) {
                                sectionSelect.value = options[i].value;
                                break;
                            }
                        }
                        
                        // 2. Charger automatiquement la section si elle est trouvée
                        if (sectionSelect.value) {
                            loadExistingSection(sectionNum);
                        }
                    }
                }
            }
        }
    </script>
</body>
</html>