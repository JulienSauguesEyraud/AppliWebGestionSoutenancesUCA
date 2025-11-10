-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 13 oct. 2025 à 14:56
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `soutenances`
--

CREATE DATABASE IF NOT EXISTS `soutenances`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `soutenances`;

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `remonter_grilles` ()   BEGIN
    UPDATE AnneeStage a
    JOIN EvalStage s ON s.IdEtudiant = a.IdEtudiant AND s.anneeDebut = a.anneeDebut
    JOIN EvalPortfolio p ON p.IdEtudiant = a.IdEtudiant AND p.anneeDebut = a.anneeDebut
    LEFT JOIN EvalAnglais e ON e.IdEtudiant = a.IdEtudiant AND e.anneeDebut = a.anneeDebut
    SET s.Statut = 'REMONTEE',
        p.Statut = 'REMONTEE',
        e.Statut = CASE WHEN a.but3sinon2 = 1 THEN 'REMONTEE' ELSE e.Statut END
    WHERE s.Statut = 'BLOQUEE'
      AND p.Statut = 'BLOQUEE'
      AND (a.but3sinon2 = 0 OR (a.but3sinon2 = 1 AND e.Statut = 'BLOQUEE'));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_remonter_notes` (IN `p_IdEtudiant` INT, IN `p_annee` INT)   BEGIN
  DECLARE v_isBUT3 BOOLEAN DEFAULT 0;
  DECLARE v_ok INT DEFAULT 0;

  -- Vérifier BUT2/BUT3
  SELECT but3sinon2 INTO v_isBUT3
  FROM AnneeStage
  WHERE IdEtudiant = p_IdEtudiant AND anneeDebut = p_annee
  LIMIT 1;

  -- Vérifier que Stage, Portfolio et Anglais (si BUT3) sont BLOQUEE
  SELECT COUNT(*) INTO v_ok
  FROM (
    SELECT 1 FROM EvalStage es
    JOIN ModelesGrilleEval mge ON es.IdModeleEval = mge.IdModeleEval
    WHERE es.IdEtudiant = p_IdEtudiant
    AND es.anneeDebut = p_annee
    AND TRIM(mge.natureGrille) = 'STAGE'
    AND es.Statut = 'BLOQUEE'
    
    UNION ALL
    
    SELECT 1 FROM EvalPortFolio ep
    JOIN ModelesGrilleEval mge ON ep.IdModeleEval = mge.IdModeleEval
    WHERE ep.IdEtudiant = p_IdEtudiant
    AND ep.anneeDebut = p_annee
    AND TRIM(mge.natureGrille) = 'PORTFOLIO'
    AND ep.Statut = 'BLOQUEE'
    
    UNION ALL
    
    SELECT 1 FROM EvalAnglais ea
    JOIN ModelesGrilleEval mge ON ea.IdModeleEval = mge.IdModeleEval
    WHERE ea.IdEtudiant = p_IdEtudiant
    AND ea.anneeDebut = p_annee
    AND TRIM(mge.natureGrille) = 'ANGLAIS'
    AND ea.Statut = 'BLOQUEE'
    AND v_isBUT3 = 1
  ) as checks;

  -- Validation : Stage + Portfolio (BUT2) ou Stage + Portfolio + Anglais (BUT3)
  IF (v_isBUT3 = 1 AND v_ok < 3) OR (v_isBUT3 = 0 AND v_ok < 2) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Conditions non satisfaites: toutes les grilles requises doivent être au statut BLOQUEE.';
  END IF;

  START TRANSACTION;

  -- Remonter Stage, Portfolio et Anglais (si BUT3)
  UPDATE EvalStage es
  JOIN ModelesGrilleEval m ON m.IdModeleEval = es.IdModeleEval
    AND TRIM(m.natureGrille) = 'STAGE'
  SET es.Statut = 'REMONTEE'
  WHERE es.IdEtudiant = p_IdEtudiant
    AND es.anneeDebut = p_annee
    AND es.Statut = 'BLOQUEE';

  UPDATE EvalPortFolio ep
  JOIN ModelesGrilleEval m ON m.IdModeleEval = ep.IdModeleEval
    AND TRIM(m.natureGrille) = 'PORTFOLIO'
  SET ep.Statut = 'REMONTEE'
  WHERE ep.IdEtudiant = p_IdEtudiant
    AND ep.anneeDebut = p_annee
    AND ep.Statut = 'BLOQUEE';

  IF v_isBUT3 THEN
    UPDATE EvalAnglais ea
    JOIN ModelesGrilleEval m ON m.IdModeleEval = ea.IdModeleEval
      AND TRIM(m.natureGrille) = 'ANGLAIS'
    SET ea.Statut = 'REMONTEE'
    WHERE ea.IdEtudiant = p_IdEtudiant
      AND ea.anneeDebut = p_annee
      AND ea.Statut = 'BLOQUEE';
  END IF;

  -- Remonter Rapport et Soutenance si possible (sans vérification préalable)
  UPDATE EvalRapport er
  JOIN ModelesGrilleEval m ON m.IdModeleEval = er.IdModeleEval
    AND TRIM(m.natureGrille) = 'RAPPORT'
  SET er.Statut = 'REMONTEE'
  WHERE er.IdEtudiant = p_IdEtudiant
    AND er.anneeDebut = p_annee
    AND er.Statut = 'BLOQUEE';

  UPDATE EvalSoutenance es
  JOIN ModelesGrilleEval m ON m.IdModeleEval = es.IdModeleEval
    AND TRIM(m.natureGrille) = 'SOUTENANCE'
  SET es.Statut = 'REMONTEE'
  WHERE es.IdEtudiant = p_IdEtudiant
    AND es.anneeDebut = p_annee
    AND es.Statut = 'BLOQUEE';

  COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `v_soutenances_avenir` (IN `p_IdEnseignant` INT)   BEGIN
    -- Soutenances de Stage
    SELECT
        'Stage' AS type_soutenance,
        DATE(e.date_h) AS date_soutenance,
        TIME(e.date_h) AS heure,
        et.nom,
        et.prenom,
        en.nom AS entreprise,
        a.nomMaitreStageApp AS maitre_stage,
        e.presenceMaitreStageApp AS present,
        CASE
            WHEN e.IdEnseignantTuteur = p_IdEnseignant THEN 'Tuteur'
            WHEN e.IdSecondEnseignant = p_IdEnseignant THEN 'Second'
        END AS role,
        CASE
            WHEN a.but3sinon2 = 0 THEN 'Stage 2A'
            WHEN a.alternanceBUT3 = 1 THEN 'Alternance 3A'
            ELSE 'Stage 3A'
        END AS type_stage,
        e.confidentiel,
        CONCAT(sa.idSalle,' : ',sa.description) AS salle
    FROM EvalStage e
    JOIN EtudiantsBUT2ou3 et
        ON et.IdEtudiant = e.IdEtudiant
    JOIN AnneeStage a
        ON a.IdEtudiant = et.IdEtudiant 
       AND a.anneeDebut = e.anneeDebut
    JOIN Entreprises en
        ON en.IdEntreprise = a.IdEntreprise
    LEFT JOIN Salles sa
        ON sa.IdSalle = e.IdSalle
    WHERE e.date_h > NOW()
      AND (e.IdEnseignantTuteur = p_IdEnseignant OR e.IdSecondEnseignant = p_IdEnseignant)

    UNION ALL


    -- Soutenances d’Anglais
    SELECT
        'Anglais' AS type_soutenance,
        DATE(a.dateS) AS date_soutenance,
        TIME(a.dateS) AS heure,
        et.nom,
        et.prenom,
        en.nom AS entreprise,
        an.nomMaitreStageApp AS maitre_stage,
        NULL AS present,
        'Correcteur' AS role, -- si EvalAnglais n’a qu’un enseignant
        'Anglais' AS type_stage,
        0 as confidentiel,
        CONCAT(sa.idSalle,' : ',sa.description) AS salle
    FROM EvalAnglais a
    JOIN EtudiantsBUT2ou3 et
        ON et.IdEtudiant = a.IdEtudiant
    JOIN AnneeStage an
        ON an.IdEtudiant = a.IdEtudiant 
    JOIN Entreprises en
        ON en.IdEntreprise = an.IdEntreprise
    LEFT JOIN Salles sa
        ON sa.IdSalle = a.IdSalle
    WHERE a.dateS > NOW()
      AND a.IdEnseignant = p_IdEnseignant

    ORDER BY date_soutenance ASC, heure ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `v_soutenances_finies` (IN `p_IdEnseignant` INT)   BEGIN
    SELECT
        et.nom,
        et.prenom,
        en.nom AS entreprise,

        CASE
            WHEN e.Statut = 'DIFFUSEE' THEN 'NOTES diffusées'
            WHEN e.Statut = 'REMONTEE' THEN 'NOTES remontées'
            WHEN e.Statut = 'VALIDEE' THEN 'Grilles validées'
	    WHEN e.Statut = 'BLOQUEE' THEN 'Grilles bloquées'
            ELSE 'Saisie en cours / À VALIDER'
        END AS statut,
        DATE(e.date_h) AS date_soutenance,
        TIME(e.date_h) AS heure
    FROM EvalStage e
    JOIN EtudiantsBUT2ou3 et
        ON et.IdEtudiant = e.IdEtudiant
    JOIN AnneeStage a
        ON a.IdEtudiant = et.IdEtudiant
       AND a.anneeDebut = e.anneeDebut
    JOIN Entreprises en
        ON en.IdEntreprise = a.IdEntreprise
    WHERE e.date_h <= NOW()
      AND (e.IdEnseignantTuteur = p_IdEnseignant OR e.IdSecondEnseignant = p_IdEnseignant)
    

UNION ALL

 SELECT
        et.nom,
        et.prenom,
        en.nom AS entreprise,

        CASE
            WHEN sa.Statut = 'DIFFUSEE' THEN 'NOTES diffusées'
            WHEN sa.Statut = 'REMONTEE' THEN 'NOTES remontées'
            WHEN sa.Statut = 'VALIDEE' THEN 'Grilles validées'
            ELSE 'Saisie en cours / À VALIDER'
        END AS statut,
        DATE(sa.dateS) AS date_soutenance,
        TIME(sa.dateS) AS heure
    FROM EvalAnglais sa
    JOIN EtudiantsBUT2ou3 et
        ON et.IdEtudiant = sa.IdEtudiant
    JOIN AnneeStage a
        ON a.IdEtudiant = et.IdEtudiant
       AND a.anneeDebut = sa.anneeDebut
    JOIN Entreprises en
        ON en.IdEntreprise = a.IdEntreprise
    WHERE sa.dateS <= NOW()
      AND sa.IdEnseignant = p_IdEnseignant
    ORDER BY date_soutenance DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `anneestage`
--

CREATE TABLE `anneestage` (
  `anneeDebut` smallint(6) NOT NULL,
  `IdEtudiant` smallint(6) NOT NULL,
  `IdEntreprise` smallint(6) DEFAULT NULL,
  `but3sinon2` tinyint(1) NOT NULL,
  `alternanceBUT3` tinyint(1) NOT NULL,
  `nomMaitreStageApp` varchar(50) DEFAULT NULL,
  `sujet` varchar(200) NOT NULL,
  `noteEntreprise` float DEFAULT NULL CHECK (`noteEntreprise` >= 0),
  `typeMission` varchar(50) DEFAULT NULL,
  `cadreMission` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `anneestage`
--

INSERT INTO `anneestage` (`anneeDebut`, `IdEtudiant`, `IdEntreprise`, `but3sinon2`, `alternanceBUT3`, `nomMaitreStageApp`, `sujet`, `noteEntreprise`, `typeMission`, `cadreMission`) VALUES
(2023, 40, NULL, 0, 0, NULL, '', NULL, NULL, NULL),
(2023, 41, 1, 0, 0, 'maitre stape airbus 2023 ', 'sujet stage airbus 2023 but2 ', NULL, NULL, 'mission airbus 2023 but2 '),
(2023, 42, 2, 1, 1, 'maitre app renault 2023 but3 app ', 'sujet  app renault 2023 but3 app ', NULL, NULL, 'mission  app renault 2023 but3 app '),
(2024, 1, 11, 0, 0, 'Maitre app CAPGEMINI 2024 ', 'app web ', NULL, 'dev back office', NULL),
(2025, 1, 20, 1, 0, 'maitre app dubois', 'creation d\'une site web', NULL, NULL, NULL),
(2025, 2, 2, 1, 1, 'maitre app Renault', 'application VR avec Unity ', NULL, 'dev 3D ', NULL),
(2025, 3, 3, 0, 0, 'MAitre Stage BUT2  ', 'dev en pyhton - info indus.', NULL, 'programmation', NULL),
(2025, 4, 3, 1, 1, 'Maitre App Toal ', 'solution de simulation ', NULL, 'dev3D & VR', '?'),
(2025, 5, 4, 0, 0, 'maitre stage orange', 'sitre intranet', NULL, 'dev web ', NULL),
(2025, 6, 6, 0, 0, 'maitre app EDF 2025 ', 'dev app mobile ', NULL, 'developpement et UI ', NULL),
(2025, 7, 7, 1, 1, 'MA Dassault 2025 ', 'App RA ', NULL, 'dev c# ', 'Confidentiel  '),
(2025, 8, 1, 0, 0, 'MAairbus', 'avion', NULL, NULL, NULL),
(2025, 9, 8, 1, 0, 'MAsafran', 'safran', NULL, NULL, NULL),
(2025, 10, 7, 1, 0, 'fqsf', 'fazefa', NULL, NULL, NULL),
(2025, 11, 17, 1, 1, 'MaMichelin', 'voiture', NULL, NULL, NULL),
(2025, 13, 4, 1, 0, 'MAOrange', 'réseau', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `anneesuniversitaires`
--

CREATE TABLE `anneesuniversitaires` (
  `anneeDebut` smallint(6) NOT NULL CHECK (`anneeDebut` > 2020),
  `fin` smallint(6) NOT NULL CHECK (`fin` > `anneeDebut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `anneesuniversitaires`
