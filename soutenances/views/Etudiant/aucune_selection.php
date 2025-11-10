<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Aucune sélection</title>
  <link rel="stylesheet" href="assets/style/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .empty-wrap{max-width:920px;margin:24px auto;padding:0 12px}
    .empty-card{background:#fbfdff;border:1px dashed #e6eef9;border-radius:12px;padding:28px 20px;text-align:center;box-shadow:0 1px 2px rgba(16,24,40,.04)}
    .empty-card .icon{color:#cbd5e1;margin-bottom:10px}
    .empty-card h2{margin:6px 0 8px;color:#0f172a;font-size:1.25rem}
    .empty-card p{margin:0 0 14px;color:#6b7280}
    .actions{display:flex;gap:10px;justify-content:center;margin-top:8px}
    .btn-uca{background: var(--uca-blue, #0055a4);border:none;color:#fff !important;padding:.6rem 1rem;border-radius:10px;font-weight:600;box-shadow: var(--card-shadow, 0 2px 10px rgba(0,0,0,.08));text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-uca:hover{background: var(--uca-dark-blue, #003366);color:#fff;text-decoration:none}
    .btn-outline{background:#fff;border:1px solid #dbe7f7;color:#0f3f78 !important;padding:.55rem .95rem;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-outline:hover{background:#f6f9fe;border-color:#cfe0f6}
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
      <a class="btn-back" href="index.php?action=listeRemontee">Retour à la page précédente</a>
    </div>
    <div class="page-header">
      <h1>Étudiants en remontée</h1>
      <p class="subtitle">Université Clermont Auvergne</p>
    </div>

    <div class="empty-wrap">
      <div class="empty-card">
        <i class="fas fa-clipboard-list fa-3x icon"></i>
        <h2>Aucune sélection effectuée</h2>
        <p>Veuillez sélectionner au moins un étudiant pour continuer.</p>
      </div>
    </div>
  </div>
</body>
</html>
