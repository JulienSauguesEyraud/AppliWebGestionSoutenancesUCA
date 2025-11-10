<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Gestion des Soutenances - Université Clermont Auvergne' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/style/style.css" rel="stylesheet">
    <style>
        :root {
            --uca-blue: #0055a4;
            --uca-light-blue: #e6f0fa;
            --uca-dark-blue: #003366;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px;
        }
        
        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .btn-uca {
            background: var(--uca-blue);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-uca:hover {
            background: var(--uca-dark-blue);
            color: white;
        }
        
        .container-main {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--uca-blue) 0%, var(--uca-dark-blue) 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: 10px;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #0055a4;
        }

        .badge {
            font-weight: 500;
        }

        .progress {
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_info']) && empty($hideGlobalLogout)): ?>
        <div class="topbar">
            <span></span>
            <a href="index.php?action=logout" class="btn btn-uca">
                <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
            </a>
        </div>
    <?php endif; ?>

    <div class="container-main">
        <?= $content ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>