--

INSERT INTO `anneesuniversitaires` (`anneeDebut`, `fin`) VALUES
(2022, 2023),
(2023, 2024),
(2024, 2025),
(2025, 2026),
(2028, 2029),
(2029, 2030);

-- --------------------------------------------------------

--
-- Structure de la table `critereseval`
--

CREATE TABLE `critereseval` (
  `IdCritere` smallint(6) NOT NULL,
  `descLongue` varchar(500) DEFAULT NULL,
  `descCourte` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `critereseval`
--

INSERT INTO `critereseval` (`IdCritere`, `descLongue`, `descCourte`) VALUES
(1, 'à remplir uniquement par l\'enseignant responsable du stage : l\'ENSEIGNANT TUTEUR\nRespecter scrupuleusement l\'évaluation fournis par l\'entreprise', 'Evaluation du stage par l\'entreprise'),
(2, 'A titre indicatif : ci-dessous les critères d’évaluation  Niveau de travail demandé / 5 :Le niveau de travail demandé par l entreprise était :\\r\\n 0 Insuffisant 0.5 Très Facile 1 Facile 2 Classique 3 Difficile 4 Très difficile 5 exceptionnel (dépassant le BUT) \\r\\n\\r\\nLa qualité / quantité du travail fourni /5    \\n La qualité et  la quantité du travail fourni par le stagiaire était : 0 Très 0.5 Insuffisant 1 Insuffisant 2 Moyen 3 Bon 4 Très bon 5 exceptionnel (dépassant le BUT)', 'Evaluation du stage par l\'enseignant TUTEUR'),
(3, 'Cette note est le résultat de la grille d\'évaluation du rapport de stage, remplie par l\'enseignant tuteur qui a suivi le stage', 'Evaluation du rapport de stage'),
(4, 'cette note est le résultat des grilles d\'évaluation des stages remplies par les 2 enseignants du jury.  Elle ne devrait pas être modifiée ', 'Evaluation de la soutenance de stage'),
(5, 'Présentation claire de l’entreprise et de son secteur d’activité, des enjeux de la structure, et des missions confiées ainsi que de l’utilité du travail réalisé pour l’entreprise.', 'Contexte et missions'),
(6, 'Le point technique présenté est pertinent, bien expliqué, avec un bon niveau de complexité.', 'Choix du point technique et complexité'),
(7, 'L’étudiant utilise un vocabulaire professionnel précis, adapté au domaine d’activité. Il montre qu’il comprend ce qu’il dit et qu’il maîtrise la thématique abordée. Il sait vulgariser les concepts techniques pour un public non expert. Il sait approfondir son propos face à un public expert.', 'Maîtrise du langage professionnel'),
(8, 'La démonstration doit porter sur un livrable finalisé ou un outil utilisé durant le stage. Elle est en lien avec les missions présentées. Les explications sont claires pendant la manipulation. Le discours est accessible, vulgarisé et appuyé d’exemples concrets. La démonstration est intégrée avec fluidité dans la présentation.', 'Qualité de la démonstration'),
(9, 'La conclusion permet l’identification des compétences techniques et humaines acquises. L’étudiant fait le lien avec son projet professionnel. Une conclusion improvisée sera pénalisée.', 'Développement des compétences et PPP'),
(10, 'Diapositives lisibles, structurées, illustrées (graphiques, images, schémas légendés), sans surcharge. Cohérence visuelle. Pas de fautes récurrentes ou grossières. Aucun texte seul. La diapo conclusion est structurée.', 'Qualité du support visuel'),
(11, 'Le support est utilisé pour illustrer activement les propos (pas un simple décor ni une lecture de texte).', 'Exploitation du support'),
(12, 'Expression fluide, rythme, voix posée, regard, gestuelle, gestion du temps, langage soutenu. Pas d’expression ou de mot familiers.', 'Communication orale'),
(13, 'Posture professionnelle, écoute active, capacité à accueillir les remarques pendant les questions.', 'Attitude et maturité'),
(14, '1pt : PAragraphe succintement le dossier    3pts : presente le theme et le dossier succintement et simplement  4pts : mets en evidence le thème du dossier  6pt : présente le dossier d\'une faon claire et organisée', 'Expression orale en CONTINU'),
(15, '2pt : peut intervenir mais la communication repose sur l\'aide apportée par l\'examinateur   3pt : repond et régit de facon simple mais sans prendre l\'initiative    5pts : s\'implique dans l\'échange   7pt : parvient a faire ressortir de facon convaincante ce qu\'il a compris des documents ', 'Expression Orale en INTERACTION'),
(16, '1pt : est partiellement compréhensible    3pt : s\'exprime dans une langue globalement compréhensible    5pts: s\'exprime dans une langue globalement correcte et intelligible    7pt : s\'exprime dans une langue fluide et correcte', 'compétences linguistiques'),
(17, 'criter eval stage 2023 \r\n\r\nc\'est un critere d\'une ancienne grille . ne devrait pas apparaître . conservé pour historique', 'criter eval stage 2023 '),
(18, 'Présentation claire de l’organisation générale du portfolio (page, d’accueil, catégories, navigation, types de contenus). Explication du lien entre la construction du portfolio et le projet personnel et professionnel de l’étudiant (PPP).', 'Introduction du portfolio'),
(19, 'Utilisation de visuels pertinents (captures, vidéos, schémas…). Mise en page lisible, hiérarchie claire de l\'information, design cohérent avec le projet professionnel', 'Qualité et pertinence du portfolio'),
(20, 'Contexte, objectifs, étapes du projet et outils utilisés expliqués de manière synthétique et structurée.', 'Présentation d’un projet spécifique'),
(21, 'Capacité à identifier, illustrer et articuler les compétences développées (techniques et humaines), avec un lien explicite au PPP.', 'Approche réflexive'),
(22, 'Posture, gestion du temps, articulation, regard, gestuelle. L’étudiant capte l’attention du jury', 'Communication orale'),
(23, 'L’étudiant s’appuie habilement sur son portfolio pour illustrer son propos', 'Appui du support pour la mise en valeur'),
(24, 'Originalité de la présentation ou difficulté dans la réalisation du portfolio', 'BONUS'),
(25, 'Contexte bien posé, objectifs clairs, cheminement annoncé. Conclusion synthétique, bilan et ouverture professionnelle.', 'Pertinence de l’introduction et de la conclusion'),
(26, 'Entreprise bien décrite (structure, organisation, équipe, missions). Place de l\'étudiant clairement identifiée.', 'Présentation du contexte professionnel'),
(27, 'Présentation claire des missions, structuration en partie générale et technique. Problèmes identifiés et solutions argumentées.', 'Qualité de l’analyse expérimentale'),
(28, 'Compétences techniques et humaines identifiées. Lien explicite avec le projet personnel et professionnel.', 'Développement des compétences et PPP'),
(29, 'Qualité des procédés graphiques utilisés (captures d’écran, schémas, images, photos) et pertinence de l’explication associée. Présence d’au moins 10 sources de qualité (ouvrages de référence, articles scientifiques, sources spécialisées). Bibliographie structurée et correctement référencée.', 'Utilisation de procédés graphiques et sources pertinentes'),
(30, 'Présence de tous les éléments attendus : couverture, page de garde, sommaire, tables des illustrations, pagination, remerciements, illustrations légendées, bibliographie, annexes.', 'Respect de la structure demandée'),
(31, 'Titres hiérarchisés, alinéas, texte justifié, présentation agréable. Graphiques et tableaux lisibles, bien insérés et numérotés.', 'Clarté de la mise en page'),
(32, 'Orthographe correcte, syntaxe claire, vocabulaire maîtrisé. Pas de fautes récurrentes ou grossières. Un style d’écriture grossièrement robotique est pénalisé.', 'Langue et orthographe'),
(33, 'Légendes aux illustrations, renvois aux annexes, index/glossaire si nécessaire. Table des illustrations placée après le sommaire', 'Cohérence graphique et rigueur documentaire'),
(34, 'Longueur du texte (20–25 pages hors annexes), format PDF, poids < 10 Mo, rendu via UCA Suivi Stage.', 'Respect des consignes de rendu');

--
-- Déclencheurs `critereseval`
--
DELIMITER $$
CREATE TRIGGER `CheckCritereEvalInsert` BEFORE INSERT ON `critereseval` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM CriteresEval WHERE descLongue= NEW.descLongue OR descCourte=NEW.descCourte) THEN 
        SET NEW.descLongue = NULL;
	SET NEW.descCourte = NULL;
    END IF;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `enseignants`
--

