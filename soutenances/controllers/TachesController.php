<?php
function taches_index($pdo) {
    $taches = [];

    // Portfolio
    $sqlPortfolio = "SELECT ep.IdEvalPortfolio AS id, 'Portfolio' AS type_grille, CONCAT_WS(' ', s.nom, s.prenom) AS etudiant, CONCAT_WS(' ', e.nom, e.prenom) AS enseignant, ep.Statut AS statut, NULL AS date_limite, 'Aucun' AS retard, ep.anneeDebut AS annee
        FROM evalportfolio ep
        JOIN etudiantsbut2ou3 s ON ep.IdEtudiant = s.IdEtudiant
        LEFT JOIN enseignants e ON e.IdEnseignant = 1";
    foreach ($pdo->query($sqlPortfolio) as $row) { $taches[] = $row; }

    // Rapport
    $sqlRapport = "SELECT er.IdEvalRapport AS id, 'Rapport' AS type_grille, CONCAT_WS(' ', s.nom, s.prenom) AS etudiant, CONCAT_WS(' ', e.nom, e.prenom) AS enseignant, er.Statut AS statut, NULL AS date_limite, 'Aucun' AS retard, er.anneeDebut AS annee
        FROM evalrapport er
        JOIN etudiantsbut2ou3 s ON er.IdEtudiant = s.IdEtudiant
        LEFT JOIN enseignants e ON e.IdEnseignant = 1";
    foreach ($pdo->query($sqlRapport) as $row) { $taches[] = $row; }

    // Anglais
    $sqlAnglais = "SELECT ea.IdEvalAnglais AS id, 'Anglais' AS type_grille, CONCAT_WS(' ', s.nom, s.prenom) AS etudiant, CONCAT_WS(' ', e.nom, e.prenom) AS enseignant, ea.Statut AS statut, ea.dateS AS date_limite, 'Aucun' AS retard, ea.anneeDebut AS annee
        FROM evalanglais ea
        JOIN etudiantsbut2ou3 s ON ea.IdEtudiant = s.IdEtudiant
        LEFT JOIN enseignants e ON ea.IdEnseignant = e.IdEnseignant";
    foreach ($pdo->query($sqlAnglais) as $row) { $taches[] = $row; }

    // Soutenance
    $sqlSoutenance = "SELECT es.IdEvalSoutenance AS id, 'Soutenance' AS type_grille, CONCAT_WS(' ', s.nom, s.prenom) AS etudiant, CONCAT_WS(' ', e.nom, e.prenom) AS enseignant, es.Statut AS statut, NULL AS date_limite, 'Aucun' AS retard, es.anneeDebut AS annee
        FROM evalsoutenance es
        JOIN etudiantsbut2ou3 s ON es.IdEtudiant = s.IdEtudiant
        LEFT JOIN enseignants e ON e.IdEnseignant = 1";
    foreach ($pdo->query($sqlSoutenance) as $row) { $taches[] = $row; }

    // Stage
    $sqlStage = "SELECT es.IdEvalStage AS id, 'Stage' AS type_grille, CONCAT_WS(' ', s.nom, s.prenom) AS etudiant, CONCAT_WS(' ', e.nom, e.prenom) AS enseignant, es.Statut AS statut, es.date_h AS date_limite, 'Aucun' AS retard, es.anneeDebut AS annee
        FROM evalstage es
        JOIN etudiantsbut2ou3 s ON es.IdEtudiant = s.IdEtudiant
        LEFT JOIN enseignants e ON es.IdEnseignantTuteur = e.IdEnseignant";
    foreach ($pdo->query($sqlStage) as $row) { $taches[] = $row; }

    require_once __DIR__ . '/../views/taches/list.php';
}
