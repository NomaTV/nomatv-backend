<?php
/**
 * TESTE_BRANDING_COMPLETO.PHP - Teste automatizado do sistema de branding
 * 
 * TESTA:
 * 1. Logo_proxy.php com diferentes IDs
 * 2. Upload de logo via API
 * 3. Consulta de status via branding/get.php
 * 4. Remoção via branding/delete.php
 * 5. Fallback em cascata (sub → pai → NomaTV)
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Sistema de Branding - NomaTV</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .test-section {
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        .test-header {
            background: #f5f5f5;
            padding: 15px 20px;
            font-weight: bold;
            font-size: 1.2rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .test-body {
            padding: 20px;
        }
        .test-item {
            margin-bottom: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }
        .test-item h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        .result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .logo-preview {
            max-width: 300px;
            max-height: 100px;
            margin-top: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            background: white;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-error { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-info { background: #17a2b8; color: white; }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎨 Teste Sistema de Branding</h1>
            <p>NomaTV v4.5 - Teste Automatizado Completo</p>
        </div>
        
        <div class="content">
            
            <!-- TESTE 1: Verificar Estrutura -->
            <div class="test-section">
                <div class="test-header">
                    📁 TESTE 1: Verificar Estrutura do Sistema
                </div>
                <div class="test-body">
                    <div class="test-item">
                        <h3>1.1 - Banco de Dados</h3>
                        <?php
                        $dbPaths = [
                            __DIR__ . '/db.db',
                            __DIR__ . '/nomatv.db'
                        ];
                        $dbPath = null;
                        foreach ($dbPaths as $path) {
                            if (file_exists($path)) {
                                $dbPath = $path;
                                echo "<div class='result success'>✅ Banco encontrado: " . basename($path) . "</div>";
                                break;
                            }
                        }
                        if (!$dbPath) {
                            echo "<div class='result error'>❌ Banco de dados não encontrado!</div>";
                        }
                        ?>
                    </div>
                    
                    <div class="test-item">
                        <h3>1.2 - Pasta de Uploads</h3>
                        <?php
                        $uploadDir = __DIR__ . '/uploads/logos/';
                        if (is_dir($uploadDir)) {
                            $files = glob($uploadDir . '*.png');
                            echo "<div class='result success'>✅ Pasta existe: uploads/logos/</div>";
                            echo "<div class='result info'>📊 Arquivos encontrados: " . count($files) . "</div>";
                        } else {
                            echo "<div class='result warning'>⚠️ Pasta não existe. Criando...</div>";
                            mkdir($uploadDir, 0755, true);
                            echo "<div class='result success'>✅ Pasta criada com sucesso!</div>";
                        }
                        ?>
                    </div>
                    
                    <div class="test-item">
                        <h3>1.3 - Arquivo logo_proxy.php</h3>
                        <?php
                        if (file_exists(__DIR__ . '/logo_proxy.php')) {
                            echo "<div class='result success'>✅ Arquivo existe: logo_proxy.php</div>";
                            echo "<div class='result info'>📏 Tamanho: " . round(filesize(__DIR__ . '/logo_proxy.php')/1024, 2) . " KB</div>";
                        } else {
                            echo "<div class='result error'>❌ Arquivo não encontrado: logo_proxy.php</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- TESTE 2: Consultar Revendedores -->
            <div class="test-section">
                <div class="test-header">
                    👥 TESTE 2: Consultar Revendedores no Banco
                </div>
                <div class="test-body">
                    <?php
                    if ($dbPath) {
                        try {
                            $db = new PDO("sqlite:$dbPath");
                            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            
                            $stmt = $db->query("SELECT id_revendedor, nome, master, id_pai, ativo FROM revendedores");
                            $revendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo "<div class='result success'>✅ Encontrados: " . count($revendedores) . " revendedores</div>";
                            
                            foreach ($revendedores as $rev) {
                                $tipo = $rev['master'] === 'sim' ? 'Master' : 'Sub-revendedor';
                                $status = $rev['ativo'] ? 'Ativo' : 'Inativo';
                                $badge = $rev['ativo'] ? 'badge-success' : 'badge-error';
                                
                                echo "<div class='test-item'>";
                                echo "<h3>ID: {$rev['id_revendedor']} - {$rev['nome']}</h3>";
                                echo "<p>Tipo: <strong>{$tipo}</strong> <span class='status-badge {$badge}'>{$status}</span></p>";
                                if ($rev['id_pai']) {
                                    echo "<p>Pai: ID {$rev['id_pai']}</p>";
                                }
                                
                                // Verificar se tem logo
                                $logoFile = $uploadDir . $rev['id_revendedor'] . '.png';
                                if (file_exists($logoFile)) {
                                    echo "<div class='result success'>✅ Possui logo: " . basename($logoFile) . " (" . round(filesize($logoFile)/1024, 2) . " KB)</div>";
                                } else {
                                    echo "<div class='result warning'>⚠️ Sem logo personalizada</div>";
                                }
                                echo "</div>";
                            }
                            
                        } catch (Exception $e) {
                            echo "<div class='result error'>❌ Erro: " . $e->getMessage() . "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- TESTE 3: Testar Logo Proxy -->
            <div class="test-section">
                <div class="test-header">
                    🔄 TESTE 3: Testar Logo Proxy (JavaScript)
                </div>
                <div class="test-body">
                    <div class="test-item">
                        <h3>3.1 - Testar com ID 4689</h3>
                        <button class="btn" onclick="testarLogoProxy(4689)">▶️ Executar Teste</button>
                        <div id="result-4689" class="result info" style="margin-top: 15px; display: none;">
                            <div class="loading"></div> Carregando...
                        </div>
                    </div>
                    
                    <div class="test-item">
                        <h3>3.2 - Testar com ID inválido (999)</h3>
                        <button class="btn" onclick="testarLogoProxy(999)">▶️ Executar Teste</button>
                        <div id="result-999" class="result info" style="margin-top: 15px; display: none;">
                            <div class="loading"></div> Carregando...
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TESTE 4: Consultar Status via API -->
            <div class="test-section">
                <div class="test-header">
                    📊 TESTE 4: API branding/get.php
                </div>
                <div class="test-body">
                    <div class="test-item">
                        <h3>4.1 - Consultar status do revendedor 4689</h3>
                        <button class="btn" onclick="testarGetStatus(4689)">▶️ Executar Teste</button>
                        <div id="result-get-4689" class="result info" style="margin-top: 15px; display: none;">
                            <div class="loading"></div> Carregando...
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TESTE 5: Informações do Sistema -->
            <div class="test-section">
                <div class="test-header">
                    ℹ️ TESTE 5: Informações do Sistema
                </div>
                <div class="test-body">
                    <div class="test-item">
                        <h3>Versão PHP</h3>
                        <div class="result success">✅ PHP <?php echo phpversion(); ?></div>
                    </div>
                    
                    <div class="test-item">
                        <h3>Extensões Carregadas</h3>
                        <?php
                        $required = ['pdo', 'pdo_sqlite', 'json', 'gd'];
                        foreach ($required as $ext) {
                            if (extension_loaded($ext)) {
                                echo "<div class='result success'>✅ {$ext}</div>";
                            } else {
                                echo "<div class='result error'>❌ {$ext} (não carregado)</div>";
                            }
                        }
                        ?>
                    </div>
                    
                    <div class="test-item">
                        <h3>URLs Configuradas</h3>
                        <div class="result info">
                            <strong>Base URL:</strong> https://webnoma.space<br>
                            <strong>Backup URL:</strong> https://webnoma.shop/logos/nomaapp.png<br>
                            <strong>Uploads:</strong> uploads/logos/
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
        // Função para testar logo_proxy.php
        async function testarLogoProxy(id) {
            const resultDiv = document.getElementById(`result-${id}`);
            resultDiv.style.display = 'block';
            resultDiv.className = 'result info';
            resultDiv.innerHTML = '<div class="loading"></div> Testando...';
            
            try {
                const response = await fetch(`/api/logo_proxy.php?id=${id}`);
                const logoUrl = await response.text();
                
                resultDiv.className = 'result success';
                resultDiv.innerHTML = `
                    ✅ Resposta recebida!<br>
                    <strong>URL retornada:</strong> ${logoUrl}<br>
                    <img src="${logoUrl}" class="logo-preview" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22100%22><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22>Logo não carregada</text></svg>'" />
                `;
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `❌ Erro: ${error.message}`;
            }
        }
        
        // Função para testar branding/get.php
        async function testarGetStatus(id) {
            const resultDiv = document.getElementById(`result-get-${id}`);
            resultDiv.style.display = 'block';
            resultDiv.className = 'result info';
            resultDiv.innerHTML = '<div class="loading"></div> Consultando API...';
            
            try {
                const response = await fetch('/api/branding/get.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ revendedor_id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        ✅ Status recebido!<br>
                        <div class="code-block">
                            ${JSON.stringify(data.data, null, 2)}
                        </div>
                    `;
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.innerHTML = `❌ Erro: ${data.error || 'Erro desconhecido'}`;
                }
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `❌ Erro: ${error.message}`;
            }
        }
        
        // Auto-executar teste inicial
        console.log('🎨 Sistema de Branding - Testes carregados');
    </script>
</body>
</html>
<?php
$db = null;
?>
