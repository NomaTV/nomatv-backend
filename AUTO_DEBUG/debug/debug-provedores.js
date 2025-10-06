const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  console.log('🚀 Iniciando debug da sessão de provedores...');

  const browser = await puppeteer.launch({
    headless: false,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
    slowMo: 1000
  });

  const page = await browser.newPage();

  try {
    // 1. Navegar para o sistema NomaTV
    await page.goto('http://localhost:8080/', { waitUntil: 'networkidle2' });
    console.log('📱 Sistema NomaTV carregado');

    // 2. Screenshot inicial
    await page.screenshot({ path: 'antes.png' });
    const htmlAntes = await page.content();
    fs.writeFileSync('html_antes.html', htmlAntes);
    console.log('📸 Estado inicial capturado');

    // 3. Fazer login
    await page.type('#usuario', 'admin');
    await page.type('#senha', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle2' });
    console.log('✅ Login realizado com sucesso');

    // 4. Navegar para seção de provedores
    await page.click('[data-section="provedores"]');
    await page.waitForTimeout(2000);
    console.log('🏗️ Seção de provedores acessada');

    // 5. Verificar se carregou
    const provedoresSection = await page.$('.provedores-section');
    if (!provedoresSection) {
      throw new Error('Seção de provedores não carregou');
    }

    // 6. Contar provedores existentes
    const provedoresIniciais = await page.$$eval('.provedor-row', rows => rows.length);
    console.log(`📊 Provedores existentes: ${provedoresIniciais}`);

    // 7. Testar criação de provedor
    console.log('➕ Testando criação de provedor...');
    await page.click('#btn-add-provedor');
    await page.waitForSelector('#provedor-modal', { visible: true, timeout: 5000 });

    // Preencher dados
    await page.type('#provedor-nome', 'Provedor Debug Automação');
    await page.type('#provedor-dns', 'http://debug.automacao.com:8080');
    await page.select('#provedor-tipo', 'xtream');
    await page.type('#provedor-usuario', 'debug_user');
    await page.type('#provedor-senha', 'debug_pass');

    // Salvar
    await page.click('#btn-salvar-provedor');

    // Aguardar atualização da lista
    await page.waitForFunction(
      (count) => document.querySelectorAll('.provedor-row').length > count,
      {},
      provedoresIniciais
    );

    // Verificar se foi criado
    const provedoresAposCriacao = await page.$$eval('.provedor-row', rows => rows.length);
    console.log(`📊 Provedores após criação: ${provedoresAposCriacao}`);

    if (provedoresAposCriacao > provedoresIniciais) {
      console.log('✅ Provedor criado com sucesso!');
    } else {
      console.log('❌ Falha na criação do provedor');
    }

    // 8. Testar edição
    console.log('✏️ Testando edição do provedor...');
    await page.click('.btn-editar-provedor:first-child');
    await page.waitForSelector('#provedor-modal', { visible: true });

    // Editar nome
    await page.evaluate(() => {
      document.querySelector('#provedor-nome').value = '';
    });
    await page.type('#provedor-nome', 'Provedor Editado Debug');
    await page.click('#btn-salvar-provedor');
    await page.waitForTimeout(2000);
    console.log('✅ Provedor editado com sucesso!');

    // 9. Testar exclusão
    console.log('🗑️ Testando exclusão do provedor...');
    const provedoresAntesExclusao = await page.$$eval('.provedor-row', rows => rows.length);

    await page.click('.btn-excluir-provedor:first-child');

    // Aguardar exclusão
    await page.waitForFunction(
      (count) => document.querySelectorAll('.provedor-row').length < count,
      {},
      provedoresAntesExclusao
    );

    const provedoresAposExclusao = await page.$$eval('.provedor-row', rows => rows.length);
    console.log(`📊 Provedores após exclusão: ${provedoresAposExclusao}`);

    if (provedoresAposExclusao < provedoresAntesExclusao) {
      console.log('✅ Provedor excluído com sucesso!');
    } else {
      console.log('❌ Falha na exclusão do provedor');
    }

    // 10. Screenshot final
    await page.screenshot({ path: 'depois.png' });
    const htmlDepois = await page.content();
    fs.writeFileSync('html_depois.html', htmlDepois);
    console.log('📸 Estado final capturado');

    // 11. Comparação
    const mudou = htmlAntes !== htmlDepois;
    console.log(`🔍 Mudanças detectadas: ${mudou ? 'SIM' : 'NÃO'}`);

    console.log('🎉 Debug da sessão de provedores concluído!');

  } catch (error) {
    console.error('❌ Erro durante debug:', error.message);
    await page.screenshot({ path: 'erro.png' });
  } finally {
    await browser.close();
  }
})();