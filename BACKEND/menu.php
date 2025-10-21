<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selecció de Joc</title>
  <link rel="stylesheet" href="../css/menu_css.css">
  <link rel="stylesheet" href="../css/header.css" />
  <link rel="stylesheet" href="../css/sanitize.css" />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
  <?php include 'header.php'; ?>

  <main class="panel-container">
    <h2>Tria el teu Joc</h2>
    <div class="jocs-container">
      <div class="joc">
        <a href="joc1/index.php"><img src="../img/joc1.jpg" alt="Joc 1"></a>
        <h3>JOC 1</h3>
      </div>
      <div class="joc">
        <a href="joc2/joc.php"><img src="../img/start.png" alt="Joc 2"></a>
        <h3>JOC 2</h3>
      </div>
      <div class="joc">
        <a href="joc3/index.php"><img src="../img/snakelogo.jpg" alt="Joc 3"></a>
        <h3>JOC 3</h3>
      </div>
      <div class="joc">
        <a href="joc4/index.php"><img src="../img/fruit.png" alt="Joc 4"></a>
        <h3>JOC 4</h3>
      </div>
    </div>
  </main>

  <footer>
    <p>© 2025 POMASA MI MASSA - Tots els drets reservats</p>
  </footer>
</body>
</html>
