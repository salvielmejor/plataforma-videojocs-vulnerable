<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SANKE PRO</title>
  <link rel="stylesheet" href="../../css/snake.css" />
</head>
<body>
  <div class="container">
    <h1> SANKE PRO</h1>
    <div class="stats">
      <span id="score">Puntuación: 0</span>
      <span id="level">Nivel: 1</span>
      <span id="speed">Velocidad: 100ms</span>
    </div>
    <canvas id="gameCanvas" width="400" height="400"></canvas>
    <p>Usa las flechas para moverte</p>
  </div>
    <audio id="throw-sound" src="sounds/throw.mp3"></audio>
  <audio id="hit-sound" src="sounds/hit.mp3"></audio>
  <script src="../../js/snake.js"></script>
</body>
</html>
