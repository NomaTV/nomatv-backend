<?php
// Teste de autenticação

$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer MTIzNDU2Nzg6YWRtaW46MTc1OTcxMjI3OA==';

require_once 'api/config/database_sqlite.php';
require_once 'api/config/session.php';

$user = verificarAutenticacao();

if ($user) {
    echo "✅ Autenticação OK\n";
    echo "Usuário: {$user['usuario']}\n";
    echo "ID: {$user['id']}\n";
    echo "Tipo: {$user['master']}\n";
} else {
    echo "❌ Autenticação FALHOU\n";
}
?>