<?php
session_start();

$host = 'localhost';
$user = 'plataforma_user';
$pass = '123456789a';
$dbname = 'plataforma_videojocs';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de connexió']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['usuari_id'], $data['puntuacio'], $data['durada'])) {
    echo json_encode(['status' => 'error', 'message' => 'Dades incompletes']);
    exit;
}

// Extraer nivel máximo alcanzado si se proporciona
$nivell_maximo_alcanzado = isset($data['nivell_maximo_alcanzado']) ? intval($data['nivell_maximo_alcanzado']) : 1;
$actualizar_nivel = isset($data['actualizar_nivel']) ? (bool)$data['actualizar_nivel'] : false;

if (!isset($_SESSION['usuari_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sessió no iniciada']);
    exit;
}

if ($data['usuari_id'] != $_SESSION['usuari_id']) {
    echo json_encode(['status' => 'error', 'message' => 'Usuari no autoritzat']);
    exit;
}

$usuari_id = intval($data['usuari_id']);
$puntuacio = intval($data['puntuacio']);
$durada = intval($data['durada']);

// --- 0. Resoldre joc_id pel nom (creant si no existeix) ---
// Evita violacions de clau forana si el client envia un joc_id que no existeix
$joc_nom = 'Atrapa les Estrelles';
$stmt = $conn->prepare("SELECT id FROM jocs WHERE nom_joc=?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error prepare select joc: ' . $conn->error]);
    exit;
}
$stmt->bind_param("s", $joc_nom);
$stmt->execute();
$resJoc = $stmt->get_result();
$joc_id = null;
if ($resJoc && $resJoc->num_rows > 0) {
    $rowJ = $resJoc->fetch_assoc();
    $joc_id = intval($rowJ['id']);
} else {
    $insJ = $conn->prepare("INSERT INTO jocs (nom_joc, descripcio, puntuacio_maxima, nivells_totals, actiu) VALUES (?, '', 0, 3, 1)");
    if (!$insJ) {
        echo json_encode(['status' => 'error', 'message' => 'Error prepare insert joc: ' . $conn->error]);
        exit;
    }
    $insJ->bind_param("s", $joc_nom);
    if (!$insJ->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Error execució insert joc: ' . $insJ->error]);
        exit;
    }
    $joc_id = $conn->insert_id;
    $insJ->close();
}
$stmt->close();

// --- 1. Inserir partida ---
$stmt = $conn->prepare("INSERT INTO partides (usuari_id, joc_id, nivell_jugat, puntuacio_obtinguda, durada_segons) VALUES (?, ?, 1, ?, ?)");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error prepare partides: ' . $conn->error]);
    exit;
}
$stmt->bind_param("iiii", $usuari_id, $joc_id, $puntuacio, $durada);
$stmt->execute();
$stmt->close();

// --- 2. Actualitzar o inserir al progrés ---
$sql_check = "SELECT id, nivell_actual, puntuacio_maxima, partides_jugades FROM progres_usuari WHERE usuari_id=? AND joc_id=?";
$stmt = $conn->prepare($sql_check);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Error prepare check: ' . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $usuari_id, $joc_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res === false) {
    echo json_encode(['status' => 'error', 'message' => 'Error execució select: ' . $conn->error]);
    exit;
}

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $max = max($row['puntuacio_maxima'], $puntuacio);
    $total = $row['partides_jugades'] + 1;
    $nuevo_nivel = $row['nivell_actual'];
    
    // Si se debe actualizar el nivel y el nivel máximo alcanzado es mayor al actual
    if ($actualizar_nivel && $nivell_maximo_alcanzado > $row['nivell_actual']) {
        $nuevo_nivel = $nivell_maximo_alcanzado;
    }

    $upd = $conn->prepare("UPDATE progres_usuari SET nivell_actual=?, puntuacio_maxima=?, partides_jugades=?, ultima_partida=NOW() WHERE usuari_id=? AND joc_id=?");
    if (!$upd) {
        echo json_encode(['status' => 'error', 'message' => 'Error prepare update: ' . $conn->error]);
        exit;
    }
    $upd->bind_param("iiiii", $nuevo_nivel, $max, $total, $usuari_id, $joc_id);
    $upd->execute();
    if ($upd->error) {
        echo json_encode(['status' => 'error', 'message' => 'Error execució update: ' . $upd->error]);
        exit;
    }
    $upd->close();

} else {
    $nivel_inicial = $nivell_maximo_alcanzado > 0 ? $nivell_maximo_alcanzado : 1;
    $ins = $conn->prepare("INSERT INTO progres_usuari (usuari_id, joc_id, nivell_actual, puntuacio_maxima, partides_jugades, ultima_partida) VALUES (?, ?, ?, ?, 1, NOW())");
    if (!$ins) {
        echo json_encode(['status' => 'error', 'message' => 'Error prepare insert progres: ' . $conn->error]);
        exit;
    }
    $ins->bind_param("iiii", $usuari_id, $joc_id, $nivel_inicial, $puntuacio);
    $ins->execute();
    if ($ins->error) {
        echo json_encode(['status' => 'error', 'message' => 'Error execució insert: ' . $ins->error]);
        exit;
    }
    $ins->close();
}

echo json_encode(['status' => 'ok', 'message' => 'Partida i progrés guardats correctament']);
$conn->close();
?>