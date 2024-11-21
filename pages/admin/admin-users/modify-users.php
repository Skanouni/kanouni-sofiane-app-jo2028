<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du users est fourni dans l'URL
if (!isset($_GET['id_utilisateur'])) {
    $_SESSION['error'] = "ID du users manquant.";
    header("Location: manage-users.php");
    exit();
}

$id_utilisateur = filter_input(INPUT_GET, 'id_utilisateur', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du users est un entier valide
if (!$id_utilisateur && $id_utilisateur !== 0) {
    $_SESSION['error'] = "ID du users invalide.";
    header("Location: manage-users.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du users pour affichage dans le formulaire
try {
    $queryusers = "SELECT * FROM UTILISATEUR WHERE id_utilisateur = :id_utilisateur";
    $statementusers = $connexion->prepare($queryusers);
    $statementusers->bindParam(":id_utilisateur", $id_utilisateur, PDO::PARAM_INT);
    $statementusers->execute();

    if ($statementusers->rowCount() > 0) {
        $users = $statementusers->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Utilisateur non trouvé.";
        header("Location: manage-users.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-users.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_utilisateur = filter_input(INPUT_POST, 'nom_utilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenom_utilisateur = filter_input(INPUT_POST, 'prenom_utilisateur', FILTER_SANITIZE_SPECIAL_CHARS);
    $new_password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le nom ou le prénom est vide
    if (empty($nom_utilisateur) || empty($prenom_utilisateur)) {
        $_SESSION['error'] = "Le nom et le prénom de l'utilisateur ne peuvent pas être vides.";
        header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
        exit();
    }

    try {
        // Vérifiez si un utilisateur avec le même nom et prénom existe déjà (sauf pour l'utilisateur actuel)
        $queryCheck = "SELECT id_utilisateur FROM UTILISATEUR WHERE nom_utilisateur = :nom_utilisateur AND prenom_utilisateur = :prenom_utilisateur AND id_utilisateur <> :id_utilisateur";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":id_utilisateur", $id_utilisateur, PDO::PARAM_INT);
        $statementCheck->bindParam(":nom_utilisateur", $nom_utilisateur, PDO::PARAM_STR);
        $statementCheck->bindParam(":prenom_utilisateur", $prenom_utilisateur, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'utilisateur existe déjà.";
            header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
            exit();
        }

        // Mise à jour des informations de l'utilisateur
        $query = "UPDATE UTILISATEUR SET nom_utilisateur = :nom_utilisateur, prenom_utilisateur= :prenom_utilisateur";
        
        // Ajout de la mise à jour du mot de passe si un nouveau mot de passe est saisi
        if (!empty($new_password)) {
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id_utilisateur = :id_utilisateur";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":id_utilisateur", $id_utilisateur, PDO::PARAM_INT);
        $statement->bindParam(":nom_utilisateur", $nom_utilisateur, PDO::PARAM_STR);
        $statement->bindParam(":prenom_utilisateur", $prenom_utilisateur, PDO::PARAM_STR);

        // Lier le mot de passe haché si un nouveau mot de passe a été saisi
        if (!empty($new_password)) {
            $statement->bindParam(":password", $new_password_hashed, PDO::PARAM_STR);
        }

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'utilisateur a été modifié avec succès.";
            header("Location: manage-users.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du users.";
            header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-users.php?id_utilisateur=$id_utilisateur");
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
    <title>Modifier un utilisateur - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un utilisateur</h1>

        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-users.php?id_utilisateur=<?php echo $id_utilisateur; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet utilisateur?')">

            <label for="nom_utilisateur">Nom de l'utilisateur :</label>
            <input type="text" name="nom_utilisateur" id="nom_utilisateur"
                value="<?php echo htmlspecialchars($users['nom_utilisateur']); ?>" required>
                
            <label for="prenom_utilisateur">Prénom de l'utilisateur :</label>
            <input type="text" name="prenom_utilisateur" id="prenom_utilisateur"
                value="<?php echo htmlspecialchars($users['prenom_utilisateur']); ?>" required>

            <label for="password">Nouveau mot de passe (ne pas remplir pour ne pas modifier) :</label>
            <input type="password" name="password" id="password">

            <input type="submit" value="Modifier l'utilisateur">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-users.php">Retour à la gestion des Utilisateurs</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>
