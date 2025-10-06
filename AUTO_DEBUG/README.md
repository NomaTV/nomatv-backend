
dados acesso pagina de login

logon admin

senha admin123




# Sistema Padronizado de Respostas - NomaTV API v4.5

## 🎯 Objetivo
Garantir que todas as APIs retornem **dados completos do revendedor** com **fallbacks automáticos**, evitando que o frontend quebre por falta de dados.

## 📋 Funcionalidades Implementadas

### 1. **Helper de Respostas Padronizadas** (`api/helpers/response_helper.php`)
- `getDefaultRevendedorData()`: Retorna dados padrão do revendedor
- `mergeRevendedorData($dadosReais)`: Mescla dados reais com fallbacks
- `respostaSucessoPadronizada($dados, $message, $extraData)`: Resposta de sucesso
- `respostaErroPadronizada($message, $code, $extraData)`: Resposta de erro
- `respostaNaoAutenticadoPadronizada($message)`: Resposta não autenticado
- `getRevendedorCompleto($db, $id)`: Busca dados completos do revendedor

### 2. **Estrutura de Dados Padronizada**
Todas as respostas seguem o formato:
```json
{
  "success": true|false,
  "message": "Mensagem da operação",
  "data": {
    "id_revendedor": 0,
    "usuario": "usuario_padrao",
    "nome": "Usuário Padrão",
    "master": "nao",
    "email": "usuario@padrao.com",
    "telefone": "(00) 00000-0000",
    "credito": 0.00,
    "limite_credito": 100.00,
    "status": "ativo",
    "ultimo_acesso": "2025-10-05 22:35:12",
    "total_clientes": 0,
    "total_vendas": 0,
    "comissao": 0.00
  },
  "extraData": null|{},
  "timestamp": "2025-10-05 22:35:12"
}
```

### 3. **APIs Atualizadas**
- ✅ `stats.php`: Estatísticas do dashboard com dados do revendedor
- ✅ `revendedores.php`: CRUD de revendedores com respostas padronizadas
- 🔄 Outras APIs podem ser atualizadas seguindo o mesmo padrão

## 🔧 Como Usar

### Para novas APIs:
```php
require_once __DIR__ . '/helpers/response_helper.php';
// Conectar banco
$db = getDatabaseConnection();
// Autenticar
$user = verificarAutenticacao();
$dadosRevendedor = getRevendedorCompleto($db, $user['id'] ?? 0);

// Sucesso
respostaSucessoPadronizada($dadosRevendedor, 'Operação OK');

// Erro
respostaErroPadronizada('Erro interno');
```

### Para APIs existentes:
Substituir `standardResponse()` por:
- `respostaSucessoPadronizada()` para sucesso
- `respostaErroPadronizada()` para erro
- `respostaNaoAutenticadoPadronizada()` para não autenticado

## 🛡️ Benefícios

1. **Frontend nunca quebra**: Sempre há dados disponíveis, mesmo em erro
2. **Desenvolvimento consistente**: Todas as APIs seguem o mesmo padrão
3. **Manutenção facilitada**: Mudanças no formato de dados são centralizadas
4. **Debugging melhorado**: Dados completos facilitam identificação de problemas
5. **Experiência do usuário**: Interface sempre mostra informações relevantes

## 🧪 Como Testar

```bash
# Testar resposta de erro (não autenticado)
curl http://localhost:8080/api/stats.php

# Testar com autenticação (se implementado)
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/stats.php
```

Todas as respostas incluirão dados completos do revendedor com fallbacks apropriados.

## 📈 Próximos Passos

- Atualizar demais APIs (`auth.php`, `provedores.php`, etc.)
- Implementar sistema de cache para dados do revendedor
- Adicionar validação de dados obrigatórios
- Criar middleware para padronização automática









# Projeto de Automação de Testes com Puppeteer

Este projeto demonstra uma automação completa de testes web usando Puppeteer, incluindo monitoramento de mudanças no DOM, screenshots, logs detalhados e correção automática de erros.

## 📋 Funcionalidades

- ✅ **Automação de Testes**: Navegação automática em páginas HTML locais
- ✅ **Monitoramento em Tempo Real**: Detecção de mudanças no DOM após ações
- ✅ **Captura de Screenshots**: Antes e depois das interações
- ✅ **Logs Detalhados**: Salvamento de logs com timestamps em arquivo
- ✅ **Correção Automática**: Tentativas múltiplas com fallback em caso de erro
- ✅ **Debug Integrado**: Configuração pronta para debugging no VS Code

## 🚀 Instalação

1. **Instalar dependências**:
   ```bash
   npm install
   ```

2. **Executar testes**:
   ```bash
   node test-automation.js
   ```

## 🛠️ Estrutura do Projeto

```
test-automation-project/
├── package.json              # Dependências e scripts
├── test-automation.js        # Script principal de automação
├── seu-arquivo.html          # Página HTML de teste com botão dinâmico
├── .vscode/
│   └── launch.json           # Configuração de debug
├── logs.txt                  # Logs gerados durante execução
├── antes.png                 # Screenshot antes do clique
├── depois.png                # Screenshot depois do clique
├── html_antes.html           # HTML capturado antes
└── html_depois.html          # HTML capturado depois
```

## 🎯 Como Funciona

### Fluxo de Execução
1. **Inicialização**: Lança browser headless e navega para `seu-arquivo.html`
2. **Monitoramento Inicial**: Captura screenshot e HTML da página
3. **Ação**: Clica no botão `#seuBotao`
4. **Espera Mudanças**: Aguarda detecção de novo elemento `<p>` no DOM
5. **Monitoramento Final**: Captura screenshot e HTML pós-interação
6. **Comparação**: Verifica diferenças entre estados inicial e final
7. **Logs**: Salva todas as ações em `logs.txt` com timestamps

### Página de Teste
A página `seu-arquivo.html` contém:
- Um botão que, ao ser clicado, adiciona dinamicamente um parágrafo
- JavaScript que simula mudanças no DOM para teste de detecção

## 🔧 Debug no VS Code

1. Abra o projeto no VS Code
2. Vá para **Run and Debug** (Ctrl + Shift + D)
3. Selecione "Debug Test Automation"
4. Clique em **Start Debugging** (F5)
5. Adicione breakpoints no código para inspecionar variáveis como `htmlAntes`, `htmlDepois`, etc.

## 📊 Saídas Geradas

Após execução, o projeto gera:
- **logs.txt**: Log completo com timestamps de todas as operações
- **antes.png/depois.png**: Screenshots visuais da página
- **html_antes.html/html_depois.html**: Estados do HTML para comparação

## 🐛 Tratamento de Erros

- **Loop de Tentativas**: Até 3 tentativas automáticas
- **Correção Automática**: Recarrega página em caso de falha
- **Logs de Erro**: Detalhamento completo de problemas encontrados

## 📝 Personalização

Para adaptar o projeto:
- Modifique `seu-arquivo.html` para testar diferentes interações
- Ajuste seletores em `test-automation.js` para outros elementos
- Adicione mais ações no fluxo de teste
- Configure `waitForFunction` para detectar mudanças específicas

## 🔍 Exemplo de Log Gerado

```
[2025-10-06T00:53:49.073Z] Tentativa 1 iniciada.
Screenshot e HTML inicial capturados.
Botão clicado.
Mudança no DOM detectada.
Screenshot e HTML pós-clique capturados.
Atualizações detectadas na página.
Tentativa 1 concluída com sucesso.
Testes automatizados concluídos com sucesso.
```

## 📚 Dependências

- **Puppeteer**: Automação de browser
- **Node.js**: Runtime JavaScript

## 🎨 Demonstração

O projeto inclui uma página HTML com JavaScript que adiciona conteúdo dinamicamente, permitindo testar a detecção automática de mudanças no DOM após cliques.

---

**Projeto criado para demonstração de automação web avançada com Puppeteer.**



# 📘 NomaTV Backend - Entendimento Completo do Sistema

**Versão**: 1.0  
**Data**: 04/10/2025  
**Autor**: GitHub Copilot  
**Objetivo**: Documentar entendimento técnico do backend NomaTV para validação

---

## 🏗️ **ARQUITETURA GERAL**

### **Estrutura de 3 Camadas**

