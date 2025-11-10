// Script JavaScript unifié pour la gestion des grilles d'évaluation
// Gère à la fois les formulaires de création/modification et les tests de grilles

// Variables globales
let noteMaxInput;

// === INITIALISATION PRINCIPALE ===
document.addEventListener('DOMContentLoaded', () => {
    // Initialisations communes
    noteMaxInput = document.getElementById('note_max');
    
    // Sections/critères (formulaires gridAdd/gridModify)
    if (document.querySelector('.critere-valeur-max')) {
        attachEventListeners();
        updateNoteMax();
        initializeExistingFields();
    }
    
    // Notes (test de grille gridTest)
    if (document.querySelector('.note-input')) {
        initNoteInputs();
    }
});

// === GESTION DES NOTES (pour gridTest) ===
function calculerTotal() {
    let total = 0;
    const inputs = document.querySelectorAll('.note-input');
    
    inputs.forEach(function(input) {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    
    const totalElement = document.getElementById('totalNote');
    if (totalElement) {
        totalElement.textContent = total.toFixed(1);
        
        // Changer la couleur selon le pourcentage
        if (window.totalMaxValue) {
            const percentage = (total / window.totalMaxValue) * 100;
            
            if (percentage >= 80) {
                totalElement.style.color = '#22c55e'; // Vert
            } else if (percentage >= 60) {
                totalElement.style.color = '#f59e0b'; // Orange
            } else if (percentage >= 40) {
                totalElement.style.color = '#ef4444'; // Rouge
            } else {
                totalElement.style.color = '#dc2626'; // Rouge foncé
            }
        }
    }
}

function resetNotes() {
    const inputs = document.querySelectorAll('.note-input');
    inputs.forEach(function(input) {
        input.value = '';
    });
    calculerTotal();
}

function initNoteInputs() {
    const inputs = document.querySelectorAll('.note-input');
    inputs.forEach(function(input) {
        // Validation en temps réel
        input.addEventListener('input', function() {
            const max = parseFloat(this.getAttribute('max'));
            const value = parseFloat(this.value);
            
            if (value > max) {
                this.value = max;
            }
            if (value < 0) {
                this.value = 0;
            }
            
            calculerTotal();
        });
        
        input.addEventListener('change', calculerTotal);
    });
    
    // Calcul initial
    calculerTotal();
}

// === GESTION DES SECTIONS/CRITÈRES (pour gridAdd/gridModify) ===
function updateNoteMax() {
    if (!noteMaxInput) {
        noteMaxInput = document.getElementById('note_max');
    }
    
    if (noteMaxInput) {
        let somme = 0;
        const criteresValeurMax = document.querySelectorAll('.critere-valeur-max');
        
        criteresValeurMax.forEach(function(input) {
            const valeur = parseFloat(input.value) || 0;
            somme += valeur;
        });
        
        noteMaxInput.value = somme.toFixed(1);
    }
}

function attachEventListeners() {
    const criteresValeurMax = document.querySelectorAll('.critere-valeur-max');
    criteresValeurMax.forEach(function(input) {
        input.removeEventListener('input', updateNoteMax);
        input.removeEventListener('change', updateNoteMax);
        input.addEventListener('input', updateNoteMax);
        input.addEventListener('change', updateNoteMax);
    });
}

function initializeExistingFields() {
    // Initialisation spécifique selon le contexte (définie dans chaque page)
    if (typeof initializePageSpecificFields === 'function') {
        initializePageSpecificFields();
    }
}

function toggleSectionType(sectionNum, type) {
    const existanteDiv = document.getElementById('section' + sectionNum + '_existante');
    const titreInput = document.getElementById('section' + sectionNum + '_titre');
    const descriptionInput = document.getElementById('section' + sectionNum + '_description');
    
    if (type === 'nouvelle') {
        if (existanteDiv) existanteDiv.style.display = 'none';
        
        // Rendre modifiables
        makeFieldEditable(titreInput);
        makeFieldEditable(descriptionInput);
        
        // Requis pour section 1
        if (sectionNum == 1) {
            if (titreInput) titreInput.required = true;
            if (descriptionInput) descriptionInput.required = true;
        }
        
        // Réactiver critères
        for (let critereNum = 1; critereNum <= 5; critereNum++) {
            enableCritereRadios(sectionNum, critereNum);
            toggleCritereType(sectionNum, critereNum, 'nouveau');
        }
    } else {
        if (existanteDiv) existanteDiv.style.display = 'block';
        
        // Lecture seule
        makeFieldReadOnly(titreInput);
        makeFieldReadOnly(descriptionInput);
        
        // Plus requis
        if (titreInput) titreInput.required = false;
        if (descriptionInput) descriptionInput.required = false;
        
        // BLOQUER TOUS LES CRITÈRES DE CETTE SECTION (1 à 5)
        for (let critereNum = 1; critereNum <= 5; critereNum++) {
            disableCritereRadios(sectionNum, critereNum);
            toggleCritereType(sectionNum, critereNum, 'existant');
        }
    }
}

function toggleCritereType(sectionNum, critereNum, type) {
    // Vérifier si section est existante
    const sectionTypeExistante = document.querySelector(`input[name="section${sectionNum}_type"][value="existante"]`);
    if (sectionTypeExistante && sectionTypeExistante.checked) {
        type = 'existant'; // Forcer existant si section existante
    }
    
    const existantDiv = document.getElementById('critere' + sectionNum + '_' + critereNum + '_existant');
    const descCourteInput = document.getElementById('critere' + sectionNum + '_' + critereNum + '_desc_courte');
    const descLongueInput = document.getElementById('critere' + sectionNum + '_' + critereNum + '_desc_longue');
    const valeurMaxInput = document.getElementById('critere' + sectionNum + '_' + critereNum + '_valeur_max');
    
    if (type === 'nouveau') {
        if (existantDiv) existantDiv.style.display = 'none';
        
        // Rendre modifiables
        makeFieldEditable(descCourteInput);
        makeFieldEditable(descLongueInput);
        makeFieldEditable(valeurMaxInput);
        
        // Requis pour critère 1.1
        if (sectionNum == 1 && critereNum == 1) {
            if (descCourteInput) descCourteInput.required = true;
            if (descLongueInput) descLongueInput.required = true;
        }
    } else {
        if (existantDiv) existantDiv.style.display = 'block';
        
        // Lecture seule
        makeFieldReadOnly(descCourteInput);
        makeFieldReadOnly(descLongueInput);
        makeFieldReadOnly(valeurMaxInput);
        
        // Plus requis
        if (descCourteInput) descCourteInput.required = false;
        if (descLongueInput) descLongueInput.required = false;
    }
}

function loadExistingSection(sectionNum) {
    const select = document.getElementById('section' + sectionNum + '_select');
    const titreInput = document.getElementById('section' + sectionNum + '_titre');
    const descriptionInput = document.getElementById('section' + sectionNum + '_description');
    
    if (select && select.value && window.sectionsDisponibles && window.sectionsDisponibles[select.value]) {
        const sectionData = window.sectionsDisponibles[select.value];
        
        // Remplir les champs de section
        if (titreInput) titreInput.value = sectionData.titre;
        if (descriptionInput) descriptionInput.value = sectionData.description;
        
        // BLOQUER TOUS LES CRITÈRES DE CETTE SECTION (1 à 5)
        for (let critereNum = 1; critereNum <= 5; critereNum++) {
            if (sectionData.criteres && critereNum <= sectionData.criteres.length) {
                // Critères existants de la section : les pré-remplir
                const critere = sectionData.criteres[critereNum - 1];
                
                // Forcer le mode "existant" et sélectionner le critère
                const radioExistant = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="existant"]`);
                const radioNouveau = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="nouveau"]`);
                if (radioExistant) radioExistant.checked = true;
                if (radioNouveau) radioNouveau.checked = false;
                
                const selectCritere = document.querySelector(`select[name="critere${sectionNum}_${critereNum}_existing_id"]`);
                if (selectCritere) selectCritere.value = critere.idCritere;
                
                // Remplir les champs
                const descCourteEl = document.getElementById(`critere${sectionNum}_${critereNum}_desc_courte`);
                const descLongueEl = document.getElementById(`critere${sectionNum}_${critereNum}_desc_longue`);
                const valeurMaxEl = document.getElementById(`critere${sectionNum}_${critereNum}_valeur_max`);
                
                if (descCourteEl) descCourteEl.value = critere.descCourte;
                if (descLongueEl) descLongueEl.value = critere.descLongue;
                if (valeurMaxEl) valeurMaxEl.value = critere.valeurMax;
                
                // Bloquer ce critère
                disableCritereRadios(sectionNum, critereNum);
                toggleCritereType(sectionNum, critereNum, 'existant');
            } else {
                // Critères au-delà du nombre existant : les désactiver complètement
                const radioNouveau = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="nouveau"]`);
                const radioExistant = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="existant"]`);
                
                // Vider les champs
                const descCourteEl = document.getElementById(`critere${sectionNum}_${critereNum}_desc_courte`);
                const descLongueEl = document.getElementById(`critere${sectionNum}_${critereNum}_desc_longue`);
                const valeurMaxEl = document.getElementById(`critere${sectionNum}_${critereNum}_valeur_max`);
                
                if (descCourteEl) descCourteEl.value = '';
                if (descLongueEl) descLongueEl.value = '';
                if (valeurMaxEl) valeurMaxEl.value = '';
                
                // Mettre en mode "nouveau" mais désactivé
                if (radioNouveau) radioNouveau.checked = true;
                if (radioExistant) radioExistant.checked = false;
                
                // BLOQUER AUSSI CES CRITÈRES VIDES
                disableCritereRadios(sectionNum, critereNum);
                
                // Désactiver tous les champs de ce critère
                const inputs = [descCourteEl, descLongueEl, valeurMaxEl];
                
                inputs.forEach(input => {
                    if (input) {
                        input.disabled = true;
                        input.style.backgroundColor = '#f8f9fa';
                        input.style.cursor = 'not-allowed';
                        input.style.opacity = '0.6';
                    }
                });
            }
        }
        
        updateNoteMax();
    } else if (select && !select.value) {
        // Si pas de sélection : réactiver TOUS les critères
        if (titreInput) titreInput.value = '';
        if (descriptionInput) descriptionInput.value = '';
        
        for (let critereNum = 1; critereNum <= 5; critereNum++) {
            // Vider tous les champs
            const descCourteEl = document.getElementById(`critere${sectionNum}_${critereNum}_desc_courte`);
            const descLongueEl = document.getElementById(`critere${sectionNum}_${critereNum}_desc_longue`);
            const valeurMaxEl = document.getElementById(`critere${sectionNum}_${critereNum}_valeur_max`);
            
            if (descCourteEl) descCourteEl.value = '';
            if (descLongueEl) descLongueEl.value = '';
            if (valeurMaxEl) valeurMaxEl.value = '';
            
            // Réactiver tous les critères
            enableCritereRadios(sectionNum, critereNum);
            toggleCritereType(sectionNum, critereNum, 'nouveau');
            
            // Réactiver tous les champs
            const inputs = [descCourteEl, descLongueEl, valeurMaxEl];
            
            inputs.forEach(input => {
                if (input) {
                    input.disabled = false;
                    input.style.backgroundColor = '';
                    input.style.cursor = '';
                    input.style.opacity = '';
                }
            });
        }
        
        updateNoteMax();
    }
}

