#!/usr/bin/env node

const QRCode = require('qrcode');

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

async function main() {
  const args = parseArgs(process.argv);
  const text = String(args.text || '').trim();
  const size = Math.max(96, Math.min(1024, Number(args.size || 200) || 200));

  if (!text) {
    throw new Error('Informe --text com o conteúdo do QR Code.');
  }

  const svg = await QRCode.toString(text, {
    type: 'svg',
    width: size,
    margin: 1,
    errorCorrectionLevel: 'M',
    color: {
      dark: '#10203C',
      light: '#FFFFFFFF',
    },
  });

  process.stdout.write(svg);
}

main().catch((error) => {
  const message = error instanceof Error ? error.message : String(error);
  process.stderr.write(`${message}\n`);
  process.exit(1);
});