CREATE TABLE `enseignants` (
  `IdEnseignant` smallint(6) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `mail` varchar(80) NOT NULL,
  `mdp` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `enseignants`
--

INSERT INTO `enseignants` (`IdEnseignant`, `nom`, `prenom`, `mail`, `mdp`) VALUES
(1, 'Martin', 'Sophie', 'sophie.martin@univ-lyon.fr', '39d3a2e969b3dec11478d65ecb96ee6066ebd98920c816df9de7d131d9c67d0c'),
(2, 'Durand', 'Pierre', 'pierre.durand@univ-lyon.fr', '15664d36cc71b0be3b70c6fe704e39257cbe3bb215c0a329abc4afaff379698b'),
(3, 'Bernard', 'Claire', 'claire.bernard@univ-lyon.fr', '3a4d5b86cfa208b50f1175f39165355a3b05a4ce8ef30fa6ee9ea326905efe9b'),
(4, 'Petit', 'Julien', 'julien.petit@univ-lyon.fr', 'c03a43a858c183d2f07ffeafecaeafa8b31f3bf4f51448221d08ed9848253a8a'),
(5, 'Robert', 'Isabelle', 'isabelle.robert@univ-lyon.fr', '2238dd61a1bf83816b40ad894518814b8edf7221d84d897ffd2c0466ace07c41'),
(6, 'Richard', 'Thomas', 'thomas.richard@univ-lyon.fr', '1deb0a3b86750d0e4a4c21dc9601736954ae5ccac654c57f3d45563a1f595998'),
(7, 'Durieux', 'Camille', 'camille.durieux@univ-lyon.fr', '3c9ef432fc3bce3f9e3975464cf69e9eccdcb57706e93f29e5006405c3a3de88'),
(8, 'Moreau', 'Lucas', 'lucas.moreau@univ-lyon.fr', '593ee8628ec754d503fb1d0b38d08e4882070cb8ffc11f8a538c2ebd8237647e'),
(9, 'Simon', 'Nathalie', 'nathalie.simon@univ-lyon.fr', '7c6e1bf16286b2bcb2790eee9d42a406f0de86615436d85baf86efa3b7d9eb20'),
(10, 'Laurent', 'Antoine', 'antoine.laurent@univ-lyon.fr', '009069811c400a8f8edf00a40f583653182531239f89eb55a857bd6cc491a655'),
(11, 'Michel', 'Elise', 'elise.michel@univ-lyon.fr', 'b7ba366af35cfabe87a7ea8db02b824c50df329100727f5c8b4aea8d782e7939'),
(12, 'Garcia', 'David', 'david.garcia@univ-lyon.fr', '2c3aaefea8267c66822f6edc0e42d9b7384695f9c0407eabda141770aab8901e'),
(13, 'Roux', 'Caroline', 'caroline.roux@univ-lyon.fr', 'c918eb6accf7327b596878e9b462596a2ff84b3d7f095323b970f2c7f1b2038b'),
(14, 'Fournier', 'Alexandre', 'alexandre.fournier@univ-lyon.fr', '71a1f722960c0f8ec291de8ec781cea78a55f6aecbb22f78c3b58ac3b284eadb'),
(15, 'Girard', 'Hélène', 'helene.girard@univ-lyon.fr', 'f7268b0c0520e34f7a99a1641f34a82a043985e5ab4c39b4d28439207cf6a53e');

--
-- Déclencheurs `enseignants`
--
DELIMITER $$
CREATE TRIGGER `CheckEnseignantUpdate` BEFORE UPDATE ON `enseignants` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Enseignants WHERE mail = NEW.mail AND IdEnseignant <> OLD.IdEnseignant AND mail <> OLD.mail ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre un enseignants qui a une adresse mail qui existe déjà.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `hash_mdp_before_insert` BEFORE INSERT ON `enseignants` FOR EACH ROW BEGIN
  IF CHAR_LENGTH(NEW.mdp) != 64 THEN
    SET NEW.mdp = SHA2(NEW.mdp, 256);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `hash_mdp_before_update` BEFORE UPDATE ON `enseignants` FOR EACH ROW BEGIN
  IF NEW.mdp != OLD.mdp AND CHAR_LENGTH(NEW.mdp) != 64 THEN
    SET NEW.mdp = SHA2(NEW.mdp, 256);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `supprimerEnseignant` BEFORE DELETE ON `enseignants` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM EvalStage WHERE IdEnseignantTuteur = OLD.idEnseignant OR IdSecondEnseignant = OLD.idEnseignant) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de supprimer cet enseignant : il a été associé à au moins une évaluation.';
    END IF;


IF EXISTS(SELECT 1 FROM EvalAnglais WHERE IdEnseignant  = OLD.idEnseignant) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de supprimer cet enseignant : il a été associé à au moins une évaluation.';
    END IF;
    

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `entreprises`
--

CREATE TABLE `entreprises` (
  `IdEntreprise` smallint(6) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `villeE` varchar(50) NOT NULL,
  `codePostal` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `entreprises`
--

INSERT INTO `entreprises` (`IdEntreprise`, `nom`, `villeE`, `codePostal`) VALUES
(1, 'Airbus', 'Toulouse', '31000'),
(2, 'Renault', 'Boulogne-Billancourt', '92100'),
(3, 'TotalEnergies', 'Courbevoie', '92400'),
(4, 'Orange', 'Paris', '75015'),
(5, 'SNCF', 'Saint-Denis', '93200'),
(6, 'EDF', 'Paris', '75008'),
(7, 'Dassault Aviation', 'Saint-Cloud', '92210'),
(8, 'Safran', 'Issy-les-Moulineaux', '92130'),
(9, 'Thales', 'La Défense', '92000'),
(10, 'Bouygues Construction', 'Guyancourt', '78280'),
(11, 'Capgemini', 'Paris', '75017'),
(12, 'Engie', 'Courbevoie', '92400'),
(13, 'Suez', 'La Défense', '92000'),
(14, 'Veolia', 'Aubervilliers', '93300'),
(15, 'Saint-Gobain', 'Courbevoie', '92400'),
(16, 'PSA Peugeot Citroën', 'Poissy', '78300'),
(17, 'Michelin', 'Clermont-Ferrand', '63000'),
(18, 'Alstom', 'Saint-Ouen', '93400'),
(19, 'Vinci', 'Rueil-Malmaison', '92500'),
(20, 'AccorHotels', 'Issy-les-Moulineaux', '92130'),
(21, 'Publicis Groupe', 'Paris', '75008'),
(22, 'Société Générale', 'Paris', '75009'),
(23, 'BNP Paribas', 'Paris', '75009'),
(24, 'Crédit Agricole', 'Montrouge', '92120'),
(25, 'AXA', 'Paris', '75008'),
(26, 'La Poste', 'Paris', '75015'),
(27, 'Carrefour', 'Massy', '91300'),
(28, 'Auchan Retail', 'Villeneuve-d’Ascq', '59650'),
(29, 'Decathlon', 'Villeneuve-d’Ascq', '59650'),
(30, 'LVMH', 'Paris', '75008');

--
-- Déclencheurs `entreprises`
--
DELIMITER $$
CREATE TRIGGER `CheckEntreprisesInsert` BEFORE INSERT ON `entreprises` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Entreprises WHERE nom = NEW.nom  ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre une entreprise qui a un nom qui existe déjà.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `CheckEntreprisesUpdate` BEFORE UPDATE ON `entreprises` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Entreprises WHERE nom = NEW.nom AND IdEntreprise <> NEW.IdEntreprise ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre une entreprise qui a un nom qui existe déjà.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `supprimerEntreprise` BEFORE DELETE ON `entreprises` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM  AnneeStage WHERE IdEntreprise = OLD.IdEntreprise )  THEN 
    
SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de supprimer cette entreprise : elle a été associé à au moins un stage.';
    END IF;


END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `etudiantsbut2ou3`
--

