<?php
header("Content-Type: application/json; charset=utf-8");
require "Yhteys.php";

$Metodi = $_SERVER["REQUEST_METHOD"] ?? "GET";

// GET = Hakee kaikki varaukset
if ($Metodi === "GET") {
    $Lause = $conn->query("SELECT varaus_id, user_id, huone_id, alku_aika, loppu_aika, tila FROM Varaus");
    $Vastaus = $Lause->fetchAll();
    echo json_encode($Vastaus, JSON_UNESCAPED_UNICODE);
    exit;
}

// POST = Lisää varauksen
if ($Metodi === "POST") {
    try {
        $RaakaData = file_get_contents("php://input");
        $Tiedot = json_decode($RaakaData, true);

        if (!is_array($Tiedot) || empty($Tiedot)) {
            $Tiedot = $_POST;
        }

        // Suodattaa pakolliset kentät
        $User_ID = filter_var($Tiedot["user_id"] ?? null, FILTER_VALIDATE_INT);
        $Huone_ID = filter_var($Tiedot["huone_id"] ?? null, FILTER_VALIDATE_INT);
        $Alku_Aika = trim((string)($Tiedot["alku_aika"] ?? ""));
        $Loppu_Aika = trim((string)($Tiedot["loppu_aika"] ?? ""));
        $Tila = trim((string)($Tiedot["tila"] ?? ""));

        if ($User_ID === false || $User_ID === null || $Huone_ID === false || $Huone_ID === null || $Alku_Aika === "" || $Loppu_Aika === "" || $Tila === "") {
            echo json_encode([
                "Onnistui" => false,
                "Viesti" => "Täytä kaikki kentät oikein ennen lisäämistä."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $Lause = $conn->prepare("INSERT INTO Varaus (user_id, huone_id, alku_aika, loppu_aika, tila) VALUES (:user_id, :huone_id, :alku_aika, :loppu_aika, :tila)");
        $Lause->bindValue(":user_id", $User_ID, PDO::PARAM_INT);
        $Lause->bindValue(":huone_id", $Huone_ID, PDO::PARAM_INT);
        $Lause->bindValue(":alku_aika", $Alku_Aika, PDO::PARAM_STR);
        $Lause->bindValue(":loppu_aika", $Loppu_Aika, PDO::PARAM_STR);
        $Lause->bindValue(":tila", $Tila, PDO::PARAM_STR);
        $Lause->execute();

        echo json_encode([
            "Onnistui" => true,
            "Viesti" => "Varaus lisätty onnistuneesti."
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            "Onnistui" => false,
            "Viesti" => "Virhe: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// PUT = Päivittää olemassa olevan varauksen
if ($Metodi === "PUT") {
    try {
        $RaakaData = file_get_contents("php://input");
        $Tiedot = json_decode($RaakaData, true);

        $Varaus_ID = filter_var($Tiedot["varaus_id"] ?? null, FILTER_VALIDATE_INT);
        $User_ID = filter_var($Tiedot["user_id"] ?? null, FILTER_VALIDATE_INT);
        $Huone_ID = filter_var($Tiedot["huone_id"] ?? null, FILTER_VALIDATE_INT);
        $Alku_Aika = trim((string)($Tiedot["alku_aika"] ?? ""));
        $Loppu_Aika = trim((string)($Tiedot["loppu_aika"] ?? ""));
        $Tila = trim((string)($Tiedot["tila"] ?? ""));

        if ($Varaus_ID === false || $Varaus_ID === null || $User_ID === false || $User_ID === null || $Huone_ID === false || $Huone_ID === null || $Alku_Aika === "" || $Loppu_Aika === "" || $Tila === "") {
            echo json_encode([
                "Onnistui" => false,
                "Viesti" => "Täytä kaikki kentät ennen tallentamista."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $Lause = $conn->prepare("UPDATE Varaus SET user_id = :user_id, huone_id = :huone_id, alku_aika = :alku_aika, loppu_aika = :loppu_aika, tila = :tila WHERE varaus_id = :varaus_id");
        $Lause->bindValue(":user_id", $User_ID, PDO::PARAM_INT);
        $Lause->bindValue(":huone_id", $Huone_ID, PDO::PARAM_INT);
        $Lause->bindValue(":alku_aika", $Alku_Aika, PDO::PARAM_STR);
        $Lause->bindValue(":loppu_aika", $Loppu_Aika, PDO::PARAM_STR);
        $Lause->bindValue(":tila", $Tila, PDO::PARAM_STR);
        $Lause->bindValue(":varaus_id", $Varaus_ID, PDO::PARAM_INT);
        $Lause->execute();

        if ($Lause->rowCount() === 0) {
            $Tarkistus = $conn->prepare("SELECT varaus_id FROM Varaus WHERE varaus_id = :varaus_id");
            $Tarkistus->bindValue(":varaus_id", $Varaus_ID, PDO::PARAM_INT);
            $Tarkistus->execute();
            if (!$Tarkistus->fetch()) {
                echo json_encode([
                    "Onnistui" => false,
                    "Viesti" => "Varausta ei löytynyt."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        echo json_encode([
            "Onnistui" => true,
            "Viesti" => "Varauksen tiedot päivitetty."
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            "Onnistui" => false,
            "Viesti" => "Virhe: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// DELETE = Poistaa varauksen
if ($Metodi === "DELETE") {
    try {
        $RaakaData = file_get_contents("php://input");
        $Tiedot = json_decode($RaakaData, true);

        $Varaus_ID = filter_var($Tiedot["varaus_id"] ?? null, FILTER_VALIDATE_INT);

        if ($Varaus_ID === false || $Varaus_ID === null) {
            echo json_encode([
                "Onnistui" => false,
                "Viesti" => "Varauksen ID on virheellinen."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $Lause = $conn->prepare("DELETE FROM Varaus WHERE varaus_id = :varaus_id");
        $Lause->bindValue(":varaus_id", $Varaus_ID, PDO::PARAM_INT);
        $Lause->execute();

        if ($Lause->rowCount() === 0) {
            $Tarkistus = $conn->prepare("SELECT varaus_id FROM Varaus WHERE varaus_id = :varaus_id");
            $Tarkistus->bindValue(":varaus_id", $Varaus_ID, PDO::PARAM_INT);
            $Tarkistus->execute();
            if (!$Tarkistus->fetch()) {
                echo json_encode([
                    "Onnistui" => false,
                    "Viesti" => "Varausta ei löytynyt."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        echo json_encode([
            "Onnistui" => true,
            "Viesti" => "Varaus poistettu onnistuneesti."
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        echo json_encode([
            "Onnistui" => false,
            "Viesti" => "Virhe: " . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Virhekoodi
http_response_code(405);
echo json_encode([
    "Onnistui" => false,
    "Viesti" => "Metodi ei ole tuettu."
], JSON_UNESCAPED_UNICODE);
?>
