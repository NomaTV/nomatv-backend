const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  console.log('🔍 Testando apenas o login...');

  const browser = await puppeteer.launch({
    headless: false,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
    slowMo: 500
  });

  const page = await browser.newPage();

  try {
    await page.goto('http://localhost:8080/', { waitUntil: 'networkidle2' });
    console.log('📱 Página inicial carregada');

    // Verificar se os campos existem
    const usuarioField = await page.$('#usuario');
    const senhaField = await page.$('#senha');
    const submitBtn = await page.$('button[type="submit"]');

    console.log('Campos encontrados:', {
      usuario: !!usuarioField,
      senha: !!senhaField,
      submit: !!submitBtn
    });

    if (!usuarioField || !senhaField || !submitBtn) {
      throw new Error('Campos de login não encontrados');
    }

    await page.screenshot({ path: 'login-inicial.png' });
    console.log('📸 Screenshot da tela de login');

    // Fazer login com timeout menor
    await page.type('#usuario', 'admin');
    await page.type('#senha', 'admin123');

    console.log('📝 Dados preenchidos, clicando em submit...');
    await page.click('button[type="submit"]');

    // Aguardar redirecionamento com timeout menor
    await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 });
    console.log('✅ Login realizado - redirecionado para admin');

    await page.screenshot({ path: 'login-sucesso.png' });
    console.log('📸 Screenshot do painel admin');

    // Verificar se estamos no admin
    const currentUrl = page.url();
    console.log('URL atual:', currentUrl);

    if (currentUrl.includes('admin') || currentUrl.includes('painel')) {
      console.log('✅ Login validado com sucesso!');
    } else {
      console.log('⚠️ Login realizado, mas URL não indica painel admin');
    }

  } catch (error) {
    console.error('❌ Erro no login:', error.message);
    await page.screenshot({ path: 'login-erro.png' });
  } finally {
    await browser.close();
  }
})();