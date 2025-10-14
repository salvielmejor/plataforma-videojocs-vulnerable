<?php
$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $nuevo_usuario = $_POST["new_username"];
  $nueva_contrasena = $_POST["new_password"];

  // Aquí iría la lógica para guardar el usuario en una base de datos
  $mensaje = "<p class='exito'>¡Registro exitoso para $nuevo_usuario!</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro Gamer</title>
  <link rel="stylesheet" href="css/estilo.css">
  <link rel="stylesheet" href="css/logo.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
     <video autoplay muted loop id="video-fondo">
  <source src="video/reg.mp4" type="video/mp4">
  Tu navegador no soporta video HTML5.
</video>
 

   <div class="logo-wrapper">
  <h1 id="logo">MARC & SALVI LANDIA</h1>
</div>
<div class="overlay"></div>
  <div class="container">
    <h2>Registro de Jugador</h2>
    <form method="POST">
      <input type="text" name="new_username" placeholder="Nuevo usuario" required>
      <input type="password" name="new_password" placeholder="Nueva contraseña" required>
      <button type="submit">Registrarse</button>
    </form>
    <a href="index.php" class="registro-btn">¿Ya tienes cuenta? Inicia sesión</a>
    <?php echo $mensaje; ?>
  </div>
</body>
</html>
