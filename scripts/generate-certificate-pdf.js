#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer-core');

function parseArgs(argv) {
  const options = {};

  for (let index = 2; index < argv.length; index += 1) {
    const arg = argv[index];
    if (!arg.startsWith('--')) {
      continue;
    }

    const key = arg.slice(2);
    const value = argv[index + 1];
    if (!value || value.startsWith('--')) {
      options[key] = true;
      continue;
    }

    options[key] = value;
    index += 1;
  }

  return options;
}

function fail(message) {
  process.stderr.write(`${message}\n`);
  process.exit(1);
}

async function main() {
  const args = parseArgs(process.argv);
  const targetUrl = String(args.url || '').trim();
  const outputPath = String(args.output || '').trim();
  const chromePath = String(args.chrome || '').trim();
  const userDataDir = String(args['user-data-dir'] || '').trim();

  if (!targetUrl) {
    fail('Informe --url com a página do certificado.');
  }

  if (!outputPath) {
    fail('Informe --output com o caminho de saída do PDF.');
  }

  if (!chromePath) {
    fail('Informe --chrome com o executável do Chrome.');
  }

  if (!userDataDir) {
    fail('Informe --user-data-dir com o diretório temporário do Chrome.');
  }

  const outputDir = path.dirname(outputPath);
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  if (!fs.existsSync(userDataDir)) {
    fs.mkdirSync(userDataDir, { recursive: true });
  }

  let browser;

  try {
    browser = await puppeteer.launch({
      executablePath: chromePath,
      headless: 'new',
      userDataDir,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-crash-reporter',
        '--no-first-run',
        '--no-default-browser-check',
        '--font-render-hinting=medium',
      ],
      defaultViewport: {
        width: 1400,
        height: 990,
        deviceScaleFactor: 1,
      },
    });

    const page = await browser.newPage();
    await page.goto(targetUrl, {
      waitUntil: 'domcontentloaded',
      timeout: 20000,
    });

    await page.emulateMediaType('screen');
    await page.waitForSelector('.certificate-card, .certificate-pending', {
      timeout: 15000,
    });
    await page.evaluate(async () => {
      if (document.fonts && document.fonts.ready) {
        await document.fonts.ready;
      }
    });

    await page.pdf({
      path: outputPath,
      format: 'A4',
      landscape: true,
      printBackground: true,
      margin: {
        top: '0',
        right: '0',
        bottom: '0',
        left: '0',
      },
      preferCSSPageSize: false,
    });
  } finally {
    if (browser) {
      await browser.close();
    }
  }
}

main().catch((error) => {
  fail(error instanceof Error ? error.stack || error.message : String(error));
});
