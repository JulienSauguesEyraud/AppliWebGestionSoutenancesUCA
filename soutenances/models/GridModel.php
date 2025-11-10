<?php
// Récupère toutes les grilles avec statistiques d'utilisation
function getAllGrids($pdo) {
    $sql = "SELECT m.idModeleEval AS id, m.natureGrille AS nature, m.nomModuleGrilleEvaluation AS nom, 
                   m.noteMaxGrille AS note_max, CONCAT(m.anneeDebut, '-', m.anneeDebut + 1) AS annee,
                   COALESCE(e1.cnt, 0) + COALESCE(e2.cnt, 0) + COALESCE(e3.cnt, 0) + COALESCE(e4.cnt, 0) + COALESCE(e5.cnt, 0) AS nb_utilisations
            FROM ModelesGrilleEval m
            LEFT JOIN (SELECT IdModeleEval, COUNT(*) as cnt FROM EvalStage GROUP BY IdModeleEval) e1 ON m.idModeleEval = e1.IdModeleEval
            LEFT JOIN (SELECT IdModeleEval, COUNT(*) as cnt FROM EvalRapport GROUP BY IdModeleEval) e2 ON m.idModeleEval = e2.IdModeleEval  
            LEFT JOIN (SELECT IdModeleEval, COUNT(*) as cnt FROM EvalSoutenance GROUP BY IdModeleEval) e3 ON m.idModeleEval = e3.IdModeleEval
            LEFT JOIN (SELECT IdModeleEval, COUNT(*) as cnt FROM EvalPortfolio GROUP BY IdModeleEval) e4 ON m.idModeleEval = e4.IdModeleEval
            LEFT JOIN (SELECT IdModeleEval, COUNT(*) as cnt FROM EvalAnglais GROUP BY IdModeleEval) e5 ON m.idModeleEval = e5.IdModeleEval
            ORDER BY m.anneeDebut DESC, m.natureGrille";
    
    $results = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // Conversion des natures DB vers format formulaire - CORRECTION ICI
    foreach ($results as $key => $grid) {
        if (isset($grid['nature'])) {
            $results[$key]['nature'] = convertNatureFromDB($grid['nature']);
        }
    }
    return $results;
}

