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
           
            <form action="rekisteroityminen.php" id="form1" method="post">
                <h2>Rekisteröidy</h2>

                <br>
                <label for="firstname">Etunimi:</label>
                <input type="text" class="form-control" id="firstname" name="firstname" required>
                
                <br>
                
                <label for="lastname">Sukunimi:</label>
                <input type="text" class="form-control" id="lastname" name="lastname" required>
                
                <br>
                
                <label for="littymispvm">Sähköposti:</label>
                <input type="email" class="form-control" id="littymispvm" name="littymispvm" required>
                
                <br>
                
                <label for="password">Salasana:</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <br>
                <button type="submit" id="registerBtn">Rekisteröidy</button>
                
            </form>

            
        </div>
    </body>
</html>


<?php

// Yhdistetään yhteys.php 
require_once "yhteys.php";
// if lause 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $etunimi = $_POST["firstname"];
    $sukunimi = $_POST["lastname"];
    $sahkoposti = $_POST["littymispvm"];
    $salasana = $_POST["password"];

    if (
        trim($etunimi) === "" ||
        trim($sukunimi) === "" ||
        !filter_var($sahkoposti, FILTER_VALIDATE_EMAIL) ||
        $salasana === ""
    ) {
        die("Virheelliset tiedot.");
    }

    // Salasanan hashauksen tekeminen
    $salasana_hash = password_hash($salasana, PASSWORD_DEFAULT);

        // Tietojen lisääminen
    $sql = "INSERT INTO `User`
            (etunimi, sukunimi, sahkoposti, salasana_hash, luotu)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $etunimi,
        $sukunimi,
        $sahkoposti,
        $salasana_hash
    ]);
    // Tietokantayhteyden viesti
    echo "Rekisteröityminen onnistui!";
}
?>


