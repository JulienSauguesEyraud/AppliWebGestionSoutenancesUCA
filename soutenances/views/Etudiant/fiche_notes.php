<?php
$hasNotes = false;
foreach ($notes as $evaluations) {
    if (!empty($evaluations)) { $hasNotes = true; break; }
}
?>

<style>
/* Styles locaux et sobres pour une fiche de notes plus simple */
.fiche-notes{ font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#1f2a37; }

/* En-tête de page */
.fiche-notes .page-header{ display:flex; align-items:center; gap:16px; margin-bottom:16px; }
.fiche-notes .page-title{ margin:0; color: var(--uca-blue, #0055a4); font-weight:700; letter-spacing:.2px; font-size:1.35rem; }

/* Bouton retour */
.fiche-notes .btn{ display:inline-block; text-decoration:none; }
.fiche-notes .btn-uca{ background: var(--uca-blue, #0055a4); border:none; color:#fff !important; padding:.55rem .9rem; border-radius:10px; font-weight:600; transition: background .2s ease; box-shadow: var(--card-shadow, 0 2px 10px rgba(0,0,0,.08)); }
.fiche-notes .btn-uca:hover{ background: var(--uca-dark-blue, #003366); color:#fff; text-decoration:none; }
.fiche-notes .me-2{ margin-right:.5rem !important; }

/* Infos étudiant sous forme de "chips" */
.fiche-notes .student-info{ display:flex; flex-wrap:wrap; gap:10px; margin-bottom:16px; }
.fiche-notes .info-chip{ display:flex; align-items:center; gap:8px; padding:.55rem .75rem; background: #f5f9ff; border:1px solid #e6eef9; border-radius:10px; }
.fiche-notes .info-chip i{ color: var(--uca-blue, #0055a4); }
.fiche-notes .info-chip strong{ color:#334155; }

/* Sections de notes */
.fiche-notes .section-title{ display:flex; align-items:center; gap:8px; margin:18px 0 8px; font-size:1.05rem; color:#0f172a; font-weight:700; }
.fiche-notes .section-title i{ color: var(--uca-blue, #0055a4); }
.fiche-notes .section{ padding:8px 0; border-top:1px solid #eef2f7; }

/* Stats compactes */
.fiche-notes .stats{ display:flex; flex-wrap:wrap; gap:10px; margin:6px 0 12px; }
.fiche-notes .stat{ flex:0 1 200px; background:#fafcff; border:1px solid #eaeef7; border-radius:10px; padding:.65rem .8rem; }
.fiche-notes .stat h4{ margin:0 0 2px; color: var(--uca-blue, #0055a4); font-size:1.1rem; }
.fiche-notes .stat small{ color:#6b7280; }

/* Tableau léger */
.fiche-notes .table{ width:100%; border-collapse:separate; border-spacing:0; }
.fiche-notes .table thead th{ background:#f6f9fe; color:#0f3f78; font-weight:600; border-bottom:1px solid #e6eef9; padding:.65rem .9rem; }
.fiche-notes .table td{ padding:.65rem .9rem; border-bottom:1px solid #f1f5fb; vertical-align:middle; }
.fiche-notes .table tbody tr:hover{ background:#fbfdff; }

/* Badges simples */
.fiche-notes .badge{ display:inline-block; border-radius:8px; font-weight:600; padding:.4rem .55rem; color:#fff; }
.fiche-notes .badge.bg-primary{ background: var(--uca-blue, #0055a4) !important; }
.fiche-notes .badge.bg-success{ background:#16a34a !important; }
.fiche-notes .badge.bg-warning{ background:#f59e0b !important; color:#111827; }
.fiche-notes .badge.bg-secondary{ background:#cbd5e1 !important; color:#334155; }
.fiche-notes .fs-6{ font-size:1rem; }
.fiche-notes .small{ font-size:.925rem; }

/* Etat vide discret */
.fiche-notes .empty{ text-align:center; padding:32px 16px; background:#fbfdff; border:1px dashed #e6eef9; border-radius:12px; }
.fiche-notes .empty .empty-icon{ color:#cbd5e1; }
.fiche-notes .text-muted{ color:#6b7280 !important; }
</style>

<div class="fiche-notes">
  <div class="page-header">
    <a href="index.php?action=search-students" class="btn btn-uca"><i class="fas fa-arrow-left me-2"></i>Retour</a>
    <h2 class="page-title">Fiche de notes - <?= htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']) ?></h2>
  </div>

  <!-- Infos étudiant en chips -->
  <div class="student-info">
    <div class="info-chip"><i class="fas fa-id-card"></i><strong>Nom:</strong> <?= htmlspecialchars($etudiant['nom']) ?></div>
    <div class="info-chip"><i class="fas fa-user"></i><strong>Prénom:</strong> <?= htmlspecialchars($etudiant['prenom']) ?></div>
    <div class="info-chip"><i class="fas fa-envelope"></i><strong>Email:</strong> <?= htmlspecialchars($etudiant['mail']) ?></div>
  </div>

  <!-- Résumé des moyennes -->
  <?php if ($hasNotes && !empty($moyennes['generale'])): ?>
  <div class="section">
    <div class="section-title"><i class="fas fa-chart-line"></i>Résumé des moyennes</div>
    <div class="stats">
      <div class="stat">
        <h4><?= number_format($moyennes['generale'], 2) ?></h4>
        <small class="text-muted">Moyenne générale</small>
      </div>
      <?php foreach ($moyennes as $type => $moyenne): ?>
        <?php if ($type !== 'generale' && !empty($moyenne)): ?>
        <div class="stat">
          <h4><?= number_format($moyenne, 2) ?></h4>
          <small class="text-muted">Moyenne <?= ucfirst($type) ?></small>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Détails des notes -->
  <?php if ($hasNotes): ?>
    <?php foreach ($notes as $type => $evaluations): ?>
      <?php if (!empty($evaluations)): ?>
      <div class="section">
        <div class="section-title">
          <i class="fas fa-<?= $type === 'soutenances' ? 'microphone' : ($type === 'rapports' ? 'file-alt' : ($type === 'portfolios' ? 'briefcase' : ($type === 'stages' ? 'building' : 'language'))) ?>"></i>
          Notes de <?= ucfirst($type) ?>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th width="30%">Type d'évaluation</th>
                <th width="15%" class="text-center">Note</th>
                <th width="15%" class="text-center">Statut</th>
                <th width="40%">Commentaire</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($evaluations as $evaluation): ?>
              <tr>
                <td>
                  <i class="fas fa-graduation-cap text-muted me-2"></i>
                  <?= htmlspecialchars($evaluation['nomModuleGrilleEvaluation']) ?>
                </td>
                <td class="text-center">
                  <?php if (!empty($evaluation['note'])): ?>
                    <span class="badge bg-primary fs-6"><?= htmlspecialchars($evaluation['note']) ?>/20</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Non noté</span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <span class="badge bg-<?= $evaluation['Statut'] === 'VALIDEE' ? 'success' : 'warning' ?>">
                    <?= htmlspecialchars($evaluation['Statut']) ?>
                  </span>
                </td>
                <td class="small">
                  <?php if (!empty($evaluation['commentaireJury'])): ?>
                    <?= htmlspecialchars($evaluation['commentaireJury']) ?>
                  <?php else: ?>
                    <span class="text-muted">Aucun commentaire</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="empty">
      <i class="fas fa-clipboard-list fa-3x empty-icon mb-2"></i>
      <h4 class="text-muted" style="margin:8px 0 6px; font-size:1.05rem; font-weight:700;">Aucune note disponible</h4>
      <p class="text-muted" style="margin:0;">Cet étudiant n'a pas encore d'évaluations enregistrées.</p>
    </div>
  <?php endif; ?>
</div>