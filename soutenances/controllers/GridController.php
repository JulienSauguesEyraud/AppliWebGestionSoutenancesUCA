<?php
require_once("models/GridModel.php");

// Affichage de l'index des grilles
function indexGrid($pdo) {
    require("views/grid/grid.php");
}

// Consultation détaillée d'une grille
function consulterGrid($pdo, $id) {
    $sections = consultGrid($pdo, $id);
    $grid = getGridById($pdo, $id);
    require("views/grid/gridConsult.php");
}

// Test d'une grille avec saisie de notes
function testerGrid($pdo, $id) {
    $sections = consultGrid($pdo, $id);
    $grid = getGridById($pdo, $id);
    require("views/grid/gridTest.php");
}

// Copie d'une grille existante
function copyGrid($pdo) {
    $noms = getAllGridNames($pdo);
    $sectionsName = getAllSectionsNames($pdo);
    $criteresName = getAllCriteresNames($pdo);
    $grid = getGridById($pdo, $_GET['modele_id']);
    $sections = consultGrid($pdo, $_GET['modele_id']);
    
    // Récupération des données via le modèle
    $sectionsData = getSectionsData($pdo);
    $criteresData = getCriteresData($pdo);
    $criteresValeurMax = getCriteresValeurMax($pdo);
    
    require("views/grid/gridAdd.php");
}

// Affichage formulaire d'ajout
function ajouterGrid($pdo) {
    $noms = getAllGridNames($pdo);
    $sectionsName = getAllSectionsNames($pdo);
    $criteresName = getAllCriteresNames($pdo);
    
    // Récupération des données via le modèle
    $sectionsData = getSectionsData($pdo);
    $criteresData = getCriteresData($pdo);
    $criteresValeurMax = getCriteresValeurMax($pdo); // LIGNE MANQUANTE AJOUTÉE
    
    require("views/grid/gridAdd.php");
}
 
// Enregistrement nouvelle grille
function storeGrid($pdo) {
    $nom = $_POST['nom']; 
    $nature = $_POST['nature'];
    $note_max = $_POST['note_max']; 
    $annee_debut = $_POST['annee'];
    $sections = $_POST['sections'] ?? [];

    if (!empty($nom) && !empty($nature) && !empty($note_max) && !empty($annee_debut)) {
        createGrid($pdo, $nom, $nature, $note_max, $annee_debut, $sections);
        indexGrid($pdo);
    }
}

// Affichage formulaire de modification
function modifyGrid($pdo, $id) {
    $grid = getGridById($pdo, $id);
    $sections = consultGrid($pdo, $id);
    $noms = getAllGridNames($pdo);
    $sectionsName = getAllSectionsNames($pdo);
    $criteresName = getAllCriteresNames($pdo);
    
    // Récupération des données via le modèle
    $sectionsData = getSectionsData($pdo);
    $criteresData = getCriteresData($pdo);
    $criteresValeurMax = getCriteresValeurMax($pdo);
    
    require("views/grid/gridModify.php");
}

// Mise à jour d'une grille existante
function updateGrid($pdo) {
    $id = $_GET['id']; 
    $nom = $_POST['nom']; 
    $nature = $_POST['nature'];
    $note_max = $_POST['note_max']; 
    $annee_debut = $_POST['annee'];
    $sections = $_POST['sections'] ?? [];

    // Traitement sections/critères existants sélectionnés via formulaire
    foreach ($_POST as $key => $value) {
        // Gestion sections existantes
        if (preg_match('/^section(\d+)_existing_id$/', $key, $matches) && !empty($value)) {
            $sectionNum = $matches[1];
            $sectionExistante = getSectionById($pdo, $value);
            
            if ($sectionExistante) {
                $sections[$sectionNum] = [
                    'titre' => $sectionExistante['titre'], 
                    'description' => $sectionExistante['description'],
                    'criteres' => $sections[$sectionNum]['criteres'] ?? []
                ];
            }
        }
        
        // Gestion critères existants
        if (preg_match('/^critere(\d+)_(\d+)_existing_id$/', $key, $matches) && !empty($value)) {
            $sectionNum = $matches[1];
            $critereNum = $matches[2];
            
            // Récupération infos critère existant via le modèle
            $critereExistant = getCritereById($pdo, $value);
            
            if ($critereExistant) {
                if (!isset($sections[$sectionNum]['criteres'][$critereNum])) {
                    $sections[$sectionNum]['criteres'][$critereNum] = [];
                }
                $sections[$sectionNum]['criteres'][$critereNum]['description_courte'] = $critereExistant['descCourte'];
                $sections[$sectionNum]['criteres'][$critereNum]['description_longue'] = $critereExistant['descLongue'];
            }
        }
    }

    if (!empty($id) && !empty($nom) && !empty($nature) && !empty($note_max) && !empty($annee_debut)) {
        updateGridInDB($pdo, $id, $nom, $nature, $note_max, $annee_debut, $sections);
        indexGrid($pdo);
    }
}

// Suppression d'une grille avec redirection
function deleteGridController($pdo) {
    if (isset($_GET['id'])) {
        deleteGrid($pdo, $_GET['id']);
        header('Location: index.php?action=grids&deleted=1');
        exit();
    } else {
        header('Location: index.php?action=grids');
        exit();
    }
}

?>