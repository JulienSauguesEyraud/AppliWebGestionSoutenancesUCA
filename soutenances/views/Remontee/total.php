<?php
// Normalise le paramètre de route puis délègue via l'index unifié de soutenances
$r = isset($_GET['r']) && is_string($_GET['r']) && $_GET['r'] !== '' ? $_GET['r'] : 'total/index';

// On passe par l'action remontee-notes pour réutiliser le routeur intégré
$target = '../../index.php?action=remontee-notes&r=' . urlencode($r);
header('Location: ' . $target);
exit;

