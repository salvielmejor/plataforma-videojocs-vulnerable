<?php
require_once __DIR__ . '/session.php';
if (!isset($_SESSION["usuario"])) {
    header("Location: ../index.php");
    exit();
}

$usuario = $_SESSION["usuario"];
$conexion = new mysqli("localhost", "plataforma_user", "123456789a", "plataforma_videojocs");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$mensaje_subida = "";
$mensaje_actualizo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ACTUALITZAR DADES
    if (isset($_POST["actualizar_datos"])) {
        $nuevo_nom = $_POST["nom_complet"] ?? '';
        $nuevo_email = $_POST["email"] ?? '';
        $nueva_password = $_POST["password"] ?? '';

        $sql_update = "UPDATE usuaris SET 
            nom_complet='$nuevo_nom',
            email='$nuevo_email',
            password_hash='$nueva_password'
            WHERE nom_usuari='$usuario'";

        if ($conexion->query($sql_update)) {
            $mensaje_actualizo = "Dades actualitzades correctament.";
        } else {
            $mensaje_actualizo = "Error en actualitzar les dades: " . $conexion->error;
        }
    }

    // PUJAR FOTO
    if (isset($_POST["subir_foto"]) && isset($_FILES["foto"]) && $_FILES["foto"]["error"] == UPLOAD_ERR_OK) {
        $uploadsDir = "../uploads/";
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
    
        $filename = basename($_FILES["foto"]["name"]);
        $targetFile = $uploadsDir . $filename;
    
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $targetFile)) {
            $ruta_relativa = "../uploads/" . $filename;
            $sql_img = "UPDATE usuaris SET foto_perfil='$ruta_relativa' WHERE nom_usuari='$usuario'";
            $conexion->query($sql_img);
            $mensaje_subida = "Foto pujada correctament.";
        } else {
            $mensaje_subida = "Error en pujar la foto.";
        }
    }
}

// CONSULTAR DADES ACTUALS DE L'USUARI
$sql = "SELECT nom_usuari, email, nom_complet, foto_perfil, password_hash, data_registre 
        FROM usuaris WHERE nom_usuari='$usuario' LIMIT 1";

$resultado = $conexion->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
} else {
    session_destroy();
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Perfil d'Usuari</title>
    <link rel="stylesheet" href="../css/header.css" />
    <link rel="stylesheet" href="../css/sanitize.css" />
    <link rel="stylesheet" href="../css/perfil.css" />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet" />
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="panel-container perfil-container">

        <div class="perfil-content">
            <div class="perfil-foto">
                <img src="<?php echo htmlspecialchars($fila['foto_perfil'] ?? '../uploads/default.png'); ?>" 
                     alt="Foto de perfil" 
                     style="max-width:200px; max-height:200px;" />
            </div>

            <div class="perfil-dades">
                <form method="POST" enctype="multipart/form-data">
                    <p><strong>Nom d'usuari:</strong> 
                        <span><?php echo htmlspecialchars($fila['nom_usuari']); ?></span>
                    </p>

                    <label for="nom_complet">Nom complet:</label><br />
                    <input type="text" id="nom_complet" name="nom_complet" 
                        value="<?php echo htmlspecialchars($fila['nom_complet']); ?>" /><br/><br/>

                    <label for="email">Correu:</label><br />
                    <input type="email" id="email" name="email" 
                        value="<?php echo htmlspecialchars($fila['email']); ?>" /><br/><br/>

                    <label for="password">Contrasenya:</label><br />
                    <input type="text" id="password" name="password" 
                        value="<?php echo htmlspecialchars($fila['password_hash']); ?>" /><br/><br/>

                    <label for="foto">Canvia la teva foto de perfil:</label><br/>
                    <input type="file" name="foto" id="foto" accept="image/*" /><br/><br/>

                    <button type="submit" name="actualizar_datos">Actualitzar dades</button>
                    <button type="submit" name="subir_foto">Pujar foto</button>
                </form>

                <?php 
                if($mensaje_actualizo) echo "<p>$mensaje_actualizo</p>";
                if($mensaje_subida) echo "<p>$mensaje_subida</p>";
                ?>
            </div>
        </div>
    </main>

    <footer>
        <p>© 2025 POMASA MI MASSA - Tots els drets reservats</p>
    </footer>
</body>
</html>
