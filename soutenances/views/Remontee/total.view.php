<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>administration des notes</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/style/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<style>
		body{font-family:system-ui,Arial,sans-serif;margin:24px}
		h1{margin:0 0 12px}
		.msg{padding:10px 12px;border-radius:6px;margin:12px 0}
		.ok{background:#e7f7ed;border:1px solid #b7e2c4;color:#156a2f}
		.err{background:#fdecea;border:1px solid #f5c2c0;color:#b42318}
		table{border-collapse:collapse;width:100%;margin-top:12px}
		th,td{border:1px solid #e5e7eb;padding:8px 10px}
		th{background:#f9fafb;text-align:left}
		tr:nth-child(even){background:#fafafa}
		button{cursor:pointer;padding:6px 10px;border:1px solid #d1d5db;background:#111827;color:#fff;border-radius:4px}
		button:hover{background:#0b1220}
		.empty{color:#6b7280}
		.saisie{color:#b42318;font-weight:600}
		        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .deconnexion {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .deconnexion button {
            padding: 8px 16px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .deconnexion button:hover {
            background-color: #d32f2f;
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
		<div class="page-header">
			<h1>Administration des notes</h1>
			<p class="subtitle">Remontées automatiques et manuelles</p>
		</div>

	<?php if ($vueSeule): ?>
		<div class="msg ok">Mode vue seule actif (paramètre ?vue=1) : action masquée.</div>
	<?php endif; ?>

	<?php if (!is_null($ok)): ?>
		<div class="msg ok">Remontée effectuée.</div>
	<?php endif; ?>

	<?php if (!empty($err)): ?>
		<div class="msg err"><?php echo htmlspecialchars($err); ?></div>
	<?php endif; ?>

	<?php if (isset($_GET['email_sent'])): ?>
		<div class="msg ok">Email envoyé avec succès !</div>
	<?php endif; ?>

	<?php if (isset($_GET['email_error'])): ?>
		<div class="msg err">Erreur d'envoi d'email : <?php echo htmlspecialchars($_GET['email_error']); ?></div>
	<?php endif; ?>

	<?php if (isset($_GET['reminders_sent'])): ?>
		<div class="msg ok">Rappels envoyés : <?php echo (int)$_GET['reminders_sent']; ?> succès, <?php echo (int)($_GET['reminders_errors'] ?? 0); ?> erreurs</div>
	<?php endif; ?>

	<?php if (isset($_GET['reminders_error'])): ?>
		<div class="msg err">Erreur lors de l'envoi des rappels aux tuteurs</div>
	<?php endif; ?>

	<?php if (isset($_GET['error']) && $_GET['error'] === 'missing_fields'): ?>
		<div class="msg err">Veuillez remplir tous les champs obligatoires</div>
	<?php endif; ?>

	<?php if (isset($_GET['event_enabled'])): ?>
		<div class="msg ok">Event quotidien activé avec succès</div>
	<?php endif; ?>

	<?php if (isset($_GET['event_disabled'])): ?>
		<div class="msg ok">Event quotidien désactivé avec succès</div>
	<?php endif; ?>

	<?php if (isset($_GET['triggers_enabled'])): ?>
		<div class="msg ok">Triggers automatiques activés avec succès</div>
	<?php endif; ?>

	<?php if (isset($_GET['triggers_disabled'])): ?>
		<div class="msg ok">Triggers automatiques désactivés avec succès</div>
	<?php endif; ?>

	<?php if (isset($_GET['error']) && $_GET['error'] === 'triggers_conflict'): ?>
		<div class="msg err">Impossible d'activer l'event quotidien : les triggers sont déjà activés</div>
	<?php endif; ?>

	<?php if (isset($_GET['error']) && $_GET['error'] === 'event_conflict'): ?>
		<div class="msg err">Impossible d'activer les triggers : l'event quotidien est déjà activé</div>
	<?php endif; ?>

	<?php if (isset($_GET['error']) && $_GET['error'] === 'event_failed'): ?>
		<div class="msg err">Erreur lors de la modification de l'event quotidien</div>
	<?php endif; ?>

	<?php if (isset($_GET['error']) && $_GET['error'] === 'triggers_failed'): ?>
		<div class="msg err">Erreur lors de la modification des triggers</div>
	<?php endif; ?>

	<?php if (isset($_GET['error']) && $_GET['error'] === 'no_selection'): ?>
		<div class="msg err">⚠️ Veuillez sélectionner au moins un étudiant avant de remonter les notes</div>
	<?php endif; ?>

	<hr style="margin:24px 0">
	<h2>⚙️ Gestion des remontées automatiques</h2>
	<p style="color: #6b7280; font-style: italic; margin-bottom: 12px;">
		Configurez la remontée automatique des notes : soit via un event quotidien programmé, soit via des triggers instantanés dès que les 3 notes sont bloquées.
		<br><strong>⚠️ Attention :</strong> Les deux modes ne peuvent pas être actifs simultanément.
	</p>

	<?php if (!$vueSeule): ?>
	<div style="display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap;">
		<!-- Event quotidien -->
		<div style="flex: 1; min-width: 300px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb;">
			<h3 style="margin-top: 0; color: #333; display: flex; align-items: center; gap: 8px;">
				🕐 Event quotidien
				<?php if ($eventEnabled): ?>
					<span style="background: #10b981; color: white; font-size: 12px; padding: 4px 8px; border-radius: 4px;">ACTIF</span>
				<?php else: ?>
					<span style="background: #6b7280; color: white; font-size: 12px; padding: 4px 8px; border-radius: 4px;">INACTIF</span>
				<?php endif; ?>
			</h3>
			<p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
				Remontée automatique des notes à une heure précise chaque jour (configurée dans l'event MySQL).
			</p>
			<form method="post" style="margin: 0;">
				<input type="hidden" name="action" value="toggle_event">
				<select name="toggle_event" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; width: 100%; margin-bottom: 12px;" <?php if ($triggersEnabled): ?>disabled<?php endif; ?>>
					<option value="1" <?php if ($eventEnabled) echo 'selected'; ?> <?php if ($triggersEnabled) echo 'disabled'; ?>>Activer</option>
					<option value="0" <?php if (!$eventEnabled) echo 'selected'; ?>>Désactiver</option>
				</select>
				<button type="submit" style="width: 100%;" <?php if ($triggersEnabled): ?>disabled title="Désactivez les triggers d'abord"<?php endif; ?>>
					Appliquer
				</button>
				<?php if ($triggersEnabled): ?>
					<small style="color: #dc2626; display: block; margin-top: 8px;">⚠️ Désactivez les triggers pour activer l'event</small>
				<?php endif; ?>
			</form>
		</div>

		<!-- Triggers automatiques -->
		<div style="flex: 1; min-width: 300px; border: 2px solid #e5e7eb; border-radius: 8px; padding: 16px; background: #f9fafb;">
			<h3 style="margin-top: 0; color: #333; display: flex; align-items: center; gap: 8px;">
				⚡ Triggers automatiques
				<?php if ($triggersEnabled): ?>
					<span style="background: #10b981; color: white; font-size: 12px; padding: 4px 8px; border-radius: 4px;">ACTIFS</span>
				<?php else: ?>
					<span style="background: #6b7280; color: white; font-size: 12px; padding: 4px 8px; border-radius: 4px;">INACTIFS</span>
				<?php endif; ?>
			</h3>
			<p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
				Remontée instantanée dès que les 3 évaluations (Stage, Portfolio, Anglais pour BUT3) passent au statut BLOQUEE.
			</p>
			<form method="post" style="margin: 0;">
				<input type="hidden" name="action" value="toggle_trigger">
				<select name="toggle_trigger" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; width: 100%; margin-bottom: 12px;" <?php if ($eventEnabled): ?>disabled<?php endif; ?>>
					<option value="1" <?php if ($triggersEnabled) echo 'selected'; ?> <?php if ($eventEnabled) echo 'disabled'; ?>>Activer</option>
					<option value="0" <?php if (!$triggersEnabled) echo 'selected'; ?>>Désactiver</option>
				</select>
				<button type="submit" style="width: 100%;" <?php if ($eventEnabled): ?>disabled title="Désactivez l'event d'abord"<?php endif; ?>>
					Appliquer
				</button>
				<?php if ($eventEnabled): ?>
					<small style="color: #dc2626; display: block; margin-top: 8px;">⚠️ Désactivez l'event pour activer les triggers</small>
				<?php endif; ?>
			</form>
		</div>
	</div>
	<?php else: ?>
		<p style="color: #6b7280; font-style: italic;">
			Mode vue seule actif : Statut actuel - Event: <?php echo $eventEnabled ? '✅ Activé' : '❌ Désactivé'; ?> | Triggers: <?php echo $triggersEnabled ? '✅ Activés' : '❌ Désactivés'; ?>
		</p>
	<?php endif; ?>

	<hr style="margin:24px 0">
	<h2>📋 Remontée manuelle des notes</h2>
	<p style="color: #6b7280; font-style: italic; margin-bottom: 12px;">
		Sélectionnez les étudiants pour lesquels vous souhaitez remonter les notes manuellement.
	</p>

	<?php if (empty($rowsCandidats)): ?>
		<p class="empty">Aucun candidat éligible pour la remontée de notes pour le moment.</p>
	<?php else: ?>
		<h3>Candidats éligibles pour la remontée manuelle</h3>
		<p style="color: #6b7280; font-style: italic; margin-bottom: 12px;">
			Les étudiants ci-dessous ont toutes leurs grilles d'évaluation au statut BLOQUEE et peuvent être remontés manuellement.
		</p>
		<?php if (!$vueSeule): ?>
		<form method="post">
		<?php endif; ?>
		<table>
			<thead>
				<tr>
					<?php if (!$vueSeule): ?><th>Sélection</th><?php endif; ?>
					<th>Année</th>
					<th>Étudiant</th>
					<th>Type</th>
					<th>Entreprise</th>
					<th>Sujet</th>
					<th>Maître de stage</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rowsCandidats as $r): ?>
					<tr>
						<?php if (!$vueSeule): ?>
							<td><input type="checkbox" name="sel[]" value="<?php echo (int)$r['IdEtudiant']; ?>_<?php echo (int)$r['anneeDebut']; ?>" /></td>
						<?php endif; ?>
						<td><?php echo htmlspecialchars($r['anneeDebut']); ?></td>
						<td><?php echo htmlspecialchars($r['nomEtudiant'] . ' ' . $r['prenomEtudiant']); ?></td>
						<td><?php 
							$type = 'BUT2';
							if ($r['but3sinon2'] == 1) {
								$type = $r['alternanceBUT3'] == 1 ? 'BUT3 Apprentissage' : 'BUT3 Stage';
							}
							echo htmlspecialchars($type);
						?></td>
						<td><?php echo htmlspecialchars($r['entreprise_nom'] ?? '—'); ?></td>
						<td><?php echo htmlspecialchars($r['sujet'] ?? '—'); ?></td>
						<td><?php echo htmlspecialchars($r['nomMaitreStageApp'] ?? '—'); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$vueSeule): ?>
			<p style="margin-top:12px">
				<button type="submit" title="Remonter les lignes cochées">Remonter les notes des étudiants sélectionnés</button>
			</p>
		</form>
		<?php endif; ?>
	<?php endif; ?>

	<hr style="margin:24px 0">
	<h2>Étudiants avec notes remontées (statut "REMONTEE")</h2>
	<p style="color: #6b7280; font-style: italic; margin-bottom: 12px;">
			Les étudiants ci-dessous ont toutes leurs grilles d'évaluation au statut REMONTEE et peuvent etre repassées au statut SAISIE.
	</p>
	<?php if (empty($rowsRemontee)): ?>
		<p class="empty">Aucun étudiant avec notes remontées pour le moment.</p>
	<?php else: ?>
		<?php if (!$vueSeule): ?>
		<form method="post">
		<?php endif; ?>
		<table>
			<thead>
				<tr>
					<?php if (!$vueSeule): ?><th>Sélection</th><?php endif; ?>
					<th>Année</th>
					<th>Étudiant</th>
					<th>Statut Stage</th>
					<th>Statut Anglais</th>
					<th>Statut Portfolio</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rowsRemontee as $r): ?>
					<tr>
						<?php if (!$vueSeule): ?>
							<td><input type="checkbox" name="sel_remontee[]" value="<?php echo (int)$r['IdEtudiant']; ?>_<?php echo (int)$r['anneeDebut']; ?>" /></td>
						<?php endif; ?>
						<td><?php echo htmlspecialchars($r['anneeDebut']); ?></td>
						<td><?php echo htmlspecialchars($r['nomEtudiant'] . ' ' . $r['prenomEtudiant']); ?></td>
						<td><?php echo htmlspecialchars($r['StatutStage'] ?? '—'); ?></td>
						<td><?php echo htmlspecialchars($r['StatutAnglais'] ?? 'Non déf (BUT2)'); ?></td>
						<td><?php echo htmlspecialchars($r['StatutPortfolio'] ?? '—'); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php if (!$vueSeule): ?>
			<p style="margin-top:12px">
				<input type="hidden" name="action" value="remettre_saisie">
				<button type="submit" title="Remettre en saisie les lignes cochées">Remettre en saisie les étudiants sélectionnés</button>
			</p>
		</form>
		<?php endif; ?>
	<?php endif; ?>

	<hr style="margin:24px 0">
	<h2>Export des notes remontées</h2>
	
	<?php if (!empty($notesBut2)): ?>
		<h3>Notes BUT2 remontées</h3>
		<button type="button" onclick="openExportModal('but2')" style="background-color: #28a745; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 12px;">
			📊 EXPORT BUT2
		</button>
		<table>
			<thead>
				<tr>
					<th>Année</th>
					<th>Étudiant</th>
					<th>Note Stage</th>
					<th>Note Portfolio</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($notesBut2 as $note): ?>
					<tr>
						<td><?php echo htmlspecialchars($note['anneeDebut']); ?></td>
						<td><?php echo htmlspecialchars($note['nomEtudiant'] . ' ' . $note['prenomEtudiant']); ?></td>
						<td><?php echo htmlspecialchars($note['noteStage']); ?></td>
						<td><?php echo htmlspecialchars($note['notePortfolio']); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p class="empty">Aucune note BUT2 remontée pour le moment.</p>
	<?php endif; ?>

	<?php if (!empty($notesBut3)): ?>
		<?php if (!empty($notesBut2)): ?>
			<hr style="margin:24px 0">
		<?php endif; ?>
		<h3>Notes BUT3 remontées</h3>
		<button type="button" onclick="openExportModal('but3')" style="background-color: #17a2b8; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 12px;">
			📊 EXPORT BUT3
		</button>
		<table>
			<thead>
				<tr>
					<th>Année</th>
					<th>Étudiant</th>
					<th>Note Stage</th>
					<th>Note Portfolio</th>
					<th>Note Anglais</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($notesBut3 as $note): ?>
					<tr>
						<td><?php echo htmlspecialchars($note['anneeDebut']); ?></td>
						<td><?php echo htmlspecialchars($note['nomEtudiant'] . ' ' . $note['prenomEtudiant']); ?></td>
						<td><?php echo htmlspecialchars($note['noteStage']); ?></td>
						<td><?php echo htmlspecialchars($note['notePortfolio']); ?></td>
						<td><?php echo htmlspecialchars($note['noteAnglais']); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else: ?>
		<p class="empty">Aucune note BUT3 remontée pour le moment.</p>
	<?php endif; ?>

	<?php if (!empty($rowsLate)): ?>
		<hr style="margin:24px 0">
		<h2>Soutenances passées mais encore au statut "SAISIE"</h2>
		<?php if (!empty($emailsTuteursEnRetard)): ?>
		<button type="button" onclick="openRelancerModal()" style="background-color: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 12px;">
			📧 Relancer les tuteurs
		</button>
		<?php endif; ?>
		<table>
			<thead>
				<tr>
					<th>Année</th>
					<th>Étudiant</th>
					<th>Date stage</th>
					<th>Statut Stage</th>
					<th>Statut Portfolio</th>
					<th>Statut Anglais</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rowsLate as $r): ?>
					<tr>
						<td><?php echo htmlspecialchars($r['anneeDebut']); ?></td>
						<td><?php echo htmlspecialchars($r['nomEtudiant'] . ' ' . $r['prenomEtudiant']); ?></td>
						<td><?php echo htmlspecialchars($r['dateStage'] ? $r['dateStage'] : '—'); ?></td>
						<td><?php $v = $r['StatutStage'] ?? '—'; echo ($v === 'SAISIE') ? '<span class="saisie">SAISIE</span>' : htmlspecialchars($v); ?></td>
						<td><?php $v = $r['StatutPortfolio'] ?? '—'; echo ($v === 'SAISIE') ? '<span class="saisie">SAISIE</span>' : htmlspecialchars($v); ?></td>
						<td><?php $v = $r['StatutAnglais'] ?? 'Non déf(BUT2)'; echo ($v === 'SAISIE') ? '<span class="saisie">SAISIE</span>' : htmlspecialchars($v); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
	<!-- Modal pour l'export CSV -->
	<div id="exportModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: white; margin: 15% auto; padding: 20px; border-radius: 8px; width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<h3 style="margin-top: 0; color: #333;">Options d'export CSV</h3>
			<p style="color: #666; margin-bottom: 20px;">Choisissez comment vous souhaitez exporter les données :</p>
			
			<div style="display: flex; gap: 10px; justify-content: center;">
				<button id="downloadBtn" onclick="downloadCSV()" style="background-color: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
					📥 Télécharger CSV
				</button>
				<button onclick="openEmailModal()" style="background-color: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
					📧 Envoyer CSV par email
				</button>
			</div>
			
			<div style="text-align: center; margin-top: 15px;">
				<button onclick="closeExportModal()" style="background-color: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer;">
					Annuler
				</button>
			</div>
		</div>
	</div>

	<!-- Modal pour l'envoi d'email -->
	<div id="emailModal" style="display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: white; margin: 5% auto; padding: 20px; border-radius: 8px; width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<h3 style="margin-top: 0; color: #333;">📧 Envoi d'email</h3>
			
			<div style="margin-top: 20px;">
				<input type="hidden" id="emailExportType" value="">
				
				<div style="margin-bottom: 15px;">
					<label for="destinataires" style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Destinataires :</label>
					<input type="email" id="destinataires" name="destinataires" multiple required 
						   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
						   placeholder="email1@example.com, email2@example.com">
					<small style="color: #666;">Séparez plusieurs adresses par des virgules</small>
				</div>
				
				<div style="margin-bottom: 15px;">
					<label for="sujet" style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Sujet :</label>
					<input type="text" id="sujet" name="sujet" required 
						   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
						   placeholder="Export des notes - BUT2/BUT3">
				</div>
				
				<div style="margin-bottom: 15px;">
					<label for="message" style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Message :</label>
					<textarea id="message" name="message" rows="6" required 
							  style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: vertical;"
							  placeholder="Bonjour,

Veuillez trouver ci-joint le fichier CSV contenant les notes remontées.

Cordialement,
L'équipe pédagogique">Bonjour,

Veuillez trouver ci-joint le fichier CSV contenant les notes remontées.

Cordialement,
L'équipe pédagogique</textarea>
				</div>
				
				<div style="margin-bottom: 15px;">
					<label style="display: block; margin-bottom: 5px; font-weight: bold; color: #333;">Pièce jointe :</label>
					<div style="background-color: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6;">
						<span id="attachmentInfo" style="color: #495057;">📎 Fichier CSV sera automatiquement joint</span>
					</div>
				</div>
				
				<div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
					<button type="button" onclick="closeEmailModal()" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
						Annuler
					</button>
					<button type="button" onclick="sendEmailWithCsv()" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
						📧 Envoyer l'email
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal pour relancer les tuteurs -->
	<div id="relancerModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
		<div style="background-color: white; margin: 5% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<h3 style="margin-top: 0; color: #dc3545;">📧 Relancer les tuteurs en retard</h3>
			
			<form style="margin-top: 20px;">
				<div style="margin-bottom: 15px;">
					<label for="relancerDestinataires" style="display: block; margin-bottom: 5px; font-weight: bold;">Destinataires :</label>
					<textarea id="relancerDestinataires" name="destinataires" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;" readonly><?php 
						$emails = array_column($emailsTuteursEnRetard, 'mail');
						echo htmlspecialchars(implode(', ', $emails));
					?></textarea>
					<small style="color: #666; font-style: italic;">
						<?php echo count($emailsTuteursEnRetard); ?> tuteur(s) concerné(s) :
						<?php foreach ($emailsTuteursEnRetard as $tuteur): ?>
							<br>• <?php echo htmlspecialchars($tuteur['nomTuteur']); ?> (<?php echo htmlspecialchars($tuteur['mail']); ?>) - <?php echo $tuteur['nbEtudiantsEnRetard']; ?> étudiant(s)
						<?php endforeach; ?>
					</small>
				</div>
				
				<div style="margin-bottom: 15px;">
					<label for="relancerSujet" style="display: block; margin-bottom: 5px; font-weight: bold;">Sujet :</label>
					<input type="text" id="relancerSujet" name="sujet" value="Rappel : Notes de soutenance en attente de saisie" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
				</div>
				
				<div style="margin-bottom: 15px;">
					<label for="relancerMessage" style="display: block; margin-bottom: 5px; font-weight: bold;">Message :</label>
					<textarea id="relancerMessage" name="message" rows="8" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">Bonjour,

Nous vous contactons pour vous rappeler que vous avez des notes en attente de saisie.

Des étudiants dont vous êtes le tuteur ont passé leur soutenance mais leurs évaluations sont encore au statut "SAISIE".

Merci de bien vouloir finaliser la saisie de ces notes dans les plus brefs délais.

Cordialement,
L'équipe pédagogique</textarea>
				</div>
				
				<div style="text-align: right; margin-top: 20px;">
					<button type="button" onclick="closeRelancerModal()" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;">
						❌ Annuler
					</button>
					<button type="button" onclick="sendReminderToTutors()" style="background-color: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
						📧 Envoyer les rappels
					</button>
				</div>
			</form>
		</div>
	</div>

	</div>
	<script>
		let currentExportType = '';
		
		function openExportModal(type) {
			currentExportType = type;
			document.getElementById('exportModal').style.display = 'block';
		}
		
		function closeExportModal() {
			document.getElementById('exportModal').style.display = 'none';
		}
		
		function downloadCSV() {
			// Créer un formulaire temporaire pour télécharger le CSV
			const form = document.createElement('form');
			form.method = 'post';
			form.style.display = 'none';
			
			const actionInput = document.createElement('input');
			actionInput.type = 'hidden';
			actionInput.name = 'action';
			actionInput.value = 'export_' + currentExportType;
			
			form.appendChild(actionInput);
			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
			
			closeExportModal();
		}
		
		function openEmailModal() {
			closeExportModal();
			document.getElementById('emailExportType').value = currentExportType;
			document.getElementById('attachmentInfo').textContent = '📎 Fichier CSV ' + currentExportType.toUpperCase() + ' sera automatiquement joint';
			document.getElementById('emailModal').style.display = 'block';
		}
		
		function closeEmailModal() {
			document.getElementById('emailModal').style.display = 'none';
		}
		
		function sendEmailWithCsv() {
			// Créer un formulaire temporaire pour envoyer l'email
			const form = document.createElement('form');
			form.method = 'post';
			form.style.display = 'none';
			
			const actionInput = document.createElement('input');
			actionInput.type = 'hidden';
			actionInput.name = 'action';
			actionInput.value = 'send_email_export';
			
			const typeInput = document.createElement('input');
			typeInput.type = 'hidden';
			typeInput.name = 'export_type';
			typeInput.value = currentExportType;
			
			const destinatairesInput = document.createElement('input');
			destinatairesInput.type = 'hidden';
			destinatairesInput.name = 'destinataires';
			destinatairesInput.value = document.getElementById('destinataires').value;
			
			const sujetInput = document.createElement('input');
			sujetInput.type = 'hidden';
			sujetInput.name = 'sujet';
			sujetInput.value = document.getElementById('sujet').value;
			
			const messageInput = document.createElement('input');
			messageInput.type = 'hidden';
			messageInput.name = 'message';
			messageInput.value = document.getElementById('message').value;
			
			form.appendChild(actionInput);
			form.appendChild(typeInput);
			form.appendChild(destinatairesInput);
			form.appendChild(sujetInput);
			form.appendChild(messageInput);
			
			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
		}
		
		function openRelancerModal() {
			document.getElementById('relancerModal').style.display = 'block';
		}
		
		function closeRelancerModal() {
			document.getElementById('relancerModal').style.display = 'none';
		}
		
		function sendReminderToTutors() {
			// Créer un formulaire temporaire pour envoyer les rappels
			const form = document.createElement('form');
			form.method = 'post';
			form.style.display = 'none';
			
			const actionInput = document.createElement('input');
			actionInput.type = 'hidden';
			actionInput.name = 'action';
			actionInput.value = 'send_reminder_tutors';
			
			const sujetInput = document.createElement('input');
			sujetInput.type = 'hidden';
			sujetInput.name = 'sujet';
			sujetInput.value = document.getElementById('relancerSujet').value;
			
			const messageInput = document.createElement('input');
			messageInput.type = 'hidden';
			messageInput.name = 'message';
			messageInput.value = document.getElementById('relancerMessage').value;
			
			form.appendChild(actionInput);
			form.appendChild(sujetInput);
			form.appendChild(messageInput);
			
			document.body.appendChild(form);
			form.submit();
			document.body.removeChild(form);
		}
		
		// Fermer les modals en cliquant à l'extérieur
		window.onclick = function(event) {
			const exportModal = document.getElementById('exportModal');
			const emailModal = document.getElementById('emailModal');
			const relancerModal = document.getElementById('relancerModal');
			
			if (event.target === exportModal) {
				closeExportModal();
			}
			if (event.target === emailModal) {
				closeEmailModal();
			}
			if (event.target === relancerModal) {
				closeRelancerModal();
			}
		}
	</script>
</body>
</html>

