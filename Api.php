<?php
header("Content-Type: application/json; charset=utf-8");
require "yhteys.php";

$Metodi = $_SERVER["REQUEST_METHOD"] ?? "GET";
// Lukee parametrin, joka kertoo mitä taulua käsitellään (esim. api.php?taulu=varaus)
$Taulu = $_GET["taulu"] ?? "";

$SallitutTaulut = ["varaus", "luokkahuone", "user"];
if (!in_array($Taulu, $SallitutTaulut)) {
    http_response_code(400);
    echo json_encode(["Onnistui" => false, "Viesti" => "Virheellinen tai puuttuva taulu-parametri (?taulu=varaus|luokkahuone|user)"], JSON_UNESCAPED_UNICODE);
    exit;
}

// Ottaa vastaan lomakedata valmiiksi yhteen muuttujaan
$RaakaData = file_get_contents("php://input");
$Tiedot = json_decode($RaakaData, true);
if (!is_array($Tiedot) || empty($Tiedot)) {
    $Tiedot = $_POST;
}

    // ==========================================
    // 1. GET - TIETOJEN HAKEMINEN
    // ==========================================
if ($Metodi === "GET") {

    // Varaus osio
    if ($Taulu === "varaus") {
        $Lause = $conn->query("SELECT varaus_id, user_id, huone_id, alku_aika, loppu_aika, tila FROM Varaus");

    // Luokkahuone osio
    } elseif ($Taulu === "luokkahuone") {
        $Lause = $conn->query("SELECT huone_id, nimi, sijainti, kapasiteetti, varustelu FROM Luokkahuone");

    // User osio
    } elseif ($Taulu === "user") {
        $Lause = $conn->query("SELECT user_id, etunimi, sukunimi, sahkoposti, luotu FROM User");
    }
    echo json_encode($Lause->fetchAll(), JSON_UNESCAPED_UNICODE);
    exit;
}

    // ==========================================
    // 2. POST - TIETOJEN LISÄÄMINEN
    // ==========================================
