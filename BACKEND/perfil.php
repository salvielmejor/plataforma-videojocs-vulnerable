<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Perfil d'Usuari</title>
  <link rel="stylesheet" href="../css/menu_css.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Fons de vídeo i capa fosca -->
  <video autoplay muted loop id="video-fondo">
    <source src="fons.mp4" type="video/mp4">
  </video>
  <div class="overlay"></div>

  <!-- Menú superior -->
  <header class="menu-superior">
    <nav>
      <a href="menu.php">Inici</a>
      <a href="ranking.php">Ranking</a>
      <a href="https://www.google.com">Sortir de la sessió</a>
    </nav>
  </header>

  <!-- Panell de perfil -->
  <main class="panel-container perfil-container">
    <h2>Perfil d'Usuari</h2>

    <div class="perfil-content">
      <div class="perfil-foto">
        <img src="../img/usuari.png" alt="Foto de perfil">
      </div>

      <div class="perfil-dades">
        <p><strong>Nom d'usuari:</strong> <span>NeoPlayer</span></p>
        <p><strong>Correu:</strong> <span>neoplayer@example.com</span></p>
        <p><strong>Nivell:</strong> <span>7</span></p>
        <p><strong>Punts totals:</strong> <span>12.450</span></p>
        <p><strong>Última connexió:</strong> <span>14/10/2025 - 18:32h</span></p>
      </div>
    </div>

    <button class="editar-btn">Editar Perfil</button>
  </main>

  <!-- Peu de pàgina -->
  <footer>
    <p>© 2025 Selecció de Joc - Perfil d'Usuari</p>
  </footer>

</body>
</html>
