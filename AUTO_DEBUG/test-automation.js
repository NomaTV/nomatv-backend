const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  let browser;
  let sucesso = false;

  for (let tentativa = 1; tentativa <= 3; tentativa++) {
    const timestamp = new Date().toISOString();
    const logMsg = `[${timestamp}] Tentativa ${tentativa} iniciada.\n`;
    fs.appendFileSync('logs.txt', logMsg);
    console.log(logMsg.trim());

    try {
      browser = await puppeteer.launch({ headless: false }); // Modo não-headless para visualizar
      const page = await browser.newPage();
      await page.goto('file://' + __dirname + '/seu-arquivo.html');

      // Monitoramento Inicial
      await page.screenshot({ path: 'antes.png' });
      const htmlAntes = await page.content();
      fs.writeFileSync('html_antes.html', htmlAntes);
      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML inicial capturados.\n`);
      console.log('Screenshot e HTML inicial capturados.');

      // Verificar se o botão existe
      const botaoExiste = await page.$('#seuBotao');
      if (!botaoExiste) {
        throw new Error('Botão #seuBotao não encontrado.');
      }

      // Ação Principal: Clicar no botão
      await page.click('#seuBotao');
      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Botão clicado.\n`);
      console.log('Botão clicado.');

      // Aguardar atualizações: esperar por novo parágrafo (elemento dinâmico)
      await page.waitForFunction(() => document.querySelectorAll('p').length > 0, { timeout: 5000 });
      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Mudança no DOM detectada (novo parágrafo).\n`);
      console.log('Mudança no DOM detectada.');

      // Monitoramento Pós-Ação
      await page.screenshot({ path: 'depois.png' });
      const htmlDepois = await page.content();
      fs.writeFileSync('html_depois.html', htmlDepois);
      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML pós-clique capturados.\n`);
      console.log('Screenshot e HTML pós-clique capturados.');

      // Comparar HTMLs
      if (htmlAntes !== htmlDepois) {
        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Atualizações detectadas na página.\n`);
        console.log('Atualizações detectadas na página.');
      } else {
        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Nenhuma mudança detectada.\n`);
        console.log('Nenhuma mudança detectada.');
      }

      sucesso = true;
      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Tentativa ${tentativa} concluída com sucesso.\n`);
      console.log(`Tentativa ${tentativa} concluída com sucesso.`);
      break; // Sai do loop se sucesso

    } catch (error) {
      const errorMsg = `[${new Date().toISOString()}] Erro na tentativa ${tentativa}: ${error.message}\n`;
      fs.appendFileSync('logs.txt', errorMsg);
      console.error(`Erro na tentativa ${tentativa}:`, error.message);

      // Correção automática: recarregar a página
      if (browser) {
        try {
          const pages = await browser.pages();
          if (pages.length > 0) {
            await pages[0].reload();
            fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Página recarregada para correção.\n`);
            console.log('Página recarregada para correção.');
          }
        } catch (reloadError) {
          fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Falha ao recarregar: ${reloadError.message}\n`);
        }
      }
    } finally {
      if (browser) {
        await browser.close();
        browser = null;
      }
    }
  }

  // Finalização
  const finalMsg = sucesso ? 'Testes automatizados concluídos com sucesso.' : 'Falha geral após 3 tentativas.';
  fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ${finalMsg}\n`);
  console.log(finalMsg);
})();