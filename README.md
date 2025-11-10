# Appli Web – Gestion des Soutenances (UCA)

Application PHP (pattern MVC léger) pour planifier, évaluer et diffuser les notes des soutenances de stage (BUT2/BUT3) avec back-office d'administration.

## ✨ Fonctionnalités principales

- Authentification (back-office et zones protégées)
- Gestion des entités: Étudiants, Enseignants, Entreprises, Salles
- Stages: affectations tuteur/second/anglais, planification, modification, suppression
- Soutenances de Stage et d’Anglais: planification avec contrôles de conflits (étudiant, prof, salle)
- Grilles d’évaluation (Soutenance, Stage, Anglais, Portfolio, Rapport) + modèles/sections/critères
- Saisie, validation, blocage et remontée des notes (workflow Statut: SAISIE → BLOQUEE → VALIDEE → REMONTEE → DIFFUSEE)
- Remontée technique: procédures/événements/déclencheurs SQL, écran d’activation/désactivation
- Back-office: alertes, analytics, recherche d’étudiants

## 🧰 Pile technique

- PHP 8.x (procédural + MVC léger)
- MySQL / MariaDB (dump fourni: `CreerBdD.sql`) avec contraintes, triggers et procédures stockées
- HTML/CSS/JS (assets simples + composant grid)
- Pas de Composer requis (PHPMailer inclus en source dans `soutenances/src/`)

## 📦 Structure du dépôt (extrait)

```
CreerBdD.sql                # Script de création + jeux de données
soutenances/
  index.php                 # Routeur frontal (action via ?action=...)
  config/database.php       # Connexion PDO (hôte/DB/utilisateur/mot de passe)
  controllers/              # Contrôleurs procéduraux
  models/                   # Modèles (accès BDD)
  views/                    # Vues (front/back, grilles, listes, formulaires)
  assets/                   # CSS / JS / images
  src/PHPMailer.php ...     # Librairie email incluse (optionnelle)
```

## ✅ Pré-requis

- Windows, macOS ou Linux
- Serveur web avec PHP 8.0+ (XAMPP, WAMP, MAMP, Apache/Nginx)
- MySQL/MariaDB 10.4+ (ou compatible)
- Navigateur récent

## 🚀 Installation rapide (XAMPP sous Windows)

1) Copier le projet dans le dossier web
- Dézippez ce dépôt puis placez le dossier `AppliWebGestionSoutenancesUCA` dans `C:\xampp\htdocs\` (ou l’équivalent de votre serveur).

2) Créer la base de données et charger les données
- Ouvrez phpMyAdmin et importez `CreerBdD.sql`
  - ou via terminal PowerShell (si l’outil `mysql` est dans le PATH):

```powershell
# Remplacez <motdepasse> si nécessaire (laisser vide sur XAMPP par défaut)
mysql -u root -p < "c:\Users\jujus\Downloads\AppliWebGestionSoutenancesUCA\AppliWebGestionSoutenancesUCA\CreerBdD.sql"
```

3) Configurer la connexion BDD
- Éditez `soutenances/config/database.php` si vos identifiants diffèrent:
  - hôte (`$host`), base (`$db_name`), utilisateur (`$username`), mot de passe (`$password`).

4) Lancer l’application
- Démarrez Apache et MySQL dans XAMPP
- Ouvrez:
  - `http://localhost/AppliWebGestionSoutenancesUCA/soutenances/` (redirige vers login)
  - `http://localhost/AppliWebGestionSoutenancesUCA/soutenances/index.php?action=login`

## 🔐 Comptes et accès

- Un utilisateur back-office de démonstration est créé dans la table `utilisateursbackoffice`:
  - email: `admin@test.fr`
  - mot de passe: un hash SHA-256 est déjà stocké dans le dump (le mot de passe en clair n’est pas fourni).

Si vous avez besoin de définir un mot de passe connu, mettez à jour la ligne en SQL (exemple):

```sql
UPDATE utilisateursbackoffice 
SET mdp = SHA2('VotreMotDePasseFort', 256)
WHERE mail = 'admin@test.fr';
```

Les écrans protégés (exemples):
- `?action=backoffice-alertes`
- `?action=backoffice-analytics`
- `?action=search-students`
- `?action=fiche-notes`
- Pages de remontée: `?action=remontee` et `?action=remontee-notes`

## 🗺️ Navigation utile (routes)

- Accueil (après login): `?action=accueil`
- Étudiants: liste `?action=listEtudiant` • ajout `?action=addEtudiant`
- Enseignants: liste `?action=listEnseignant` • affectations (tuteur/second/anglais)
- Entreprises: `?action=listEntreprise` • Salles: `?action=listSalle`
- Stages:
  - création `?action=addStage`
  - affecter enseignants `?action=addEnseignantStage` / `?action=addEnseignantAnglais`
- Soutenances:
  - lister `?action=listerSoutenances`
  - planifier `?action=planifierSoutenance`
  - modifier `?action=modifierSoutenance` (ou version restreinte)
- Grilles: index `?action=grids` • consulter/modifier/ajouter/copier
- Remontée (technique): `?action=remontee` pour activer/désactiver triggers/événements

## 🗃️ Base de données

- Script complet: `CreerBdD.sql` (tables, contraintes, index, triggers, procédures, jeux de données)
- Procédures clés: `sp_remonter_notes`, `remonter_grilles`, vues procédurales `v_soutenances_avenir`, `v_soutenances_finies`
- Contrôles d’intégrité par triggers: prévention de doublons (mails, noms), conflits de planning (étudiant/prof/salle), hachage des mots de passe côté SQL, etc.

## 🛠️ Développement

- Routeur: `soutenances/index.php` via `?action=...`
- Connexion BDD: `soutenances/config/database.php` (PDO, UTF-8, exceptions)
- Contrôleurs procéduraux dans `soutenances/controllers/`
- Modèles SQL dans `soutenances/models/`
- Vues dans `soutenances/views/` (layouts, backoffice, front, grilles)
- Email: `soutenances/src/PHPMailer.php` et `SMTP.php` (utilisation optionnelle)

## 🧪 Données de démonstration

Le dump fournit des exemples pour: années, entreprises, enseignants, étudiants, modèles et évaluations. Vous pouvez démarrer immédiatement puis ajuster selon vos besoins.

## 🧩 Dépannage

- Page blanche/erreur connexion: vérifiez `database.php` et que la base `soutenances` existe.
- Import SQL échoue: utilisez MariaDB/MySQL ≥ 10.4 et assurez-vous d’exécuter le script avec un utilisateur ayant les droits (triggers/procédures).
- Conflits lors de la planification: messages d’erreur générés par les triggers BDD (étudiant/enseignant/salle déjà pris sur le créneau).
- Mot de passe inconnu: réinitialisez via `UPDATE ... SET mdp = SHA2('nouveau',256)`.

## 🔒 Sécurité (vue d’ensemble)

- Mots de passe hachés côté BDD (triggers) pour enseignants et utilisateurs back-office
- Contrôles de doublons et cohérence métiers en base (triggers + contraintes)
- Sessions PHP pour protéger les actions sensibles (liste `protected_actions` dans le routeur)