<?php
require "yhteys.php";

// Luo muuttujan mahdollisille virheviesteille, jotka halutaan näyttää lomakkeella
$virheviesti = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // HTML-Rekisteröinti sivun tiedot
    $etunimi = $_POST["firstname"];
    $sukunimi = $_POST["lastname"];
    $sahkoposti = $_POST["sahkoposti"];
    $salasana = $_POST["password"];
    
    // Kryptaa salasanan
    $salasana_hash = password_hash($salasana, PASSWORD_DEFAULT);
    
    // Tarkistaa, että onko sähköposti jo olemassa tietokannassa (Estetään siis tuplaantumiset)
    $check_email = $yhteys->prepare("SELECT sahkoposti FROM User WHERE sahkoposti = ?");
    $check_email->execute([$sahkoposti]);
    
    if ($check_email->fetch()) {
        $virheviesti = "Virhe: Sähköpostiosoite on jo rekisteröity!";
        $check_email = null;
    } else {
        $check_email = null;
        
        // Valmistaa SQL-lauseen tietojen lisäämiseksi tietokantaan User kohtaan
        $rektiedot = $yhteys->prepare("INSERT INTO User (etunimi, sukunimi, sahkoposti, salasana_hash) VALUES (?, ?, ?, ?)");
        
        // Kertoo käyttäjälle onnistuiko
        if ($rektiedot->execute([$etunimi, $sukunimi, $sahkoposti, $salasana_hash])) {
            $rektiedot = null;
            $yhteys = null;
            // Ohjaa käyttäjän kirjautumissivulle
            header("Location: kirjautuminen.php?viesti=rekisterointi_ok");
            exit();
        } else {
            $virheviesti = "Virhe: Jotain meni pieleen!";
            $rektiedot = null;
        }
    }
}

// Sulkee tietokantayhteyden
$yhteys = null;
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekisteröityminen</title>
    <link rel="stylesheet" href="logstyles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-3">
        <form action="rekisterointi.php" method="post"  class="card p-4 shadow-sm">
            <h2>Rekisteröidy</h2>
            
            <?php if (!empty($virheviesti)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $virheviesti; ?>
                </div>
            <?php endif; ?>
            
            <br>
            <label for="firstname">Etunimi:</label>
            <input type="text" class="form-control" id="firstname" name="firstname" required>
            <br>
            <label for="lastname">Sukunimi:</label>
            <input type="text" class="form-control" id="lastname" name="lastname" required>
            <br>
            <label for="sahkoposti">Sähköposti:</label>
            <input type="email" class="form-control" id="sahkoposti" name="sahkoposti" required>
            <br>
            <label for="password">Salasana:</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <br>
            <button type="submit" id="registerBtn" class="btn btn-primary w-100">Rekisteröidy</button>
        </form>
        <p class="mt-3 text-center">Onko sinulla jo tili? <a href="kirjautuminen.php">Kirjaudu tästä!</a></p>
    </div>
</body>
</html>
