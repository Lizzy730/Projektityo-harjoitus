<?php
require "yhteys.php";

$conn = new mysqli($host, $user, $password, $dbname);

// Tarkistaa yhteyden
if ($conn->connect_error) 
    {
    die("Virhe: Tietokantayhteys epäonnistui! " . $conn->connect_error);
    }

    // Saadaan ääkköset toimimaan jos niitä sattuu tulemaan
    $conn->set_charset("utf8mb4");
if ($_SERVER["REQUEST_METHOD"] == "POST") 
    {
    
    // HTML-Rekisteröinti sivun tiedot
    $etunimi = $_POST["firstname"];
    $sukunimi = $_POST["lastname"];
    $sahkoposti = $_POST["sahkoposti"];
    $salasana = $_POST["password"];
    
    // Kryptaa salasanan
    $salasana_hash = password_hash($salasana, PASSWORD_DEFAULT);
    
    // Tarkistaa, onko sähköposti jo olemassa tietokannassa (Estetään siis tuplaantumiset)
    $check_email = $conn->prepare("SELECT sahkoposti FROM User WHERE sahkoposti = ?");
    $check_email->bind_param("s", $sahkoposti);
    $check_email->execute();
    $check_email->store_result();
    
    
    if ($check_email->num_rows > 0) 
        {
        echo "Virhe: Sähköpostiosoite on jo rekisteröity!";
        $check_email->close();
        } else 
            {
                $check_email->close();
        
                // Valmistaa SQL-lauseen tietojen lisäämiseksi tietokantaan User kohtaan
                $rektiedot = $conn->prepare("INSERT INTO User (etunimi, sukunimi, sahkoposti, salasana_hash) VALUES (?, ?, ?, ?)");
                // SSSS = String x4 eli siis niitten tyyppi
                $rektiedot->bind_param("ssss", $etunimi, $sukunimi, $sahkoposti, $salasana_hash);
        
                // Kertoo käyttäjälle onnistuiko
                if ($rektiedot->execute()) {
                // Ohjaa käyttäjän etusivulle
                header("Location: käyttöliittymä.html?viesti=rekisterointi_ok");
                exit();

                } else {
                echo "Virhe: Jotain meni pieleen! " . $rektiedot->error;
                }
        
                $rektiedot->close();
            }
        }

    // Sulkee tietokantayhteyden
    $conn->close();

?>



