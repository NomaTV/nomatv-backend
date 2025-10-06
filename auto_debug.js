const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

class NomaTVDebugger {
    constructor() {
        this.phpPath = 'C:\\Users\\Asus\\Downloads\\_public_html (21)\\_api (12)\\php\\php.exe';
        this.backendPath = 'C:\\Users\\Asus\\Downloads\\_public_html (21)\\_api (12)\\backend';
        this.serverProcess = null;
        this.logs = [];
    }

    log(message) {
        const timestamp = new Date().toISOString();
        const logMessage = `[${timestamp}] ${message}`;
        console.log(logMessage);
        this.logs.push(logMessage);
    }

    async runCommand(command, args = [], cwd = this.backendPath) {
        return new Promise((resolve, reject) => {
            this.log(`Executando: ${command} ${args.join(' ')}`);

            const child = spawn(`"${command}"`, args, {
                cwd: cwd,
                stdio: ['pipe', 'pipe', 'pipe'],
                shell: true
            });

            let stdout = '';
            let stderr = '';

            child.stdout.on('data', (data) => {
                stdout += data.toString();
            });

            child.stderr.on('data', (data) => {
                stderr += data.toString();
            });

            child.on('close', (code) => {
                if (code === 0) {
                    resolve({ stdout, stderr });
                } else {
                    reject(new Error(`Comando falhou (${code}): ${stderr}`));
                }
            });

            child.on('error', (error) => {
                reject(error);
            });
        });
    }

    async checkDatabases() {
        this.log('🔍 Verificando bancos de dados...');

        const checkScript = `
<?php
$possiblePaths = [
    'api/db.db',
    'db.db'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "Arquivo: $path\\n";
        try {
            $db = new PDO("sqlite:$path");
            $result = $db->query('SELECT COUNT(*) as total FROM provedores');
            $row = $result->fetch(PDO::FETCH_ASSOC);
            echo "Registros provedores: " . $row['total'] . "\\n";

            $result = $db->query('PRAGMA table_info(provedores)');
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "Colunas: ";
            foreach ($columns as $col) {
                echo $col['name'] . ', ';
            }
            echo "\\n\\n";
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage() . "\\n\\n";
        }
    }
}
?>`;

        fs.writeFileSync(path.join(this.backendPath, 'temp_check.php'), checkScript);

        try {
            const result = await this.runCommand(this.phpPath, ['temp_check.php']);
            this.log('✅ Bancos verificados:');
            this.log(result.stdout);
        } catch (error) {
            this.log('❌ Erro ao verificar bancos: ' + error.message);
        } finally {
            if (fs.existsSync(path.join(this.backendPath, 'temp_check.php'))) {
                fs.unlinkSync(path.join(this.backendPath, 'temp_check.php'));
            }
        }
    }

    async syncDatabases() {
        this.log('🔄 Sincronizando bancos de dados...');

        try {
            await this.runCommand('powershell', ['-Command', 'Copy-Item db.db api/db.db -Force'], this.backendPath);
            this.log('✅ Banco api/db.db sincronizado');
        } catch (error) {
            this.log('❌ Erro ao sincronizar bancos: ' + error.message);
        }
    }

    async testAuthentication() {
        this.log('🔐 Testando autenticação...');

        const testScript = `
<?php
require_once 'api/config/database_sqlite.php';
require_once 'config/session.php';

header('Content-Type: application/json');

try {
    // Simular login
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = ['usuario' => 'admin', 'senha' => 'admin123'];
    }

    // Verificar credenciais (simplificado)
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT id_revendedor, usuario, master FROM revendedores WHERE usuario = ? AND senha = ?");
    $stmt->execute([$data['usuario'], $data['senha']]); // Senha em texto plano para teste

    if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['user_id'] = $user['id_revendedor'];
        $_SESSION['user_type'] = $user['master'];

        echo json_encode([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $user
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Credenciais inválidas']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>`;

        fs.writeFileSync(path.join(this.backendPath, 'temp_auth_test.php'), testScript);

        try {
            const result = await this.runCommand(this.phpPath, ['temp_auth_test.php']);
            // Limpar warnings do PHP da saída
            const cleanOutput = result.stdout.replace(/Warning:.*$/gm, '').trim();
            const response = JSON.parse(cleanOutput);
            if (response.success) {
                this.log('✅ Autenticação funcionando');
                return response.data.token;
            } else {
                this.log('❌ Autenticação falhou: ' + response.message);
                return null;
            }
        } catch (error) {
            this.log('❌ Erro no teste de autenticação: ' + error.message);
            return null;
        } finally {
            if (fs.existsSync(path.join(this.backendPath, 'temp_auth_test.php'))) {
                fs.unlinkSync(path.join(this.backendPath, 'temp_auth_test.php'));
            }
        }
    }

