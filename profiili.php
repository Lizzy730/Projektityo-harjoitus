<?php require_once "tarkista_kirjautuminen.php"; ?>

<!DOCTYPE html>
<html lang="fi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profiili – Luokkavaraus</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
  <link rel="stylesheet" href="käyttöliittymä.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
  <div class="profile-actions d-flex flex-column align-items-end gap-2 p-3">
    <button id="kirjauduulos" class="btn btn-outline-dark" type="button">Kirjaudu ulos</button>
    <button id="vaihdasalasana" class="btn btn-outline-dark" type="button">Vaihda salasana</button>
    <button id="poistakayttaja" class="btn btn-outline-danger" type="button">Poista käyttäjä</button>
  </div>
  <h1 class="text-center">Profiili</h1>
  <nav class="navbar navbar-expand-sm navbar-light">
  <div class="container-fluid">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" href="käyttöliittymä.html">Etusivu</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="varaukset.html">Varaukset</a>
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
        <a class="nav-link active" href="profiili.html">Profiili</a>
      </li>
    </ul>
  </div>
</nav>
<main class="container my-4">
  <section aria-labelledby="muokkaaOtsikko">
    <h2 id="muokkaaOtsikko">Muokkaa varausta</h2>
    <label for="varausLista" class="form-label">Valitse muokattava varaus:</label>
    <select id="varausLista" class="form-select" disabled>
      <option value="">Ei varauksia</option>
    </select>
    <div class="row g-3 mt-2">
      <div class="col-sm-4">
        <label for="muokkaaLuokka" class="form-label">Luokka</label>
        <select id="muokkaaLuokka" class="form-select" disabled></select>
      </div>
      <div class="col-sm-4">
        <label for="muokkaaAlku" class="form-label">Alkamisaika</label>
        <select id="muokkaaAlku" class="form-select" disabled></select>
      </div>
      <div class="col-sm-4">
        <label for="muokkaaLoppu" class="form-label">Päättymisaika</label>
        <select id="muokkaaLoppu" class="form-select" disabled></select>
      </div>
    </div>
    <button id="tallennaMuutos" class="btn btn-success mt-3" type="button" disabled>Tallenna muutos</button>
    <button id="poistaVaraus" class="btn btn-danger mt-3" type="button" disabled>Poista varaus</button>
  </section>
  <p id="eiVarauksia" class="alert alert-info mt-3" role="status" hidden>
    Sinulla ei ole vielä varauksia.
    <a href="varaukset.html">Siirry tekemään varaus</a>
  </p>
  <p id="varausTila" class="mt-2" role="status" aria-live="polite"></p>