function loadExistingCritere(sectionNum, critereNum) {
    const select = document.querySelector(`select[name="critere${sectionNum}_${critereNum}_existing_id"]`);
    const descCourteInput = document.getElementById(`critere${sectionNum}_${critereNum}_desc_courte`);
    const descLongueInput = document.getElementById(`critere${sectionNum}_${critereNum}_desc_longue`);
    const valeurMaxInput = document.getElementById(`critere${sectionNum}_${critereNum}_valeur_max`);
    
    if (select && select.value && window.criteresDisponibles && window.criteresDisponibles[select.value]) {
        const critereData = window.criteresDisponibles[select.value];
        
        // Remplir descriptions
        if (descCourteInput) descCourteInput.value = critereData.descCourte;
        if (descLongueInput) descLongueInput.value = critereData.descLongue;
        
        // Remplir valeur max
        const valeurMax = (window.criteresValeurMaxDisponibles && window.criteresValeurMaxDisponibles[select.value]) || 5;
        if (valeurMaxInput) valeurMaxInput.value = valeurMax;
        
        updateNoteMax();
    } else if (select && !select.value) {
        // Vider si pas de sélection
        if (descCourteInput) descCourteInput.value = '';
        if (descLongueInput) descLongueInput.value = '';
        if (valeurMaxInput) valeurMaxInput.value = '';
        
        updateNoteMax();
    }
}

