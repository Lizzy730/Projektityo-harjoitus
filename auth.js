(function() {
  const accountsKey = "luokkavaraus-accounts";
  const sessionKey = "luokkavaraus-session";
  const allowedDestinations = ["käyttöliittymä.html", "varaukset.html", "profiili.html"];

  function getAccounts() {
    const stored = localStorage.getItem(accountsKey);
    if (stored === null) {
      return [];
    }

    const accounts = JSON.parse(stored);
    if (!Array.isArray(accounts) || !accounts.every(account =>
      account && typeof account.email === "string" &&
      typeof account.salt === "string" && typeof account.passwordHash === "string"
    )) {
      throw new Error("Käyttäjätilien tiedot eivät ole kelvollisia.");
    }
    return accounts;
  }

  function encodeBytes(bytes) {
    return btoa(String.fromCharCode(...bytes));
  }

  function decodeBytes(value) {
    return Uint8Array.from(atob(value), character => character.charCodeAt(0));
  }

  async function hashPassword(password, salt) {
    if (!crypto.subtle) {
      throw new Error("Selaimen salausominaisuudet eivät ole käytettävissä. Avaa sivusto localhostin tai HTTPS-yhteyden kautta.");
    }
    const key = await crypto.subtle.importKey(
      "raw",
      new TextEncoder().encode(password),
      "PBKDF2",
      false,
      ["deriveBits"]
    );
    const bits = await crypto.subtle.deriveBits(
      { name: "PBKDF2", salt, iterations: 120000, hash: "SHA-256" },
      key,
      256
    );
    return new Uint8Array(bits);
  }

  function sameBytes(first, second) {
    return first.length === second.length &&
      first.every((byte, index) => byte === second[index]);
  }

  function destinationFromUrl() {
    const requested = new URLSearchParams(location.search).get("next");
    return allowedDestinations.includes(requested) ? requested : "käyttöliittymä.html";
  }

  function redirectToRegistration(destination) {
    const query = new URLSearchParams({ next: destination });
    location.replace(`rekisteroidy.html?${query}`);
  }

  window.Auth = {
    isLoggedIn() {
      const email = sessionStorage.getItem(sessionKey);
      return Boolean(email && getAccounts().some(account => account.email === email));
    },

    protectPage() {
      let loggedIn = false;
      try {
        loggedIn = this.isLoggedIn();
      } catch (error) {
        console.error("Kirjautumistilan tarkistaminen epäonnistui.", error);
      }
      if (!loggedIn) {
        const page = location.pathname.split("/").pop();
        redirectToRegistration(allowedDestinations.includes(page) ? page : "käyttöliittymä.html");
      }
    },

    async register(email, password) {
      const normalizedEmail = email.trim().toLowerCase();
      const accounts = getAccounts();
      if (accounts.some(account => account.email === normalizedEmail)) {
        throw new Error("Tällä sähköpostiosoitteella on jo käyttäjätili. Kirjaudu sisään.");
      }

      const salt = crypto.getRandomValues(new Uint8Array(16));
      const passwordHash = await hashPassword(password, salt);
      accounts.push({
        email: normalizedEmail,
        salt: encodeBytes(salt),
        passwordHash: encodeBytes(passwordHash)
      });
      localStorage.setItem(accountsKey, JSON.stringify(accounts));
      sessionStorage.setItem(sessionKey, normalizedEmail);
    },

    async login(email, password) {
      const normalizedEmail = email.trim().toLowerCase();
      const account = getAccounts().find(item => item.email === normalizedEmail);
      if (!account) {
        throw new Error("Sähköpostiosoite tai salasana on väärä.");
      }

      const passwordHash = await hashPassword(password, decodeBytes(account.salt));
      if (!sameBytes(passwordHash, decodeBytes(account.passwordHash))) {
        throw new Error("Sähköpostiosoite tai salasana on väärä.");
      }
      sessionStorage.setItem(sessionKey, normalizedEmail);
    },

    logout() {
      sessionStorage.removeItem(sessionKey);
      location.replace("rekisteroidy.html");
    },

    destinationFromUrl
  };
})();
