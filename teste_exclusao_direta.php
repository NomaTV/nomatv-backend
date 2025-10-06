<?php
// Teste direto da exclusão via API simulando o servidor Node.js

// Simular variáveis de ambiente que o servidor Node.js define
$_SERVER['REQUEST_METHOD'] = 'DELETE';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==';
$_GET['id'] = '1'; // ID do provedor a excluir

// Incluir o arquivo da API (sem output antes)
require_once __DIR__ . '/provedores.php';
?>