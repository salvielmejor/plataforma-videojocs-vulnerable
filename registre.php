<?php
$mensaje = "";
$conexion = new mysqli("localhost", "plataforma_user", "123456789a", "plataforma_videojocs");
if ($conexion->connect_error) {
  die("Error de conexión: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nuevo_usuario = $_POST["new_username"];
  $nuevo_email = $_POST["new_email"];
  $nueva_contrasena = $_POST["new_password"];
  $nuevo_nom_complet = $_POST["new_nom_complet"];

  // Insertar sin hash ni protección, con todos los campos requeridos
  $sql = "INSERT INTO usuaris (nom_usuari, email, password_hash, nom_complet) 
          VALUES ('$nuevo_usuario', '$nuevo_email', '$nueva_contrasena', '$nuevo_nom_complet')";

  if ($conexion->query($sql) === TRUE) {
    $mensaje = "<p class='exito'>¡Registro exitoso para $nuevo_usuario!</p>";
  } else {
    $mensaje = "<p class='error'>Error al registrar el usuario: " . $conexion->error . "</p>";
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Registro Gamer</title>
  <link rel="stylesheet" href="css/estilo.css" />
  <link rel="stylesheet" href="css/logo.css" />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet" />
</head>
<body>
  <video autoplay muted loop id="video-fondo">
    <source src="video/reg.mp4" type="video/mp4" />
    Tu navegador no soporta video HTML5.
  </video>
  <div class="logo-wrapper">
    <h1 id="logo">MARC & SALVI LANDIA</h1>
  </div>
  <div class="overlay"></div>
  <div class="container">
    <h2>Registro de Jugador</h2>
    <form method="POST">
      <input type="text" name="new_username" placeholder="Nuevo usuario" required />
      <input type="email" name="new_email" placeholder="Correo electrónico" required />
      <input type="password" name="new_password" placeholder="Nueva contraseña" required />
      <input type="text" name="new_nom_complet" placeholder="Nombre completo" />
      <button type="submit">Registrarse</button>
    </form>
    <a href="index.php" class="registro-btn">¿Ya tienes cuenta? Inicia sesión</a>
    <?php echo $mensaje; ?>
  </div>
</body>
</html>