// Récupère le contenu détaillé d'une grille (sections + critères)
function consultGrid($pdo, $id) {
    $sql = "SELECT sc.titre AS section, sc.description AS description, c.descLongue AS description_critere, 
                   c.descCourte AS critere, c.idCritere, scc.valeurMaxCritereEval AS valeur_max
            FROM SectionsEval s
            JOIN SectionCritereEval sc ON s.idSection = sc.idSection
            JOIN SectionContenirCriteres scc ON sc.idSection = scc.idSection
            JOIN CriteresEval c ON scc.idCritere = c.idCritere
            WHERE s.idModeleEval = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupère tous les noms de grilles pour les sélecteurs
function getAllGridNames($pdo) {
    return $pdo->query("SELECT idModeleEval, nomModuleGrilleEvaluation AS nom FROM ModelesGrilleEval")->fetchAll(PDO::FETCH_ASSOC);
}

// Récupère toutes les sections pour les sélecteurs
function getAllSectionsNames($pdo) {
    return $pdo->query("SELECT idSection, titre FROM SectionCritereEval")->fetchAll(PDO::FETCH_ASSOC);
}

// Récupère tous les critères pour les sélecteurs
function getAllCriteresNames($pdo) {
    return $pdo->query("SELECT idCritere, descCourte FROM CriteresEval")->fetchAll(PDO::FETCH_ASSOC);
}

// Conversion énumération DB vers format formulaire
function convertNatureFromDB($natureDB) {
    $mapping = ['ANGLAIS ' => 'anglais', 'SOUTENANCE' => 'soutenance', 'RAPPORT' => 'rapport', ' STAGE' => 'stage', ' PORTFOLIO' => 'portfolio'];
    return $mapping[$natureDB] ?? strtolower(trim($natureDB));
}

// Conversion format formulaire vers énumération DB
function convertNatureToDB($natureForm) {
    $mapping = ['anglais' => 'ANGLAIS ', 'soutenance' => 'SOUTENANCE', 'rapport' => 'RAPPORT', 'stage' => ' STAGE', 'portfolio' => ' PORTFOLIO'];
    return $mapping[$natureForm] ?? strtoupper($natureForm);
}

// Récupère une grille par son ID
function getGridById($pdo, $id) {
    $sql = "SELECT idModeleEval AS id, natureGrille AS nature, nomModuleGrilleEvaluation AS nom, 
                   noteMaxGrille AS note_max, anneeDebut AS annee  
            FROM ModelesGrilleEval WHERE idModeleEval = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Convertir la nature de la DB vers le format formulaire
    if ($result && isset($result['nature'])) {
        $result['nature'] = convertNatureFromDB($result['nature']);
    }
    return $result;
}

// Création d'une nouvelle grille d'évaluation
function createGrid($pdo, $nom, $nature, $note_max, $annee_debut, $sections) {
    // Créer l'année universitaire si inexistante
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM AnneesUniversitaires WHERE anneeDebut = ?");
    $stmt->execute([$annee_debut]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO AnneesUniversitaires (anneeDebut, fin) VALUES (?, ?)");
        $stmt->execute([$annee_debut, $annee_debut + 1]);
    }
    
    // Générer nom unique si collision
    $nom_unique = $nom; $compteur = 1;
    do {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ModelesGrilleEval WHERE nomModuleGrilleEvaluation = ?");
        $stmt->execute([$nom_unique]);
        $existe_nom = $stmt->fetchColumn();
        if ($existe_nom > 0) $nom_unique = $nom . "_" . $compteur++;
    } while ($existe_nom > 0);
    
    // CORRECTION: Stocker la conversion dans une variable
    $natureDB = convertNatureToDB($nature);
    
    // Insertion grille principale
    $stmt = $pdo->prepare("INSERT INTO ModelesGrilleEval (nomModuleGrilleEvaluation, natureGrille, noteMaxGrille, anneeDebut) 
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([$nom_unique, $natureDB, $note_max, $annee_debut]);
    $idModeleEval = $pdo->lastInsertId();
    
    // Traitement des sections et critères
    foreach ($sections as $section) {
        if (empty($section['titre']) || empty($section['description'])) continue;

        // Créer ou récupérer ID section
        $stmt = $pdo->prepare("SELECT idSection FROM SectionCritereEval WHERE titre = ?");
        $stmt->execute([$section['titre']]);
        $idSection = $stmt->fetchColumn();
        
        if (!$idSection) {
            $stmt = $pdo->prepare("INSERT INTO SectionCritereEval (titre, description) VALUES (?, ?)");
            $stmt->execute([$section['titre'], $section['description']]);
            $idSection = $pdo->lastInsertId();
        }

        // Créer liaison section-grille si inexistante
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM SectionsEval WHERE idModeleEval = ? AND idSection = ?");
        $stmt->execute([$idModeleEval, $idSection]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("INSERT INTO SectionsEval (idModeleEval, idSection) VALUES (?, ?)");
            $stmt->execute([$idModeleEval, $idSection]);
        }
        
        // Traitement des critères de la section
        if (isset($section['criteres']) && is_array($section['criteres'])) {
            foreach ($section['criteres'] as $critere) {
                if (empty($critere['description_courte']) || empty($critere['description_longue'])) continue;
                
                // Créer ou récupérer ID critère
                $stmt = $pdo->prepare("SELECT idCritere FROM CriteresEval WHERE descCourte = ? AND descLongue = ?");
                $stmt->execute([$critere['description_courte'], $critere['description_longue']]);
                $idCritere = $stmt->fetchColumn();
                
                if (!$idCritere) {
                    $stmt = $pdo->prepare("INSERT INTO CriteresEval (descCourte, descLongue) VALUES (?, ?)");
                    $stmt->execute([$critere['description_courte'], $critere['description_longue']]);
                    $idCritere = $pdo->lastInsertId();
                }

                // Créer liaison critère-section si inexistante
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM SectionContenirCriteres WHERE idSection = ? AND idCritere = ?");
                $stmt->execute([$idSection, $idCritere]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO SectionContenirCriteres (idSection, idCritere, valeurMaxCritereEval) VALUES (?, ?, ?)");
                    $stmt->execute([$idSection, $idCritere, $critere['valeur_max']]);
                }
            }
        }
    }
}

// Mise à jour d'une grille existante
function updateGridInDB($pdo, $id, $nom, $nature, $note_max, $annee_debut, $sections) {
    $pdo->beginTransaction();
    
    // Créer année universitaire si inexistante
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM AnneesUniversitaires WHERE anneeDebut = ?");
    $stmt->execute([$annee_debut]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO AnneesUniversitaires (anneeDebut, fin) VALUES (?, ?)");
        $stmt->execute([$annee_debut, $annee_debut + 1]);
    }
    
    // CORRECTION: Stocker la conversion dans une variable au lieu d'utiliser bindParam
    $natureDB = convertNatureToDB($nature);

    // Mettre à jour grille principale
    $stmt = $pdo->prepare("UPDATE ModelesGrilleEval SET nomModuleGrilleEvaluation = ?, natureGrille = ?, noteMaxGrille = ?, anneeDebut = ? WHERE idModeleEval = ?");
    $stmt->execute([$nom, $natureDB, $note_max, $annee_debut, $id]);
    
    // Supprimer anciennes liaisons pour reconstruction
    $stmt = $pdo->prepare("DELETE scc FROM SectionContenirCriteres scc INNER JOIN SectionsEval se ON scc.idSection = se.idSection WHERE se.idModeleEval = ?");
    $stmt->execute([$id]);
    
    $stmt = $pdo->prepare("DELETE FROM SectionsEval WHERE idModeleEval = ?");
    $stmt->execute([$id]);
    
    // Recréer sections et critères avec logique similaire à createGrid
    foreach ($sections as $section) {
        if (empty($section['titre']) || empty($section['description'])) continue;

        // Créer ou récupérer ID section
        $stmt = $pdo->prepare("SELECT idSection FROM SectionCritereEval WHERE titre = ?");
        $stmt->execute([$section['titre']]);
        $idSection = $stmt->fetchColumn();
        
        if (!$idSection) {
            $stmt = $pdo->prepare("INSERT INTO SectionCritereEval (titre, description) VALUES (?, ?)");
            $stmt->execute([$section['titre'], $section['description']]);
            $idSection = $pdo->lastInsertId();
        }

        // Créer liaison section-modèle (reconstruction complète)
        $stmt = $pdo->prepare("INSERT INTO SectionsEval (idModeleEval, idSection) VALUES (?, ?)");
        $stmt->execute([$id, $idSection]);
        
        // Traitement critères de la section
        if (isset($section['criteres']) && is_array($section['criteres'])) {
            foreach ($section['criteres'] as $critere) {
                if (empty($critere['description_courte']) || empty($critere['description_longue'])) continue;
                
                // Créer ou récupérer ID critère
                $stmt = $pdo->prepare("SELECT idCritere FROM CriteresEval WHERE descCourte = ? AND descLongue = ?");
                $stmt->execute([$critere['description_courte'], $critere['description_longue']]);
                $idCritere = $stmt->fetchColumn();
                
                if (!$idCritere) {
                    $stmt = $pdo->prepare("INSERT INTO CriteresEval (descCourte, descLongue) VALUES (?, ?)");
                    $stmt->execute([$critere['description_courte'], $critere['description_longue']]);
                    $idCritere = $pdo->lastInsertId();
                }

                // Créer liaison critère-section (vérification sécurité)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM SectionContenirCriteres WHERE idSection = ? AND idCritere = ?");
                $stmt->execute([$idSection, $idCritere]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO SectionContenirCriteres (idSection, idCritere, valeurMaxCritereEval) VALUES (?, ?, ?)");
                    $stmt->execute([$idSection, $idCritere, $critere['valeur_max']]);
                }
            }
        }
    }
    $pdo->commit();
    return true;
}

// Suppression complète d'une grille
function deleteGrid($pdo, $id) {
    $pdo->beginTransaction();
    // Supprimer liaisons sections-modèle puis modèle principal
    $stmt = $pdo->prepare("DELETE FROM SectionsEval WHERE idModeleEval = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $stmt = $pdo->prepare("DELETE FROM ModelesGrilleEval WHERE idModeleEval = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $pdo->commit();
    return true;
}

// Récupération des données complètes des sections avec leurs critères
function getSectionsData($pdo) {
    $sectionsData = [];
    
    // Récupération des sections
    $stmt = $pdo->prepare("SELECT idSection, titre, description FROM SectionCritereEval");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sectionsData[$row['idSection']] = [
            'titre' => $row['titre'], 
            'description' => $row['description'], 
            'criteres' => []
        ];
    }
    
    // Récupération des critères par section
    $stmt = $pdo->prepare("SELECT DISTINCT sce.idSection, scc.idCritere, ce.descCourte, ce.descLongue, scc.valeurMaxCritereEval
                           FROM SectionCritereEval sce 
                           INNER JOIN SectionContenirCriteres scc ON sce.idSection = scc.idSection
                           INNER JOIN CriteresEval ce ON scc.idCritere = ce.idCritere 
                           ORDER BY sce.idSection, scc.idCritere");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $idSection = $row['idSection'];
        if (isset($sectionsData[$idSection])) {
            $sectionsData[$idSection]['criteres'][] = [
                'idCritere' => $row['idCritere'],
                'descCourte' => $row['descCourte'],
                'descLongue' => $row['descLongue'],
                'valeurMax' => $row['valeurMaxCritereEval']
            ];
        }
    }
    
    return $sectionsData;
}

// Récupération des données des critères
function getCriteresData($pdo) {
    $criteresData = [];
    $stmt = $pdo->prepare("SELECT idCritere, descCourte, descLongue FROM CriteresEval");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $criteresData[$row['idCritere']] = [
            'descCourte' => $row['descCourte'], 
            'descLongue' => $row['descLongue']
        ];
    }
    return $criteresData;
}

// Récupération des valeurs max moyennes des critères
function getCriteresValeurMax($pdo) {
    $criteresValeurMax = [];
    $stmt = $pdo->prepare("SELECT scc.idCritere, AVG(scc.valeurMaxCritereEval) as valeur_moyenne 
                           FROM SectionContenirCriteres scc GROUP BY scc.idCritere");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $criteresValeurMax[$row['idCritere']] = round($row['valeur_moyenne'], 1);
    }
    return $criteresValeurMax;
}

// Récupération d'une section existante par ID
function getSectionById($pdo, $idSection) {
    $stmt = $pdo->prepare("SELECT titre, description FROM SectionCritereEval WHERE idSection = ?");
    $stmt->execute([$idSection]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupération d'un critère existant par ID
function getCritereById($pdo, $idCritere) {
    $stmt = $pdo->prepare("SELECT descCourte, descLongue FROM CriteresEval WHERE idCritere = ?");
    $stmt->execute([$idCritere]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>