```
┌─────────────────────────────────────────────────────────────┐
│                    CAMADA 1: FRONTEND                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ admin.html   │  │revendedor.html│ │sub_revendedor│      │
│  │ (Painel Admin)│  │(Painel Reseller)│ │  .html       │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │         Apps Smart TV (LG WebOS/Samsung Tizen)       │   │
│  │  - login.html → autenticando.html → home.html       │   │
│  │  - canais.html, filmes.html, series.html            │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            ↓ HTTP/HTTPS
┌─────────────────────────────────────────────────────────────┐
│              CAMADA 2: PROXY NODE.JS + NGROK                 │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Node.js Express (server.js) - Porta 8080             │  │
│  │ - Serve arquivos estáticos (HTML, CSS, JS)          │  │
│  │ - Proxy para PHP via spawn()                         │  │
│  │ - Gerencia CORS e sessões                            │  │
│  └──────────────────────────────────────────────────────┘  │
│                            ↓                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ Ngrok Tunnel (Domínio Público)                       │  │
│  │ excitable-boyce-ideographical.ngrok-free.dev         │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            ↓ spawn()
┌─────────────────────────────────────────────────────────────┐
│              CAMADA 3: BACKEND PHP + SQLite                  │
│                                                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ PHP 8.1 Local (php/php.exe)                          │  │
│  │ - 19+ endpoints REST API                             │  │
│  │ - Sistema de autenticação com bcrypt                 │  │
│  │ - Gestão de revendedores e provedores                │  │
│  │ - Sistema de branding (logos personalizadas)         │  │
│  └──────────────────────────────────────────────────────┘  │
│                            ↓                                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ SQLite Database (db.db)                              │  │
│  │ - 10 tabelas principais                              │  │
│  │ - Dados persistentes sem servidor externo            │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## �️ **MAPA DE RELACIONAMENTOS - CASCATA COMPLETA**

### **Diagrama de Conexões entre Tabelas e Painéis**

```
┌─────────────────────────────────────────────────────────────────────┐
│                    [ADMIN] (tipo='admin')                            │
│                                                                       │
│  Tabela: revendedores (id=1, master='admin', tipo='admin')          │
│  Painel: admin.html                                                  │
│  Logo: /logos/nomaapp.png (FIXA, sem upload)                        │
│                                                                       │
│  Pode gerenciar:                                                     │
│  ✅ Criar/Editar/Deletar REVENDEDORES                                │
│  ✅ Criar/Editar/Deletar SUB-REVENDEDORES                            │
│  ✅ Criar/Editar/Deletar TODOS os PROVEDORES                         │
│  ✅ Ver TODOS os logs, auditoria, faturas                            │
│  ✅ Configurações globais (planos, permissões)                       │
└─────────────────────────────────────────────────────────────────────┘
                              ↓ cria
┌─────────────────────────────────────────────────────────────────────┐
│              [REVENDEDOR] (master='sim', tipo='revendedor')          │
│                                                                       │
│  Tabela: revendedores (id=2, master='sim', revendedor_pai_id=NULL)  │
│  Painel: revendedor.html                                             │
│  Logo: /uploads/logos/logo_2.png (PODE fazer upload 🎨)             │
│                                                                       │
│  Pode gerenciar:                                                     │
│  ✅ Criar SUB-REVENDEDORES (com revendedor_pai_id=2)                 │
│  ✅ Criar PROVEDORES (com revendedor_id=2)                           │
│  ✅ Ver relatórios financeiros SEUS                                  │
│  ✅ Configurar IPs bloqueados                                        │
│  ✅ Fazer upload de logo personalizada                               │
│  ❌ NÃO vê dados de outros revendedores                              │
└─────────────────────────────────────────────────────────────────────┘
                              ↓ cria                ↓ cria
           ┌──────────────────────────┐      ┌──────────────────────┐
           │   SUB-REVENDEDOR         │      │   PROVEDOR           │
           └──────────────────────────┘      └──────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│         [SUB-REVENDEDOR] (master='nao', tipo='sub_revendedor')       │
│                                                                       │
│  Tabela: revendedores (id=10, master='nao', revendedor_pai_id=2)    │
│  Painel: sub_revendedor.html                                         │
│  Logo: /uploads/logos/logo_10.png (PODE fazer upload 🎨)            │
│        OU herda de logo_2.png (pai) se não tiver própria             │
│                                                                       │
│  Pode gerenciar:                                                     │
│  ✅ Criar PROVEDORES (com sub_revendedor_id=10)                      │
│  ✅ Ver relatórios financeiros SEUS                                  │
│  ✅ Fazer upload de logo personalizada (NOVO!)                       │
│  ❌ NÃO pode criar sub-revendedores                                  │
│  ❌ NÃO vê dados do revendedor pai                                   │
│                                                                       │
│  🔄 FALLBACK DE LOGO:                                                 │
│  1. Tem logo_10.png? → usa logo_10.png                              │
│  2. Não tem? → busca revendedor_pai_id=2 → usa logo_2.png           │
│  3. Pai não tem? → usa /logos/nomaapp.png                           │
└─────────────────────────────────────────────────────────────────────┘
                              ↓ cria
┌─────────────────────────────────────────────────────────────────────┐
│                      [PROVEDOR]                                      │
│                                                                       │
│  Tabela: provedores (id=50)                                          │
│  Campos:                                                             │
│    - revendedor_id = 2 (se criado por revendedor)                   │
│    - sub_revendedor_id = 10 (se criado por sub-revendedor)          │
│    - nome = "NET Brasil"                                             │
│    - dns = "http://servidor.iptv.com"                                │
│                                                                       │
│  🔑 REGRA DE IDENTIFICAÇÃO:                                           │
│  Se sub_revendedor_id IS NOT NULL → dono é o SUB (id=10)            │
│  Se revendedor_id IS NOT NULL → dono é o REVENDEDOR (id=2)          │
└─────────────────────────────────────────────────────────────────────┘
                              ↓ contém
┌─────────────────────────────────────────────────────────────────────┐
│                      [CLIENT_IDS]                                    │
│                                                                       │
│  Tabela: client_ids (id=100)                                         │
│  Campos:                                                             │
│    - provedor_id = 50                                                │
│    - username = "usuario123"                                         │
│    - password = "senha123"                                           │
│    - status = "ativo"                                                │
│    - data_expiracao = "2025-12-31"                                   │
│                                                                       │
│  🔗 CONEXÃO:                                                          │
│  client_ids.provedor_id → provedores.id                             │
│  provedores.sub_revendedor_id → revendedores.id (sub)               │
│  revendedores.revendedor_pai_id → revendedores.id (pai)             │
└─────────────────────────────────────────────────────────────────────┘
                              ↓ usado por
┌─────────────────────────────────────────────────────────────────────┐
│                   [USUÁRIO FINAL - APP SMART TV]                     │
│                                                                       │
│  Interface: login.html (LG WebOS / Samsung Tizen)                    │
│                                                                       │
│  📱 FLUXO DE LOGIN:                                                   │
│  1. Usuário digita:                                                  │
│     - provedor: "NET Brasil"                                         │
│     - username: "usuario123"                                         │
│     - password: "senha123"                                           │
│                                                                       │
│  2. JavaScript faz POST → /api/validar_login.php                     │
│                                                                       │
│  3. PHP valida:                                                      │
│     - Busca provedor "NET Brasil" → provedor_id = 50                │
│     - Busca credenciais em client_ids                                │
│     - Valida username+password                                       │
│     - Identifica revendedor_id:                                      │
│       • Se provedor tem sub_revendedor_id=10 → retorna ID 10        │
│       • Se provedor tem revendedor_id=2 → retorna ID 2              │
│                                                                       │
│  4. Response JSON:                                                   │
│     {                                                                │
│       "success": true,                                               │
│       "data": {                                                      │
│         "provedor": "NET Brasil",                                    │
│         "username": "usuario123",                                    │
│         "password": "senha123",                                      │
│         "dns": "http://servidor.iptv.com",                           │
│         "revendedor_id": 10  // ✨ CHAVE DO BRANDING                 │
│       }                                                              │
│     }                                                                │
│                                                                       │
│  5. App armazena no localStorage:                                    │
│     localStorage.setItem('revendedor_id', '10')                      │
│                                                                       │
│  6. App chama window.loadBrandingLogo()                              │
│     → Carrega /api/logo_proxy.php?r=10                               │
│     → Logo personalizada aparece na Smart TV 🎨                      │
└─────────────────────────────────────────────────────────────────────┘
```

### **Resumo das Conexões (Chave Primária: `revendedor_id`)**

```
revendedores.id (PK)
    ↓
    ├── provedores.revendedor_id (FK) ────┐
    │                                      │
    ├── provedores.sub_revendedor_id (FK) ┤
    │                                      ↓
    └── revendedores.revendedor_pai_id ───→ provedores.id
                                              ↓
                                         client_ids.provedor_id (FK)
                                              ↓
                                         [USUÁRIO FINAL]
                                              ↓
                                         localStorage.revendedor_id
                                              ↓
                                         /api/logo_proxy.php?r=X
                                              ↓
                                         🎨 LOGO PERSONALIZADA
