<!-- Page d'erreur claire affichant le message SQL/trigger -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>soutenances</title>
  <link rel="stylesheet" href="assets/style/style.css">
    </head>
<body>
  <div class="error-box">
    <h2 class="error-title">Action impossible</h2>
    <p class="error-message">
      <?= isset($errorMessage) ? htmlspecialchars($errorMessage) : '' ?>
    </p>
    <a class="back-link" href="index.php?action=<?= htmlspecialchars($backAction ?? 'index') ?>">Retour à la liste</a>
  </div>
</body>
</html>
