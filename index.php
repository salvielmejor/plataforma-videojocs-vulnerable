<?php
session_start();
$mensaje = "";

$conexion = new mysqli("localhost", "plataforma_user", "123456789a", "plataforma_videojocs");
if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $usuario = $_POST["username"];
  $contrasena = $_POST["password"];

  // Consulta usando prepared statement para evitar inyección SQL
  $sql = "SELECT * FROM usuaris WHERE nom_usuari=? AND password_hash=?";
  $stmt = $conexion->prepare($sql);
  $stmt->bind_param("ss", $usuario, $contrasena);  // Aquí comparación directa sin hash
  $stmt->execute();
  $resultado = $stmt->get_result();

  if ($resultado && $resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    // Guardar datos en sesión
    $_SESSION["usuario"] = $row["nom_usuari"];
    $_SESSION["usuari_id"] = $row["id"];

    header("Location: BACKEND/menu.php");
    exit();
  } else {
    $mensaje = "<p class='error'>Usuario o contraseña incorrectos</p>";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Login Gamer</title>
  <link rel="stylesheet" href="css/estilo.css" />
  <link rel="stylesheet" href="css/logo.css" />
</head>
<body>
  <video autoplay muted loop id="video-fondo">
    <source src="video/mine.mp4" type="video/mp4" />
    Tu navegador no soporta video HTML5.
  </video>
  <div class="overlay"></div>
  <div class="logo-wrapper">
    <h1 id="logo">POMASA LANDIA</h1>
  </div>
  <script src="js/logo.js"></script>
  <div class="container">
    <h2>Plataforma Gamer</h2>
    <form method="POST">
      <input type="text" name="username" placeholder="Usuario" required />
      <input type="password" name="password" placeholder="Contraseña" required />
      <button type="submit">Ingresar</button>
    </form>
    <a href="registre.php" class="registro-btn">¿No tienes cuenta? Regístrate</a>
    <?php echo $mensaje; ?>
  </div>
</body>
</html>