```

### **Fluxo Completo: Do Painel ao App Smart TV**

```
PAINEL                      BANCO DE DADOS              APP SMART TV
──────────────────────────────────────────────────────────────────────

1️⃣ revendedor.html
   └─ Revendedor (ID 2)
      └─ Faz upload: logo_2.png ───→ revendedores.logo_filename = 'logo_2.png'
      └─ Cria Sub (ID 10) ──────────→ revendedores (id=10, revendedor_pai_id=2)
      └─ Cria Provedor ─────────────→ provedores (id=50, revendedor_id=2)
      └─ Cria Credencial ───────────→ client_ids (username=usuario123, provedor_id=50)

2️⃣ sub_revendedor.html
   └─ Sub-revendedor (ID 10)
      └─ OPÇÃO A: Faz upload: logo_10.png ───→ revendedores.logo_filename = 'logo_10.png'
      └─ OPÇÃO B: Não faz upload ────────────→ Herda logo_2.png do pai
      └─ Cria Provedor ─────────────────────→ provedores (id=60, sub_revendedor_id=10)
      └─ Cria Credencial ───────────────────→ client_ids (username=usuario456, provedor_id=60)

3️⃣ login.html (Smart TV)
   └─ Usuário digita: provedor + username + password
      └─ POST /api/validar_login.php
         └─ Busca client_ids ───────────────→ Encontra provedor_id=60
         └─ Busca provedores ───────────────→ sub_revendedor_id=10
         └─ Retorna JSON: { revendedor_id: 10 }
         
   └─ App armazena: localStorage.setItem('revendedor_id', '10')

4️⃣ autenticando.html (Smart TV)
   └─ Chama: window.loadBrandingLogo()
      └─ GET /api/logo_proxy.php?r=10
         └─ Proxy busca: uploads/logos/logo_10.*
            ├─ CENÁRIO A: Encontrou logo_10.png → Retorna logo do SUB 🎨
            └─ CENÁRIO B: Não encontrou
               └─ Busca revendedor_pai_id=2
               └─ Busca uploads/logos/logo_2.*
                  ├─ Encontrou logo_2.png → Retorna logo do PAI 🎨
                  └─ Não encontrou → Retorna /logos/nomaapp.png (fallback final)

5️⃣ home.html, canais.html, filmes.html, series.html
   └─ Todas chamam: window.loadBrandingLogo() na inicialização
      └─ Logo personalizada aparece no canto da tela 🎨
