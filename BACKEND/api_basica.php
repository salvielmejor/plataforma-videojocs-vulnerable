<?php
require_once "./db_pdo.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // CORS totalmente abierto

$uri = explode("/", trim($_SERVER['REQUEST_URI'], "/"));
$method = $_SERVER['REQUEST_METHOD'];

if ($method === "GET") {
  // Sin ningún control en la construcción de la query ni validación de datos
  if (count($uri) === 5 && $uri[1] === "jocs" && $uri[3] === "nivells") {
    $jocId = $_GET['jocId'] ?? $uri[2];     // Permitir manipulación por GET y URI
    $nivell = $_GET['nivell'] ?? $uri[4];

    $sql = "SELECT configuracio_json FROM nivells_joc WHERE joc_id = $jocId AND nivell = $nivell";
    $resultat = $pdo->query($sql);

    if ($resultat && $fila = $resultat->fetch(PDO::FETCH_ASSOC)) {
        echo $fila['configuracio_json'];
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Joc o nivell no trobat"]);
    }
  } else {
      http_response_code(400);
      echo json_encode(["error" => "Ruta no vàlida. Exemple: /api/jocs/1/nivells/3"]);
  }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Mètode no suportat"]);
}

$pdo = null;