// === UTILITAIRES ===
function makeFieldEditable(field) {
    if (field) {
        field.readOnly = false;
        field.style.backgroundColor = '';
        field.style.cursor = '';
    }
}

function makeFieldReadOnly(field) {
    if (field) {
        field.readOnly = true;
        field.style.backgroundColor = '#f8f9fa';
        field.style.cursor = 'not-allowed';
    }
}

function enableCritereRadios(sectionNum, critereNum) {
    const radioNouveau = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="nouveau"]`);
    const radioExistant = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="existant"]`);
    const radioGroup = radioNouveau ? radioNouveau.closest('.radio-group') : null;
    const selectCritere = document.querySelector(`select[name="critere${sectionNum}_${critereNum}_existing_id"]`);
    
    if (radioNouveau) {
        radioNouveau.disabled = false;
        radioNouveau.checked = true;
    }
    if (radioExistant) {
        radioExistant.disabled = false;
        radioExistant.checked = false;
    }
    if (radioGroup) {
        radioGroup.classList.remove('radio-disabled');
    }
    
    // Réactiver la liste déroulante des critères
    if (selectCritere) {
        selectCritere.disabled = false;
        selectCritere.style.backgroundColor = '';
        selectCritere.style.cursor = '';
        selectCritere.style.opacity = '';
        selectCritere.value = '';
    }
    
    // Réactiver aussi les champs de saisie
    const inputs = [
        document.getElementById(`critere${sectionNum}_${critereNum}_desc_courte`),
        document.getElementById(`critere${sectionNum}_${critereNum}_desc_longue`),
        document.getElementById(`critere${sectionNum}_${critereNum}_valeur_max`)
    ];
    
    inputs.forEach(input => {
        if (input) {
            input.disabled = false;
            input.style.backgroundColor = '';
            input.style.cursor = '';
            input.style.opacity = '';
        }
    });
}

function disableCritereRadios(sectionNum, critereNum) {
    const radioNouveau = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="nouveau"]`);
    const radioExistant = document.querySelector(`input[name="critere${sectionNum}_${critereNum}_type"][value="existant"]`);
    const radioGroup = radioNouveau ? radioNouveau.closest('.radio-group') : null;
    const selectCritere = document.querySelector(`select[name="critere${sectionNum}_${critereNum}_existing_id"]`);
    
    if (radioNouveau) {
        radioNouveau.disabled = true;
        radioNouveau.checked = false;
    }
    if (radioExistant) {
        radioExistant.disabled = true;
        radioExistant.checked = true;
    }
    if (radioGroup) {
        radioGroup.classList.add('radio-disabled');
    }
    
    // Désactiver aussi la liste déroulante des critères
    if (selectCritere) {
        selectCritere.disabled = true;
        selectCritere.style.backgroundColor = '#f8f9fa';
        selectCritere.style.cursor = 'not-allowed';
        selectCritere.style.opacity = '0.6';
    }
}