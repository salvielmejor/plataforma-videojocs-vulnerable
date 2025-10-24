<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    // Mostrar mensaje de error y redirigir
    echo "<script>alert('⚠️ No estás logueado. Redirigiendo al menú...'); window.location.href='../menu.php';</script>";
    exit();
}

$usuario = $_SESSION["usuario"];
$conexion = new mysqli("localhost", "plataforma_user", "123456789a", "plataforma_videojocs");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener el ID del usuario
$sql_usuario = "SELECT id FROM usuaris WHERE nom_usuari='$usuario'";
$resultado_usuario = $conexion->query($sql_usuario);
if ($resultado_usuario && $resultado_usuario->num_rows > 0) {
    $fila_usuario = $resultado_usuario->fetch_assoc();
    $usuario_id = $fila_usuario['id'];
} else {
    header("Location: ../menu.php");
    exit();
}

// Obtener la puntuación máxima actual del usuario para el juego 3
$sql_puntuacion = "SELECT puntuacio_maxima FROM progres_usuari WHERE usuari_id='$usuario_id' AND joc_id=3";
$resultado_puntuacion = $conexion->query($sql_puntuacion);
$puntuacion_actual = 0;
if ($resultado_puntuacion && $resultado_puntuacion->num_rows > 0) {
    $fila_puntuacion = $resultado_puntuacion->fetch_assoc();
    $puntuacion_actual = $fila_puntuacion['puntuacio_maxima'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SNAKE PRO</title>
  <link rel="stylesheet" href="../../css/snake.css" />
</head>
<body>
  <div class="container">
    <div class="header-controls">
      <h1>SNAKE PRO</h1>
      <button class="menu-btn" onclick="window.location.href='../menu.php'">← Volver al Menú</button>
    </div>
    <div class="stats">
      <span id="score">Puntuación: 0</span>
      <span id="level">Nivel: 1</span>
      <span id="speed">Velocidad: 100ms</span>
      <span id="best">Mejor: <?php echo $puntuacion_actual; ?></span>
    </div>
    <canvas id="gameCanvas" width="400" height="400"></canvas>
    <p>Usa las flechas para moverte</p>
    <div id="gameOver" style="display: none;">
      <h2>¡Juego Terminado!</h2>
      <p id="finalScore">Puntuación Final: 0</p>
      <button onclick="restartGame()">Jugar de Nuevo</button>
      <button onclick="window.location.href='../menu.php'">Volver al Menú</button>
    </div>
  </div>
  <script>
    const usuarioId = <?php echo $usuario_id; ?>;
    const puntuacionActual = <?php echo $puntuacion_actual; ?>;
  </script>
  <script src="../../js/snake.js"></script>
</body>
</html>
