const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  console.log('🚀 Iniciando teste básico...');

  const browser = await puppeteer.launch({ headless: false });
  const page = await browser.newPage();

  try {
    await page.goto('http://localhost:8080/');
    console.log('📱 Página carregada');

    await page.screenshot({ path: 'antes.png' });
    console.log('📸 Screenshot inicial salvo');

    await page.type('#usuario', 'admin');
    await page.type('#senha', 'admin123');
    await page.click('button[type="submit"]');

    await page.waitForNavigation();
    console.log('✅ Login realizado');

    await page.screenshot({ path: 'depois.png' });
    console.log('📸 Screenshot após login salvo');

    console.log('🎉 Teste básico concluído!');

  } catch (error) {
    console.error('❌ Erro:', error.message);
  } finally {
    await browser.close();
  }
})();