// --------- Classe Base ---------
class Entitat {
  constructor(posicio = {x: 0, y:0}, ample = 50, alt = 50) {
    this.x = posicio.x;
    this.y = posicio.y;
    this.ample = ample;
    this.alt = alt;
    //Crear l'element HTML
    this.elementHTML = document.createElement("div");
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
    this.elementHTML.style.width = this.ample + "px";
    this.elementHTML.style.height = this.alt + "px";
  }

  // Modifica la posició de l'element a la pantalla
  dibuixar() {
    this.elementHTML.style.left = this.x + "px";
    this.elementHTML.style.top = this.y + "px";
  }

  moure() {
    // Implementar la lògica de moviment
  }
}

class Jugador extends Entitat {
  constructor(nom, vides, velocitat, posicio, ample, alt) {
    super(posicio, ample, alt);
    this.nom = nom;
    this.vides = vides;
    this.velocitat = velocitat;
    this.punts = 0;
    this.derribats = 0;
    this.elementHTML.classList.add("nau", "jugador");
  }

  moure() {
    if (this.y < 0) {
      this.y = 0;
    } else if (this.y + this.alt > pantallaAlt) {
      this.y = pantallaAlt - this.alt;
    }
  }
}

class Enemic extends Entitat {
  constructor(jugador, velocitat, posicio, ample, alt) {
    super(posicio, ample, alt);
    this.jugador = jugador;
    this.velocitat = velocitat;
    this.elementHTML.classList.add("nau", "enemic");
  }

  moure() {
    this.x -= this.velocitat;
    if (this.x < -this.ample) {
      this.jugador.vides--;
      this.x = pantallaAmple + this.ample;
    }
  }
}

class Asteroide extends Entitat {
  constructor(velocitat, posicio, ample = 5, alt = 5) {
    super(posicio, ample, alt);
    this.velocitat = velocitat;
    this.elementHTML.classList.add("asteroide");
  }

  moure() {
    this.x -= this.velocitat;
    if (this.x < -this.ample) {
      this.x = pantallaAmple + this.ample;
    }
  }
}