if ($Metodi === "POST") {
    try {

        // Varaus osio
        if ($Taulu === "varaus") {
            $User_ID = filter_var($Tiedot["user_id"] ?? null, FILTER_VALIDATE_INT);
            $Huone_ID = filter_var($Tiedot["huone_id"] ?? null, FILTER_VALIDATE_INT);
            $Alku_Aika = trim((string)($Tiedot["alku_aika"] ?? ""));
            $Loppu_Aika = trim((string)($Tiedot["loppu_aika"] ?? ""));
            $Tila = trim((string)($Tiedot["tila"] ?? ""));

            if ($User_ID === false || $User_ID === null || $Huone_ID === false || $Huone_ID === null || $Alku_Aika === "" || $Loppu_Aika === "" || $Tila === "") {
            echo json_encode(["Onnistui" => false, "Viesti" => "Täytä kaikki varauksen kentät."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Lause = $conn->prepare("INSERT INTO Varaus (user_id, huone_id, alku_aika, loppu_aika, tila) VALUES (:user_id, :huone_id, :alku_aika, :loppu_aika, :tila)");
            $Lause->execute([":user_id" => $User_ID, ":huone_id" => $Huone_ID, ":alku_aika" => $Alku_Aika, ":loppu_aika" => $Loppu_Aika, ":tila" => $Tila]);
        
        // Luokkahuone osio
        } elseif ($Taulu === "luokkahuone") {
            $Nimi = trim((string)($Tiedot["nimi"] ?? ""));
            $Sijainti = trim((string)($Tiedot["sijainti"] ?? ""));
            $Kapasiteetti = filter_var($Tiedot["kapasiteetti"] ?? null, FILTER_VALIDATE_INT);
            $Varustelu = trim((string)($Tiedot["varustelu"] ?? ""));

            if ($Nimi === "" || $Sijainti === "" || $Kapasiteetti === false || $Kapasiteetti === null) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Täytä huoneen nimi, sijainti ja kapasiteetti."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Lause = $conn->prepare("INSERT INTO Luokkahuone (nimi, sijainti, kapasiteetti, varustelu) VALUES (:nimi, :sijainti, :kapasiteetti, :varustelu)");
            $Lause->execute([":nimi" => $Nimi, ":sijainti" => $Sijainti, ":kapasiteetti" => $Kapasiteetti, ":varustelu" => $Varustelu !== "" ? $Varustelu : null]);

        // User osio
        } elseif ($Taulu === "user") {
            // Käyttäjän luonti (Rekisteröinti) salasanan kryptauksella
            $Etunimi = trim((string)($Tiedot["etunimi"] ?? ""));
            $Sukunimi = trim((string)($Tiedot["sukunimi"] ?? ""));
            $Sahkoposti = filter_var($Tiedot["sahkoposti"] ?? '', FILTER_VALIDATE_EMAIL);
            $Salasana = (string)($Tiedot["salasana"] ?? "");

            if ($Etunimi === "" || $Sukunimi === "" || !$Sahkoposti || $Salasana === "") {
            echo json_encode(["Onnistui" => false, "Viesti" => "Täytä käyttäjätiedot oikein."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Tarkistus = $conn->prepare("SELECT user_id FROM User WHERE sahkoposti = :sahkoposti");
            $Tarkistus->execute([":sahkoposti" => $Sahkoposti]);
            if ($Tarkistus->fetch()) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Sähköposti on jo varattu."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Salasana_Hash = password_hash($Salasana, PASSWORD_DEFAULT);
            $Lause = $conn->prepare("INSERT INTO User (etunimi, sukunimi, sahkoposti, salasana_hash) VALUES (:etunimi, :sukunimi, :sahkoposti, :salasana_hash)");
            $Lause->execute([":etunimi" => $Etunimi, ":sukunimi" => $Sukunimi, ":sahkoposti" => $Sahkoposti, ":salasana_hash" => $Salasana_Hash]);
        }

        echo json_encode(["Onnistui" => true, "Viesti" => "Tieto lisätty onnistuneesti tauluun $Taulu."], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        echo json_encode(["Onnistui" => false, "Viesti" => "Virhe: " . $e->getMessage()], JSON_UNESCAPED_UNICODE); exit;
    }
}

    // ==========================================
    // 3. PUT - TIETOJEN PÄIVITTÄMINEN
    // ==========================================
if ($Metodi === "PUT") {
    try {

        // Varaus osio
        if ($Taulu === "varaus") {
            $Varaus_ID = filter_var($Tiedot["varaus_id"] ?? null, FILTER_VALIDATE_INT);
            $User_ID = filter_var($Tiedot["user_id"] ?? null, FILTER_VALIDATE_INT);
            $Huone_ID = filter_var($Tiedot["huone_id"] ?? null, FILTER_VALIDATE_INT);
            $Alku_Aika = trim((string)($Tiedot["alku_aika"] ?? ""));
            $Loppu_Aika = trim((string)($Tiedot["loppu_aika"] ?? ""));
            $Tila = trim((string)($Tiedot["tila"] ?? ""));

            if ($Varaus_ID === false || $Varaus_ID === null || $User_ID === false || $User_ID === null || $Huone_ID === false || $Huone_ID === null || $Alku_Aika === "" || $Loppu_Aika === "" || $Tila === "") {
            echo json_encode(["Onnistui" => false, "Viesti" => "Täytä kaikki kentät."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Lause = $conn->prepare("UPDATE Varaus SET user_id = :user_id, huone_id = :huone_id, alku_aika = :alku_aika, loppu_aika = :loppu_aika, tila = :tila WHERE varaus_id = :varaus_id");
            $Lause->execute([":user_id" => $User_ID, ":huone_id" => $Huone_ID, ":alku_aika" => $Alku_Aika, ":loppu_aika" => $Loppu_Aika, ":tila" => $Tila, ":varaus_id" => $Varaus_ID]);
            $ID_Tarkistus = $Varaus_ID;

        // Luokkahuone osio
        } elseif ($Taulu === "luokkahuone") {
            $Huone_ID = filter_var($Tiedot["huone_id"] ?? null, FILTER_VALIDATE_INT);
            $Nimi = trim((string)($Tiedot["nimi"] ?? ""));
            $Sijainti = trim((string)($Tiedot["sijainti"] ?? ""));
            $Kapasiteetti = filter_var($Tiedot["kapasiteetti"] ?? null, FILTER_VALIDATE_INT);
            $Varustelu = trim((string)($Tiedot["varustelu"] ?? ""));

            if ($Huone_ID === false || $Huone_ID === null || $Nimi === "" || $Sijainti === "" || $Kapasiteetti === false || $Kapasiteetti === null) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Täytä kaikki vaaditut kentät."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Lause = $conn->prepare("UPDATE Luokkahuone SET nimi = :nimi, sijainti = :sijainti, kapasiteetti = :kapasiteetti, varustelu = :varustelu WHERE huone_id = :huone_id");
            $Lause->execute([":nimi" => $Nimi, ":sijainti" => $Sijainti, ":kapasiteetti" => $Kapasiteetti, ":varustelu" => $Varustelu !== "" ? $Varustelu : null, ":huone_id" => $Huone_ID]);
            $ID_Tarkistus = $Huone_ID;

        // User osio
        } elseif ($Taulu === "user") {
            $User_ID = filter_var($Tiedot["user_id"] ?? null, FILTER_VALIDATE_INT);
            $Etunimi = trim((string)($Tiedot["etunimi"] ?? ""));
            $Sukunimi = trim((string)($Tiedot["sukunimi"] ?? ""));
            $Sahkoposti = filter_var($Tiedot["sahkoposti"] ?? '', FILTER_VALIDATE_EMAIL);

            if ($User_ID === false || $User_ID === null || $Etunimi === "" || $Sukunimi === "" || !$Sahkoposti) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Täytä profiilitiedot oikein."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Tarkistus = $conn->prepare("SELECT user_id FROM User WHERE sahkoposti = :sahkoposti AND user_id != :user_id");
            $Tarkistus->execute([":sahkoposti" => $Sahkoposti, ":user_id" => $User_ID]);
            if ($Tarkistus->fetch()) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Sähköposti on jo varattu toiselle käyttäjälle."], JSON_UNESCAPED_UNICODE); exit;
            }

            $Lause = $conn->prepare("UPDATE User SET etunimi = :etunimi, sukunimi = :sukunimi, sahkoposti = :sahkoposti WHERE user_id = :user_id");
            $Lause->execute([":etunimi" => $Etunimi, ":sukunimi" => $Sukunimi, ":sahkoposti" => $Sahkoposti, ":user_id" => $User_ID]);
            $ID_Tarkistus = $User_ID;
        }

        // Tarkistaa löytyykö päivitettävää riviä
        if ($Lause->rowCount() === 0) {
            $TarkistusKanta = $conn->prepare("SELECT 1 FROM " . ucfirst($Taulu) . " WHERE " . $Taulu . "_id = :id");
            $TarkistusKanta->execute([":id" => $ID_Tarkistus]);
            if (!$TarkistusKanta->fetch()) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Kohdetta ei löytynyt taulusta $Taulu."], JSON_UNESCAPED_UNICODE); exit;
            }
        }

        echo json_encode(["Onnistui" => true, "Viesti" => "Tiedot päivitetty tauluun $Taulu."], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        echo json_encode(["Onnistui" => false, "Viesti" => "Virhe: " . $e->getMessage()], JSON_UNESCAPED_UNICODE); exit;
    }
}

    // ==========================================
    // 4. DELETE - TIETOJEN POISTAMINEN
    // ==========================================
        if ($Metodi === "DELETE") {
        try {
            $ID = filter_var($Tiedot[$Taulu . "_id"] ?? null, FILTER_VALIDATE_INT);
            if ($ID === false || $ID === null) {
            echo json_encode(["Onnistui" => false, "Viesti" => "Kohteen ID on virheellinen."], JSON_UNESCAPED_UNICODE); exit;
            }
            $Lause = $conn->prepare("DELETE FROM " . ucfirst($Taulu) . " WHERE " . $Taulu . "_id = :id");
            $Lause->execute([":id" => $ID]);
            if ($Lause->rowCount() === 0) {
            $TarkistusKanta = $conn->prepare("SELECT 1 FROM " . ucfirst($Taulu) . " WHERE " . $Taulu . "_id = :id");
            $TarkistusKanta->execute([":id" => $ID]);

        if (!$TarkistusKanta->fetch()) {
        echo json_encode(["Onnistui" => false, "Viesti" => "Kohdetta ei löytynyt taulusta $Taulu."], JSON_UNESCAPED_UNICODE); exit;
            }
        }
        echo json_encode(["Onnistui" => true, "Viesti" => "Kohde poistettu taulusta $Taulu."], JSON_UNESCAPED_UNICODE);
        exit;
        } catch (PDOException $e) {
        echo json_encode(["Onnistui" => false, "Viesti" => "Poisto epäonnistui. Kohde saattaa olla liitettynä toiseen tauluun (Foreign Key rajoite)."], JSON_UNESCAPED_UNICODE);
        exit;
        }
    }
    // Metodivirhe
    http_response_code(405);
    echo json_encode(["Onnistui" => false, "Viesti" => "Metodi ei ole tuettu."], JSON_UNESCAPED_UNICODE);
?>