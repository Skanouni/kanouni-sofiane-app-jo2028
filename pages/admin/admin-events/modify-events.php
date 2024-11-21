<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'épreuve est fourni dans l'URL pour modification
if (!isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'épreuve manquant.";
    header("Location: manage-events.php");
    exit();
}

$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'épreuve est un entier valide
if (!$id_epreuve && $id_epreuve !== 0) {
    $_SESSION['error'] = "ID de l'épreuve invalide.";
    header("Location: manage-events.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'épreuve pour affichage dans le formulaire
try {
    $queryEpreuve = "SELECT * FROM EPREUVE WHERE id_epreuve = :idEpreuve";
    $statementEpreuve = $connexion->prepare($queryEpreuve);
    $statementEpreuve->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);
    $statementEpreuve->execute();

    if ($statementEpreuve->rowCount() > 0) {
        $epreuve = $statementEpreuve->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Épreuve non trouvée.";
        header("Location: manage-events.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-events.php");
    exit();
}

// Récupérez les options de lieu et de sport
$queryLieu = "SELECT * FROM LIEU";
$statementLieu = $connexion->prepare($queryLieu);
$statementLieu->execute();
$lieuOptions = $statementLieu->fetchAll(PDO::FETCH_ASSOC);

$querySport = "SELECT * FROM SPORT";
$statementSport = $connexion->prepare($querySport);
$statementSport->execute();
$sportOptions = $statementSport->fetchAll(PDO::FETCH_ASSOC);

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifiez si le formulaire est soumis pour modifier l'épreuve
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomEpreuve = filter_input(INPUT_POST, 'nomEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $dateEpreuve = filter_input(INPUT_POST, 'dateEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $heureEpreuve = filter_input(INPUT_POST, 'heureEpreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $idLieu = filter_input(INPUT_POST, 'idLieu', FILTER_VALIDATE_INT);
    $idSport = filter_input(INPUT_POST, 'idSport', FILTER_VALIDATE_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: modify-events.php?id_epreuve=$id_epreuve");
        exit();
    }

    // Vérifiez si le nom de l'épreuve est vide
    if (empty($nomEpreuve)) {
        $_SESSION['error'] = "Le nom de l'épreuve ne peut pas être vide.";
        header("Location: modify-events.php?id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Vérifiez si l'épreuve existe déjà avec les mêmes données
        $queryCheck = "SELECT id_epreuve FROM EPREUVE WHERE nom_epreuve = :nomEpreuve AND date_epreuve = :dateEpreuve 
                       AND heure_epreuve = :heureEpreuve AND id_lieu = :idLieu AND id_sport = :idSport AND id_epreuve != :idEpreuve";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomEpreuve", $nomEpreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":dateEpreuve", $dateEpreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":heureEpreuve", $heureEpreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":idLieu", $idLieu, PDO::PARAM_INT);
        $statementCheck->bindParam(":idSport", $idSport, PDO::PARAM_INT);
        $statementCheck->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'épreuve avec ces détails existe déjà.";
            header("Location: modify-events.php?id_epreuve=$id_epreuve");
            exit();
        } else {
            // Requête pour modifier l'épreuve
            $query = "UPDATE EPREUVE SET nom_epreuve = :nomEpreuve, date_epreuve = :dateEpreuve, heure_epreuve = :heureEpreuve, 
                      id_lieu = :idLieu, id_sport = :idSport WHERE id_epreuve = :idEpreuve";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomEpreuve", $nomEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":dateEpreuve", $dateEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":heureEpreuve", $heureEpreuve, PDO::PARAM_STR);
            $statement->bindParam(":idLieu", $idLieu, PDO::PARAM_INT);
            $statement->bindParam(":idSport", $idSport, PDO::PARAM_INT);
            $statement->bindParam(":idEpreuve", $id_epreuve, PDO::PARAM_INT);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'épreuve a été modifiée avec succès.";
                header("Location: manage-events.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de la modification de l'épreuve.";
                header("Location: modify-events.php?id_epreuve=$id_epreuve");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-events.php?id_epreuve=$id_epreuve");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Modifier une Epreuve - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

    </style>
</head>
<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-genres/manage-genres.php">Gestion Genres</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <h1>Modifier une Epreuve</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="modify-events.php?id_epreuve=<?php echo $id_epreuve; ?>" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cette épreuve?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <label for="nomEpreuve">Nom de l'Epreuve :</label>
            <input type="text" name="nomEpreuve" id="nomEpreuve" value="<?php echo htmlspecialchars($epreuve['nom_epreuve']); ?>" required>
            
            <label for="dateEpreuve">Date de l'Epreuve :</label>
            <input type="date" name="dateEpreuve" id="dateEpreuve" value="<?php echo htmlspecialchars($epreuve['date_epreuve']); ?>" required>
            
            <label for="heureEpreuve">Heure de l'Epreuve :</label>
            <input type="time" name="heureEpreuve" id="heureEpreuve" value="<?php echo htmlspecialchars($epreuve['heure_epreuve']); ?>" required>
            
            <label for="idLieu">Lieu :</label>
            <select name="idLieu" id="idLieu" required>
                <?php foreach ($lieuOptions as $lieu): ?>
                    <option value="<?php echo $lieu['id_lieu']; ?>" <?php if ($lieu['id_lieu'] == $epreuve['id_lieu']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($lieu['nom_lieu']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="idSport">Sport :</label>
            <select name="idSport" id="idSport" required>
                <?php foreach ($sportOptions as $sport): ?>
                    <option value="<?php echo $sport['id_sport']; ?>" <?php if ($sport['id_sport'] == $epreuve['id_sport']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($sport['nom_sport']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <input type="submit" value="Modifier l'Epreuve">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion des épreuves</a>
        </p>
    </main>
    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>
</html>
