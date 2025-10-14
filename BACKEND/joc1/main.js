// --------- Pantalla del Joc ---------
const pantalla = document.querySelector("#pantalla");
const infoPartida = document.querySelector("#infoPartida");
const pantallaAmple = window.innerWidth;
const pantallaAlt = window.innerHeight;
const fotogrames = 1000 / 60; // Actualització cada 16ms, aprox 60 fps.

// ---------- Variables per la gestió de la partida --------
const nivell = 1;
const maxPunts = 100;
const vectorAsteroides = [];
const vectorEnemics = [];
const maxAsteroides = 100;
const maxEnemics = 12;

// ---- Objecte Jugador ----
// Constructor: nom, vides, velocitat, posicio, ample, alt
const jugador = new Jugador("Pepito", 3, 15, {x: 100, y: 300}, 150, 100);
pantalla.append(jugador.elementHTML);

// ---- Vector d'objectes Enemic ----
for (let i = 0; i < maxEnemics; i++) {
  // Fem els enemics de 50x50
  let posX = pantallaAmple + 50;
  let posY = Math.floor(Math.random() * (pantallaAlt - 50));
  let velocitat = Math.floor(Math.random() * 5) + 1;
  vectorEnemics.push(new Enemic(jugador, velocitat, {x: posX, y: posY}, 50, 50));
  pantalla.append(vectorEnemics[i].elementHTML);
}

// ---- Vector d'objectes Asteroides ----
for (let i = 0; i < maxAsteroides; i++) {
  // Fem els asteroides de 5x5
  let posX = Math.floor(Math.random() * pantallaAmple - 3);
  let posY = Math.floor(Math.random() * pantallaAlt - 3);
  let velocitat = Math.floor(Math.random() * 10) + 1;
  vectorAsteroides.push(new Asteroide(velocitat, {x: posX, y: posY}, 3, 3));
  pantalla.append(vectorAsteroides[i].elementHTML);
}

// ------- Informació de la partida -------
const elementNom = document.createElement("p");
const elementPunts = document.createElement("p");
const elementDerribats = document.createElement("p");
const elementVides = document.createElement("p");
elementNom.innerHTML = `Jugador: ${jugador.nom}`;
infoPartida.append(elementNom);
elementPunts.innerHTML = `Punts: ${jugador.punts}`;
infoPartida.append(elementPunts);
elementDerribats.innerHTML = `Kills: ${jugador.derribats}`;
infoPartida.append(elementDerribats);
elementVides.innerHTML = `Vides: ${jugador.vides}`;
infoPartida.append(elementVides);

// ----- Esdeveniments de teclat -----
// Control de la nau del jugador quan prem una tecla
window.addEventListener("keydown", (event) => {
  switch(event.code) {
    case "ArrowUp":
      jugador.y -= jugador.velocitat;
      break;
    case "ArrowDown":
      jugador.y += jugador.velocitat;
      break;
    default:
      break;
  }
});

// ----- Comprovació de Col·lisions -----
function comprovarCollisions() {
  vectorEnemics.forEach(enemic => {
    if (jugador.x <= enemic.x + enemic.ample &&
        jugador.x + jugador.ample >= enemic.x &&
        jugador.y <= enemic.y + enemic.alt &&
        jugador.y + jugador.alt >= enemic.y) {
      // Col·lisió detectada
      enemic.x = pantallaAmple + enemic.ample;
      jugador.punts = jugador.punts + (nivell*10);
      jugador.derribats++;
      infoPartida.querySelector("p:nth-child(2)").innerHTML = `Punts: ${jugador.punts}`;
      infoPartida.querySelector("p:nth-child(3)").innerHTML = `Kills: ${jugador.derribats}`;
      if (jugador.punts >= maxPunts) {
        jugador.velocitat = 0;
        vectorEnemics.forEach(enemic => {
          enemic.velocitat = 0;
        });
        // Preparar el següent nivell
        alert("Nivell superat! :)");
      }
    }
  });
}

// ----- Bucle d'animació del joc -----
setInterval(() => {
  // 0. Gestió de col·lisions
  comprovarCollisions();

  // 1. Gestió del jugador
  infoPartida.querySelector("p:nth-child(4)").innerHTML = `Vides: ${jugador.vides}`;
  if (jugador.vides < 0) {
    jugador.velocitat = 0;
    setTimeout(() => {
      location.reload();
    }, 5000);
  }
  jugador.dibuixar();
  jugador.moure();

  // 2. Gestió dels enemics
  vectorEnemics.forEach(enemic => {
    enemic.dibuixar();
    enemic.moure();
  });

  // 3. Gestió dels asteroides
  vectorAsteroides.forEach(asteroide => {
    asteroide.dibuixar();
    asteroide.moure();
  });
}, fotogrames);