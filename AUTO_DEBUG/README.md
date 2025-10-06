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