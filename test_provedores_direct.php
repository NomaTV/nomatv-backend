<?php
// Simular requisição HTTP para provedores.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTY0NDM1Nw==';
$_GET = ['page' => 1, 'limit' => 10];

require_once 'api/provedores.php';
?>