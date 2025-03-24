<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

require_once("../../../database/database.php");

// Vérifie si l'ID de l'athlète est passé via l'URL
if (isset($_GET['id_athlete'])) {
    $id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);

    // Vérifie si l'ID est valide
    if ($id_athlete === false) {
        $_SESSION['error'] = "ID d'athlète invalide.";
        header('Location: manage-athletes.php');
        exit();
    }

    try {
        // On commence par supprimer les participations associées
        $deleteParticipationSql = "DELETE FROM PARTICIPER WHERE id_athlete = :id_athlete";
        $stmt = $connexion->prepare($deleteParticipationSql);
        $stmt->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
        $stmt->execute();

        // Ensuite, on supprime l'athlète
        $deleteAthleteSql = "DELETE FROM ATHLETE WHERE id_athlete = :id_athlete";
        $stmt = $connexion->prepare($deleteAthleteSql);
        $stmt->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
        $stmt->execute();

        // Redirige avec un message de succès
        $_SESSION['success'] = "L'athlète a été supprimé avec succès.";
        header('Location: manage-athletes.php');
        exit();
    } catch (PDOException $e) {
        // En cas d'erreur, on redirige avec un message d'erreur
        $_SESSION['error'] = "Erreur lors de la suppression de l'athlète : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header('Location: manage-athletes.php');
        exit();
    }
} else {
    $_SESSION['error'] = "ID de l'athlète manquant.";
    header('Location: manage-athletes.php');
    exit();
}
?>