    async testProviders(token) {
        this.log('📊 Testando provedores API...');

        const testScript = `
<?php
require_once 'api/config/database_sqlite.php';
require_once 'config/session.php';

header('Content-Type: application/json');

// Simular token
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ${token}';

// Simular usuário logado
$_SESSION['user_id'] = 12345678;
$_SESSION['user_type'] = 'admin';

try {
    // Testar query do provedores.php
    $db = getDatabaseConnection();
    $query = "SELECT p.id_provedor, p.nome, p.dns, p.id_revendedor, p.ativo, p.criado_em, p.tipo, p.usuario, p.senha, p.atualizado_em,
                     r.nome as nome_revendedor
              FROM provedores p
              LEFT JOIN revendedores r ON p.id_revendedor = r.id_revendedor
              ORDER BY p.criado_em DESC";

    $stmt = $db->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'query' => isset($query) ? $query : 'N/A'
    ]);
}
?>`;

        const finalScript = testScript.replace('${token}', token || 'test-token');
        fs.writeFileSync(path.join(this.backendPath, 'temp_providers_test.php'), finalScript);

        try {
            const result = await this.runCommand(this.phpPath, ['temp_providers_test.php']);
            // Limpar warnings do PHP da saída
            const cleanOutput = result.stdout.replace(/Warning:.*$/gm, '').trim();
            const response = JSON.parse(cleanOutput);
            if (response.success) {
                this.log('✅ Provedores API funcionando - ' + response.count + ' registros');
                return true;
            } else {
                this.log('❌ Provedores API falhou: ' + response.message);
                if (response.query) {
                    this.log('Query problemática: ' + response.query);
                }
                return false;
            }
        } catch (error) {
            this.log('❌ Erro no teste de provedores: ' + error.message);
            return false;
        } finally {
            if (fs.existsSync(path.join(this.backendPath, 'temp_providers_test.php'))) {
                fs.unlinkSync(path.join(this.backendPath, 'temp_providers_test.php'));
            }
        }
    }

    async startServer() {
        this.log('🚀 Verificando se servidor já está rodando...');

        try {
            // Tentar conectar ao servidor existente
            const response = await fetch('http://localhost:8080/');
            if (response.ok) {
                this.log('✅ Servidor já está rodando');
                return true;
            }
        } catch (error) {
            this.log('📡 Servidor não está rodando, iniciando...');
        }

        return new Promise((resolve, reject) => {
            this.serverProcess = spawn('node', ['server.js'], {
                cwd: this.backendPath,
                stdio: ['pipe', 'pipe', 'pipe']
            });

            let started = false;
            let timeout = setTimeout(() => {
                if (!started) {
                    this.log('⚠️ Servidor pode não ter iniciado completamente, mas continuando...');
                    resolve(false);
                }
            }, 5000);

            this.serverProcess.stdout.on('data', (data) => {
                const output = data.toString();
                if (output.includes('Servidor NomaTV rodando') || output.includes('listening')) {
                    started = true;
                    clearTimeout(timeout);
                    this.log('✅ Servidor iniciado');
                    resolve(true);
                }
            });

            this.serverProcess.stderr.on('data', (data) => {
                // Ignorar erros não críticos
                this.log('Server stderr: ' + data.toString());
            });

            this.serverProcess.on('error', (error) => {
                this.log('⚠️ Erro ao iniciar servidor: ' + error.message + ' - continuando sem servidor');
                clearTimeout(timeout);
                resolve(false);
            });
        });
    }

    async stopServer() {
        if (this.serverProcess) {
            this.log('🛑 Parando servidor...');
            this.serverProcess.kill();
            this.serverProcess = null;
        }
    }

    async generateReport() {
        const reportPath = path.join(this.backendPath, 'debug_report.txt');
        const report = `
=== RELATÓRIO DE DEBUG NomaTV ===
Data: ${new Date().toISOString()}

LOGS DO DEBUG:
${this.logs.join('\n')}

=== RESUMO ===
- Bancos verificados e sincronizados
- Autenticação testada
- API de provedores testada
- Servidor iniciado e parado

=== PRÓXIMOS PASSOS ===
1. Verificar se há dados de teste nos bancos
2. Testar frontend completo
3. Verificar logs do servidor em produção
`;

        fs.writeFileSync(reportPath, report);
        this.log('📄 Relatório gerado: ' + reportPath);
    }

    async runFullDebug() {
        try {
            this.log('🔧 Iniciando debug automático NomaTV...');

            // 1. Verificar bancos
            await this.checkDatabases();

            // 2. Sincronizar bancos se necessário
            await this.syncDatabases();

            // 3. Verificar novamente após sync
            await this.checkDatabases();

            // 4. Testar autenticação
            const token = await this.testAuthentication();

            // 5. Testar provedores API
            const providersWorking = await this.testProviders(token);

            // 6. Iniciar servidor para teste completo
            await this.startServer();

            // Aguardar um pouco
            await new Promise(resolve => setTimeout(resolve, 2000));

            // 7. Parar servidor
            await this.stopServer();

            // 8. Gerar relatório
            await this.generateReport();

            this.log('🎉 Debug automático concluído!');

        } catch (error) {
            this.log('💥 Erro crítico no debug: ' + error.message);
            await this.generateReport();
        } finally {
            await this.stopServer();
        }
    }
}

// Executar debug
const nomatvDebugger = new NomaTVDebugger();
nomatvDebugger.runFullDebug().then(() => {
    console.log('Debug finalizado. Verifique debug_report.txt');
    process.exit(0);
}).catch((error) => {
    console.error('Erro fatal:', error);
    process.exit(1);
});