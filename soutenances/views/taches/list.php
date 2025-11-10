<?php
/**
 * Vue: Tableau des tâches (grilles d'évaluation)
 * - Affichage DataTables des tâches avec filtres et pagination
 * - Boutons pour masquer/afficher certaines colonnes
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau des tâches à réaliser</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-container {
            max-width: 1100px;
            margin: 40px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 35px 30px;
        }
        .header {
            background-color: #0055a4;
            color: white;
            padding: 25px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff !important;
        }
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
            color: #fff !important;
        }
        .btn-group-taches {
            margin-bottom: 25px;
        }
        .table thead th {
            background-color: #eaf1fb;
            color: #0055a4;
            font-weight: 600;
        }
        .badge {
            font-size: 1em;
            padding: 0.5em 1em;
        }
        .dataTables_filter {
            float: right !important;
            margin-bottom: 12px;
        }
        /* Place le sélecteur "Afficher" sur la même ligne, compact */
        .dataTables_length {
            float: left !important;
            margin-bottom: 12px;
        }
        .dataTables_length label,
        .dataTables_filter label {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .dataTables_length select {
            width: auto;
            min-width: 80px;
            max-width: 110px;
            height: 34px;
            padding: 4px 10px;
            border-radius: 10px;
        }
        .dataTables_filter input[type="search"] {
            width: 220px;
            height: 34px;
            padding: 4px 10px;
            border-radius: 10px;
        }
        .dataTables_paginate {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }
    /* Espacement des boutons de pagination DataTables */
        .dataTables_paginate .paginate_button {
            margin: 0 6px !important;
            cursor: pointer;                 /* montrer que c'est cliquable */
            user-select: none;               /* éviter la sélection de texte */
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
    /* Espace entre numéros au sein du <span> */
        .dataTables_paginate span {
            display: inline-flex;
            gap: 5px;
        }
    /* Curseur par défaut pour page courante et boutons désactivés */
        .dataTables_paginate .paginate_button.current,
        .dataTables_paginate .paginate_button.disabled {
            cursor: default !important;
        }
        .pagination-modern .page-item .page-link {
            border-radius: 50px;
            margin: 0 2px;
            color: #0055a4;
            border: 1px solid #eaf1fb;
            background: #f8f9fa;
            transition: background 0.2s, color 0.2s;
        }
        .pagination-modern .page-item.active .page-link {
            background: #0055a4;
            color: #fff;
            border-color: #0055a4;
        }
        .pagination-modern .page-item.disabled .page-link {
            color: #ccc;
            background: #f8f9fa;
        }
        @media (max-width: 900px) {
            .main-container {
                padding: 15px 5px;
            }
            .header {
                padding: 15px 8px;
            }
            .table-responsive {
                font-size: 0.95em;
            }
            .dataTables_filter {
                float: none !important;
                text-align: right;
            }
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
            <a class="btn-back" href="index.php?action=accueil">Retour à l'accueil</a>
        </div>
        <div class="main-container">
        <div class="header">
            <h1>Tableau des tâches à réaliser</h1>
            <p>Gestion des grilles d'évaluation - Université Clermont Auvergne</p>
        </div>
        <div class="btn-group-taches">
            <button onclick="toggleColumn(0)" class="btn btn-info me-2"><i class="bi bi-person-badge"></i> Enseignant</button>
            <button onclick="toggleColumn(1)" class="btn btn-warning me-2"><i class="bi bi-person"></i> Étudiant</button>
            <button onclick="toggleColumn(2)" class="btn btn-primary me-2"><i class="bi bi-check2-square"></i> Type de Grille</button>
            <button onclick="toggleColumn(3)" class="btn btn-success"><i class="bi bi-clipboard-check"></i> Statut</button>
        </div>
        <div class="table-responsive">
            <table id="grillesTable" class="table table-striped table-bordered align-middle">
                <thead>
                    <tr>
                        <th>Enseignant</th>
                        <th>Étudiant</th>
                        <th>Type de grille</th>
                        <th>Statut</th>
                        <th>Date limite</th>
                        <th>Retard</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($taches as $tache): ?>
                    <tr>
                        <td><?= htmlspecialchars($tache['enseignant']) ?></td>
                        <td><?= htmlspecialchars($tache['etudiant']) ?></td>
                        <td><?= htmlspecialchars($tache['type_grille']) ?></td>
                        <td>
                            <?php
                            $statut = strtolower($tache['statut']);
                            if ($statut === 'validée' || $statut === 'validee') {
                                echo '<span class="badge bg-success">🟢 Validée</span>';
                            } elseif ($statut === 'en retard') {
                                echo '<span class="badge bg-danger">🔴 En retard</span>';
                            } elseif ($statut === 'saisie en cours') {
                                echo '<span class="badge bg-warning text-dark">🟡 Saisie en cours</span>';
                            } else {
                                echo htmlspecialchars($tache['statut']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            // Formatage de la date et heure si présente
                            if (!empty($tache['date_limite'])) {
                                $date = date_create($tache['date_limite']);
                                echo $date ? date_format($date, 'd/m/Y H:i') : htmlspecialchars($tache['date_limite']);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($tache['retard']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            window.grillesTable = $('#grillesTable').DataTable({
                language: {
                    decimal: ",",
                    thousands: " ",
                    lengthMenu: "Afficher _MENU_ lignes",
                    zeroRecords: "Aucun enregistrement trouvé",
                    info: "Affichage de _START_ à _END_ sur _TOTAL_ lignes",
                    infoEmpty: "Affichage de 0 à 0 sur 0 ligne",
                    infoFiltered: "(filtré à partir de _MAX_ lignes au total)",
                    search: "Rechercher:",
                    loadingRecords: "Chargement...",
                    processing: "Traitement...",
                    paginate: {
                        previous: '<span class="page-link">&laquo;</span>',
                        next: '<span class="page-link">&raquo;</span>'
                    }
                },
                pageLength: 50,
                responsive: true,
                drawCallback: function() {
                    // Appliquer une classe Bootstrap à la pagination
                    $('.dataTables_paginate ul.pagination').addClass('pagination-modern');
                }
            });
        });
        function toggleColumn(colIndex) {
            var column = window.grillesTable.column(colIndex);
            column.visible(!column.visible());
        }
    </script>

</div>
</div>
</body>
</html>