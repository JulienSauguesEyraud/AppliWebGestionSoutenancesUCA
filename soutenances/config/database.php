<?php
/**
 * Fournit une connexion PDO à la base "soutenances" (style procédural).
 * - Une seule instance est créée et mémorisée durant la requête HTTP.
 * - La connexion est configurée en UTF-8 et lève des exceptions sur erreur.
 * Utilisation: $pdo = db_get_connection();
 */
function db_get_connection() {
    // Mémorise l'unique instance pour éviter des reconnections successives.
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    $host = 'localhost';
    $db_name = 'soutenances';
    $username = 'root';
    $password = '';

    try {
        $conn = new PDO("mysql:host={$host};dbname={$db_name}", $username, $password);
        $conn->exec("set names utf8");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $exception) {
        echo "Erreur de connexion: " . $exception->getMessage();
    }

    return $conn;
}

/**
 * Crochet de compatibilité: PDO se ferme automatiquement en fin de script.
 * Cette fonction ne fait rien mais est conservée pour stabiliser l'API.
 */
function db_close_connection() {
    // Intentionnellement vide.
}
?>
