<?php

function evalstage_getEtudiantsRemontee(PDO $pdo): array {
    $sql = "SELECT 
                e.IdEtudiant,
                e.anneeDebut,
                et.nom,
                et.prenom,
                a.but3sinon2,
                e.note AS noteStage,
                pf.note AS notePortfolio,
                ea.note AS noteAnglais
            FROM EvalStage e
            JOIN EtudiantsBUT2ou3 et ON e.IdEtudiant = et.IdEtudiant
            JOIN AnneeStage a ON e.IdEtudiant = a.IdEtudiant AND a.anneeDebut = e.anneeDebut
            LEFT JOIN EvalPortFolio pf ON pf.IdEtudiant = e.IdEtudiant AND pf.anneeDebut = e.anneeDebut
            LEFT JOIN EvalAnglais ea ON ea.IdEtudiant = e.IdEtudiant AND ea.anneeDebut = e.anneeDebut
            WHERE e.Statut = 'REMONTEE'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function evalstage_getAllEtudiantsBUT2(PDO $pdo): array {
    $sql = "SELECT e.IdEtudiant, e.nom, e.prenom, s.anneeDebut,
                   s.note AS noteStage,
                   p.note AS notePortfolio
            FROM EtudiantsBUT2ou3 e
            LEFT JOIN EvalStage s 
                ON e.IdEtudiant = s.IdEtudiant
            LEFT JOIN EvalPortFolio p 
                ON e.IdEtudiant = p.IdEtudiant
            LEFT JOIN AnneeStage a
                ON e.IdEtudiant = a.IdEtudiant
            WHERE a.but3sinon2 = 0
            ORDER BY e.nom, e.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function evalstage_getAllEtudiantsBUT3(PDO $pdo): array {
    $sql = "SELECT e.IdEtudiant, e.nom, e.prenom, s.anneeDebut,
                   s.note AS noteStage,
                   p.note AS notePortfolio,
                   a.note AS noteAnglais
            FROM EtudiantsBUT2ou3 e
            LEFT JOIN EvalStage s 
                ON e.IdEtudiant = s.IdEtudiant
            LEFT JOIN EvalPortFolio p 
                ON e.IdEtudiant = p.IdEtudiant
            LEFT JOIN EvalAnglais a 
                ON e.IdEtudiant = a.IdEtudiant
            LEFT JOIN AnneeStage t
                ON e.IdEtudiant = t.IdEtudiant
            WHERE t.but3sinon2 = 1
            ORDER BY e.nom, e.prenom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}