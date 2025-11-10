<!-- Page d'accueil Backoffice (fragment inséré dans le layout principal) -->
    <?php if (isset($_SESSION['user_info'])): ?>
    <div class="topbar">
        <span></span>
        <a href="index.php?action=logout" class="btn btn-uca"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a>
    </div>
    <?php endif; ?>
    <div class="container-main">
        <div class="page-header">
            <?php
            $displayName = 'Utilisateur';
            if (isset($_SESSION['user_info']['prenom']) && isset($_SESSION['user_info']['nom'])) {
                $displayName = $_SESSION['user_info']['prenom'] . ' ' . $_SESSION['user_info']['nom'];
            }
            ?>
            <h1>Bienvenue, <?= htmlspecialchars($displayName) ?></h1>
            <p class="lead">Espace Backoffice - Université Clermont Auvergne</p>
        </div>

        <!-- Fonctionnalités Backoffice spécialisées -->
        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <a href="index.php?action=backoffice-alertes" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                            <h5>Alertes</h5>
                            <p class="text-muted">Gérer les alertes de soutenances</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="index.php?action=backoffice-analytics" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                            <h5>Analyses</h5>
                            <p class="text-muted">Statistiques et analyses</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-4 mb-4">
                <a href="index.php?action=search-students" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-search fa-3x text-success mb-3"></i>
                            <h5>Recherche</h5>
                            <p class="text-muted">Recherche d'étudiants</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <a href="index.php?action=taches" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-list-check fa-3x text-primary mb-3"></i>
                            <h5>Tâches</h5>
                            <p class="text-muted">Tableau des tâches à réaliser</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="index.php?action=listeRemontee" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-paper-plane fa-3x text-success mb-3"></i>
                            <h5>Diffuser les notes</h5>
                            <p class="text-muted">Sélectionner les étudiants et lancer la diffusion</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-4">
                <a href="index.php?action=remontee-notes&r=total/index" class="text-decoration-none">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calculator fa-3x text-dark mb-3"></i>
                            <h5>Remonter les notes</h5>
                            <p class="text-muted">Accéder aux paramètres de remontée des notes</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="alert alert-info mb-4">
            <h4><i class="fas fa-cog me-2"></i>Espace d'administration</h4>
            <p class="mb-0">Vous avez accès à toutes les fonctionnalités backoffice et de gestion.</p>
        </div>

        <!-- Actions principales de gestion -->
    <h2>Gestion des données</h2>
    <ul class="action-list row list-unstyled ps-0">
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-primary w-100" href="index.php?action=addEnseignant"><i class="fas fa-user-plus me-2"></i>Ajouter un Enseignant</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-secondary w-100" href="index.php?action=listEnseignant"><i class="fas fa-user-edit me-2"></i>Supprimer ou modifier un Enseignant</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-primary w-100" href="index.php?action=addSalle"><i class="fas fa-door-open me-2"></i>Ajouter une Salle</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-secondary w-100" href="index.php?action=listSalle"><i class="fas fa-edit me-2"></i>Supprimer ou modifier une Salle</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-primary w-100" href="index.php?action=addEntreprise"><i class="fas fa-building me-2"></i>Ajouter une Entreprise</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-secondary w-100" href="index.php?action=listEntreprise"><i class="fas fa-building me-2"></i>Supprimer ou modifier une Entreprise</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-primary w-100" href="index.php?action=addEtudiant"><i class="fas fa-user-graduate me-2"></i>Ajouter un Étudiant</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-secondary w-100" href="index.php?action=listEtudiant"><i class="fas fa-user-graduate me-2"></i>Supprimer ou modifier un Étudiant</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-success w-100" href="index.php?action=listStage"><i class="fas fa-briefcase me-2"></i>Ajouter un Stage à un étudiant</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-warning w-100" href="index.php?action=listEnseignantStage"><i class="fas fa-user-tie me-2"></i>Ajouter un enseignant tuteur et secondaire à un stage</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-info w-100" href="index.php?action=listEnseignantAnglais"><i class="fas fa-language me-2"></i>Ajouter un enseignant d'anglais à un stage</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-dark w-100" href="index.php?action=viewEnseignant"><i class="fas fa-eye me-2"></i>Voir les stages suivis par un enseignant</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-danger w-100" href="index.php?action=listerSoutenances"><i class="fas fa-clipboard-check me-2"></i>Planifier et gérer les soutenances</a></li>
            <li class="col-md-6 mb-2"><a class="action-link btn btn-outline-primary w-100" href="index.php?action=grids"><i class="fas fa-table me-2"></i>Gérer les modèles de grilles d'évaluation</a></li>
        </ul>
    </div>