<?php

//session_start(); ei tarvita, koska tarkista_kirjautuminen.php hoitaa sen
require_once "tarkista_kirjautuminen.php";

// Tarkistetaan onko pyyntö GET
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["hae"])) {
    header("Content-Type: application/json; charset=utf-8");

    // haetaan varaukset tietokannasta
    try {
        require_once __DIR__ . "/yhteys.php";

        $sql = "SELECT
                    huone_id AS room,
                    DATE_FORMAT(alku_aika, '%d.%m.%Y') AS date,
                    DATE_FORMAT(alku_aika, '%H:%i') AS start,
                    DATE_FORMAT(loppu_aika, '%H:%i') AS end
                FROM Varaus
                WHERE tila = 'voimassa'
                ORDER BY alku_aika";

          // Sql lause ja haetaan varaukset tietokannasta
        $stmt = $yhteys->query($sql);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(["error" => "Varausten hakeminen epäonnistui."]);
    }

    exit;
}
require_once "tarkista_kirjautuminen.php";
// Tarkistetaan onko pyyntö POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json; charset=utf-8");

    try {
        // Otetaan tietokantayhteys käyttöön
        require_once __DIR__ . "/yhteys.php";

        // Haetaan lähetetyt varaustiedot
        $data = json_decode(file_get_contents("php://input"), true);
        $varaukset = $data["varaukset"] ?? [];

        // Tarkistetaan että varauksia on
        if (!is_array($varaukset) || count($varaukset) === 0) {
            throw new Exception("Varauksia ei löytynyt.");
        }

        // Aloitetaan tietokannan tallennus
        $yhteys->beginTransaction();

        if (isset($_SESSION["user_id"])) {
            $user_id = (int) $_SESSION["user_id"];
        } else {
            $vieras = $yhteys->prepare(
                "INSERT INTO User (etunimi, sukunimi, sahkoposti, salasana_hash)
                 VALUES ('Vieras', 'Käyttäjä', ?, ?)
                 ON DUPLICATE KEY UPDATE user_id = LAST_INSERT_ID(user_id)"
            );
            $vieras->execute([
                "vieras-varaukset@invalid.example",
                password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT)
            ]);
            $user_id = (int) $yhteys->lastInsertId();
        }

        // SQL-lause varauksen lisäämiseen
        $sql = "INSERT INTO Varaus
                (user_id, huone_id, alku_aika, loppu_aika, tila)
                VALUES (?, ?, ?, ?, 'voimassa')";

        $stmt = $yhteys->prepare($sql);

        // Käydään varaukset läpi
        foreach ($varaukset as $varaus) {

            // Haetaan luokan numero
            $huone_id = filter_var(
                $varaus["room"] ?? null,
                FILTER_VALIDATE_INT
            );

            // Haetaan päivämäärä ja kellonajat
            $paiva = $varaus["date"] ?? "";
            $alku = $varaus["start"] ?? "";
            $loppu = $varaus["end"] ?? "";

            // Muutetaan päivämäärä oikeaan muotoon
            $pvm = DateTimeImmutable::createFromFormat("!d.m.Y", $paiva);

            // Tarkistetaan varaustiedot
            if (
                !$pvm ||
                $pvm->format("d.m.Y") !== $paiva ||
                $huone_id === false ||
                $huone_id < 1 ||
                $huone_id > 10 ||
                !preg_match('/^(?:[01]\d|2[0-3]):00$/', $alku) ||
                !preg_match('/^(?:[01]\d|2[0-3]|24):00$/', $loppu) ||
                $alku >= $loppu
            ) {
                throw new Exception("Virheelliset varaustiedot.");
            }

            // Muodostetaan alkamisaika
            $alkuaika = $pvm->format("Y-m-d") . " " . $alku . ":00";

            // Muodostetaan päättymisaika
            if ($loppu === "24:00") {
                $loppuaika = $pvm->modify("+1 day")
                    ->format("Y-m-d") . " 00:00:00";
            } else {
                $loppuaika = $pvm->format("Y-m-d") . " " . $loppu . ":00";
            }

            // Lisätään tiedot SQL-lauseeseen
            $stmt->execute([
                $user_id,
                $huone_id,
                $alkuaika,
                $loppuaika
            ]);
        }
        $yhteys->commit();

        // Ilmoitetaan onnistumisesta
        echo json_encode([
            "success" => true,
            "message" => "Varaus tallennettu!"
        ]);

    } catch (Throwable $e) {

        if (isset($yhteys) && $yhteys instanceof PDO && $yhteys->inTransaction()) {
            $yhteys->rollBack();
        }

        error_log($e->getMessage());

        // Ilmoitetaan virheestä
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Varauksen tallentaminen epäonnistui."
        ]);
    }

    exit;
}
?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
  <link rel="stylesheet" href="käyttöliittymä.css">