```

---

## �👥 **HIERARQUIA DE USUÁRIOS**

### **3 Tipos de Usuários no Sistema**

```
┌──────────────────────────────────────────────────────────────┐
│                    1. ADMIN (Administrador)                   │
├──────────────────────────────────────────────────────────────┤
│ Tabela: revendedores (tipo='admin' ou master='admin')        │
│ Painel: admin.html                                            │
│ Poderes:                                                      │
│ ✅ Criar/editar/deletar REVENDEDORES                          │
│ ✅ Criar/editar/deletar SUB-REVENDEDORES                      │
│ ✅ Criar/editar/deletar PROVEDORES                            │
│ ✅ Ver todos os logs e auditoria                              │
│ ✅ Configurações globais do sistema                           │
│ ✅ Gerenciar planos e permissões                              │
│ ❌ NÃO pode fazer upload de logo (usa logo NomaTV fixa)      │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    2. REVENDEDOR (Master)                     │
├──────────────────────────────────────────────────────────────┤
│ Tabela: revendedores (master='sim')                          │
│ Painel: revendedor.html                                       │
│ Poderes:                                                      │
│ ✅ Criar/editar/deletar seus próprios SUB-REVENDEDORES        │
│ ✅ Criar/editar/deletar seus próprios PROVEDORES              │
│ ✅ Ver relatórios financeiros (faturas, pagamentos)           │
│ ✅ Configurar IPs bloqueados                                  │
│ ✅ FAZER UPLOAD DE LOGO PERSONALIZADA 🎨                      │
│ ✅ Ver logos de seus sub-revendedores                         │
│ ❌ NÃO pode ver dados de outros revendedores                  │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                  3. SUB-REVENDEDOR (Filho)                    │
├──────────────────────────────────────────────────────────────┤
│ Tabela: revendedores (master='nao', revendedor_pai_id=X)     │
│ Painel: sub_revendedor.html                                   │
│ Poderes:                                                      │
│ ✅ Criar/editar/deletar seus próprios PROVEDORES              │
│ ✅ Ver seus relatórios financeiros                            │
│ ✅ FAZER UPLOAD DE LOGO PERSONALIZADA 🎨 (NOVO!)              │
│ ❌ NÃO pode criar sub-revendedores                            │
│ ❌ NÃO pode ver dados do revendedor pai                       │
│ ❌ NÃO pode alterar configurações globais                     │
│                                                               │
│ 🔄 FALLBACK DE LOGO:                                          │
│ 1. Se sub tem logo própria → usa a dele                      │
│ 2. Se não tem → usa logo do revendedor_pai                   │
│ 3. Se pai não tem → usa logo NomaTV                          │
└──────────────────────────────────────────────────────────────┘
```

---

## 🗄️ **BANCO DE DADOS (SQLite)**

### **Arquivo**: `db.db`

### **Tabelas Principais (10)**

#### **1. revendedores**
```sql
CREATE TABLE revendedores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario VARCHAR(50) UNIQUE NOT NULL,       -- Login do revendedor
    senha VARCHAR(255) NOT NULL,                -- Hash bcrypt
    nome VARCHAR(100) NOT NULL,                 -- Nome completo
    email VARCHAR(100),
    telefone VARCHAR(20),
    master VARCHAR(3) DEFAULT 'nao',            -- 'sim'=Revendedor | 'nao'=Sub-revendedor
    revendedor_pai_id INTEGER,                  -- ID do pai (se master='nao')
    tipo VARCHAR(20) DEFAULT 'revendedor',      -- 'admin', 'revendedor', 'sub_revendedor'
    status VARCHAR(20) DEFAULT 'ativo',         -- 'ativo', 'inativo', 'suspenso'
    logo_filename VARCHAR(50),                  -- 🎨 NOVA COLUNA (branding)
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revendedor_pai_id) REFERENCES revendedores(id)
);
```

**Lógica**:
- **Admin**: `tipo='admin'` OU `master='admin'`
- **Revendedor**: `master='sim'` e `revendedor_pai_id=NULL`
- **Sub-revendedor**: `master='nao'` e `revendedor_pai_id IS NOT NULL`

#### **2. provedores**
```sql
CREATE TABLE provedores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(100) NOT NULL,                 -- Nome do provedor (ex: "NET Brasil")
    dns VARCHAR(255) NOT NULL,                  -- URL do servidor IPTV
    revendedor_id INTEGER,                      -- Se pertence direto a revendedor
    sub_revendedor_id INTEGER,                  -- Se pertence a sub-revendedor
    status VARCHAR(20) DEFAULT 'ativo',
    max_conexoes INTEGER DEFAULT 1,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id),
    FOREIGN KEY (sub_revendedor_id) REFERENCES revendedores(id)
);
```

**Lógica de Propriedade**:
- Se `revendedor_id IS NOT NULL` → provedor pertence diretamente ao revendedor
- Se `sub_revendedor_id IS NOT NULL` → provedor pertence ao sub-revendedor
- **Para branding**: Se é sub, pega logo do sub (ou do pai como fallback)

#### **3. client_ids**
```sql
CREATE TABLE client_ids (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provedor_id INTEGER NOT NULL,               -- Qual provedor
    username VARCHAR(100) NOT NULL,             -- Usuário IPTV
    password VARCHAR(100) NOT NULL,             -- Senha IPTV
    status VARCHAR(20) DEFAULT 'ativo',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_expiracao DATETIME,
    FOREIGN KEY (provedor_id) REFERENCES provedores(id),
    UNIQUE(provedor_id, username)
);
```

**Uso**: Credenciais para login nos apps Smart TV

#### **4. auditoria**
```sql
CREATE TABLE auditoria (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    revendedor_id INTEGER,
    acao VARCHAR(50) NOT NULL,                  -- 'login', 'logout', 'criar_provedor', etc
    descricao TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id)
);
```

**Uso**: Log de todas as ações do sistema

#### **5. planos**
```sql
CREATE TABLE planos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome VARCHAR(50) NOT NULL,
    descricao TEXT,
    max_provedores INTEGER DEFAULT 0,
    max_usuarios INTEGER DEFAULT 0,
    preco_mensal DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'ativo'
);
```

#### **6. ips_bloqueados**
```sql
CREATE TABLE ips_bloqueados (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    revendedor_id INTEGER,
    ip VARCHAR(45) NOT NULL,
    motivo TEXT,
    data_bloqueio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id)
);
```

#### **7. permissoes**
```sql
CREATE TABLE permissoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    revendedor_id INTEGER NOT NULL,
    recurso VARCHAR(50) NOT NULL,               -- 'criar_provedor', 'ver_relatorios', etc
    pode_criar BOOLEAN DEFAULT 0,
    pode_editar BOOLEAN DEFAULT 0,
    pode_deletar BOOLEAN DEFAULT 0,
    pode_visualizar BOOLEAN DEFAULT 1,
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id)
);
```

#### **8. branding** *(Possível duplicação - verificar)*
```sql
-- ⚠️ ATENÇÃO: Esta tabela pode ser redundante
-- A coluna logo_filename já existe em revendedores
-- Decisão: usar APENAS revendedores.logo_filename
CREATE TABLE branding (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    revendedor_id INTEGER NOT NULL UNIQUE,
    logo_filename VARCHAR(50),
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id)
);
```

**⚠️ NOTA**: Entendo que devemos usar **APENAS** `revendedores.logo_filename` e **REMOVER** tabela `branding` para evitar duplicação.

#### **9. faturas**
```sql
CREATE TABLE faturas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    revendedor_id INTEGER NOT NULL,
    plano_id INTEGER,
    valor DECIMAL(10,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente',      -- 'pendente', 'paga', 'vencida'
    data_emissao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id),
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);
```

#### **10. pagamentos**
```sql
CREATE TABLE pagamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fatura_id INTEGER NOT NULL,
    revendedor_id INTEGER NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    metodo VARCHAR(50),                         -- 'pix', 'boleto', 'cartao'
    comprovante TEXT,
    data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'confirmado',
    FOREIGN KEY (fatura_id) REFERENCES faturas(id),
    FOREIGN KEY (revendedor_id) REFERENCES revendedores(id)
);
```

---

## 🔌 **ENDPOINTS PHP (API REST)**

### **Arquivo**: `server.js` (Node.js)
**Função**: Proxy que executa PHP via `spawn()`

```javascript
const apiRoutes = [
    '/api/auth.php',
    '/api/validar_login.php',
    '/api/verificar_sessao.php',
    '/api/verificar_provedor.php',
    '/api/revendedores.php',
    '/api/provedores.php',
    '/api/client_ids.php',
    '/api/planos.php',
    '/api/permissoes.php',
    '/api/ips.php',
    '/api/logs.php',
    '/api/stats.php',
    '/api/relatorios.php',
    '/api/financeiro.php',
    '/api/seguranca.php',
    '/api/configuracoes.php',
    '/api/cleanup.php',
    '/api/rede_revendedor.php',
    '/api/logo_proxy.php',              // 🎨 BRANDING
    '/api/branding/get.php',            // 🎨 BRANDING
    '/api/branding/upload.php',         // 🎨 BRANDING
    '/api/branding/delete.php'          // 🎨 BRANDING
];
```

### **Endpoints Existentes (Criados)**

#### **1. `/api/auth.php`** ✅
**Função**: Login/Logout de revendedores nos painéis  
**Métodos**:
- `POST action=login` → Autentica revendedor
- `POST action=logout` → Encerra sessão

**Request Login**:
```json
{
    "action": "login",
    "usuario": "admin",
    "senha": "admin123"
}
```

**Response Sucesso**:
```json
{
    "success": true,
    "data": {
        "id": 1,
        "usuario": "admin",
        "nome": "Administrador",
        "tipo": "admin",
        "master": "sim"
    },
    "timestamp": "2025-10-04T10:30:00Z"
}
```

**Sessões**: Usa `$_SESSION['revendedor_id']` e `$_SESSION['tipo']`

---

#### **2. `/api/validar_login.php`** ⏳ (Precisa atualizar)
**Função**: Validar login dos **usuários finais** nos apps Smart TV  
**Uso**: Quando usuário faz login no `login.html` da Smart TV

**Request**:
```json
{
    "provedor": "NET Brasil",
    "username": "usuario123",
    "password": "senha123"
}
```

**Response Atual**:
```json
{
    "success": true,
    "data": {
        "provedor": "NET Brasil",
        "username": "usuario123",
        "password": "senha123",
        "dns": "http://servidor.iptv.com"
    }
}
```

**🔄 PRECISA ADICIONAR**: `revendedor_id` para branding
```json
{
    "success": true,
    "data": {
        "provedor": "NET Brasil",
        "username": "usuario123",
        "password": "senha123",
        "dns": "http://servidor.iptv.com",
        "revendedor_id": 5  // ✨ NOVO
    }
}
```

**Lógica**:
1. Buscar `provedor_id` pelo nome
2. Buscar `username`+`password` em `client_ids`
3. Identificar se provedor pertence a:
   - `revendedor_id` → retornar esse ID
   - `sub_revendedor_id` → retornar esse ID (com fallback para pai)
4. Armazenar `revendedor_id` no `localStorage` do app

---

#### **3. `/api/verificar_provedor.php`** ⏳ (Precisa atualizar)
**Função**: Verificar se provedor existe e está ativo  
**Uso**: Validação antes de fazer login

**Request**:
```json
{
    "provedor": "NET Brasil"
}
```

**Response Atual**:
```json
{
    "success": true,
    "data": {
        "dns": "http://servidor.iptv.com"
    },
    "timestamp": "2025-10-04T10:30:00Z"
}
```

**🔄 PRECISA ADICIONAR**: `revendedor_id`
```json
{
    "success": true,
    "data": {
        "dns": "http://servidor.iptv.com",
        "revendedor_id": 5  // ✨ NOVO
    },
    "timestamp": "2025-10-04T10:30:00Z"
}
```

---

### **Endpoints de Branding (Novos)** 🎨

#### **4. `/api/logo_proxy.php`** 🆕
**Função**: Proxy inteligente com fallback em cascata  
**Método**: `GET`  
**Parâmetros**: `?r={revendedor_id}`

**Fluxo**:
```
1. Recebe revendedor_id
2. Verifica se existe logo em uploads/logos/logo_{id}.{ext}
3. Se SIM → retorna a logo
4. Se NÃO e revendedor é SUB:
   - Busca revendedor_pai_id
   - Verifica logo do pai
   - Se existe → retorna logo do pai
