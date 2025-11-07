<?php
session_start();
header('Content-Type: application/json');

// Requiere usuario logueado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No hay sesión iniciada"]);
    exit;
}

// Configuración de base de datos
$host = "localhost";
$dbname = "plataforma_videojocs";
$user = "plataforma_user";
$pass = "123456789a";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de connexió: " . $e->getMessage()]);
    exit;
}

// Comprobar si se ha recibido JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["success" => false, "message" => "No s'han rebut dades"]);
    exit;
}

// Extraer datos enviados
$puntuacio = intval($data["puntuacio"] ?? 0);
$nivell_jugat = intval($data["nivell_id"] ?? 1);
$nivell_maximo_alcanzado = intval($data["nivell_maximo_alcanzado"] ?? $nivell_jugat);
$actualizar_nivel = isset($data["actualizar_nivel"]) ? (bool)$data["actualizar_nivel"] : false;

// Obtener el ID del usuario desde la sesión (nombre de usuario)
$nomUsuari = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT id FROM usuaris WHERE nom_usuari = ?");
$stmt->execute([$nomUsuari]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Usuario de sesión no válido"]);
    exit;
}
$usuari_id = (int)$row['id'];

// Obtener el ID del juego (Fruit Ninja)
$stmt = $pdo->prepare("SELECT id FROM jocs WHERE nom_joc = 'Fruit Ninja'");
$stmt->execute();
$joc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$joc) {
    // Crear el juego si no existe
    $stmt = $pdo->prepare("INSERT INTO jocs (nom_joc, descripcio, puntuacio_maxima, nivells_totals) VALUES ('Fruit Ninja', 'Juego de cortar frutas', 0, 3)");
    $stmt->execute();
    $joc_id = $pdo->lastInsertId();
} else {
    $joc_id = $joc["id"];
}

// Insertar partida en la tabla partides
$stmt = $pdo->prepare("INSERT INTO partides (usuari_id, joc_id, nivell_jugat, puntuacio_obtinguda, data_partida) VALUES (?, ?, ?, ?, NOW())");

try {
    $stmt->execute([$usuari_id, $joc_id, $nivell_jugat, $puntuacio]);
    
    // Actualizar progreso del usuario
    $stmt = $pdo->prepare("SELECT * FROM progres_usuari WHERE usuari_id = ? AND joc_id = ?");
    $stmt->execute([$usuari_id, $joc_id]);
    $progres = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($progres) {
        // Actualizar progreso existente
        $nuevo_nivel = $progres['nivell_actual'];
        
        // Si se debe actualizar el nivel y el nivel máximo alcanzado es mayor al actual
        if ($actualizar_nivel && $nivell_maximo_alcanzado > $progres['nivell_actual']) {
            $nuevo_nivel = $nivell_maximo_alcanzado;
        }
        
        $stmt = $pdo->prepare("UPDATE progres_usuari SET 
            nivell_actual = ?,
            puntuacio_maxima = GREATEST(puntuacio_maxima, ?), 
            partides_jugades = partides_jugades + 1,
            ultima_partida = NOW()
            WHERE usuari_id = ? AND joc_id = ?");
        $stmt->execute([$nuevo_nivel, $puntuacio, $usuari_id, $joc_id]);
    } else {
        // Crear nuevo progreso
        $nivel_inicial = $nivell_maximo_alcanzado > 0 ? $nivell_maximo_alcanzado : 1;
        $stmt = $pdo->prepare("INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, ultima_partida) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$usuari_id, $joc_id, $nivel_inicial, $puntuacio]);
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Partida guardada correctament",
        "usuari_id" => $usuari_id,
        "joc_id" => $joc_id,
        "nivell_jugat" => $nivell_jugat,
        "puntuacio" => $puntuacio
    ]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error al guardar: " . $e->getMessage()]);
}
?>
