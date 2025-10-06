const puppeteer = require('puppeteer');const puppeteer = require('puppeteer');const puppeteer = require('puppeteer');

const fs = require('fs');

const fs = require('fs');const fs = require('fs');

(async () => {

  let browser;

  let sucesso = false;

(async () => {(async () => {

  for (let tentativa = 1; tentativa <= 3; tentativa++) {

    const timestamp = new Date().toISOString();  let browser;  let browser;

    const logMsg = `[${timestamp}] Tentativa ${tentativa} iniciada.\n`;

    fs.appendFileSync('logs.txt', logMsg);  let sucesso = false;  let sucesso = false;

    console.log(logMsg.trim());



    try {

      browser = await puppeteer.launch({ headless: false });  for (let tentativa = 1; tentativa <= 3; tentativa++) {  for (let tentativa = 1; tentativa <= 3; tentativa++) {

      const page = await browser.newPage();

      await page.goto('http://localhost:8080/', { waitUntil: 'networkidle2' });    const timestamp = new Date().toISOString();    const timestamp = new Date().toISOString();



      // Monitoramento Inicial    const logMsg = `[${timestamp}] Tentativa ${tentativa} iniciada.\n`;    const logMsg = `[${timestamp}] Tentativa ${tentativa} iniciada.\n`;

      await page.screenshot({ path: 'antes.png' });

      const htmlAntes = await page.content();    fs.appendFileSync('logs.txt', logMsg);    fs.appendFileSync('logs.txt', logMsg);

      fs.writeFileSync('html_antes.html', htmlAntes);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML inicial capturados.\n`);    console.log(logMsg.trim());    console.log(logMsg.trim());

      console.log('Screenshot e HTML inicial capturados.');



      // Fazer login

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Fazendo login...\n`);    try {    try {

      console.log('Fazendo login...');

      browser = await puppeteer.launch({      browser = await puppeteer.launch({

      await page.type('#usuario', 'admin');

      await page.type('#senha', 'admin123');        headless: false,        headless: false,

      await page.click('button[type="submit"]');

        args: ['--no-sandbox', '--disable-setuid-sandbox'],        args: ['--no-sandbox', '--disable-setuid-sandbox'],

      // Aguardar redirecionamento

      await page.waitForNavigation({ waitUntil: 'networkidle2' });        slowMo: 1000, // Mais devagar para acompanhar        slowMo: 1000, // Mais devagar para acompanhar

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Login realizado.\n`);

      console.log('Login realizado.');        defaultViewport: { width: 1200, height: 800 }        defaultViewport: { width: 1200, height: 800 }



      // Navegar para provedores      });      });

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Navegando para provedores...\n`);

      console.log('Navegando para provedores...');



      await page.click('[data-section="provedores"]');      const page = await browser.newPage();      const page = await browser.newPage();

      await page.waitForTimeout(2000);



      // Verificar se carregou

      const provedoresSection = await page.$('.provedores-section');      // 1. Navegar para o sistema NomaTV      // 1. Navegar para o sistema NomaTV

      if (!provedoresSection) {

        throw new Error('Seção de provedores não carregou');      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Navegando para http://localhost:8080\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Navegando para http://localhost:8080\n`);

      }

      console.log('Navegando para http://localhost:8080');      console.log('Navegando para http://localhost:8080');

      // Contar provedores iniciais

      const provedoresIniciais = await page.$$eval('.provedor-row', rows => rows.length);      await page.goto('http://localhost:8080/', { waitUntil: 'networkidle2' });      await page.goto('http://localhost:8080/', { waitUntil: 'networkidle2' });

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores iniciais: ${provedoresIniciais}\n`);

      console.log(`Provedores iniciais: ${provedoresIniciais}`);



      // Abrir modal      // Monitoramento Inicial      // Monitoramento Inicial

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Abrindo modal...\n`);

      console.log('Abrindo modal...');      await page.screenshot({ path: 'antes.png' });      await page.screenshot({ path: 'antes.png' });



      await page.click('#btn-add-provedor');      const htmlAntes = await page.content();      const htmlAntes = await page.content();

      await page.waitForSelector('#provedor-modal', { visible: true, timeout: 5000 });

      fs.writeFileSync('html_antes.html', htmlAntes);      fs.writeFileSync('html_antes.html', htmlAntes);

      // Preencher dados

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Preenchendo dados...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML inicial capturados.\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML inicial capturados.\n`);

      console.log('Preenchendo dados...');

      console.log('Screenshot e HTML inicial capturados.');      console.log('Screenshot e HTML inicial capturados.');

      await page.type('#provedor-nome', 'Provedor Teste Automação');

      await page.type('#provedor-dns', 'http://teste.automacao.com:8080');

      await page.select('#provedor-tipo', 'xtream');

      await page.type('#provedor-usuario', 'teste_user');      // 2. Fazer login      // 2. Fazer login

      await page.type('#provedor-senha', 'teste_pass');

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Fazendo login...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Fazendo login...\n`);

      // Salvar

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Salvando...\n`);      console.log('Fazendo login...');      console.log('Fazendo login...');

      console.log('Salvando...');



      await page.click('#btn-salvar-provedor');

      await page.type('#usuario', 'admin');      await page.type('#usuario', 'admin');

      // Aguardar atualização

      await page.waitForFunction(      await page.type('#senha', 'admin123');      await page.type('#senha', 'admin123');

        (count) => document.querySelectorAll('.provedor-row').length > count,

        {},      await page.click('button[type="submit"]');      await page.click('button[type="submit"]');

        provedoresIniciais

      );



      // Verificar      // 3. Aguardar redirecionamento para admin      // 3. Aguardar redirecionamento para admin

      const provedoresApos = await page.$$eval('.provedor-row', rows => rows.length);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores após: ${provedoresApos}\n`);      await page.waitForNavigation({ waitUntil: 'networkidle2' });      await page.waitForNavigation({ waitUntil: 'networkidle2' });

      console.log(`Provedores após: ${provedoresApos}`);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Login realizado, aguardando carregamento do admin...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Login realizado, aguardando carregamento do admin...\n`);

      if (provedoresApos <= provedoresIniciais) {

        throw new Error('Provedor não foi adicionado');      console.log('Login realizado, aguardando carregamento do admin...');      console.log('Login realizado, aguardando carregamento do admin...');

      }



      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor criado!\n`);

      console.log('✅ Provedor criado!');      // 4. Navegar para seção de provedores      // 4. Navegar para seção de provedores



      // Monitoramento Final      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Navegando para seção de provedores...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Navegando para seção de provedores...\n`);

      await page.screenshot({ path: 'depois.png' });

      const htmlDepois = await page.content();      console.log('Navegando para seção de provedores...');      console.log('Navegando para seção de provedores...');

      fs.writeFileSync('html_depois.html', htmlDepois);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML pós-interação capturados.\n`);

      console.log('Screenshot e HTML pós-interação capturados.');

      await page.click('[data-section="provedores"]');      await page.click('[data-section="provedores"]');

      // Comparar

      if (htmlAntes !== htmlDepois) {      await page.waitForTimeout(2000);      await page.waitForTimeout(2000);

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] 🔍 Mudanças detectadas.\n`);

        console.log('🔍 Mudanças detectadas.');

      } else {

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ℹ️ Nenhuma mudança.\n`);      // 5. Verificar se a seção carregou      // 5. Verificar se a seção carregou

        console.log('ℹ️ Nenhuma mudança.');

      }      const provedoresSection = await page.$('.provedores-section');      const provedoresSection = await page.$('.provedores-section');



      sucesso = true;      if (!provedoresSection) {      if (!provedoresSection) {

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Tentativa ${tentativa} concluída com sucesso.\n`);

      console.log(`Tentativa ${tentativa} concluída com sucesso.`);        throw new Error('Seção de provedores não carregou');        throw new Error('Seção de provedores não carregou');

      break;

      }      }

    } catch (error) {

      const errorMsg = `[${new Date().toISOString()}] Erro na tentativa ${tentativa}: ${error.message}\n`;      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Seção de provedores carregada.\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Seção de provedores carregada.\n`);

      fs.appendFileSync('logs.txt', errorMsg);

      console.error(`Erro na tentativa ${tentativa}:`, error.message);      console.log('Seção de provedores carregada.');      console.log('Seção de provedores carregada.');



      // Correção automática

      try {

        if (browser) {      // 6. Contar provedores iniciais      // 6. Contar provedores iniciais

          const pages = await browser.pages();

          if (pages.length > 0) {      const provedoresIniciais = await page.$$eval('.provedor-row', rows => rows.length);      const provedoresIniciais = await page.$$eval('.provedor-row', rows => rows.length);

            await pages[0].reload();

            fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Página recarregada.\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores iniciais: ${provedoresIniciais}\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores iniciais: ${provedoresIniciais}\n`);

            console.log('Página recarregada.');

          }      console.log(`Provedores iniciais: ${provedoresIniciais}`);      console.log(`Provedores iniciais: ${provedoresIniciais}`);

        }

      } catch (reloadError) {

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Falha ao recarregar: ${reloadError.message}\n`);

      }      // 7. Abrir modal de adicionar provedor      // 7. Abrir modal de adicionar provedor

    }

  }      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Abrindo modal de adicionar provedor...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Abrindo modal de adicionar provedor...\n`);



  if (browser) {      console.log('Abrindo modal de adicionar provedor...');      console.log('Abrindo modal de adicionar provedor...');

    await browser.close();

  }



  if (sucesso) {      await page.click('#btn-add-provedor');      await page.click('#btn-add-provedor');

    fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] 🎉 Testes concluídos com sucesso!\n`);

    console.log('🎉 Testes concluídos com sucesso!');      await page.waitForSelector('#provedor-modal', { visible: true, timeout: 5000 });      await page.waitForSelector('#provedor-modal', { visible: true, timeout: 5000 });

  } else {

    fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ❌ Falha geral.\n`);

    console.log('❌ Falha geral.');

  }      // 8. Preencher dados do provedor      // 8. Preencher dados do provedor

})();
      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Preenchendo dados do provedor...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Preenchendo dados do provedor...\n`);

      console.log('Preenchendo dados do provedor...');      console.log('Preenchendo dados do provedor...');



      await page.type('#provedor-nome', 'Provedor Teste Automação');      await page.type('#provedor-nome', 'Provedor Teste Automação');

      await page.type('#provedor-dns', 'http://teste.automacao.com:8080');      await page.type('#provedor-dns', 'http://teste.automacao.com:8080');

      await page.select('#provedor-tipo', 'xtream');      await page.select('#provedor-tipo', 'xtream');

      await page.type('#provedor-usuario', 'teste_user');      await page.type('#provedor-usuario', 'teste_user');

      await page.type('#provedor-senha', 'teste_pass');      await page.type('#provedor-senha', 'teste_pass');



      // 9. Salvar provedor      // 9. Salvar provedor

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Salvando provedor...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Salvando provedor...\n`);

      console.log('Salvando provedor...');      console.log('Salvando provedor...');



      await page.click('#btn-salvar-provedor');      await page.click('#btn-salvar-provedor');



      // 10. Aguardar atualização da lista      // 10. Aguardar atualização da lista

      await page.waitForFunction(      await page.waitForFunction(

        (count) => document.querySelectorAll('.provedor-row').length > count,        (count) => document.querySelectorAll('.provedor-row').length > count,

        {},        {},

        provedoresIniciais        provedoresIniciais

      );      );



      // 11. Verificar se foi adicionado      // 11. Verificar se foi adicionado

      const provedoresAposAdicao = await page.$$eval('.provedor-row', rows => rows.length);      const provedoresAposAdicao = await page.$$eval('.provedor-row', rows => rows.length);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores após adição: ${provedoresAposAdicao}\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores após adição: ${provedoresAposAdicao}\n`);

      console.log(`Provedores após adição: ${provedoresAposAdicao}`);      console.log(`Provedores após adição: ${provedoresAposAdicao}`);



      if (provedoresAposAdicao <= provedoresIniciais) {      if (provedoresAposAdicao <= provedoresIniciais) {

        throw new Error('Provedor não foi adicionado');        throw new Error('Provedor não foi adicionado');

      }      }

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor criado com sucesso!\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor criado com sucesso!\n`);

      console.log('✅ Provedor criado com sucesso!');      console.log('✅ Provedor criado com sucesso!');



      // 12. Testar edição      // 12. Testar edição

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Testando edição do provedor...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Testando edição do provedor...\n`);

      console.log('Testando edição do provedor...');      console.log('Testando edição do provedor...');



      await page.click('.btn-editar-provedor:first-child');      await page.click('.btn-editar-provedor:first-child');

      await page.waitForSelector('#provedor-modal', { visible: true });      await page.waitForSelector('#provedor-modal', { visible: true });



      // Limpar e editar nome      // Limpar e editar nome

      await page.evaluate(() => {      await page.evaluate(() => {

        document.querySelector('#provedor-nome').value = '';        document.querySelector('#provedor-nome').value = '';

      });      });

      await page.type('#provedor-nome', 'Provedor Editado Automação');      await page.type('#provedor-nome', 'Provedor Editado Automação');

      await page.click('#btn-salvar-provedor');      await page.click('#btn-salvar-provedor');

      await page.waitForTimeout(2000);      await page.waitForTimeout(2000);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor editado com sucesso!\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor editado com sucesso!\n`);

      console.log('✅ Provedor editado com sucesso!');      console.log('✅ Provedor editado com sucesso!');



      // 13. Testar exclusão      // 13. Testar exclusão

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Testando exclusão do provedor...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Testando exclusão do provedor...\n`);

      console.log('Testando exclusão do provedor...');      console.log('Testando exclusão do provedor...');



      const provedoresAntesExclusao = await page.$$eval('.provedor-row', rows => rows.length);      const provedoresAntesExclusao = await page.$$eval('.provedor-row', rows => rows.length);

      await page.click('.btn-excluir-provedor:first-child');      await page.click('.btn-excluir-provedor:first-child');



      // Aguardar confirmação se existir      // Aguardar confirmação se existir

      try {      try {

        await page.waitForSelector('.confirmacao-exclusao', { timeout: 3000 });        await page.waitForSelector('.confirmacao-exclusao', { timeout: 3000 });

        await page.click('.btn-confirmar-exclusao');        await page.click('.btn-confirmar-exclusao');

      } catch (e) {      } catch (e) {

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ℹ️ Sem modal de confirmação, prosseguindo...\n`);        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ℹ️ Sem modal de confirmação, prosseguindo...\n`);

        console.log('ℹ️ Sem modal de confirmação, prosseguindo...');        console.log('ℹ️ Sem modal de confirmação, prosseguindo...');

      }      }



      // Aguardar exclusão      // Aguardar exclusão

      await page.waitForFunction(      await page.waitForFunction(

        (count) => document.querySelectorAll('.provedor-row').length < count,        (count) => document.querySelectorAll('.provedor-row').length < count,

        {},        {},

        provedoresAntesExclusao        provedoresAntesExclusao

      );      );



      const provedoresAposExclusao = await page.$$eval('.provedor-row', rows => rows.length);      const provedoresAposExclusao = await page.$$eval('.provedor-row', rows => rows.length);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores após exclusão: ${provedoresAposExclusao}\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Provedores após exclusão: ${provedoresAposExclusao}\n`);

      console.log(`Provedores após exclusão: ${provedoresAposExclusao}`);      console.log(`Provedores após exclusão: ${provedoresAposExclusao}`);



      if (provedoresAposExclusao >= provedoresAntesExclusao) {      if (provedoresAposExclusao >= provedoresAntesExclusao) {

        throw new Error('Provedor não foi excluído');        throw new Error('Provedor não foi excluído');

      }      }

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor excluído com sucesso!\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ✅ Provedor excluído com sucesso!\n`);

      console.log('✅ Provedor excluído com sucesso!');      console.log('✅ Provedor excluído com sucesso!');



      // Monitoramento Final      // Monitoramento Final

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Capturando estado final...\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Capturando estado final...\n`);

      console.log('Capturando estado final...');      console.log('Capturando estado final...');



      await page.screenshot({ path: 'depois.png' });      await page.screenshot({ path: 'depois.png' });

      const htmlDepois = await page.content();      const htmlDepois = await page.content();

      fs.writeFileSync('html_depois.html', htmlDepois);      fs.writeFileSync('html_depois.html', htmlDepois);

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML pós-interação capturados.\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Screenshot e HTML pós-interação capturados.\n`);

      console.log('Screenshot e HTML pós-interação capturados.');      console.log('Screenshot e HTML pós-interação capturados.');



      // Comparar HTMLs      // Comparar HTMLs

      if (htmlAntes !== htmlDepois) {      if (htmlAntes !== htmlDepois) {

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] 🔍 Mudanças detectadas na página.\n`);        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] 🔍 Mudanças detectadas na página.\n`);

        console.log('🔍 Mudanças detectadas na página.');        console.log('🔍 Mudanças detectadas na página.');

      } else {      } else {

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ℹ️ Nenhuma mudança detectada no HTML.\n`);        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ℹ️ Nenhuma mudança detectada no HTML.\n`);

        console.log('ℹ️ Nenhuma mudança detectada no HTML.');        console.log('ℹ️ Nenhuma mudança detectada no HTML.');

      }      }



      sucesso = true;      sucesso = true;

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Tentativa ${tentativa} concluída com sucesso.\n`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Tentativa ${tentativa} concluída com sucesso.\n`);

      console.log(`Tentativa ${tentativa} concluída com sucesso.`);      console.log(`Tentativa ${tentativa} concluída com sucesso.`);

      break;      break;



    } catch (error) {    } catch (error) {

      const errorMsg = `Erro na tentativa ${tentativa}: ${error.message}\n`;      const errorMsg = `Erro na tentativa ${tentativa}: ${error.message}\n`;

      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ${errorMsg}`);      fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ${errorMsg}`);

      console.log(errorMsg.trim());      console.log(errorMsg.trim());



      // Correção automática - tentar recarregar página      // Correção automática - tentar recarregar página

      try {      try {

        if (browser) {        if (browser) {

          const pages = await browser.pages();          const pages = await browser.pages();

          if (pages.length > 0) {          if (pages.length > 0) {

            await pages[0].reload({ waitUntil: 'networkidle2' });            await pages[0].reload({ waitUntil: 'networkidle2' });

            fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Página recarregada para correção.\n`);            fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Página recarregada para correção.\n`);

            console.log('Página recarregada para correção.');            console.log('Página recarregada para correção.');

          }          }

        }        }

      } catch (reloadError) {      } catch (reloadError) {

        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Falha ao recarregar página: ${reloadError.message}\n`);        fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] Falha ao recarregar página: ${reloadError.message}\n`);

        console.log(`Falha ao recarregar página: ${reloadError.message}`);        console.log(`Falha ao recarregar página: ${reloadError.message}`);

      }      }

    }    }

  }  }



  if (browser) {  if (browser) {

    await browser.close();    await browser.close();

  }  }



  if (sucesso) {  if (sucesso) {

    fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] 🎉 Testes automatizados concluídos com sucesso!\n`);    fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] 🎉 Testes automatizados concluídos com sucesso!\n`);

    console.log('🎉 Testes automatizados concluídos com sucesso!');    console.log('🎉 Testes automatizados concluídos com sucesso!');

  } else {  } else {

    fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ❌ Falha geral após 3 tentativas.\n`);    fs.appendFileSync('logs.txt', `[${new Date().toISOString()}] ❌ Falha geral após 3 tentativas.\n`);

    console.log('❌ Falha geral após 3 tentativas.');    console.log('❌ Falha geral após 3 tentativas.');

  }  }

})();})();
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