CREATE TABLE `etudiantsbut2ou3` (
  `IdEtudiant` smallint(6) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `mail` varchar(80) NOT NULL,
  `empreinte` varchar(256) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `etudiantsbut2ou3`
--

INSERT INTO `etudiantsbut2ou3` (`IdEtudiant`, `nom`, `prenom`, `mail`, `empreinte`) VALUES
(1, 'Dubois', 'Mathieu', 'mathieu.dubois@etu.univ-lyon.fr', 'EmpreinteDubois'),
(2, 'Lefevre', 'Alice', 'alice.lefevre@etu.univ-lyon.fr', 'Empreintesdf'),
(3, 'Morel', 'Hugo', 'hugo.morel@etu.univ-lyon.fr', 'Empreinteazd'),
(4, 'Lambert', 'Emma', 'emma.lambert@etu.univ-lyon.fr', 'Empreinteqzg'),
(5, 'Fontaine', 'Lucas', 'julien.saugues--eyraud@etu.uca.fr', 'ef2d127de37b942baad06145e54b0c619a1f22327b2ebbcfbec78f5564afe39d'),
(6, 'Chevalier', 'Sarah', 'sarah.chevalier@etu.univ-lyon.fr', 'EmpreinteDG'),
(7, 'Blanc', 'Thomas', 'thomas.blanc@etu.univ-lyon.fr', 'EmpreinteGDF'),
(8, 'Guillaume', 'Manon', 'manon.guillaume@etu.univ-lyon.fr', 'Empreintefsdf'),
(9, 'Legrand', 'Nathan', 'nathan.legrand@etu.univ-lyon.fr', 'Empreintedfng'),
(10, 'Marchand', 'Chloé', 'chloe.marchand@etu.univ-lyon.fr', 'Empreintejg'),
(11, 'Perrin', 'Julien', 'julien.perrin@etu.univ-lyon.fr', 'Empreintefsd'),
(12, 'Barbier', 'Camille', 'camille.barbier@etu.univ-lyon.fr', 'Empreintebvcn'),
(13, 'Renard', 'Léo', 'leo.renard@etu.univ-lyon.fr', 'Empreintesdhg'),
(14, 'Renaud', 'Inès', 'ines.renaud@etu.univ-lyon.fr', 'Empreintecv'),
(15, 'Charles', 'Adrien', 'adrien.charles@etu.univ-lyon.fr', 'Empreinte ds'),
(16, 'Moulin', 'Sophie', 'sophie.moulin@etu.univ-lyon.fr', 'Empreintedsf'),
(17, 'Lopez', 'Antoine', 'antoine.lopez@etu.univ-lyon.fr', 'Empreintebfg'),
(18, 'Garnier', 'Laura', 'laura.garnier@etu.univ-lyon.fr', 'Empreinte54'),
(19, 'Faure', 'Clément', 'clement.faure@etu.univ-lyon.fr', 'Empreinte4345'),
(20, 'Andre', 'Eva', 'eva.andre@etu.univ-lyon.fr', 'Empreinte54345'),
(21, 'Mercier', 'Alexandre', 'alexandre.mercier@etu.univ-lyon.fr', 'Empreinte543...'),
(22, 'Dupuis', 'Lina', 'lina.dupuis@etu.univ-lyon.fr', 'Empreinte45.54'),
(23, 'Meyer', 'Maxime', 'maxime.meyer@etu.univ-lyon.fr', 'Empreint454e'),
(24, 'Lucas', 'Elodie', 'elodie.lucas@etu.univ-lyon.fr', 'Empreinte35345'),
(25, 'Henry', 'Bastien', 'bastien.henry@etu.univ-lyon.fr', 'Empreinte786'),
(26, 'Riviere', 'Amélie', 'amelie.riviere@etu.univ-lyon.fr', 'Empreinte543.4'),
(27, 'Noel', 'Victor', 'victor.noel@etu.univ-lyon.fr', 'Empreintegsd '),
(28, 'Giraud', 'Mélanie', 'melanie.giraud@etu.univ-lyon.fr', 'Empreinsdfs dte'),
(29, 'Francois', 'Alexis', 'alexis.francois@etu.univ-lyon.fr', 'Empreintcze'),
(30, 'Collet', 'Justine', 'justine.collet@etu.univ-lyon.fr', 'Empreintczree'),
(31, 'Schmitt', 'Paul', 'paul.schmitt@etu.univ-lyon.fr', 'Empreintczze'),
(32, 'Fernandez', 'Clara', 'clara.fernandez@etu.univ-lyon.fr', 'Empreintzcreze'),
(33, 'Benoit', 'Arthur', 'arthur.benoit@etu.univ-lyon.fr', 'Empreinterczcr'),
(34, 'Perrot', 'Amandine', 'amandine.perrot@etu.univ-lyon.fr', 'Empreintzcee'),
(35, 'Dupont', 'Hugo', 'hugo.dupont@etu.univ-lyon.fr', 'Empreintezcre'),
(36, 'Masson', 'Julie', 'julie.masson@etu.univ-lyon.fr', 'Empreintzevee'),
(37, 'Caron', 'Romain', 'romain.caron@etu.univ-lyon.fr', 'Empreintevzevtz'),
(38, 'Pires', 'Sonia', 'sonia.pires@etu.univ-lyon.fr', 'Empreintezevetvz'),
(39, 'Bonnet', 'Quentin', 'quentin.bonnet@etu.univ-lyon.fr', 'Empreintzvetzevtze'),
(40, 'Colin', 'Aurélie', 'aurelie.colin@etu.univ-lyon.fr', 'Empreintezvz'),
(41, 'Rolland', 'Benoît', 'benoit.rolland@etu.univ-lyon.fr', 'Empreintrbyrte'),
(42, 'Olivier', 'Marion', 'marion.olivier@etu.univ-lyon.fr', 'Empreinterbtb'),
(43, 'Da Silva', 'Kevin', 'kevin.dasilva@etu.univ-lyon.fr', 'Empreintebyre'),
(44, 'Hubert', 'Marine', 'marine.hubert@etu.univ-lyon.fr', 'Empreintezbt'),
(45, 'Gaillard', 'Samuel', 'samuel.gaillard@etu.univ-lyon.fr', 'Empreintbzbte'),
(46, 'Brun', 'Charlotte', 'charlotte.brun@etu.univ-lyon.fr', 'Empreintzbtezte'),
(47, 'Baron', 'Florian', 'florian.baron@etu.univ-lyon.fr', 'Empreintrbyre'),
(48, 'Menard', 'Océane', 'oceane.menard@etu.univ-lyon.fr', 'Empreinterbyrc'),
(49, 'Jacquet', 'Yanis', 'yanis.jacquet@etu.univ-lyon.fr', 'Empreintevyee'),
(50, 'Rodriguez', 'Anaïs', 'anais.rodriguez@etu.univ-lyon.fr', 'Empreiveyente');

--
-- Déclencheurs `etudiantsbut2ou3`
--
DELIMITER $$
CREATE TRIGGER `CheckEtudiantInsert` BEFORE INSERT ON `etudiantsbut2ou3` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Etudiantsbut2ou3 WHERE mail = NEW.mail OR empreinte=NEW.empreinte) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible d''ajouter un étudiant qui a une adresse mail ou une empreinte qui existe déjà.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `CheckEtudiantUpdate` BEFORE UPDATE ON `etudiantsbut2ou3` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Etudiantsbut2ou3 WHERE (mail = NEW.mail) AND IdEtudiant <> OLD.IdEtudiant AND mail <> OLD.mail ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre un étudiant qui a une adresse mail ou une empreinte qui existe déjà.';
    END IF;
    SET NEW.empreinte = SHA2(NEW.IdEtudiant, 256);

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `supprimerEtudiant` BEFORE DELETE ON `etudiantsbut2ou3` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM  AnneeStage WHERE IdEtudiant = OLD.IdEtudiant )  THEN 
    
SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de supprimer cet étudiant : il a été associé à au moins un stage.';
    END IF;


END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `evalanglais`
--

CREATE TABLE `evalanglais` (
  `IdEvalAnglais` smallint(6) NOT NULL,
  `dateS` datetime DEFAULT NULL,
  `note` float DEFAULT NULL CHECK (`note` >= 0),
  `commentaireJury` varchar(200) DEFAULT NULL,
  `Statut` varchar(15) NOT NULL,
  `IdSalle` varchar(10) DEFAULT NULL,
  `IdEnseignant` smallint(6) DEFAULT NULL,
  `anneeDebut` smallint(6) NOT NULL,
  `IdModeleEval` smallint(6) NOT NULL,
  `IdEtudiant` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evalanglais`
--

INSERT INTO `evalanglais` (`IdEvalAnglais`, `dateS`, `note`, `commentaireJury`, `Statut`, `IdSalle`, `IdEnseignant`, `anneeDebut`, `IdModeleEval`, `IdEtudiant`) VALUES
(1, '2026-06-21 09:00:46', 9, 'commentaire anglais et 7 ', 'SAISIE', '261', 1, 2025, 3, 7),
(4, '2025-09-09 12:40:00', NULL, NULL, 'BLOQUEE', 'AmphiB1', 1, 2025, 1, 9),
(5, NULL, NULL, NULL, 'SAISIE', NULL, 1, 2025, 3, 4),
(6, '2024-09-09 12:40:00', NULL, NULL, 'SAISIE', '355', 1, 2025, 3, 11),
(7, '2025-02-18 09:40:00', NULL, NULL, 'SAISIE', '261', 1, 2025, 1, 13);

--
-- Déclencheurs `evalanglais`
--
DELIMITER $$
CREATE TRIGGER `CheckEvalAnglaisInsert` BEFORE INSERT ON `evalanglais` FOR EACH ROW BEGIN

    IF EXISTS(
SELECT 1 
FROM anneestage
WHERE  IdEtudiant=NEW.IdEtudiant AND anneeDebut=NEW.anneeDebut AND but3sinon2=0 ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre une soutenance d''anglais à un étudiant en 2ème année.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_anglais_no_overlap_INSERT` BEFORE INSERT ON `evalanglais` FOR EACH ROW BEGIN
    -- Vérif étudiant déjà occupé
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdEtudiant = NEW.IdEtudiant
        UNION
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND IdEtudiant = NEW.IdEtudiant
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Cet étudiant a déjà une soutenance à cette date/heure';
    END IF;

    -- Vérif prof déjà occupé
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdEnseignant = NEW.IdEnseignant
        UNION
        SELECT 1 FROM EvalStage
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND (IdEnseignantTuteur = NEW.IdEnseignant 
               OR IdSecondEnseignant = NEW.IdEnseignant)
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Cet enseignant est déjà assigné sur cette heure';
    END IF;

    -- Vérif salle déjà occupée
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdSalle = NEW.IdSalle
        UNION
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND IdSalle = NEW.IdSalle
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: La salle est déjà occupée sur cette heure';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_anglais_no_overlap_UPDATE` BEFORE UPDATE ON `evalanglais` FOR EACH ROW BEGIN
    -- Vérif étudiant déjà occupé
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdEtudiant = NEW.IdEtudiant
          AND IdEvalAnglais <> OLD.IdEvalAnglais
        UNION
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND IdEtudiant = NEW.IdEtudiant
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Cet étudiant a déjà une soutenance à cette date/heure';
    END IF;

    -- Vérif prof déjà occupé
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdEnseignant = NEW.IdEnseignant
          AND IdEvalAnglais <> OLD.IdEvalAnglais
        UNION
        SELECT 1 FROM EvalStage
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND ((IdEnseignantTuteur = NEW.IdEnseignant AND IdEnseignantTuteur <> OLD.IdEnseignant)
               OR (IdSecondEnseignant = NEW.IdEnseignant AND IdSecondEnseignant <> OLD.IdEnseignant))
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Cet enseignant est déjà assigné sur cette heure';
    END IF;

    -- Vérif salle déjà occupée
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdSalle = NEW.IdSalle
          AND IdEvalAnglais <> OLD.IdEvalAnglais
        UNION
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND IdSalle = NEW.IdSalle
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: La salle est déjà occupée sur cette heure';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_check_soutenance_conflict_INSERT` BEFORE INSERT ON `evalanglais` FOR EACH ROW BEGIN
    -- Étudiant déjà occupé (Anglais ou Stage/Portfolio)
    IF EXISTS (
        SELECT 1 FROM EvalAnglais WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20 AND IdEtudiant = NEW.IdEtudiant
        UNION
        SELECT 1 FROM EvalStage WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60 AND IdEtudiant = NEW.IdEtudiant
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur: Cet étudiant a déjà une soutenance à cette date/heure';
    END IF;

    -- Professeur déjà occupé (cross-type)
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60 AND IdSalle = NEW.IdSalle AND
              (IdEnseignantTuteur = NEW.IdEnseignant OR IdSecondEnseignant = NEW.IdEnseignant)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur: L’enseignant a déjà un Stage/Portfolio à cette date/heure/salle';
    END IF;

    -- Salle déjà occupée
    IF EXISTS (
        SELECT 1 FROM EvalStage WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60 AND IdSalle = NEW.IdSalle
        UNION
        SELECT 1 FROM EvalAnglais WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20 AND IdSalle = NEW.IdSalle
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur: La salle est déjà occupée à cette date/heure';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_check_soutenance_conflict_UPDATE` BEFORE UPDATE ON `evalanglais` FOR EACH ROW BEGIN
    -- Étudiant déjà occupé (Anglais ou Stage/Portfolio)
    IF EXISTS (
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdEtudiant = NEW.IdEtudiant
          AND IdEvalAnglais <> OLD.IdEvalAnglais  -- exclure la ligne en cours d'update
        UNION
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60 
          AND IdEtudiant = NEW.IdEtudiant
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur: Cet étudiant a déjà une soutenance à cette date/heure';
    END IF;

    -- Professeur déjà occupé (cross-type)
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND IdSalle = NEW.IdSalle
          AND (IdEnseignantTuteur = NEW.IdEnseignant OR IdSecondEnseignant = NEW.IdEnseignant)
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur: L’enseignant a déjà un Stage/Portfolio à cette date/heure/salle';
    END IF;

    -- Salle déjà occupée
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, date_h, NEW.dateS)) < 60
          AND IdSalle = NEW.IdSalle
          AND IdEvalAnglais <> OLD.IdEvalAnglais  -- exclure la ligne en cours d'update
        UNION
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, dateS, NEW.dateS)) < 20
          AND IdSalle = NEW.IdSalle
          AND IdEvalAnglais <> OLD.IdEvalAnglais  -- exclure la ligne en cours d'update
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur: La salle est déjà occupée à cette date/heure';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `evalportfolio`
--