5. Fallback final → retorna logos/nomaapp.png
```

**Headers de Resposta**:
```
Content-Type: image/png (ou image/jpeg)
Cache-Control: public, max-age=3600
```

---

#### **5. `/api/branding/get.php`** 🆕
**Função**: Consultar informações de branding  
**Método**: `POST`  
**Autenticação**: Sessão obrigatória

**Request**:
```json
{
    "action": "get_info"
}
```

**Response**:
```json
{
    "success": true,
    "data": {
        "revendedor_id": 5,
        "tipo": "sub_revendedor",
        "tem_logo": true,
        "logo_url": "/api/logo_proxy.php?r=5",
        "logo_filename": "logo_5.png",
        "usando_logo_de": "proprio",  // 'proprio', 'pai', 'nomaapp'
        "revendedor_pai_id": 2,
        "pode_fazer_upload": true
    }
}
```

---

#### **6. `/api/branding/upload.php`** 🆕
**Função**: Upload de logo personalizada  
**Método**: `POST` (multipart/form-data)  
**Autenticação**: Sessão obrigatória

**Validações**:
- ✅ Formato: PNG, JPG, JPEG, WebP
- ✅ Tamanho máximo: 150KB
- ✅ Dimensões recomendadas: 300x100px
- ✅ Apenas revendedor (master='sim') OU sub-revendedor (master='nao')
- ❌ Admin NÃO pode fazer upload

**Request** (FormData):
```
logo: [FILE]
```

**Response Sucesso**:
```json
{
    "success": true,
    "message": "Logo enviada com sucesso",
    "data": {
        "filename": "logo_5.png",
        "url": "/api/logo_proxy.php?r=5"
    }
}
```

**Processo**:
1. Validar sessão (revendedor logado)
2. Verificar se é admin (bloqueia se for)
3. Validar arquivo (tamanho, formato, dimensões)
4. Deletar logo anterior se existir
5. Salvar novo arquivo: `uploads/logos/logo_{revendedor_id}.{ext}`
6. Atualizar `revendedores.logo_filename`
7. Log de auditoria

---

#### **7. `/api/branding/delete.php`** 🆕
**Função**: Remover logo personalizada  
**Método**: `POST`  
**Autenticação**: Sessão obrigatória

**Request**:
```json
{
    "action": "delete"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Logo removida com sucesso"
}
```

**Processo**:
1. Validar sessão
2. Buscar `logo_filename` do revendedor
3. Deletar arquivo físico
4. Limpar campo `logo_filename` no banco
5. Retornar para logo padrão (NomaTV ou pai)

---

## 🎨 **SISTEMA DE BRANDING - FLUXO COMPLETO**

### **Cenário 1: Admin faz login no app Smart TV**
```
1. Admin cria provedor "Provedor Admin"
2. Usuário final faz login com credenciais desse provedor
3. validar_login.php retorna revendedor_id = NULL
4. App chama window.loadBrandingLogo()
5. Nenhum revendedor_id → usa /logos/nomaapp.png
6. Logo NomaTV é exibida
```

### **Cenário 2: Revendedor Master cria provedor**
```
1. Revendedor (ID 2) faz login em revendedor.html
2. Faz upload de logo personalizada → logo_2.png
3. Cria provedor "Meu Provedor"
4. Usuário final faz login com credenciais desse provedor
5. validar_login.php identifica:
   - provedor.revendedor_id = 2
   - Retorna revendedor_id: 2
6. App armazena localStorage.setItem('revendedor_id', 2)
7. window.loadBrandingLogo() é chamada
8. Carrega /api/logo_proxy.php?r=2
9. Logo personalizada do revendedor é exibida
```

### **Cenário 3: Sub-revendedor COM logo própria**
```
1. Sub-revendedor (ID 10, pai ID 2) faz login
2. Faz upload de logo própria → logo_10.png
3. Cria provedor "Meu Sub Provedor"
4. Usuário final faz login
5. validar_login.php identifica:
   - provedor.sub_revendedor_id = 10
   - Retorna revendedor_id: 10
6. App chama /api/logo_proxy.php?r=10
7. Proxy encontra logo_10.png
8. Logo do SUB-revendedor é exibida
```

### **Cenário 4: Sub-revendedor SEM logo (herda do pai)**
```
1. Sub-revendedor (ID 15, pai ID 2) NÃO fez upload
2. Cria provedor "Provedor Filho"
3. Usuário final faz login
4. validar_login.php retorna revendedor_id: 15
5. App chama /api/logo_proxy.php?r=15
6. Proxy NÃO encontra logo_15.*
7. Busca revendedor_pai_id = 2
8. Verifica se existe logo_2.png → SIM
9. Logo do REVENDEDOR PAI é exibida (herança automática)
```

### **Cenário 5: Sub sem logo E pai sem logo**
```
1. Sub (ID 20, pai ID 8)
2. Pai (ID 8) também não tem logo
3. App chama /api/logo_proxy.php?r=20
4. Proxy não encontra logo_20.*
5. Busca pai (ID 8)
6. Não encontra logo_8.*
7. Fallback final → /logos/nomaapp.png
8. Logo NomaTV padrão é exibida
```

---

## 🔐 **SISTEMA DE AUTENTICAÇÃO**

### **Sessões PHP**
```php
session_start();

// Login bem-sucedido:
$_SESSION['revendedor_id'] = 5;
$_SESSION['tipo'] = 'revendedor';
$_SESSION['usuario'] = 'joao_reseller';
$_SESSION['master'] = 'sim';
$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];