<h1 style="text-align: center;">Varaukset</h1>
<nav class="navbar navbar-expand-sm navbar-light">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="käyttöliittymä.html">Etusivu</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="varaukset.php">Varaukset</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a class="nav-link" href="#">Rekisteröidy</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Kirjaudu</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profiili.html">Profiili</a>
            </li>
        </ul>
    </div>
</nav>

<main class="container my-4">
  <h2>Valitse varauspäivät</h2>
  <input id="varauspaivat" class="visually-hidden" type="text">
  <div class="row g-3 mt-2">
    <div class="col-sm-4">
      <label for="uusiLuokka" class="form-label">Luokka</label>
      <select id="uusiLuokka" class="form-select"></select>
    </div>
    <div class="col-sm-4">
      <label for="uusiAlku" class="form-label">Alkamisaika</label>
      <select id="uusiAlku" class="form-select"></select>
    </div>
    <div class="col-sm-4">
      <label for="uusiLoppu" class="form-label">Päättymisaika</label>
      <select id="uusiLoppu" class="form-select"></select>
    </div>
  </div>
  <button id="varaaPaivat" class="btn btn-primary mt-3" type="button" disabled>Varaa päivät</button>
  <p id="varausTila" class="mt-2" role="status" aria-live="polite"></p>
</main>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/fi.js"></script>
<script>
  const reservationInput = document.querySelector("#varauspaivat");
  const reserveButton = document.querySelector("#varaaPaivat");
  const reservationStatus = document.querySelector("#varausTila");
  const storageKey = "luokkavaraus-varaukset";
  const legacyStorageKey = "luokkavaraus-varatut-paivat";
  const classOptions = ["uusiLuokka"];
  const timeOptions = ["uusiAlku", "uusiLoppu"];
  let reservations = [];
  let storageAvailable = true;

  function populateOptions() {
    classOptions.forEach(id => {
      const select = document.getElementById(id);
      for (let room = 1; room <= 10; room += 1) {
        const option = document.createElement("option");
        option.value = String(room);
        option.textContent = `Luokka ${room}`;
        select.append(option);
      }
    });

    timeOptions.forEach(id => {
      const select = document.getElementById(id);
      for (let hour = 0; hour <= 24; hour += 1) {
        const option = document.createElement("option");
        option.value = String(hour).padStart(2, "0") + ":00";
        option.textContent = option.value;
        select.append(option);
      }
    });
    document.querySelector("#uusiLoppu").value = "17:00";
  }

  function setStatus(message, isError) {
    reservationStatus.textContent = message;
    reservationStatus.classList.toggle("text-danger", isError);
  }

  function validReservation(reservation) {
    return reservation && typeof reservation.id === "string" &&
      typeof reservation.date === "string" &&
      Number.isInteger(Number(reservation.room)) && Number(reservation.room) >= 1 && Number(reservation.room) <= 10 &&
      typeof reservation.start === "string" && typeof reservation.end === "string" &&
      /^\d{2}:00$/.test(reservation.start) && /^\d{2}:00$/.test(reservation.end) &&
      Number(reservation.start.slice(0, 2)) < Number(reservation.end.slice(0, 2)) &&
      Number(reservation.end.slice(0, 2)) <= 24;
  }

  function hasConflict(candidate, ignoredId) {
    return reservations.some(reservation =>
      reservation.id !== ignoredId &&
      reservation.date === candidate.date &&
      Number(reservation.room) === Number(candidate.room) &&
      candidate.start < reservation.end &&
      reservation.start < candidate.end
    );
  }

  function persistReservations(updatedReservations) {
    try {
      localStorage.setItem(storageKey, JSON.stringify(updatedReservations));
      reservations = updatedReservations;
      return true;
    } catch (error) {
      storageAvailable = false;
      reserveButton.disabled = true;
      setStatus("Varaustietojen tallentaminen epäonnistui. Tarkista selaimen tallennustila ja yritä uudelleen.", true);
      return false;
    }
  }

  function readReservations() {
    const stored = localStorage.getItem(storageKey);
    if (stored !== null) {
      const parsed = JSON.parse(stored);
      if (!Array.isArray(parsed) || !parsed.every(validReservation)) {
        throw new Error("Varaustiedot eivät ole kelvollisia.");
      }
      return parsed;
    }

    const legacy = localStorage.getItem(legacyStorageKey);
    if (legacy === null) {
      return [];
    }
    const oldDates = JSON.parse(legacy);
    if (!Array.isArray(oldDates) || !oldDates.every(day => typeof day === "string")) {
      throw new Error("Aiemmat varaustiedot eivät ole kelvollisia.");
    }
    const migrated = oldDates.map((date, index) => ({
      id: `vanha-${index}-${date}`,
      date,
      room: 1,
      start: "00:00",
      end: "24:00"
    }));
    localStorage.setItem(storageKey, JSON.stringify(migrated));
    return migrated;
  }

  populateOptions();
  try {
    reservations = readReservations();
  } catch (error) {
    storageAvailable = false;
    setStatus("Varaustietojen lukeminen tai muuntaminen epäonnistui. Tarkista selaimen tallennustila ja tiedot.", true);
    reserveButton.disabled = true;
  }

  const calendar = flatpickr(reservationInput, {
    mode: "multiple",
    inline: true,
    minDate: "today",
    dateFormat: "d.m.Y",
    locale: "fi",
    onChange: selectedDates => {
      reserveButton.disabled = selectedDates.length === 0 || !storageAvailable;
    }
  });

  reserveButton.addEventListener("click", async function() {
    const start = document.querySelector("#uusiAlku").value;
    const end = document.querySelector("#uusiLoppu").value;
    if (!calendar.selectedDates.length || !storageAvailable) {
      return;
    }
    if (start >= end) {
      setStatus("Päättymisajan täytyy olla alkamisaikaa myöhempi.", true);
      return;
    }

    const newReservations = calendar.selectedDates.map(date => ({
      id: `${Date.now()}-${Math.random().toString(36).slice(2)}`,
      date: calendar.formatDate(date, "d.m.Y"),
      room: document.querySelector("#uusiLuokka").value,
      start,
      end
    }));
    if (newReservations.some(candidate => hasConflict(candidate)) ||
        newReservations.some((candidate, index) => newReservations.slice(0, index).some(other =>
          candidate.date === other.date && candidate.room === other.room &&
          candidate.start < other.end && other.start < candidate.end
        ))) {
      setStatus("Valitussa luokassa on jo varaus päällekkäiselle ajalle. Valitse toinen luokka, aika tai päivä.", true);
      return;
    }

  
try {
    const response = await fetch("varaukset.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            varaukset: newReservations
        })
    });

    const data = await response.json();

    if (response.ok && data.success) {
        calendar.clear();
        reserveButton.disabled = true;
        setStatus("Varaus tallennettu tietokantaan!", false);
    } else {
        setStatus(data.message || "Varauksen tallennus epäonnistui.", true);
    }

} catch (error) {
    setStatus("Virhe API-yhteydessä.", true);
}

  });
</script>
</body>
</html