CREATE TABLE `evalportfolio` (
  `IdEvalPortfolio` smallint(6) NOT NULL,
  `note` float DEFAULT NULL CHECK (`note` >= 0),
  `commentaireJury` varchar(500) DEFAULT NULL,
  `anneeDebut` smallint(6) NOT NULL,
  `IdModeleEval` smallint(6) NOT NULL,
  `IdEtudiant` smallint(6) NOT NULL,
  `Statut` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evalportfolio`
--

INSERT INTO `evalportfolio` (`IdEvalPortfolio`, `note`, `commentaireJury`, `anneeDebut`, `IdModeleEval`, `IdEtudiant`, `Statut`) VALUES
(1, 5, 'commentaires portfolio et 7 ', 2025, 6, 7, 'SAISIE'),
(2, 10, 'commentaire portfolio BUT2  etu 6', 2025, 6, 6, 'BLOQUEE'),
(3, 10, 'commentaire portfolio BUT2  etu 42  annéé 2023', 2023, 6, 42, 'BLOQUEE'),
(4, 5.78947, '', 2025, 6, 5, 'DIFFUSEE'),
(5, 5.78947, '', 2025, 6, 9, 'BLOQUEE'),
(6, NULL, NULL, 2025, 6, 13, 'SAISIE'),
(7, NULL, NULL, 2025, 6, 2, 'SAISIE'),
(8, NULL, NULL, 2025, 6, 11, 'SAISIE'),
(9, NULL, NULL, 2025, 6, 4, 'SAISIE'),
(10, NULL, NULL, 2025, 6, 1, 'SAISIE'),
(11, NULL, NULL, 2025, 6, 10, 'SAISIE');

-- --------------------------------------------------------

--
-- Structure de la table `evalrapport`
--

CREATE TABLE `evalrapport` (
  `IdEvalRapport` smallint(6) NOT NULL,
  `note` float DEFAULT NULL CHECK (`note` >= 0),
  `commentaireJury` varchar(200) DEFAULT NULL,
  `Statut` varchar(15) NOT NULL,
  `anneeDebut` smallint(6) NOT NULL,
  `IdModeleEval` smallint(6) NOT NULL,
  `IdEtudiant` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evalrapport`
--

INSERT INTO `evalrapport` (`IdEvalRapport`, `note`, `commentaireJury`, `Statut`, `anneeDebut`, `IdModeleEval`, `IdEtudiant`) VALUES
(1, 5, 'commentaire rapport etu 7', 'SAISIE', 2025, 7, 7),
(2, 5, 'comm rapport but2  etu 76', 'VALIDEE', 2025, 7, 6),
(3, 10, 'but3 etu42  annee 2023 ', 'BLOQUEE', 2023, 7, 42),
(4, 4.5, '', 'DIFFUSEE', 2025, 7, 5),
(5, 3, 'a', 'BLOQUEE', 2025, 7, 9),
(6, 1, 'coucou', 'SAISIE', 2025, 7, 13),
(7, NULL, NULL, 'SAISIE', 2025, 7, 2),
(8, NULL, NULL, 'SAISIE', 2025, 7, 11),
(9, NULL, NULL, 'SAISIE', 2025, 7, 4),
(10, NULL, NULL, 'SAISIE', 2025, 7, 1),
(11, NULL, NULL, 'SAISIE', 2025, 7, 10);

-- --------------------------------------------------------

--
-- Structure de la table `evalsoutenance`
--

CREATE TABLE `evalsoutenance` (
  `IdEvalSoutenance` smallint(6) NOT NULL,
  `note` float DEFAULT NULL CHECK (`note` >= 0),
  `commentaireJury` varchar(500) DEFAULT NULL,
  `anneeDebut` smallint(6) NOT NULL,
  `IdModeleEval` smallint(6) NOT NULL,
  `IdEtudiant` smallint(6) NOT NULL,
  `Statut` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evalsoutenance`
--

INSERT INTO `evalsoutenance` (`IdEvalSoutenance`, `note`, `commentaireJury`, `anneeDebut`, `IdModeleEval`, `IdEtudiant`, `Statut`) VALUES
(1, 5, 'commentaire soutenance etudiant 7 ', 2025, 1, 7, 'SAISIE'),
(2, 6, 'comm soutenance BUT2 Etu 6 ', 2025, 1, 6, 'VALIDEE'),
(3, 7, 'coutenance but3 etu42  annéé 2023 ', 2023, 1, 42, 'BLOQUEE'),
(4, 3, '', 2025, 1, 5, 'DIFFUSEE'),
(5, 4.5, '', 2025, 1, 9, 'BLOQUEE'),
(6, 5, '', 2025, 1, 13, 'SAISIE'),
(7, NULL, NULL, 2025, 1, 2, 'SAISIE'),
(8, NULL, NULL, 2025, 1, 11, 'SAISIE'),
(9, NULL, NULL, 2025, 1, 4, 'SAISIE'),
(10, NULL, NULL, 2025, 1, 1, 'SAISIE'),
(11, NULL, NULL, 2025, 1, 10, 'SAISIE');

-- --------------------------------------------------------

--
-- Structure de la table `evalstage`
--

CREATE TABLE `evalstage` (
  `IdEvalStage` smallint(6) NOT NULL,
  `note` float DEFAULT NULL,
  `commentaireJury` varchar(200) DEFAULT NULL,
  `presenceMaitreStageApp` tinyint(1) DEFAULT NULL,
  `confidentiel` tinyint(1) DEFAULT NULL,
  `date_h` datetime DEFAULT NULL,
  `IdEnseignantTuteur` smallint(6) NOT NULL,
  `Statut` varchar(15) NOT NULL,
  `IdSecondEnseignant` smallint(6) DEFAULT NULL,
  `anneeDebut` smallint(6) NOT NULL,
  `IdModeleEval` smallint(6) NOT NULL,
  `IdEtudiant` smallint(6) NOT NULL,
  `IdSalle` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evalstage`
--

INSERT INTO `evalstage` (`IdEvalStage`, `note`, `commentaireJury`, `presenceMaitreStageApp`, `confidentiel`, `date_h`, `IdEnseignantTuteur`, `Statut`, `IdSecondEnseignant`, `anneeDebut`, `IdModeleEval`, `IdEtudiant`, `IdSalle`) VALUES
(1, 7.5, 'commentaire eval stage et 7', 0, 0, '2026-06-22 09:32:02', 1, 'SAISIE', 6, 2025, 2, 7, 'T21'),
(2, NULL, 'comm eval stage but2 et 6', 1, 1, '2026-06-23 10:06:39', 13, 'SAISIE', 1, 2025, 5, 6, '355'),
(4, 6, '', 0, NULL, '2025-08-01 08:00:00', 1, 'DIFFUSEE', 2, 2025, 2, 5, 'AmphiB1'),
(5, 4, '', 0, NULL, '2024-10-03 14:00:00', 3, 'BLOQUEE', 1, 2025, 2, 9, '261'),
(6, 5.5, 'coucou', NULL, NULL, NULL, 1, 'SAISIE', 13, 2025, 2, 13, NULL),
(7, NULL, NULL, 1, NULL, '2023-06-03 08:00:00', 1, 'SAISIE', 6, 2025, 2, 2, '261'),
(8, NULL, NULL, NULL, NULL, NULL, 5, 'SAISIE', 14, 2025, 2, 11, NULL),
(9, NULL, NULL, 0, NULL, '2025-10-03 10:00:00', 5, 'SAISIE', 13, 2025, 2, 4, '261'),
(10, NULL, NULL, 1, NULL, '2025-10-03 08:00:00', 1, 'SAISIE', 15, 2025, 2, 1, '261'),
(11, NULL, NULL, NULL, NULL, NULL, 2, 'SAISIE', 1, 2025, 2, 10, NULL);

--
-- Déclencheurs `evalstage`
--
DELIMITER $$
CREATE TRIGGER `CheckEnseignantTuteurAndSecondInsert` BEFORE INSERT ON `evalstage` FOR EACH ROW BEGIN

    IF NEW.IdEnseignantTuteur = NEW.IdSecondEnseignant THEN
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre le même enseignant en tant que tuteur et comme second enseignant.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `CheckEnseignantTuteurAndSecondUpdate` BEFORE UPDATE ON `evalstage` FOR EACH ROW BEGIN

    IF NEW.IdEnseignantTuteur = NEW.IdSecondEnseignant THEN
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre le même enseignant en tant que tuteur et comme second enseignant.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_check_prof_anglais_INSERT` BEFORE INSERT ON `evalstage` FOR EACH ROW BEGIN
    -- Prof déjà occupé sur Anglais à la même heure et salle
    IF EXISTS (
        SELECT 1 FROM EvalAnglais
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60 AND IdSalle = NEW.IdSalle AND IdEnseignant = NEW.IdEnseignantTuteur
    ) THEN
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Erreur: L’enseignant a déjà une soutenance Anglais à cette date/heure/salle';
    END IF;

    IF EXISTS (
        SELECT 1 FROM EvalAnglais
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h,dateS)) < 60
AND IdSalle = NEW.IdSalle AND IdEnseignant = NEW.IdSecondEnseignant
    ) THEN
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Erreur: L’enseignant secondaire a déjà une soutenance Anglais à cette date/heure/salle';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_check_prof_anglais_UPDATE` BEFORE UPDATE ON `evalstage` FOR EACH ROW BEGIN
    -- Prof déjà occupé sur Anglais à la même heure et salle
    IF EXISTS (
        SELECT 1 FROM EvalAnglais
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND IdSalle = NEW.IdSalle 
          AND IdEnseignant = NEW.IdEnseignantTuteur
    ) THEN
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Erreur: L’enseignant a déjà une soutenance Anglais à cette date/heure/salle';
    END IF;

    IF EXISTS (
        SELECT 1 FROM EvalAnglais
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND IdSalle = NEW.IdSalle 
          AND IdEnseignant = NEW.IdSecondEnseignant
    ) THEN
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Erreur: L’enseignant secondaire a déjà une soutenance Anglais à cette date/heure/salle';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_stage_no_overlap_INSERT` BEFORE INSERT ON `evalstage` FOR EACH ROW BEGIN
    -- Vérif étudiant déjà occupé
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, date_h)) < 60
          AND IdEtudiant = NEW.IdEtudiant
        UNION
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND IdEtudiant = NEW.IdEtudiant
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Cet étudiant a déjà une soutenance à cette date/heure';
    END IF;

    -- Vérif enseignants déjà occupés
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, date_h)) < 60
          AND (IdEnseignantTuteur = NEW.IdEnseignantTuteur 
               OR IdSecondEnseignant = NEW.IdEnseignantTuteur
               OR IdEnseignantTuteur = NEW.IdSecondEnseignant 
               OR IdSecondEnseignant = NEW.IdSecondEnseignant)
        UNION
        SELECT 1 FROM EvalAnglais
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND (IdEnseignant = NEW.IdEnseignantTuteur 
               OR IdEnseignant = NEW.IdSecondEnseignant)
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Un des enseignants est déjà assigné sur cette heure';
    END IF;

    -- Vérif salle déjà occupée
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, date_h)) < 60
          AND IdSalle = NEW.IdSalle
        UNION
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND IdSalle = NEW.IdSalle
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: La salle est déjà occupée sur cette heure';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_stage_no_overlap_UPDATE` BEFORE UPDATE ON `evalstage` FOR EACH ROW BEGIN
    -- Vérif étudiant déjà occupé
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, date_h)) < 60
          AND IdEtudiant = NEW.IdEtudiant
          AND IdEvalStage <> OLD.IdEvalStage  -- exclure la ligne en cours
        UNION
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND IdEtudiant = NEW.IdEtudiant
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Cet étudiant a déjà une soutenance à cette date/heure';
    END IF;

    -- Vérif enseignants déjà occupés
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, date_h)) < 60
          AND IdEvalStage <> OLD.IdEvalStage  -- exclure la ligne en cours
          AND (IdEnseignantTuteur = NEW.IdEnseignantTuteur 
               OR IdSecondEnseignant = NEW.IdEnseignantTuteur
               OR IdEnseignantTuteur = NEW.IdSecondEnseignant 
               OR IdSecondEnseignant = NEW.IdSecondEnseignant)
        UNION
        SELECT 1 FROM EvalAnglais
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND ((IdEnseignant = NEW.IdEnseignantTuteur AND IdEnseignant <> OLD.IdEnseignantTuteur )
               OR IdEnseignant = NEW.IdSecondEnseignant AND IdEnseignant <> OLD.IdSecondEnseignant)
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: Un des enseignants est déjà assigné sur cette heure';
    END IF;

    -- Vérif salle déjà occupée
    IF EXISTS (
        SELECT 1 FROM EvalStage 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, date_h)) < 60
          AND IdSalle = NEW.IdSalle
          AND IdEvalStage <> OLD.IdEvalStage  -- exclure la ligne en cours
        UNION
        SELECT 1 FROM EvalAnglais 
        WHERE ABS(TIMESTAMPDIFF(MINUTE, NEW.date_h, dateS)) < 60
          AND IdSalle = NEW.IdSalle
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Erreur: La salle est déjà occupée sur cette heure';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `lescriteresnotesanglais`
--

