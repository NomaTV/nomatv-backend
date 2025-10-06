<?php
require_once 'config/session.php';

$token = 'MTIzNDU2Nzg6YWRtaW46MTc1OTY0NDM1Nw==';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

$result = verificarAutenticacao();
echo "Resultado da verificação: ";
var_dump($result);
?>