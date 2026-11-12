<?php
session_start();
header("Content-Type: application/json; charset=utf-8");

if (($_SESSION["kirjautunut"] ?? false) !== true || !isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Kirjaudu sisään poistaaksesi käyttäjätilin."], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"] ?? "";
if ($method === "GET") {
    if (!isset($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    echo json_encode(["csrfToken" => $_SESSION["csrf_token"]], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== "POST") {
    http_response_code(405);
    header("Allow: GET, POST");
    echo json_encode(["success" => false, "message" => "Pyyntöä ei tueta."], JSON_UNESCAPED_UNICODE);
    exit;
}

$csrfToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
if (!isset($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Poistopyynnön vahvistaminen epäonnistui. Päivitä sivu ja yritä uudelleen."], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . "/yhteys.php";
    $userId = (int) $_SESSION["user_id"];

    $yhteys->beginTransaction();
    $deleteReservations = $yhteys->prepare("DELETE FROM Varaus WHERE user_id = :user_id");
    $deleteReservations->execute([":user_id" => $userId]);

    $deleteUser = $yhteys->prepare("DELETE FROM User WHERE user_id = :user_id");
    $deleteUser->execute([":user_id" => $userId]);
    if ($deleteUser->rowCount() !== 1) {
        throw new RuntimeException("Kirjautunutta käyttäjää ei löytynyt.");
    }

    $yhteys->commit();
    $_SESSION = [];
    session_destroy();

    echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if (isset($yhteys) && $yhteys instanceof PDO && $yhteys->inTransaction()) {
        $yhteys->rollBack();
    }

    error_log($error->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Käyttäjätilin ja varausten poistaminen epäonnistui."], JSON_UNESCAPED_UNICODE);
}
