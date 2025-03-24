<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}


$queryPays = "SELECT * FROM PAYS";
$statementPays = $connexion->prepare($queryPays);
$statementPays->execute();
$paysOptions = $statementPays->fetchAll(PDO::FETCH_ASSOC);

$queryGenre = "SELECT * FROM GENRE";
$statementGenre = $connexion->prepare($queryGenre);
$statementGenre->execute();
$genreOptions = $statementGenre->fetchAll(PDO::FETCH_ASSOC);

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nomAthlete = filter_input(INPUT_POST, 'nomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenomAthlete = filter_input(INPUT_POST, 'prenomAthlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $idPays = filter_input(INPUT_POST, 'idPays', FILTER_VALIDATE_INT);
    $idGenre = filter_input(INPUT_POST, 'idGenre', FILTER_VALIDATE_INT);
    $idAthlete = filter_input(INPUT_POST, 'idAthlete', FILTER_VALIDATE_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-athletes.php");
        exit();
    }

    // Vérifiez si le nom de l'athlète est vide
    if (empty($nomAthlete)) {
        $_SESSION['error'] = "Le nom de l'athlète ne peut pas être vide.";
        header("Location: add-athletes.php");
        exit();
    }

    try {
        // Vérifiez si l'athlète existe déjà (sans l'ID si c'est un nouvel athlète)
        if (empty($idAthlete)) {
            $queryCheck = "SELECT id_athlete 
                           FROM ATHLETE 
                           WHERE nom_athlete = :nomAthlete 
                           AND prenom_athlete = :prenomAthlete 
                           AND id_pays = :idPays
                           AND id_genre = :idGenre";
        } else {
            // Si l'athlète existe déjà mais qu'on est en mode de modification
            $queryCheck = "SELECT id_athlete 
                           FROM ATHLETE 
                           WHERE nom_athlete = :nomAthlete 
                           AND prenom_athlete = :prenomAthlete 
                           AND id_pays = :idPays
                           AND id_genre = :idGenre
                           AND id_athlete <> :idAthlete";
        }

        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":idPays", $idPays, PDO::PARAM_INT);
        $statementCheck->bindParam(":idGenre", $idGenre, PDO::PARAM_INT);

        // Lier idAthlete seulement si c'est une modification
        if (!empty($idAthlete)) {
            $statementCheck->bindParam(":idAthlete", $idAthlete, PDO::PARAM_INT);
        }

        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'athlète existe déjà.";
            header("Location: add-athletes.php");
            exit();
        } else {
            // Requête pour ajouter un athlète
            $query = "INSERT INTO ATHLETE (nom_athlete,prenom_athlete,id_pays,id_genre) VALUES (:nomAthlete,:prenomAthlete,:idPays,:idGenre)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":nomAthlete", $nomAthlete, PDO::PARAM_STR);
            $statement->bindParam(":prenomAthlete", $prenomAthlete, PDO::PARAM_STR);
            $statement->bindParam(":idPays", $idPays, PDO::PARAM_INT);
            $statement->bindParam(":idGenre", $idGenre, PDO::PARAM_INT);
    
            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'athlète a été ajouté avec succès.";
                header("Location: manage-athletes.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'athlète.";
                header("Location: add-athletes.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-athletes.php");
        exit();
    }
}

// Afficher les erreurs en PHP
error_reporting(E_ALL);
ini_set("display_errors", 1);
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
    <title>Ajouter un Athlète - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter un Athlète</h1>
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
        <form action="add-athletes.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet athlète?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <label for="nomAthlete">Nom de l'Athlète :</label>
            <input type="text" name="nomAthlete" id="nomAthlete"
                value="<?php echo isset($athlete['nom_athlete']) ? htmlspecialchars($athlete['nom_athlete']) : ''; ?>" required>

            <label for="prenomAthlete">Prénom de l'Athlète :</label>
            <input type="text" name="prenomAthlete" id="prenomAthlete"
                value="<?php echo isset($athlete['prenom_athlete']) ? htmlspecialchars($athlete['prenom_athlete']) : ''; ?>" required>

            <label for="idPays">Pays :</label>
            <select name="idPays" id="idPays" required>
                <?php foreach ($paysOptions as $pays): ?>
                    <option value="<?php echo $pays['id_pays']; ?>" <?php echo (isset($athlete['id_pays']) && $pays['id_pays'] == $athlete['id_pays']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($pays['nom_pays']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="idGenre">Genre :</label>
            <select name="idGenre" id="idGenre" required>
                <?php foreach ($genreOptions as $genre): ?>
                    <option value="<?php echo $genre['id_genre']; ?>" <?php echo (isset($athlete['id_genre']) && $genre['id_genre'] == $athlete['id_genre']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genre['nom_genre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Ajouter un Athlète">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion des athletes</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>
