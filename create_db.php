<?php
/**
 * Script de inicialização do banco de dados NomaTV
 * Cria as tabelas e insere dados iniciais
 */

$dbFile = __DIR__ . '/db.db';

// Remover banco existente se houver
if (file_exists($dbFile)) {
    unlink($dbFile);
    echo "✅ Banco de dados anterior removido.\n";
}

try {
    $db = new PDO("sqlite:$dbFile");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado ao banco de dados SQLite.\n";
    
    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/init.sql');
    
    // Executar os comandos SQL
    $db->exec($sql);
    
    echo "✅ Tabelas criadas com sucesso!\n";
    echo "✅ Usuário admin criado (usuario: admin, senha: admin123)\n";
    echo "✅ Planos padrão inseridos.\n";
    echo "\n";
    echo "=================================================\n";
    echo "🎉 Banco de dados inicializado com sucesso!\n";
    echo "=================================================\n";
    echo "Localização: " . $dbFile . "\n";
    echo "\n";
    
} catch (PDOException $e) {
    echo "❌ Erro ao criar banco de dados: " . $e->getMessage() . "\n";
    exit(1);
}
?>

cd "c:\Users\Asus\Downloads\_public_html (21)"; .\ngrok.exe http 8080 --domain=SEU_DOMINIO_AQUI.ngrok-free.app
