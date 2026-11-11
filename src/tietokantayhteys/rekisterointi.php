<?php
require "yhteys.php";

$conn = new mysqli($host, $user, $password, $dbname);

// Tarkistaa yhteyden
if ($conn->connect_error) {
    die("Virhe: Tietokantayhteys epäonnistui! " . $conn->connect_error);
}

// Saa ääkköset toimimaan jos niitä sattuu tulemaan
$conn->set_charset("utf8mb4");

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
    $check_email = $conn->prepare("SELECT sahkoposti FROM User WHERE sahkoposti = ?");
    $check_email->bind_param("s", $sahkoposti);
    $check_email->execute();
    $check_email->store_result();
    
    if ($check_email->num_rows > 0) {
        $virheviesti = "Virhe: Sähköpostiosoite on jo rekisteröity!";
        $check_email->close();
    } else {
        $check_email->close();
        
        // Valmistaa SQL-lauseen tietojen lisäämiseksi tietokantaan User kohtaan
        $rektiedot = $conn->prepare("INSERT INTO User (etunimi, sukunimi, sahkoposti, salasana_hash) VALUES (?, ?, ?, ?)");
        // SSSS = String x4 eli siis niitten tyyppi
        $rektiedot->bind_param("ssss", $etunimi, $sukunimi, $sahkoposti, $salasana_hash);
        
        // Kertoo käyttäjälle onnistuiko
        if ($rektiedot->execute()) {
            $rektiedot->close();
            $conn->close();
            // Ohjaa käyttäjän kirjautumissivulle
            header("Location: kirjautuminen.php?viesti=rekisterointi_ok");
            exit();
        } else {
            $virheviesti = "Virhe: Jotain meni pieleen! " . $rektiedot->error;
            $rektiedot->close();
        }
    }
}

// Sulkee tietokantayhteyden
$conn->close();
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
        <form action="rekisterointi.php" id="form1" method="post">
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
            <button type="submit" id="registerBtn">Rekisteröidy</button>
        </form>
    </div>
</body>
</html>