CREATE TABLE `lescriteresnotesanglais` (
  `IdCritere` smallint(6) NOT NULL,
  `IdEvalAnglais` smallint(6) NOT NULL,
  `noteCritere` varchar(50) DEFAULT NULL CHECK (`noteCritere` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lescriteresnotesanglais`
--

INSERT INTO `lescriteresnotesanglais` (`IdCritere`, `IdEvalAnglais`, `noteCritere`) VALUES
(14, 1, '4'),
(15, 1, '7'),
(17, 1, '2');

-- --------------------------------------------------------

--
-- Structure de la table `lescriteresnotesportfolio`
--

CREATE TABLE `lescriteresnotesportfolio` (
  `IdCritere` smallint(6) NOT NULL,
  `IdEvalPortfolio` smallint(6) NOT NULL,
  `noteCritere` varchar(50) DEFAULT NULL CHECK (`noteCritere` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lescriteresnotesportfolio`
--

INSERT INTO `lescriteresnotesportfolio` (`IdCritere`, `IdEvalPortfolio`, `noteCritere`) VALUES
(18, 1, '3'),
(18, 2, '0.5'),
(18, 4, '3'),
(18, 5, '1'),
(19, 1, '3'),
(19, 2, '0.5'),
(19, 4, '0'),
(19, 5, '0'),
(20, 1, '3'),
(20, 2, '0.5'),
(20, 4, '1.5'),
(20, 5, '0.5'),
(21, 1, '3'),
(21, 2, '0.5'),
(21, 4, '0.5'),
(21, 5, '1'),
(22, 1, '3'),
(22, 2, '0.5'),
(22, 4, '0'),
(22, 5, '0.5'),
(23, 1, '3'),
(23, 2, '0.5'),
(23, 4, '0'),
(23, 5, '0.5'),
(24, 1, '3'),
(24, 2, '0.5'),
(24, 4, '0.5'),
(24, 5, '2');

-- --------------------------------------------------------

--
-- Structure de la table `lescriteresnotesrapport`
--

CREATE TABLE `lescriteresnotesrapport` (
  `IdCritere` smallint(6) NOT NULL,
  `IdEvalRapport` smallint(6) NOT NULL,
  `noteCritere` varchar(50) DEFAULT NULL CHECK (`noteCritere` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lescriteresnotesrapport`
--

INSERT INTO `lescriteresnotesrapport` (`IdCritere`, `IdEvalRapport`, `noteCritere`) VALUES
(25, 1, '0.5'),
(25, 2, '0.5'),
(25, 4, '0'),
(25, 5, '0'),
(25, 6, '0'),
(26, 1, '0.5'),
(26, 2, '0.5'),
(26, 4, '0.5'),
(26, 5, '0'),
(26, 6, '0'),
(27, 1, '0.5'),
(27, 2, '0.5'),
(27, 4, '0'),
(27, 5, '0.5'),
(27, 6, '0'),
(28, 1, '0.5'),
(28, 2, '0.5'),
(28, 4, '0.5'),
(28, 5, '0.5'),
(28, 6, '0'),
(29, 1, '0.5'),
(29, 2, '0.5'),
(29, 4, '0.5'),
(29, 5, '0.5'),
(29, 6, '0'),
(30, 1, '0.5'),
(30, 2, '0.5'),
(30, 4, '1'),
(30, 5, '0'),
(30, 6, '0'),
(31, 1, '0.5'),
(31, 2, '0.5'),
(31, 4, '1'),
(31, 5, '1'),
(31, 6, '1'),
(32, 1, '0.5'),
(32, 2, '0.5'),
(32, 4, '0.5'),
(32, 5, '0.5'),
(32, 6, '0'),
(33, 1, '0.5'),
(33, 2, '0.5'),
(33, 4, '0.5'),
(33, 5, '0'),
(33, 6, '0'),
(34, 1, '0.5'),
(34, 2, '0.5'),
(34, 4, '0'),
(34, 5, '0'),
(34, 6, '0');

-- --------------------------------------------------------

--
-- Structure de la table `lescriteresnotessoutenance`
--

CREATE TABLE `lescriteresnotessoutenance` (
  `IdCritere` smallint(6) NOT NULL,
  `IdEvalSoutenance` smallint(6) NOT NULL,
  `noteCritere` varchar(50) DEFAULT NULL CHECK (`noteCritere` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lescriteresnotessoutenance`
--

INSERT INTO `lescriteresnotessoutenance` (`IdCritere`, `IdEvalSoutenance`, `noteCritere`) VALUES
(5, 1, '0'),
(5, 2, '0.5'),
(5, 4, '0'),
(5, 5, '1'),
(5, 6, '1'),
(6, 1, '0.5'),
(6, 2, '0.5'),
(6, 4, '0'),
(6, 5, '1'),
(7, 1, '0.5'),
(7, 2, '0.5'),
(7, 4, '0.5'),
(7, 5, '0.5'),
(8, 1, '0.5'),
(8, 2, '0.5'),
(8, 4, '0'),
(8, 5, '0.5'),
(9, 1, '0'),
(9, 2, '0.5'),
(9, 4, '0'),
(9, 5, '0'),
(10, 1, '0.5'),
(10, 2, '0.5'),
(10, 4, '0.5'),
(10, 5, '0'),
(10, 6, '1'),
(11, 1, '0.5'),
(11, 2, '0.5'),
(11, 4, '0'),
(11, 5, '0'),
(11, 6, '1'),
(12, 1, '0'),
(12, 2, '0.5'),
(12, 4, '1'),
(12, 5, '0.5'),
(12, 6, '1'),
(13, 1, '0'),
(13, 2, '0.5'),
(13, 4, '1'),
(13, 5, '1'),
(13, 6, '1');

-- --------------------------------------------------------

--
-- Structure de la table `lescriteresnotesstage`
--

CREATE TABLE `lescriteresnotesstage` (
  `IdCritere` smallint(6) NOT NULL,
  `IdEvalStage` smallint(6) NOT NULL,
  `noteCritere` varchar(50) DEFAULT NULL CHECK (`noteCritere` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lescriteresnotesstage`
--

INSERT INTO `lescriteresnotesstage` (`IdCritere`, `IdEvalStage`, `noteCritere`) VALUES
(1, 1, '5'),
(1, 2, '8'),
(2, 1, '5'),
(2, 2, '8'),
(2, 4, '4.5'),
(2, 5, '0.5'),
(2, 6, '5'),
(3, 1, '5'),
(3, 2, '8'),
(4, 1, '5'),
(4, 2, '8');

-- --------------------------------------------------------

--
-- Structure de la table `modelesgrilleeval`
--

CREATE TABLE `modelesgrilleeval` (
  `IdModeleEval` smallint(6) NOT NULL,
  `natureGrille` enum('ANGLAIS','SOUTENANCE','RAPPORT','STAGE','PORTFOLIO') NOT NULL,
  `noteMaxGrille` float DEFAULT NULL CHECK (`noteMaxGrille` > 0),
  `nomModuleGrilleEvaluation` varchar(80) NOT NULL,
  `anneeDebut` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `modelesgrilleeval`
--

INSERT INTO `modelesgrilleeval` (`IdModeleEval`, `natureGrille`, `noteMaxGrille`, `nomModuleGrilleEvaluation`, `anneeDebut`) VALUES
(1, 'SOUTENANCE', 10, 'Grille d\'évaluation des soutenances de stage 2025', 2025),
(2, 'STAGE', 20, 'la grille d\'évaluation de stage BUT2 ou BUT3  2025 2026', 2025),
(3, 'ANGLAIS', 20, 'Grille Evaluation Soutenance de stage en ANGLAIS 2025 -2026', 2025),
(4, 'ANGLAIS', 20, 'Avienne grille 2024 2025 (ne devrait plus être utilisée !!)', 2024),
(5, 'STAGE', 2023, 'c\'est une anvienne grille d\'évaluation de stage 2023 ', 2023),
(6, 'PORTFOLIO', 20, 'Grille portfolio  \r\nCréer pour l\'année 2025 2026 ', 2025),
(7, 'RAPPORT', 10, 'Grille d\'évaluation du rapport de stage \r\ncréee pendant l\'annéé 2025 2026 ', 2025);

-- --------------------------------------------------------

--
-- Structure de la table `salles`
--

CREATE TABLE `salles` (
  `IdSalle` varchar(10) NOT NULL,
  `description` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `salles`
--

INSERT INTO `salles` (`IdSalle`, `description`) VALUES
('261', 'Salle de TD avec Grand Ecran  Interactif'),
('264', 'Salle de TD&TP  avec Grand Ecran  Interactif'),
('355', 'Salle de TP avec Grand Ecran Tactile'),
('AmphiB1', 'Amphi 90 places avec vidéoprojecteur'),
('T21', 'Salle de TP du plateau technique (BUT3)'),
('T22', 'Salle de TP du plateau technique (BUT3)');

--
-- Déclencheurs `salles`
--
DELIMITER $$
CREATE TRIGGER `CheckSallesInsert` BEFORE INSERT ON `salles` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Salles WHERE idSalle = NEW.idSalle  ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre une salle qui a un nom qui existe déjà.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `CheckSallesUpdate` BEFORE UPDATE ON `salles` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM Salles WHERE idSalle = NEW.idSalle AND IdSalle <> OLD.IdSalle ) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de mettre une salle qui a un nom qui existe déjà.';
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `supprimerSalle` BEFORE DELETE ON `salles` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM EvalAnglais WHERE IdSalle = OLD.IdSalle ) OR EXISTS(SELECT 1 FROM EvalStage WHERE IdSalle  = OLD.IdSalle) Then
    SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Impossible de supprimer cette Salle : elle a été associé à au moins une soutenance.';
    END IF;


END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `sectioncontenircriteres`
--

CREATE TABLE `sectioncontenircriteres` (
  `IdCritere` smallint(6) NOT NULL,
  `IdSection` smallint(6) NOT NULL,
  `ValeurMaxCritereEVal` float NOT NULL CHECK (`ValeurMaxCritereEVal` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sectioncontenircriteres`
--

INSERT INTO `sectioncontenircriteres` (`IdCritere`, `IdSection`, `ValeurMaxCritereEVal`) VALUES
(1, 1, 10),
(2, 1, 10),
(3, 1, 10),
(4, 1, 10),
(5, 3, 1),
(6, 3, 1),
(7, 3, 1),
(8, 2, 1),
(8, 3, 1),
(9, 3, 1),
(11, 2, 1.5),
(12, 2, 1),
(13, 2, 1),
(14, 4, 6),
(15, 4, 7),
(16, 4, 7),
(17, 5, 2),
(18, 6, 3),
(19, 6, 3),
(20, 6, 3),
(21, 6, 3),
(22, 6, 3),
(23, 6, 2),
(24, 6, 2),
(25, 7, 1),
(26, 7, 1),
(27, 7, 2),
(28, 7, 0.5),
(29, 7, 0.5),
(30, 8, 1),
(31, 8, 1),
(32, 8, 1.5),
(33, 8, 1),
(34, 8, 0.5);

-- --------------------------------------------------------

--
-- Structure de la table `sectioncritereeval`
--

CREATE TABLE `sectioncritereeval` (
  `IdSection` smallint(6) NOT NULL,
  `titre` varchar(50) NOT NULL,
  `description` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sectioncritereeval`
--

INSERT INTO `sectioncritereeval` (`IdSection`, `titre`, `description`) VALUES
(1, 'Evaluation du stage', 'rassembler les 4 critères d\'évaluation : note Entreprise , note Ttueur , note soutance et note rapport, chacune sur 10. La note finale sera une note sur 20'),
(2, 'Forme de la soutenance', 'évaluer la soutenance à travers des critères de forme'),
(3, 'Contenu de la soutenance', 'évaluer la soutenance pour ce qui a été décidé de mettre en avant'),
(4, 'Evaluation Soutenance Anglais ', 'Les critères d\'expression orale en continu, en interaction et les compétences linguistiques '),
(5, 'Section eval stage 2023 ', 'une section d\'une acienne grille 2023  ... ne devrait pas apparaitre '),
(6, 'Section EVal Portfolio ', 'unique session de la grille d\'évaluation du portfolio 2025 2026 '),
(7, 'Eval Rapport : Section Contenu 2025 ', 'criteres d\'évaluation sur le contenu du rapport  2025 2026 '),
(8, 'Eval Rapport : Section FORME 2025 ', 'criteres d\'évaluation sur la FORME du rapport  2025 2026 ');

--
-- Déclencheurs `sectioncritereeval`
--
DELIMITER $$
CREATE TRIGGER `CheckSectionCritereEvalInsert` BEFORE INSERT ON `sectioncritereeval` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM SectionCritereEval WHERE titre= NEW.titre) THEN
        SET NEW.titre = NULL;
	SET NEW.description = NULL;
    END IF;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `CheckSectionCritereEvalUpdate` BEFORE UPDATE ON `sectioncritereeval` FOR EACH ROW BEGIN

    IF EXISTS(SELECT 1 FROM SectionCritereEval WHERE titre= NEW.titre AND IdSection <> NEW.IdSection) THEN
         SET NEW.titre = OLD.titre;
         SET NEW.description = OLD.description;
    END IF;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `sectionseval`
--

CREATE TABLE `sectionseval` (
  `IdSection` smallint(6) NOT NULL,
  `IdModeleEval` smallint(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sectionseval`
--

INSERT INTO `sectionseval` (`IdSection`, `IdModeleEval`) VALUES
(1, 2),
(2, 1),
(3, 1),
(4, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 7);

-- --------------------------------------------------------

--
-- Structure de la table `statutseval`
--

CREATE TABLE `statutseval` (
  `Statut` varchar(15) NOT NULL CHECK (`Statut` in ('SAISIE','BLOQUEE','REMONTEE','VALIDEE','DIFFUSEE'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `statutseval`
--

INSERT INTO `statutseval` (`Statut`) VALUES
('BLOQUEE'),
('DIFFUSEE'),
('REMONTEE'),
('SAISIE'),
('VALIDEE');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateursbackoffice`
--

CREATE TABLE `utilisateursbackoffice` (
  `Identifiant` smallint(6) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `mail` varchar(150) NOT NULL,
  `mdp` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateursbackoffice`
--

INSERT INTO `utilisateursbackoffice` (`Identifiant`, `nom`, `prenom`, `mail`, `mdp`) VALUES
(0, 'UCA', 'admin', 'admin@test.fr', 'd11172a683aba4f63de1b34478988e511dd598e45ddc3bc8e13d8702855b6d55');

--
-- Déclencheurs `utilisateursbackoffice`
--
DELIMITER $$
CREATE TRIGGER `hash_mdp_backoffice_before_insert` BEFORE INSERT ON `utilisateursbackoffice` FOR EACH ROW BEGIN
  -- Si le mot de passe n'est pas déjà un hash SHA2 (64 caractères)
  IF CHAR_LENGTH(NEW.mdp) != 64 THEN
    SET NEW.mdp = SHA2(NEW.mdp, 256);
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `hash_mdp_backoffice_before_update` BEFORE UPDATE ON `utilisateursbackoffice` FOR EACH ROW BEGIN
  -- Si le mot de passe a été modifié et n'est pas déjà un hash
  IF NEW.mdp != OLD.mdp AND CHAR_LENGTH(NEW.mdp) != 64 THEN
    SET NEW.mdp = SHA2(NEW.mdp, 256);
  END IF;
END
$$
DELIMITER ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `anneestage`
--
ALTER TABLE `anneestage`
  ADD PRIMARY KEY (`anneeDebut`,`IdEtudiant`),
  ADD KEY `IdEtudiant` (`IdEtudiant`),
  ADD KEY `IdEntreprise` (`IdEntreprise`);

--
-- Index pour la table `anneesuniversitaires`
--
ALTER TABLE `anneesuniversitaires`
  ADD PRIMARY KEY (`anneeDebut`),
  ADD UNIQUE KEY `fin` (`fin`);

--
-- Index pour la table `critereseval`
--
ALTER TABLE `critereseval`
  ADD PRIMARY KEY (`IdCritere`);

--
-- Index pour la table `enseignants`
--
ALTER TABLE `enseignants`
  ADD PRIMARY KEY (`IdEnseignant`),
  ADD UNIQUE KEY `mail` (`mail`);

--
-- Index pour la table `entreprises`
--
ALTER TABLE `entreprises`
  ADD PRIMARY KEY (`IdEntreprise`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `etudiantsbut2ou3`
--
ALTER TABLE `etudiantsbut2ou3`
  ADD PRIMARY KEY (`IdEtudiant`),
  ADD UNIQUE KEY `mail` (`mail`),
  ADD UNIQUE KEY `empreinte` (`empreinte`);

--
-- Index pour la table `evalanglais`
--
ALTER TABLE `evalanglais`
  ADD PRIMARY KEY (`IdEvalAnglais`),
  ADD UNIQUE KEY `IdEtudiant` (`IdEtudiant`,`IdModeleEval`,`anneeDebut`),
  ADD KEY `Statut` (`Statut`),
  ADD KEY `IdSalle` (`IdSalle`),
  ADD KEY `IdEnseignant` (`IdEnseignant`),
  ADD KEY `anneeDebut` (`anneeDebut`),
  ADD KEY `IdModeleEval` (`IdModeleEval`);

--
-- Index pour la table `evalportfolio`
--
ALTER TABLE `evalportfolio`
  ADD PRIMARY KEY (`IdEvalPortfolio`),
  ADD UNIQUE KEY `IdEtudiant` (`IdEtudiant`,`IdModeleEval`,`anneeDebut`),
  ADD KEY `Statut` (`Statut`),
  ADD KEY `anneeDebut` (`anneeDebut`),
  ADD KEY `IdModeleEval` (`IdModeleEval`);

--
-- Index pour la table `evalrapport`
--
ALTER TABLE `evalrapport`
  ADD PRIMARY KEY (`IdEvalRapport`),
  ADD UNIQUE KEY `IdEtudiant` (`IdEtudiant`,`IdModeleEval`,`anneeDebut`),
  ADD KEY `Statut` (`Statut`),
  ADD KEY `anneeDebut` (`anneeDebut`),
  ADD KEY `IdModeleEval` (`IdModeleEval`);

--
-- Index pour la table `evalsoutenance`
--
ALTER TABLE `evalsoutenance`
  ADD PRIMARY KEY (`IdEvalSoutenance`),
  ADD UNIQUE KEY `IdEtudiant` (`IdEtudiant`,`IdModeleEval`,`anneeDebut`),
  ADD KEY `Statut` (`Statut`),
  ADD KEY `anneeDebut` (`anneeDebut`),
  ADD KEY `IdModeleEval` (`IdModeleEval`);

--
-- Index pour la table `evalstage`
--
ALTER TABLE `evalstage`
  ADD PRIMARY KEY (`IdEvalStage`),
  ADD UNIQUE KEY `IdEtudiant` (`IdEtudiant`,`IdModeleEval`,`anneeDebut`),
  ADD KEY `IdEnseignantTuteur` (`IdEnseignantTuteur`),
  ADD KEY `Statut` (`Statut`),
  ADD KEY `IdSecondEnseignant` (`IdSecondEnseignant`),
  ADD KEY `anneeDebut` (`anneeDebut`),
  ADD KEY `IdModeleEval` (`IdModeleEval`),
  ADD KEY `IdSalle` (`IdSalle`);

--
-- Index pour la table `lescriteresnotesanglais`
--
ALTER TABLE `lescriteresnotesanglais`
  ADD PRIMARY KEY (`IdCritere`,`IdEvalAnglais`),
  ADD KEY `IdEvalAnglais` (`IdEvalAnglais`);

--
-- Index pour la table `lescriteresnotesportfolio`
--
ALTER TABLE `lescriteresnotesportfolio`
  ADD PRIMARY KEY (`IdCritere`,`IdEvalPortfolio`),
  ADD KEY `IdEvalPortfolio` (`IdEvalPortfolio`);

--
-- Index pour la table `lescriteresnotesrapport`
--
ALTER TABLE `lescriteresnotesrapport`
  ADD PRIMARY KEY (`IdCritere`,`IdEvalRapport`),
  ADD KEY `IdEvalRapport` (`IdEvalRapport`);

--
-- Index pour la table `lescriteresnotessoutenance`
--
ALTER TABLE `lescriteresnotessoutenance`
  ADD PRIMARY KEY (`IdCritere`,`IdEvalSoutenance`),
  ADD KEY `IdEvalSoutenance` (`IdEvalSoutenance`);

--
-- Index pour la table `lescriteresnotesstage`
--
ALTER TABLE `lescriteresnotesstage`
  ADD PRIMARY KEY (`IdCritere`,`IdEvalStage`),
  ADD KEY `IdEvalStage` (`IdEvalStage`);

--
-- Index pour la table `modelesgrilleeval`
--
ALTER TABLE `modelesgrilleeval`
  ADD PRIMARY KEY (`IdModeleEval`),
  ADD UNIQUE KEY `nomModuleGrilleEvaluation` (`nomModuleGrilleEvaluation`),
  ADD KEY `anneeDebut` (`anneeDebut`);

--
-- Index pour la table `salles`
--
ALTER TABLE `salles`
  ADD PRIMARY KEY (`IdSalle`);

--
-- Index pour la table `sectioncontenircriteres`
--
ALTER TABLE `sectioncontenircriteres`
  ADD PRIMARY KEY (`IdCritere`,`IdSection`),
  ADD KEY `IdSection` (`IdSection`);

--
-- Index pour la table `sectioncritereeval`
--
ALTER TABLE `sectioncritereeval`
  ADD PRIMARY KEY (`IdSection`);

--
-- Index pour la table `sectionseval`
--
ALTER TABLE `sectionseval`
  ADD PRIMARY KEY (`IdSection`,`IdModeleEval`),
  ADD KEY `IdModeleEval` (`IdModeleEval`);

--
-- Index pour la table `statutseval`
--
ALTER TABLE `statutseval`
  ADD PRIMARY KEY (`Statut`);

--
-- Index pour la table `utilisateursbackoffice`
--
ALTER TABLE `utilisateursbackoffice`
  ADD PRIMARY KEY (`Identifiant`),
  ADD UNIQUE KEY `mail` (`mail`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `critereseval`
--
ALTER TABLE `critereseval`
  MODIFY `IdCritere` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `enseignants`
--
ALTER TABLE `enseignants`
  MODIFY `IdEnseignant` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `entreprises`
--
ALTER TABLE `entreprises`
  MODIFY `IdEntreprise` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `etudiantsbut2ou3`
--
ALTER TABLE `etudiantsbut2ou3`
  MODIFY `IdEtudiant` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT pour la table `evalanglais`
--
ALTER TABLE `evalanglais`
  MODIFY `IdEvalAnglais` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `evalportfolio`
--
ALTER TABLE `evalportfolio`
  MODIFY `IdEvalPortfolio` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `evalrapport`
--
ALTER TABLE `evalrapport`
  MODIFY `IdEvalRapport` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `evalsoutenance`
--
ALTER TABLE `evalsoutenance`
  MODIFY `IdEvalSoutenance` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `evalstage`
--
ALTER TABLE `evalstage`
  MODIFY `IdEvalStage` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `modelesgrilleeval`
--
ALTER TABLE `modelesgrilleeval`
  MODIFY `IdModeleEval` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `sectioncritereeval`
--
ALTER TABLE `sectioncritereeval`
  MODIFY `IdSection` smallint(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `anneestage`
--
ALTER TABLE `anneestage`
  ADD CONSTRAINT `anneestage_ibfk_1` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`),
  ADD CONSTRAINT `anneestage_ibfk_2` FOREIGN KEY (`IdEtudiant`) REFERENCES `etudiantsbut2ou3` (`IdEtudiant`),
  ADD CONSTRAINT `anneestage_ibfk_3` FOREIGN KEY (`IdEntreprise`) REFERENCES `entreprises` (`IdEntreprise`);

--
-- Contraintes pour la table `evalanglais`
--
ALTER TABLE `evalanglais`
  ADD CONSTRAINT `evalanglais_ibfk_1` FOREIGN KEY (`Statut`) REFERENCES `statutseval` (`Statut`),
  ADD CONSTRAINT `evalanglais_ibfk_2` FOREIGN KEY (`IdSalle`) REFERENCES `salles` (`IdSalle`),
  ADD CONSTRAINT `evalanglais_ibfk_3` FOREIGN KEY (`IdEnseignant`) REFERENCES `enseignants` (`IdEnseignant`),
  ADD CONSTRAINT `evalanglais_ibfk_4` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`),
  ADD CONSTRAINT `evalanglais_ibfk_5` FOREIGN KEY (`IdModeleEval`) REFERENCES `modelesgrilleeval` (`IdModeleEval`),
  ADD CONSTRAINT `evalanglais_ibfk_6` FOREIGN KEY (`IdEtudiant`) REFERENCES `etudiantsbut2ou3` (`IdEtudiant`);

--
-- Contraintes pour la table `evalportfolio`
--
ALTER TABLE `evalportfolio`
  ADD CONSTRAINT `evalportfolio_ibfk_1` FOREIGN KEY (`Statut`) REFERENCES `statutseval` (`Statut`),
  ADD CONSTRAINT `evalportfolio_ibfk_2` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`),
  ADD CONSTRAINT `evalportfolio_ibfk_3` FOREIGN KEY (`IdModeleEval`) REFERENCES `modelesgrilleeval` (`IdModeleEval`),
  ADD CONSTRAINT `evalportfolio_ibfk_4` FOREIGN KEY (`IdEtudiant`) REFERENCES `etudiantsbut2ou3` (`IdEtudiant`);

--
-- Contraintes pour la table `evalrapport`
--
ALTER TABLE `evalrapport`
  ADD CONSTRAINT `evalrapport_ibfk_1` FOREIGN KEY (`Statut`) REFERENCES `statutseval` (`Statut`),
  ADD CONSTRAINT `evalrapport_ibfk_2` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`),
  ADD CONSTRAINT `evalrapport_ibfk_3` FOREIGN KEY (`IdModeleEval`) REFERENCES `modelesgrilleeval` (`IdModeleEval`),
  ADD CONSTRAINT `evalrapport_ibfk_4` FOREIGN KEY (`IdEtudiant`) REFERENCES `etudiantsbut2ou3` (`IdEtudiant`);

--
-- Contraintes pour la table `evalsoutenance`
--
ALTER TABLE `evalsoutenance`
  ADD CONSTRAINT `evalsoutenance_ibfk_1` FOREIGN KEY (`Statut`) REFERENCES `statutseval` (`Statut`),
  ADD CONSTRAINT `evalsoutenance_ibfk_2` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`),
  ADD CONSTRAINT `evalsoutenance_ibfk_3` FOREIGN KEY (`IdModeleEval`) REFERENCES `modelesgrilleeval` (`IdModeleEval`),
  ADD CONSTRAINT `evalsoutenance_ibfk_4` FOREIGN KEY (`IdEtudiant`) REFERENCES `etudiantsbut2ou3` (`IdEtudiant`);

--
-- Contraintes pour la table `evalstage`
--
ALTER TABLE `evalstage`
  ADD CONSTRAINT `evalstage_ibfk_1` FOREIGN KEY (`IdEnseignantTuteur`) REFERENCES `enseignants` (`IdEnseignant`),
  ADD CONSTRAINT `evalstage_ibfk_2` FOREIGN KEY (`Statut`) REFERENCES `statutseval` (`Statut`),
  ADD CONSTRAINT `evalstage_ibfk_3` FOREIGN KEY (`IdSecondEnseignant`) REFERENCES `enseignants` (`IdEnseignant`),
  ADD CONSTRAINT `evalstage_ibfk_4` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`),
  ADD CONSTRAINT `evalstage_ibfk_5` FOREIGN KEY (`IdModeleEval`) REFERENCES `modelesgrilleeval` (`IdModeleEval`),
  ADD CONSTRAINT `evalstage_ibfk_6` FOREIGN KEY (`IdEtudiant`) REFERENCES `etudiantsbut2ou3` (`IdEtudiant`),
  ADD CONSTRAINT `evalstage_ibfk_7` FOREIGN KEY (`IdSalle`) REFERENCES `salles` (`IdSalle`);

--
-- Contraintes pour la table `lescriteresnotesanglais`
--
ALTER TABLE `lescriteresnotesanglais`
  ADD CONSTRAINT `lescriteresnotesanglais_ibfk_1` FOREIGN KEY (`IdCritere`) REFERENCES `critereseval` (`IdCritere`),
  ADD CONSTRAINT `lescriteresnotesanglais_ibfk_2` FOREIGN KEY (`IdEvalAnglais`) REFERENCES `evalanglais` (`IdEvalAnglais`);

--
-- Contraintes pour la table `lescriteresnotesportfolio`
--
ALTER TABLE `lescriteresnotesportfolio`
  ADD CONSTRAINT `lescriteresnotesportfolio_ibfk_1` FOREIGN KEY (`IdCritere`) REFERENCES `critereseval` (`IdCritere`),
  ADD CONSTRAINT `lescriteresnotesportfolio_ibfk_2` FOREIGN KEY (`IdEvalPortfolio`) REFERENCES `evalportfolio` (`IdEvalPortfolio`);

--
-- Contraintes pour la table `lescriteresnotesrapport`
--
ALTER TABLE `lescriteresnotesrapport`
  ADD CONSTRAINT `lescriteresnotesrapport_ibfk_1` FOREIGN KEY (`IdCritere`) REFERENCES `critereseval` (`IdCritere`),
  ADD CONSTRAINT `lescriteresnotesrapport_ibfk_2` FOREIGN KEY (`IdEvalRapport`) REFERENCES `evalrapport` (`IdEvalRapport`);

--
-- Contraintes pour la table `lescriteresnotessoutenance`
--
ALTER TABLE `lescriteresnotessoutenance`
  ADD CONSTRAINT `lescriteresnotessoutenance_ibfk_1` FOREIGN KEY (`IdCritere`) REFERENCES `critereseval` (`IdCritere`),
  ADD CONSTRAINT `lescriteresnotessoutenance_ibfk_2` FOREIGN KEY (`IdEvalSoutenance`) REFERENCES `evalsoutenance` (`IdEvalSoutenance`);

--
-- Contraintes pour la table `lescriteresnotesstage`
--
ALTER TABLE `lescriteresnotesstage`
  ADD CONSTRAINT `lescriteresnotesstage_ibfk_1` FOREIGN KEY (`IdCritere`) REFERENCES `critereseval` (`IdCritere`),
  ADD CONSTRAINT `lescriteresnotesstage_ibfk_2` FOREIGN KEY (`IdEvalStage`) REFERENCES `evalstage` (`IdEvalStage`);

--
-- Contraintes pour la table `modelesgrilleeval`
--
ALTER TABLE `modelesgrilleeval`
  ADD CONSTRAINT `modelesgrilleeval_ibfk_1` FOREIGN KEY (`anneeDebut`) REFERENCES `anneesuniversitaires` (`anneeDebut`);

--
-- Contraintes pour la table `sectioncontenircriteres`
--
ALTER TABLE `sectioncontenircriteres`
  ADD CONSTRAINT `sectioncontenircriteres_ibfk_1` FOREIGN KEY (`IdCritere`) REFERENCES `critereseval` (`IdCritere`),
  ADD CONSTRAINT `sectioncontenircriteres_ibfk_2` FOREIGN KEY (`IdSection`) REFERENCES `sectioncritereeval` (`IdSection`);

--
-- Contraintes pour la table `sectionseval`
--
ALTER TABLE `sectionseval`
  ADD CONSTRAINT `sectionseval_ibfk_1` FOREIGN KEY (`IdSection`) REFERENCES `sectioncritereeval` (`IdSection`),
  ADD CONSTRAINT `sectionseval_ibfk_2` FOREIGN KEY (`IdModeleEval`) REFERENCES `modelesgrilleeval` (`IdModeleEval`);

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `remonter_grilles_event` ON SCHEDULE EVERY 1 DAY STARTS '2025-10-03 23:00:00' ON COMPLETION NOT PRESERVE DISABLE DO CALL remonter_grilles()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
