<?php
require_once __DIR__ . '/../models/RemonteModels.php';

function remonte_toggleEvent(PDO $pdo, $enable) {
    if ($enable && remonte_areTriggersEnabled($pdo)) {
        return "Impossible d'activer l'event quotidien si les triggers sont déjà activés.";
    }
    remonte_setEventStatus($pdo, $enable);
    return $enable ? "Event quotidien activé." : "Event quotidien désactivé.";
}

function remonte_toggleTrigger(PDO $pdo, $enable) {
    if ($enable && remonte_isEventEnabled($pdo)) {
        return "Impossible d'activer les triggers si l'event quotidien est déjà activé.";
    }
    remonte_setTriggerStatus($pdo, $enable);
    return $enable ? "Tous les triggers activés." : "Tous les triggers désactivés.";
}
?>