</main>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/fi.js"></script>
<script>
  const reservationList = document.querySelector("#varausLista");
  const removeButton = document.querySelector("#poistaVaraus");
  const saveButton = document.querySelector("#tallennaMuutos");
  const deleteAccountButton = document.querySelector("#poistakayttaja");
  const emptyReservationsMessage = document.querySelector("#eiVarauksia");
  const reservationStatus = document.querySelector("#varausTila");
  const storageKey = "luokkavaraus-varaukset";
  const legacyStorageKey = "luokkavaraus-varatut-paivat";
  const classOptions = ["muokkaaLuokka"];
  const timeOptions = ["muokkaaAlku", "muokkaaLoppu"];
  const editControls = [...classOptions, ...timeOptions];
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
    document.querySelector("#muokkaaLoppu").value = "17:00";
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

  function updateReservationList() {
    const selectedId = reservationList.value;
    emptyReservationsMessage.hidden = reservations.length > 0 || !storageAvailable;
    reservationList.replaceChildren();
    if (reservations.length === 0) {
      const option = document.createElement("option");
      option.value = "";
      option.textContent = "Ei varauksia";
      reservationList.append(option);
    } else {
      reservations.sort((a, b) => {
        const dateDifference = flatpickr.parseDate(a.date, "d.m.Y") - flatpickr.parseDate(b.date, "d.m.Y");
        return dateDifference || Number(a.room) - Number(b.room) || a.start.localeCompare(b.start);
      });
      reservations.forEach(reservation => {
        const option = document.createElement("option");
        option.value = reservation.id;
        option.textContent = `${reservation.date} – Luokka ${reservation.room}, ${reservation.start}–${reservation.end}`;
        reservationList.append(option);
      });
      if (reservations.some(reservation => reservation.id === selectedId)) {
        reservationList.value = selectedId;
      }
    }

    reservationList.disabled = reservations.length === 0 || !storageAvailable;
    const hasSelection = Boolean(reservationList.value) && storageAvailable;
    removeButton.disabled = !hasSelection;
    saveButton.disabled = !hasSelection;
    editControls.forEach(id => {
      document.getElementById(id).disabled = !hasSelection;
    });
    if (hasSelection) {
      loadSelectedReservation();
    }
  }

  function loadSelectedReservation() {
    const selected = reservations.find(reservation => reservation.id === reservationList.value);
    if (!selected) {
      return;
    }
    document.querySelector("#muokkaaLuokka").value = String(selected.room);
    document.querySelector("#muokkaaAlku").value = selected.start;
    document.querySelector("#muokkaaLoppu").value = selected.end;
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
      updateReservationList();
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
    updateReservationList();
  } catch (error) {
    storageAvailable = false;
    setStatus("Varaustietojen lukeminen tai muuntaminen epäonnistui. Tarkista selaimen tallennustila ja tiedot.", true);
    updateReservationList();
  }

  reservationList.addEventListener("change", loadSelectedReservation);

  saveButton.addEventListener("click", function() {
    const selected = reservations.find(reservation => reservation.id === reservationList.value);
    if (!selected) {
      return;
    }
    const updated = {
      ...selected,
      room: document.querySelector("#muokkaaLuokka").value,
      start: document.querySelector("#muokkaaAlku").value,
      end: document.querySelector("#muokkaaLoppu").value
    };
    if (updated.start >= updated.end) {
      setStatus("Päättymisajan täytyy olla alkamisaikaa myöhempi.", true);
      return;
    }
    if (hasConflict(updated, selected.id)) {
      setStatus("Valitussa luokassa on jo varaus päällekkäiselle ajalle.", true);
      return;
    }
    if (!persistReservations(reservations.map(reservation => reservation.id === selected.id ? updated : reservation))) {
      return;
    }
    updateReservationList();
    setStatus("Varauksen muutokset tallennettu.", false);
  });

  removeButton.addEventListener("click", function() {
    const selectedId = reservationList.value;
    if (!selectedId) {
      return;
    }
    if (!persistReservations(reservations.filter(reservation => reservation.id !== selectedId))) {
      return;
    }
    updateReservationList();
    setStatus("Varaus poistettu ja sen aika on jälleen varattavissa.", false);
  });

  deleteAccountButton.addEventListener("click", async function() {
    if (!window.confirm("Haluatko varmasti poistaa käyttäjätilisi? Tiliä ja sen varauksia ei voi palauttaa.")) {
      return;
    }

    deleteAccountButton.disabled = true;
    try {
      const tokenResponse = await fetch("poista_kayttaja.php", { credentials: "same-origin" });
      const tokenData = await tokenResponse.json();
      if (!tokenResponse.ok || typeof tokenData.csrfToken !== "string") {
        throw new Error(tokenData.message || "Poistopyynnön vahvistaminen epäonnistui.");
      }

      const response = await fetch("poista_kayttaja.php", {
        method: "POST",
        credentials: "same-origin",
        headers: { "X-CSRF-Token": tokenData.csrfToken }
      });
      const result = await response.json();
      if (!response.ok || result.success !== true) {
        throw new Error(result.message || "Käyttäjätilin poistaminen epäonnistui.");
      }

      window.location.assign("kirjautuminen.php?viesti=tili_poistettu");
    } catch (error) {
      window.alert(error.message || "Käyttäjätilin poistaminen epäonnistui.");
      deleteAccountButton.disabled = false;
    }
  });
</script>
</body>
</html