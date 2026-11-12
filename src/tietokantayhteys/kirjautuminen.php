<?php
session_start();

// Jos käyttäjä on jo kirjautunut sisään = Heitetään suoraan etusivulle
if (isset($_SESSION["kirjautunut"]) && $_SESSION["kirjautunut"] === true) {
    header("Location: käyttöliittymä.html");
    exit();
}

// Tietokantaan yhdistys
require "yhteys.php";

// Muuttujat virhe- ja onnistumisviesteille
$virheviesti = "";
$onnistumisviesti = "";

// Tarkistaa tuliko käyttäjä rekisteröitymissivulta tai kirjautuiko hän ulos
if (isset($_GET["viesti"])) {
    if ($_GET["viesti"] === "rekisterointi_ok") {
        $onnistumisviesti = "Rekisteröityminen onnistui! Voit nyt kirjautua sisään.";
    } elseif ($_GET["viesti"] === "ulos_ok") {
        $onnistumisviesti = "Olet kirjautunut onnistuneesti ulos.";
    }
}

// Lomakkeen käsittely kirjautumista varten
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sahkoposti = trim($_POST["sahkoposti"]);
    $salasana = $_POST["password"];

    if (!empty($sahkoposti) && !empty($salasana)) {
        // Hakee käyttäjän sähköpostin perusteella (Huomioitu user_id, etunimi ja salasana_hash)
        $stmt = $yhteys->prepare("SELECT user_id, etunimi, salasana_hash FROM User WHERE sahkoposti = ?");
        $stmt->execute([$sahkoposti]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            // Verifioi salasanan vertaamalla sitä tietokannan hashiin
            if (password_verify($salasana, $user_data["salasana_hash"])) {
                
                // Tallennetaan käyttäjän tiedot istuntoon
                $_SESSION["kirjautunut"] = true;
                $_SESSION["user_id"] = $user_data["user_id"];
                $_SESSION["user_etunimi"] = $user_data["etunimi"];

                $stmt = null;
                $yhteys = null;

                // Vie käyttäjän etusivulle kirjautumisen jälkeen
                header("Location: käyttöliittymä.html");
                exit();
            } else {
                $virheviesti = "Virhe: Väärä salasana!";
            }
        } else {
            $virheviesti = "Virhe: Sähköpostiosoitetta ei löydy!";
        }
        $stmt = null;
    } else {
        $virheviesti = "Täytä kaikki kentät!";
    }
}
$yhteys = null;
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirjautuminen</title>
    <link rel="stylesheet" href="logstyles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container mt-5" style="max-width: 500px;">
        
        <form action="kirjautuminen.php" method="post" class="card p-4 shadow-sm">
            <h2>Kirjaudu sisään</h2>
            
            <!-- Näyttää punaisen virhelaatikkon, jos kirjautuminen epäonnistuu -->
            <?php if (!empty($virheviesti)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $virheviesti; ?>
                </div>
            <?php endif; ?>
            
            <!-- Näyttää vihreän laatikon, jos rekisteröinti onnistui tai kirjauduttiin ulos -->
            <?php if (!empty($onnistumisviesti)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $onnistumisviesti; ?>
                </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="sahkoposti" class="form-label">Sähköpostiosoite:</label>
                <input type="email" class="form-control" id="sahkoposti" name="sahkoposti" placeholder="esimerkki@sähköposti.fi" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Salasana:</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Syötä salasana" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">Kirjaudu</button>
        </form>
        
        <p class="mt-3 text-center">Ei vielä tiliä? <a href="rekisteroityminen.php">Rekisteröidy tästä!</a></p>
    </div>
</body>
</html>
