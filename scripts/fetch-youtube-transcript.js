#!/usr/bin/env node

const { fetchTranscript } = require('youtube-transcript');

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

function writeJson(payload, exitCode = 0) {
  process.stdout.write(`${JSON.stringify(payload)}\n`);
  process.exit(exitCode);
}

function fail(message, code = 'TRANSCRIPT_ERROR', details = null) {
  writeJson({
    sucesso: false,
    code,
    mensagem: message,
    details,
  }, 1);
}

async function main() {
  const args = parseArgs(process.argv);
  const videoId = String(args['video-id'] || args.videoId || '').trim();
  const language = String(args.lang || args.language || 'pt').trim() || 'pt';

  if (!videoId) {
    fail('Informe --video-id com o identificador do YouTube.', 'INVALID_VIDEO_ID');
  }

  try {
    const items = await fetchTranscript(videoId, { lang: language });
    if (!Array.isArray(items) || items.length === 0) {
      fail('Nenhuma transcrição foi encontrada para este vídeo.', 'TRANSCRIPT_EMPTY');
    }

    const transcript = items
      .map((item) => String(item?.text || '').trim())
      .filter(Boolean)
      .join(' ')
      .replace(/\s+/g, ' ')
      .trim();

    if (!transcript) {
      fail('A transcrição retornou vazia após normalização.', 'TRANSCRIPT_EMPTY');
    }

    writeJson({
      sucesso: true,
      transcript,
      language,
      segments: items.length,
    });
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    fail('Falha ao obter a transcrição do YouTube.', 'TRANSCRIPT_FETCH_FAILED', message);
  }
}

main().catch((error) => {
  const message = error instanceof Error ? error.message : String(error);
  fail('Erro inesperado ao executar a extração da transcrição.', 'TRANSCRIPT_UNEXPECTED', message);
});