// Validação em endpoints protegidos:
if (!isset($_SESSION['revendedor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}
```

### **Permissões por Tipo**
```php
// Admin pode tudo
if ($_SESSION['tipo'] === 'admin') {
    // Acesso total
}

// Revendedor vê apenas seus dados
if ($_SESSION['tipo'] === 'revendedor') {
    $query .= " WHERE revendedor_id = " . $_SESSION['revendedor_id'];
}

// Sub-revendedor vê apenas seus provedores
if ($_SESSION['tipo'] === 'sub_revendedor') {
    $query .= " WHERE sub_revendedor_id = " . $_SESSION['revendedor_id'];
}
```

---

## 📱 **INTEGRAÇÃO COM APPS SMART TV**

### **Fluxo de Login**
```
1. Usuario abre login.html
2. Digita: provedor, username, password
3. Clica em "Entrar"
4. JavaScript faz POST para /api/validar_login.php
5. PHP valida credenciais
6. Retorna success + revendedor_id
7. JavaScript armazena:
   localStorage.setItem('provedor', 'NET Brasil')
   localStorage.setItem('username', 'usuario123')
   localStorage.setItem('password', 'senha123')
   localStorage.setItem('dns', 'http://servidor.com')
   localStorage.setItem('revendedor_id', '5')  // ✨ NOVO
8. Redireciona para autenticando.html
9. autenticando.html chama window.loadBrandingLogo()
10. Logo personalizada carrega
11. Redireciona para home.html
```

### **Função Global de Branding**
**Arquivo**: `index_casca.html`

#### **✅ MÉTODO OFICIAL: Imagem Binária Direta (PADRÃO)**
**Por que este método?**
- ✅ **Mais simples** e lógico para trabalhar
- ✅ **Funciona perfeitamente** com `<img src>`
- ✅ **Sem lógica extra** → ID da logo = ID do revendedor
- ✅ **Performance melhor** (sem fetch extra)
- ✅ **Compatível** com todos os browsers

```javascript
/**
 * 🎨 SISTEMA DE BRANDING - MÉTODO OFICIAL
 * 
 * LÓGICA SIMPLIFICADA:
 * 1. Lê revendedor_id do localStorage
 * 2. Monta URL: /api/logo_proxy.php?r={ID}
 * 3. Atribui ao src da <img>
 * 4. Proxy retorna IMAGEM BINÁRIA diretamente
 * 5. Fallback automático se erro
 * 
 * IDENTIFICAÇÃO:
 * - Logo: uploads/logos/logo_{revendedor_id}.{ext}
 * - Exemplo: logo_2.png → revendedor_id = 2
 * - SEM LÓGICA EXTRA → ID direto = simplicidade
 */
window.loadBrandingLogo = function() {
    console.log('[BRANDING] 🎨 Iniciando carregamento de logo...');
    
    // Buscar elemento de imagem existente
    const logoImg = document.querySelector('#logoImg, .logo-img, .logo, .revendedor-logo');
    
    if (!logoImg) {
        console.warn('[BRANDING] ⚠️ Elemento de logo não encontrado no DOM');
        return;
    }
    
    // Ler ID do revendedor do localStorage
    const revendedorId = localStorage.getItem('revendedor_id');
    
    if (revendedorId && revendedorId !== 'null' && parseInt(revendedorId) > 0) {
        // MÉTODO BINÁRIO: Proxy serve imagem direta
        const proxyUrl = `/api/logo_proxy.php?r=${revendedorId}`;
        
        console.log(`[BRANDING] 📡 Carregando logo do revendedor ID: ${revendedorId}`);
        console.log(`[BRANDING] 🔗 URL: ${proxyUrl}`);
        
        logoImg.src = proxyUrl;
        
        // Fallback em caso de erro
        logoImg.onerror = function() {
            console.warn('[BRANDING] ❌ Erro ao carregar logo. Usando fallback NomaTV');
            logoImg.src = '/logos/nomaapp.png';
        };
        
        // Log de sucesso
        logoImg.onload = function() {
            console.log('[BRANDING] ✅ Logo carregada com sucesso!');
        };
        
    } else {
        // Sem ID válido → logo padrão
        console.log('[BRANDING] 📺 Sem revendedor_id válido. Usando logo NomaTV padrão');
        logoImg.src = '/logos/nomaapp.png';
    }
};
```

#### **🔄 MÉTODO ALTERNATIVO: Link Direto (BACKUP SERVER)**
**Quando usar?**
- ⚠️ **APENAS para servidor de backup**
- 🔄 Se servidor principal cair
- 🌐 Link direto CDN/externo

**Por que NÃO é o padrão?**
- ❌ Fetch extra desnecessário
- ❌ Mais complexo sem ganho
- ❌ Dois pontos de falha (fetch + imagem)

```javascript
/**
 * Carregamento Dinâmico da Logo do Revendedor
 * 
 * FLUXO:
 * 1. Lê revendedor_id do localStorage
 * 2. Faz fetch para logo_proxy.php
 * 3. Recebe URL da logo como texto
 * 4. Cria elemento <img> dinamicamente
 * 5. Fallback automático se logo não existir
 */
(async () => {
    const revendedorId = localStorage.getItem("revendedor_id");
    
    if (!revendedorId) {
        console.warn("[BRANDING] ⚠️ Revendedor ID não encontrado no localStorage");
        return;
    }
    
    const proxyURL = `/api/logo_proxy.php?id=${revendedorId}`;
    
    try {
        console.log(`[BRANDING] 🔍 Buscando logo: ${proxyURL}`);
        
        const response = await fetch(proxyURL);
        const logoUrl = await response.text(); // URL como texto
        
        console.log(`[BRANDING] ✅ Logo encontrada: ${logoUrl}`);
        
        // Criar elemento de imagem dinamicamente
        const logoImg = document.createElement("img");
        logoImg.src = logoUrl;
        logoImg.alt = "Logo do Revendedor";
        logoImg.className = "revendedor-logo";
        logoImg.style.maxWidth = "200px";
        logoImg.style.margin = "20px auto";
        logoImg.style.display = "block";
        
        // Fallback se logo não carregar
        logoImg.onerror = () => {
            console.warn("[BRANDING] ⚠️ Logo personalizada não encontrada. Usando logo padrão.");
            logoImg.src = "/logos/nomaapp.png";
        };
        
        // Inserir no container
        const container = document.getElementById("logoContainer");
        if (container) {
            container.innerHTML = ''; // Limpar anterior
            container.appendChild(logoImg);
            console.log("[BRANDING] 🎨 Logo inserida no DOM");
        } else {
            console.error("[BRANDING] ❌ Container #logoContainer não encontrado");
        }
        
    } catch (e) {
        console.error("[BRANDING] ❌ Erro ao buscar logo:", e);
        
        // Fallback em caso de erro
        const fallbackImg = document.createElement("img");
        fallbackImg.src = "/logos/nomaapp.png";
        fallbackImg.alt = "Logo padrão NomaTV";
        fallbackImg.style.maxWidth = "200px";
        fallbackImg.style.margin = "20px auto";
        fallbackImg.style.display = "block";
        
        const container = document.getElementById("logoContainer");
        if (container) {
            container.innerHTML = '';
            container.appendChild(fallbackImg);
        }
    }
})();
```

#### **📋 Estrutura HTML Necessária**
```html
<!-- Adicionar no index_casca.html -->
<div id="logoContainer" class="branding-logo-wrapper">
    <!-- Logo será injetada aqui dinamicamente -->
</div>
```

#### **🎯 Diferenças entre os Métodos**

| Característica           | Método 1 (Simples)          | Método 2 (Fetch/SPA)           |
|--------------------------|------------------------------|--------------------------------|
| **Elemento HTML**        | Precisa existir previamente  | Criado dinamicamente          |
| **Tipo de resposta**     | Imagem direta (binary)       | URL como texto                |
| **Complexidade**         | Baixa                        | Média                         |
| **Flexibilidade**        | Limitada                     | Alta (SPA-friendly)           |
| **Logs detalhados**      | Básicos                      | Completos                     |
| **Recomendado para**     | Apps tradicionais            | Single Page Applications      |

#### **⚙️ Configuração do logo_proxy.php**

**IMPORTANTE**: O proxy deve retornar a **URL como texto**, não servir a imagem diretamente.

```php
<?php
// Exemplo de resposta do logo_proxy.php
header('Content-Type: text/plain; charset=utf-8');

$revendedor_id = $_GET['id'] ?? null;

if ($revendedor_id) {
    $logoPath = "/logos/{$revendedor_id}.png";
    
    if (file_exists(__DIR__ . $logoPath)) {
        echo "https://webnoma.space{$logoPath}";
    } else {
        echo "https://webnoma.space/logos/nomaapp.png";
    }
} else {
    echo "https://webnoma.space/logos/nomaapp.png";
}
?>
```

#### **📊 Fluxo Completo de Carregamento**

```
┌─────────────────────────────────────────────────────────────┐
│ 1. autenticando.html (Smart TV)                              │
│    └─ Login bem-sucedido                                     │
│       └─ localStorage.setItem('revendedor_id', '10')         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. index_casca.html (Carrega)                                │
│    └─ Script JS lê: localStorage.getItem('revendedor_id')   │
│       └─ revendedor_id = '10'                                │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. Fetch para Proxy                                          │
│    └─ GET /api/logo_proxy.php?id=10                          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. logo_proxy.php (Responde)                                 │
│    └─ Verifica: uploads/logos/logo_10.*                      │
│       ├─ ENCONTROU → retorna "https://webnoma.space/logos/10.png" │
│       └─ NÃO ENCONTROU → retorna "https://webnoma.space/logos/nomaapp.png" │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. JavaScript Cria <img>                                     │
│    └─ logoImg.src = "https://webnoma.space/logos/10.png"    │
│       └─ Insere em: document.getElementById('logoContainer') │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. Renderização                                              │
│    └─ Logo personalizada aparece na Smart TV 🎨              │
└─────────────────────────────────────────────────────────────┘
```

#### **✅ Resultado Esperado**

| Situação                            | Resultado                                             |
|-------------------------------------|-------------------------------------------------------|
| Logo personalizada existe           | Exibida automaticamente na interface                  |
| Logo não existe                     | Fallback: mostra `/logos/nomaapp.png`                 |
| Erro de rede                        | Fallback: mostra `/logos/nomaapp.png`                 |
| revendedor_id inválido              | Fallback: mostra `/logos/nomaapp.png`                 |
| Container não existe                | Log de erro no console                                |

#### **🛡️ Considerações de Segurança**

1. ✅ Validar `revendedor_id` no backend (apenas números)
2. ✅ Não expor estrutura de pastas sensíveis
3. ✅ Usar HTTPS em produção
4. ✅ Cache de logos (max-age: 3600)
5. ✅ Sanitizar parâmetros GET no PHP

### **Chamada nas Sessões**
**Em TODAS as páginas**: `home.html`, `canais.html`, `filmes.html`, `series.html`, `autenticando.html`

```javascript
window.inicializarHome = function() {
    window.loadBrandingLogo(); // 🎨 PRIMEIRA linha
    
    console.log("🏠 Inicializando Home...");
    // ... resto do código
}
```

---

## 📂 **ESTRUTURA DE PASTAS**

```
_api (12)/
├── server.js                    # Node.js proxy (porta 8080)
├── package.json
├── db.db                        # SQLite database
│
├── php/                         # PHP 8.1 local
│   └── php.exe
│
├── api/                         # Endpoints PHP
│   ├── auth.php                 ✅ Autenticação painéis
│   ├── validar_login.php        ⏳ Login Smart TV (precisa atualizar)
│   ├── verificar_provedor.php   ⏳ Validação (precisa atualizar)
│   ├── verificar_sessao.php     ⏳ Check sessão
│   ├── revendedores.php         ❌ CRUD revendedores (criar)
│   ├── provedores.php           ❌ CRUD provedores (criar)
│   ├── client_ids.php           ❌ CRUD credenciais (criar)
│   ├── planos.php               ❌ Gestão planos (criar)
│   ├── permissoes.php           ❌ Permissões (criar)
│   ├── ips.php                  ❌ IPs bloqueados (criar)
│   ├── logs.php                 ❌ Logs/auditoria (criar)
│   ├── stats.php                ❌ Estatísticas (criar)
│   ├── relatorios.php           ❌ Relatórios (criar)
│   ├── financeiro.php           ❌ Faturas/pagamentos (criar)
│   ├── seguranca.php            ❌ Segurança (criar)
│   ├── configuracoes.php        ❌ Configurações (criar)
│   ├── cleanup.php              ❌ Limpeza (criar)
│   ├── rede_revendedor.php      ❌ Rede revendedores (criar)
│   ├── logo_proxy.php           🆕 Proxy logos (criar)
│   │
│   ├── config/
│   │   └── database_sqlite.php  ✅ Conexão SQLite
│   │
│   ├── helpers/
│   │   └── auth_helper.php      ⏳ Funções auth (atualizar)
│   │
│   └── branding/
│       ├── get.php              🆕 Consultar branding (criar)
│       ├── upload.php           🆕 Upload logo (criar)
│       └── delete.php           🆕 Deletar logo (criar)
│
├── uploads/
│   └── logos/                   🆕 Logos personalizadas (criar pasta)
│       ├── logo_2.png           # Exemplo revendedor ID 2
│       ├── logo_5.jpg           # Exemplo revendedor ID 5
│       └── logo_10.webp         # Exemplo sub ID 10
│
└── logos/
    └── nomaapp.png              ✅ Logo padrão NomaTV
```

---

## 🔄 **ATUALIZAÇÕES NECESSÁRIAS**

### **Arquivos que PRECISAM ser Atualizados**

#### **1. `api/helpers/auth_helper.php`**
**Adicionar função**:
```php
function identificarRevendedorDono($db, $provedor_id) {
    // Retorna:
    // - revendedor_id (do provedor direto OU do sub)
    // - tipo ('master' ou 'sub')
    // - revendedor_pai_id (se for sub)
}
```

#### **2. `api/validar_login.php`**
**Modificar resposta** para incluir `revendedor_id`:
```php
$revendedorInfo = identificarRevendedorDono($db, $provedor_id);

echo json_encode([
    'success' => true,
    'data' => [
        'provedor' => $provedor,
        'username' => $username,
        'password' => $password,
        'dns' => $dns,
        'revendedor_id' => $revendedorInfo['revendedor_id']  // ✨ NOVO
    ]
]);
```

#### **3. `api/verificar_provedor.php`**
**Modificar resposta** para incluir `revendedor_id`:
```php
$revendedorInfo = identificarRevendedorDono($db, $provedor_id);

echo json_encode([
    'success' => true,
    'data' => [
        'dns' => $dns,
        'revendedor_id' => $revendedorInfo['revendedor_id']  // ✨ NOVO
    ]
]);
```

#### **4. `index_casca.html`**
**Adicionar função global** `window.loadBrandingLogo()`

#### **5. Apps Smart TV** (5 arquivos)
- `proxy/html/home.html`
- `proxy/html/canais.html`
- `proxy/html/filmes.html`
- `proxy/html/series.html`
- `proxy/html/autenticando.html`

**Adicionar em TODAS**:
```javascript
window.inicializarXXX = function() {
    window.loadBrandingLogo(); // 🎨 PRIMEIRA linha
    // ... resto
}
```

#### **6. Painéis Admin/Revendedor**
- `admin.html` → Interface para gerenciar revendedores
- `revendedor.html` → Seção de branding (upload logo)
- `sub_revendedor.html` → Seção de branding (upload logo)

---

## 🆕 **ARQUIVOS QUE PRECISAM SER CRIADOS**

### **Backend PHP**
1. ✅ `api/install_branding.php` - Instalador (já criado?)
2. 🆕 `api/logo_proxy.php` - Proxy inteligente
3. 🆕 `api/branding/get.php` - Consultar info
4. 🆕 `api/branding/upload.php` - Upload logo
5. 🆕 `api/branding/delete.php` - Deletar logo

### **Infraestrutura**
6. 🆕 Criar pasta `uploads/logos/` com permissões 755
7. 🆕 Adicionar coluna `logo_filename` em `revendedores`

### **Endpoints Restantes** (Fora de branding)
8. ❌ `api/revendedores.php` - CRUD revendedores
9. ❌ `api/provedores.php` - CRUD provedores
10. ❌ `api/client_ids.php` - CRUD credenciais
11. ❌ 10+ outros endpoints...

---

## ❓ **DÚVIDAS PARA VALIDAÇÃO**

### **1. Tabela `branding` - Redundante?**
❓ Entendo que devemos **remover** a tabela `branding` e usar **APENAS** `revendedores.logo_filename`. Correto?

### **2. Sub-revendedor pode fazer upload?**
✅ **SIM** - Sistema flexível com fallback em cascata  
✅ Sub pode ter logo própria OU herdar do pai

### **3. Admin pode fazer upload?**
❌ **NÃO** - Admin sempre usa logo NomaTV fixa

### **4. Formato e tamanho da logo**
✅ Formatos: PNG, JPG, JPEG, WebP  
✅ Tamanho máximo: 150KB  
✅ Dimensões recomendadas: 300x100px

### **5. Hierarquia de fallback**
```
Sub-revendedor → Revendedor Pai → Logo NomaTV (https://webnoma.shop/logos/nomaapp.png)
Revendedor → Logo NomaTV (https://webnoma.shop/logos/nomaapp.png)
Admin → Logo NomaTV (https://webnoma.shop/logos/nomaapp.png - sem opção de upload)
```

⚠️ **IMPORTANTE - SERVIDOR DE BACKUP**:
- Logo padrão NomaTV vem do domínio: `https://webnoma.shop/logos/nomaapp.png`
- **TODOS** os recursos de backup (logos, assets) vêm desse domínio
- Logos personalizadas ficam localmente em `uploads/logos/`
- Fallback final sempre redireciona para servidor externo

### **6. Onde armazenar logos?**
✅ **Logos personalizadas (locais)**: `uploads/logos/logo_{revendedor_id}.{ext}`  
✅ **Logo padrão NomaTV (externa)**: `https://webnoma.shop/logos/nomaapp.png`

### **7. Como funciona proxy?**
✅ `/api/logo_proxy.php?id=5` (retorna URL como texto, NÃO binário)  
- Busca `logo_5.*` em `uploads/logos/`
- Se encontrar → retorna URL da logo personalizada
- Se não encontrar e for sub → busca logo do pai
- Fallback final → retorna `https://webnoma.shop/logos/nomaapp.png`

**Exemplo de resposta**:
```
https://webnoma.space/uploads/logos/logo_102.png
```
OU
```
https://webnoma.shop/logos/nomaapp.png
```

---

## 🎯 **PRÓXIMOS PASSOS (APÓS VALIDAÇÃO)**

1. ✅ **Você valida este entendimento**
2. 🔧 Atualizar `auth_helper.php` (adicionar função)
3. 🔧 Atualizar `validar_login.php` (incluir revendedor_id)
4. 🔧 Atualizar `verificar_provedor.php` (incluir revendedor_id)
5. 🆕 Criar `logo_proxy.php`
6. 🆕 Criar `branding/get.php`
7. 🆕 Criar `branding/upload.php`
8. 🆕 Criar `branding/delete.php`
9. 🔧 Atualizar `index_casca.html`
10. 🔧 Atualizar 5 sessões Smart TV
11. 🧪 Testar sistema completo
12. 🎨 Criar interface painel revendedor
13. 📝 Criar demais endpoints (provedores, client_ids, etc)

---

## 🔎 **FUNCIONAMENTO DETALHADO DO CARREGAMENTO DE LOGO**

### **1. Armazenamento do ID no Login**

Quando o usuário faz login no app Smart TV, o fluxo é:

```javascript
// 1. App envia credenciais
POST /api/validar_login.php
{
    "provedor": "NET Brasil",
    "username": "usuario123",
    "password": "senha123"
}

// 2. Backend valida e retorna com revendedor_id
{
    "success": true,
    "data": {
        "provedor": "NET Brasil",
        "username": "usuario123",
        "password": "senha123",
        "dns": "http://servidor.iptv.com",
        "revendedor_id": 102  // ✨ CHAVE DO BRANDING
    }
}

// 3. App salva no localStorage (CHAVE MESTRA)
localStorage.setItem("revendedor_id", "102");
```

**Importância**: Esse ID é a "chave mestra" que liga o usuário final ao dono (revendedor ou sub).

---

### **2. Leitura pela Casca (index_casca.html)**

Toda vez que o app abre uma tela, a casca lê automaticamente:

```javascript
const revendedorId = localStorage.getItem("revendedor_id");

// Se não existir → usuário ainda não fez login
if (!revendedorId) {
    console.log("Sem revendedor_id → Usando logo NomaTV padrão");
    logoImg.src = "https://webnoma.shop/logos/nomaapp.png";
    return;
}
```

**Lógica**:
- ✅ Se existe ID → buscar logo personalizada
- ❌ Se não existe → fallback imediato para logo NomaTV

---

### **3. Busca da Logo via Proxy**

O app constrói a URL do proxy com o ID:

```javascript
const proxyURL = `/api/logo_proxy.php?id=${revendedorId}`;
// Exemplo: /api/logo_proxy.php?id=102

// Faz fetch para buscar a URL da logo
const response = await fetch(proxyURL);
const logoUrl = await response.text(); // URL como TEXTO, não binário
```

**O que o proxy faz** (`logo_proxy.php`):

```
1. Recebe: ?id=102
2. Verifica: existe uploads/logos/logo_102.png?
   ├─ SIM → retorna "https://webnoma.space/uploads/logos/logo_102.png"
   └─ NÃO → continua
3. É sub-revendedor?
   └─ Busca revendedor_pai_id no banco
   └─ Verifica: existe uploads/logos/logo_{pai_id}.png?
      ├─ SIM → retorna logo do pai
      └─ NÃO → continua
4. Fallback final → retorna "https://webnoma.shop/logos/nomaapp.png"
```

**Cascata de fallback**:
```
Sub (ID 102) → Pai (ID 5) → NomaTV Backup
```

---

### **4. Injeção no HTML**

A casca cria dinamicamente um `<img>` dentro do container:

```html
<!-- Container no HTML -->
<div id="logoContainer"></div>
```

```javascript
// Script cria a imagem
const logoImg = document.createElement("img");
logoImg.src = logoUrl; // URL recebida do proxy
logoImg.alt = "Logo do Revendedor";
logoImg.className = "revendedor-logo";

// Fallback se a logo não carregar
logoImg.onerror = () => {
    console.warn("Logo personalizada falhou. Usando backup.");
    logoImg.src = "https://webnoma.shop/logos/nomaapp.png";
};

// Inserir no DOM
document.getElementById("logoContainer").appendChild(logoImg);
```

**Garantias**:
- ✅ Se logo existe → aparece automaticamente
- ✅ Se der erro (404, timeout) → fallback NomaTV
- ✅ Nunca fica sem logo

---

### **5. Resposta Esperada do logo_proxy.php**

⚠️ **CRÍTICO**: O proxy **NÃO serve a imagem direto** (binário).  
✅ **Ele retorna apenas a URL final como TEXTO**.

**Exemplo de resposta válida**:

```text
https://webnoma.space/uploads/logos/logo_102.png
```

OU (fallback):

```text
https://webnoma.shop/logos/nomaapp.png
```

**Por que assim?**
- ✅ Mais flexível para SPAs
- ✅ Permite cache e CDN externos
- ✅ Logs detalhados no browser
- ✅ Compatível com servidor de backup

---

### **✅ Comportamento Esperado - Tabela Completa**

| Situação                                | Resultado                                                                 |
|-----------------------------------------|---------------------------------------------------------------------------|
| Revendedor tem logo                     | `https://webnoma.space/uploads/logos/logo_5.png`                          |
| Sub tem logo própria                    | `https://webnoma.space/uploads/logos/logo_102.png`                        |
| Sub sem logo, pai com logo              | `https://webnoma.space/uploads/logos/logo_5.png` (herança automática)     |
| Nenhum tem logo                         | `https://webnoma.shop/logos/nomaapp.png` (fallback final)                 |
| Erro de rede                            | `https://webnoma.shop/logos/nomaapp.png` (fallback via onerror)           |
| revendedor_id inválido ou não numérico  | `https://webnoma.shop/logos/nomaapp.png` (validação no proxy)             |

---

### **🛡️ Observações Importantes**

1. **Fluxo Automático**: Depois do login, a logo aparece em **todas** as telas do app sem intervenção manual.

2. **Cache Inteligente**: O `logo_proxy.php` pode retornar headers:
   ```php
   header('Cache-Control: public, max-age=3600'); // 1 hora
   ```

3. **Segurança**: O proxy valida se o `id` é numérico antes de fazer queries.

4. **Fallback Duplo**:
   - **Fallback 1**: Proxy não encontra logo → retorna URL do backup
   - **Fallback 2**: Imagem falha ao carregar → `onerror` no JS

5. **Servidor de Backup**: TODOS os recursos de backup vêm de `https://webnoma.shop/`

6. **Performance**: O app não baixa a logo toda vez (cache do browser + headers corretos)

---

### **📊 Diagrama de Sequência Completo**

```
┌────────────┐         ┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│  Smart TV  │         │ validar_login│         │  localStorage│         │ logo_proxy   │
│  (login)   │         │     .php     │         │              │         │    .php      │
└─────┬──────┘         └──────┬───────┘         └──────┬───────┘         └──────┬───────┘
      │                       │                        │                        │
      │ POST credenciais      │                        │                        │
      │──────────────────────>│                        │                        │
      │                       │                        │                        │
      │                       │ Valida + busca ID      │                        │
      │                       │ revendedor             │                        │
      │                       │                        │                        │
      │ JSON com revendedor_id│                        │                        │
      │<──────────────────────│                        │                        │
      │                       │                        │                        │
      │ setItem('revendedor_id', 102)                  │                        │
      │───────────────────────────────────────────────>│                        │
      │                       │                        │                        │
      │ [Redireciona para autenticando.html]           │                        │
      │                       │                        │                        │
┌─────┴──────┐         ┌──────┴───────┐         ┌──────┴───────┐         ┌──────┴───────┐
│  Smart TV  │         │ index_casca  │         │  localStorage│         │ logo_proxy   │
│  (home)    │         │    .html     │         │              │         │    .php      │
└─────┬──────┘         └──────┬───────┘         └──────┬───────┘         └──────┬───────┘
      │                       │                        │                        │
      │ Carrega página        │                        │                        │
      │──────────────────────>│                        │                        │
      │                       │                        │                        │
      │                       │ getItem('revendedor_id')                        │
      │                       │───────────────────────>│                        │
      │                       │                        │                        │
      │                       │ retorna "102"          │                        │
      │                       │<───────────────────────│                        │
      │                       │                        │                        │
      │                       │ GET /api/logo_proxy.php?id=102                  │
      │                       │────────────────────────────────────────────────>│
      │                       │                        │                        │
      │                       │                        │         Busca logo_102.*
      │                       │                        │         em uploads/logos/
      │                       │                        │                        │
      │                       │                        │         Se não existir:
      │                       │                        │         - Busca pai_id
      │                       │                        │         - Busca logo do pai
      │                       │                        │         - Fallback NomaTV
      │                       │                        │                        │
      │                       │ URL: "https://webnoma.space/uploads/logos/logo_102.png"
      │                       │<────────────────────────────────────────────────│
      │                       │                        │                        │
      │ <img src="...">       │                        │                        │
      │<──────────────────────│                        │                        │
      │                       │                        │                        │
      │ Logo exibida 🎨       │                        │                        │
      │                       │                        │                        │
```

---

### **🚀 Pronto para Implementar**

Agora que o fluxo está 100% documentado, podemos criar:

1. ✅ `logo_proxy.php` - Com lógica completa de fallback em cascata
2. ✅ `index_casca.html` - Com função `loadBrandingLogo()` global
3. ✅ `validar_login.php` - Atualizado para retornar `revendedor_id`
4. ✅ `auth_helper.php` - Com função `identificarRevendedorDono()`

**Todos os arquivos prontos para colar e funcionar!**

---

## ✅ **CONCLUSÃO**

Este é meu entendimento completo do sistema NomaTV Backend:

- **Arquitetura**: Node.js (proxy) + PHP (lógica) + SQLite (dados)
- **Hierarquia**: Admin → Revendedor → Sub-revendedor → Provedores → Usuários finais
- **Branding**: Sistema flexível com fallback em cascata
- **Autenticação**: Sessões PHP com bcrypt
- **Apps**: Smart TV (LG/Samsung) com localStorage
- **Status Atual**: ~20% completo (auth + database)
- **Próximo**: Sistema de branding (90% pronto para implementar)

**🚦 Aguardando sua validação para prosseguir! 🚦**
