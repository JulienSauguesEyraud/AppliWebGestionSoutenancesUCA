<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Diffusion effectuée</title>
  <link rel="stylesheet" href="assets/style/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .result-wrap{max-width:980px;margin:24px auto;padding:0 12px}
    .success{display:flex;align-items:center;gap:10px;background:#ecfdf5;border:1px solid #bbf7d0;border-left:4px solid #16a34a;color:#065f46;padding:12px;border-radius:10px;margin-bottom:14px}
    .result-card{background:#fff;border:1px solid #e6eef8;border-radius:12px;padding:16px;box-shadow:0 1px 2px rgba(16,24,40,.04)}
    .result-card h2{margin:0 0 10px;color:#0f172a;font-size:1.25rem}
    .list{list-style:none;margin:0;padding:0}
    .list li{padding:10px 12px;border:1px solid #eef2f7;border-radius:10px;margin:8px 0;background:#fbfdff}
    .list li .meta{color:#475569}
    .actions{display:flex;gap:10px;justify-content:flex-start;margin-top:14px}
    .btn-uca{background: var(--uca-blue, #0055a4);border:none;color:#fff !important;padding:.6rem 1rem;border-radius:10px;font-weight:600;box-shadow: var(--card-shadow, 0 2px 10px rgba(0,0,0,.08));text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-uca:hover{background: var(--uca-dark-blue, #003366);color:#fff;text-decoration:none}
    .btn-outline{background:#fff;border:1px solid #dbe7f7;color:#0f3f78 !important;padding:.55rem .95rem;border-radius:10px;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
    .btn-outline:hover{background:#f6f9fe;border-color:#cfe0f6}
    .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
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

    <div class="result-wrap">
      <div class="success"><i class="fa-solid fa-circle-check"></i> La diffusion des évaluations a bien été effectuée.</div>

      <div class="result-card">
        <div class="top">
          <h2><i class="fas fa-bullhorn"></i> Évaluations diffusées</h2>
        </div>
        <ul class="list">
          <?php foreach ($selection as $item): 
            list($idEtudiant, $anneeDebut, $nom, $prenom) = explode('-', $item);
          ?>
            <li>
              <strong>#<?= htmlspecialchars($idEtudiant) ?> - <?= htmlspecialchars($prenom) ?> <?= htmlspecialchars($nom) ?></strong>
              <div class="meta">Année : <?= htmlspecialchars($anneeDebut) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>

      </div>
    </div>
  </div>
</body>
</html>
