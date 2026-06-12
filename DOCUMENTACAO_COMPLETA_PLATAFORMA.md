# Documentação Completa da Plataforma EAD

## 1. Objetivo deste documento

Este arquivo foi criado para te ajudar a entender a plataforma de forma profunda e organizada, com foco em defesa de projeto. A ideia aqui não é só dizer o nome dos arquivos, mas explicar:

- qual é a arquitetura da aplicação;
- como uma requisição percorre o sistema;
- como funcionam autenticação, cursos, aulas, quizzes, progresso, certificados, backups e IA;
- para que serve cada arquivo principal do projeto;
- quais pastas são código-fonte, quais são dados gerados e quais são dependências externas.

Este projeto é uma plataforma EAD construída principalmente com:

- PHP no backend;
- MySQL como banco de dados;
- HTML, CSS e JavaScript no frontend;
- arquitetura MVC adaptada;
- XAMPP/Apache como ambiente comum de execução;
- Node.js em partes específicas, como geração de PDF e transcrição.

## 2. Visão geral da arquitetura

O sistema segue uma organização parecida com MVC:

- `public/` concentra a entrada web da aplicação.
- `app/controllers/` contém a lógica de orquestração dos casos de uso.
- `app/models/` contém a lógica de acesso a dados e regras ligadas ao banco.
- `app/views/` contém as telas renderizadas em HTML.
- `app/services/` concentra serviços especializados, como IA, mídia, storage e logs de backup.
- `config/` contém bootstrap, helpers, ambiente e conexão com banco.
- `migrations/` contém o schema e evoluções de banco.
- `scripts/` contém tarefas de manutenção, diagnóstico, automação e geração de artefatos.

Na prática, o arquivo mais importante da execução web é `public/index.php`. Ele funciona como um front controller: recebe a requisição, carrega configurações, processa ações `POST`, decide qual view renderizar e monta o layout.

## 3. Fluxo real de funcionamento da aplicação

### 3.1 Entrada da requisição

1. O navegador acessa a aplicação por `public/index.php` ou por uma URL amigável tratada por `public/router.php`.
2. `public/index.php` inicia a sessão, carrega banco, helpers, configuração e autoload.
3. O sistema interpreta a rota por `$_GET['page']` ou pela URL amigável capturada em `$_GET['url']`.
4. Se a requisição for `POST`, a função `processarAcao()` decide qual ação executar.
5. Se a requisição for de página, o arquivo escolhe a view certa, monta os dados necessários e renderiza tudo dentro de `app/views/layout.php`.

### 3.2 Fluxo das ações

As ações mais importantes processadas em `public/index.php` incluem:

- login;
- registro;
- recuperação de senha;
- criação e edição de curso;
- criação, edição e remoção de módulos;
- criação, edição e remoção de aulas;
- criação e resposta de quizzes;
- matrícula;
- atualização de progresso;
- emissão e download de certificados;
- exportação e importação de backups;
- ações administrativas;
- perguntas para o tutor de IA.

### 3.3 Fluxo de camadas

O padrão mais comum é:

1. a rota chama um controller;
2. o controller valida sessão, papel do usuário e contexto;
3. o controller chama um model ou service;
4. o model consulta ou altera o banco;
5. o resultado volta ao controller;
6. o `index.php` redireciona, devolve JSON ou renderiza uma view.

## 4. Módulos funcionais do sistema

### 4.1 Autenticação

O sistema possui:

- cadastro de aluno e professor;
- login com senha com `password_hash` e `password_verify`;
- controle de tentativas de login;
- bloqueio temporário por excesso de falhas;
- recuperação de senha com token;
- sessão PHP;
- controle de permissões por papel: `aluno`, `professor`, `admin`.

### 4.2 Cursos e módulos

O professor pode:

- criar cursos;
- editar cursos;
- definir categoria;
- anexar thumbnail;
- trabalhar com curso de módulo único ou com múltiplos módulos.

O sistema possui uma compatibilidade importante:

- se o curso for simples, ele garante a existência de um módulo padrão;
- se o curso for modular, ele permite criar vários módulos e reordená-los.

### 4.3 Aulas

As aulas podem ter:

- conteúdo textual;
- vídeo;
- PDF;
- arquivo;
- resumo;
- áudio associado;
- transcrição;
- conteúdo inteligente derivado da aula.

Além disso, o aluno pode marcar a aula como concluída, mas em aulas com quiz obrigatório a conclusão só é liberada após aprovação.

### 4.4 Quizzes

O sistema de avaliação é um dos módulos mais ricos do projeto:

- quiz de aula;
- quiz de módulo;
- quiz final do curso;
- controle de tentativas;
- nota mínima;
- pontuação total fechando em 20 valores;
- embaralhamento de perguntas e respostas;
- tempo limite;
- histórico de tentativas;
- cálculo de progresso e elegibilidade para certificado.

### 4.5 Certificados

O módulo de certificados:

- verifica se o aluno é elegível;
- emite certificado por módulo e por curso;
- gera código único;
- permite validação pública;
- permite renderização para PDF;
- revoga certificado quando o estado do aluno deixa de cumprir os requisitos.

### 4.6 Backups, exportação e importação

O projeto também possui um subsistema de portabilidade:

- exportação de dados do aluno;
- exportação do professor;
- exportação global;
- manifestos e checksums;
- logs de backup;
- preferências de backup automático;
- restauração segura por ZIP.

### 4.7 IA de apoio pedagógico

Há um assistente de IA por aula que:

- usa conteúdo da aula e transcrição;
- valida acesso do aluno;
- registra histórico;
- possui cache, limites e fallback;
- apoia dúvidas do estudante e geração de conteúdo auxiliar.

## 5. Banco de dados

O schema principal está em `migrations/schema.sql`. As tabelas centrais são:

- `users`: usuários do sistema;
- `courses`: cursos;
- `lessons`: aulas;
- `enrollments`: matrículas;
- `quizzes`: avaliações;
- `questions`: perguntas dos quizzes;
- `quiz_results`: estrutura legada de resultados;
- `quiz_attempts`: tentativas detalhadas;
- `quiz_attempt_answers`: respostas por tentativa;
- `lesson_progress`: progresso por aula;
- `lesson_ai_logs`: logs do tutor de IA;
- `messages`: mensagens internas;
- `certificates`: certificados emitidos.

O projeto também evolui o schema dinamicamente em alguns models/controllers com métodos `ensureSchema()`. Isso mostra que a aplicação foi sendo expandida sem depender apenas de migrações manuais.

## 6. Explicação por pasta e por arquivo

## 6.1 Arquivos da raiz

### `README.md`
Documento principal do projeto. Resume proposta, stack e instruções de uso.

### `SUMMARY.md`
Resumo de entrega do projeto. Mostra visão executiva do que foi implementado.

### `INSTALL.md`
Guia geral de instalação da plataforma.

### `INSTALL_ON_XAMPP.md`
Guia específico para executar o sistema em XAMPP.

### `DEVELOPMENT.md`
Documento de apoio ao desenvolvimento e manutenção.

### `API.md`
Referência funcional das interfaces e fluxos expostos pela aplicação.

### `QA_CHECKLIST.md`
Checklist de qualidade e validação do sistema.

### `SLIDES.md`
Conteúdo de apoio para apresentação.

### `slides.html`
Versão HTML de apresentação.

### `ROTEIRO-DEFESA-PLATAFORMA-EAD.md`
Roteiro orientado para defesa do projeto.

### `QUESTIONARIO_DEFESA_FINAL.md`
Possíveis perguntas de banca com foco mais completo.

### `QUESTIONARIO_DEFESA_RESUMIDO.md`
Versão resumida das perguntas de defesa.

### `CORRIGIR_LOGIN.md`
Documento de apoio ligado a correções no fluxo de login.

### `SOLUCAO_LOGIN.md`
Explica soluções aplicadas ao problema de autenticação.

### `README_DEPLOY.txt`
Observações rápidas de deploy.

### `Dockerfile`
Define uma forma de empacotar a aplicação em contêiner.

### `composer.json`
Define dependências PHP do projeto. A principal é `phpmailer/phpmailer`, usada para email.

### `composer.lock`
Arquivo travado de versões do Composer.

### `package.json`
Define dependências Node.js e scripts auxiliares. Principais usos:

- `puppeteer-core` para PDF;
- `qrcode` para QR code;
- `youtube-transcript` para transcrições.

### `package-lock.json`
Lock de dependências Node.

### `gerar_hash.php`
Arquivo utilitário para gerar hash de senha, normalmente usado em suporte ou manutenção.

### `.env`
Configurações sensíveis da aplicação: banco, URLs, SMTP, chaves e integrações.

### `.env.example`
Modelo de variáveis de ambiente para onboarding do projeto.

### `.gitignore`
Define quais arquivos não devem ir ao Git.

### `.gitattributes`
Configuração auxiliar do Git.

### `.codex`
Arquivo interno do ambiente de assistência/codex.

## 6.2 Pasta `config/`

### `config/env.php`
Carrega variáveis do `.env` para `getenv`, `$_ENV` e `$_SERVER`. É a base do sistema de configuração do projeto.

### `config/database.php`
Cria a conexão PDO com MySQL. Também decide defaults de desenvolvimento quando roda localmente.

### `config/app.php`
Define constantes globais como:

- `BASE_URL`;
- `APP_URL`;
- `UPLOADS_DIR`;
- `CERTIFICATE_PDF_SECRET`;
- `CHROME_BIN`;
- `NODE_BIN`.

É o arquivo que adapta o sistema ao ambiente.

### `config/helpers.php`
Biblioteca de funções auxiliares reutilizadas no projeto. Exemplos:

- redirecionamento;
- sanitização;
- validação de email e senha;
- geração de slug;
- upload;
- formatação de dados;
- utilitários de sessão.

### `config/autoload.php`
Carrega o `.env`, inclui o autoload do Composer quando existir e registra um autoloader simples para `controllers`, `models` e `services`.

## 6.3 Pasta `public/`

### `public/index.php`
É o coração da aplicação web. Faz:

- bootstrap;
- leitura da rota;
- suporte a URL amigável;
- detecção de AJAX;
- proteção CSRF;
- controle de timer e estado visual dos quizzes;
- renderização de views;
- processamento central das ações `POST`.

É o arquivo mais importante da plataforma.

### `public/router.php`
Roteador usado para servir arquivos estáticos quando existirem e redirecionar o restante para `index.php`.

### `public/image.php`
Endpoint de imagem. Normalmente serve imagens com algum tipo de mediação ou transformação.

### `public/diagnostico.php`
Arquivo público de diagnóstico da aplicação.

### `public/corrigir.php`
Arquivo público voltado a rotinas de correção/suporte.

### `public/inicializar.php`
Arquivo web para inicialização controlada do sistema.

### `public/migrate_images_to_webp.php`
Script web utilitário para migrar imagens para WebP.

### `public/restore_uploads.php`
Script de restauração de uploads.

### `public/smtp_probe.php`
Arquivo de teste/diagnóstico de SMTP.

### `public/uploads/`
Diretório de uploads reais do sistema:

- thumbnails;
- avatares;
- imagens usadas em slides ou conteúdo.

Os arquivos aqui são dados gerados pelo uso da plataforma, não código-fonte.

## 6.4 Pasta `app/controllers/`

### `app/controllers/AuthController.php`
Controla autenticação e segurança de conta:

- registro;
- login;
- recuperação de senha;
- bloqueio por falhas;
- normalização e validação de email;
- eventos de segurança;
- atualização automática do schema de segurança do usuário.

### `app/controllers/CourseController.php`
Orquestra operações ligadas a cursos:

- criar curso;
- listar cursos;
- buscar;
- obter detalhes completos;
- matricular aluno;
- atualizar e deletar curso;
- listar cursos do professor;
- montar estrutura modular do curso;
- integrar certificados no detalhe do curso.

### `app/controllers/LessonController.php`
Controla o ciclo de vida das aulas:

- criar;
- obter;
- listar;
- atualizar;
- deletar;
- marcar e desmarcar conclusão;
- recalcular progresso;
- integrar com quiz obrigatório;
- integrar com certificados;
- integrar com serviços de conteúdo inteligente e mídia.

### `app/controllers/QuizController.php`
É um dos controllers mais importantes do projeto. Ele cuida de:

- criação de quiz de aula, módulo e final;
- validação da estrutura do quiz;
- garantia de unicidade de certos quizzes;
- criação das perguntas;
- obtenção do quiz com questões;
- correção de respostas;
- cálculo de nota;
- persistência de tentativas;
- integração com progresso e conclusão do curso.

### `app/controllers/DashboardController.php`
Monta os painéis de cada papel:

- dashboard do aluno com progresso;
- dashboard do professor com estatísticas pedagógicas;
- dashboard do admin com visão sistêmica.

### `app/controllers/AdminController.php`
Controller administrativo:

- estatísticas gerais;
- listagem de usuários;
- listagem e paginação de cursos;
- listagem e paginação de quizzes;
- alteração de status de curso;
- exclusão de usuários;
- relatórios de alunos por curso.

### `app/controllers/ModuleController.php`
Gerencia módulos do curso:

- criar;
- atualizar;
- mover;
- obter;
- garantir que apenas o dono do curso altere a estrutura.

### `app/controllers/CertificateController.php`
Controller especializado em certificados:

- sincronização do estado dos certificados;
- carregamento da página do aluno;
- validação pública;
- URL segura de renderização;
- emissão de token temporário para PDF;
- download em PDF.

### `app/controllers/ExportController.php`
Responsável por exportação e backup:

- exportar dados de aluno;
- exportar dados de professor;
- exportar dados globais;
- montar manifest, checksums e arquivos;
- disponibilizar download;
- registrar logs de backup.

### `app/controllers/ImportController.php`
Responsável por restauração:

- receber ZIP;
- validar estrutura;
- validar manifest;
- conferir checksums;
- restaurar cursos, módulos, aulas, quizzes, progresso, tentativas e certificados;
- lidar com backups legados;
- proteger contra arquivos inseguros.

## 6.5 Pasta `app/models/`

### `app/models/User.php`
Camada de dados de usuários:

- busca por email ou ID;
- criação e atualização de conta;
- reset de senha;
- registro e limpeza de falhas de login;
- listagem e contagem por papel.

### `app/models/Course.php`
Camada de dados de cursos:

- criar;
- obter por ID;
- listar;
- listar por professor;
- atualizar;
- deletar;
- buscar;
- contar;
- garantir a coluna `course_structure`.

### `app/models/Module.php`
Camada de dados de módulos:

- listar por curso;
- obter por ID;
- criar;
- atualizar;
- mover;
- obter ou criar módulo padrão;
- sincronizar curso legado com a nova estrutura modular.

Esse model é importante porque faz a ponte entre a arquitetura antiga e a nova arquitetura com múltiplos módulos.

### `app/models/Lesson.php`
Camada de dados das aulas:

- criar;
- obter por ID;
- listar por curso;
- listar por módulo;
- atualizar;
- salvar transcrição;
- salvar conteúdo inteligente;
- deletar;
- contar;
- reordenar;
- expandir o schema quando necessário.

### `app/models/Enrollment.php`
Camada de dados de matrículas e progresso agregado:

- matricular;
- verificar matrícula;
- obter cursos do aluno;
- obter alunos por curso;
- atualizar progresso;
- marcar conclusão;
- remover matrícula;
- contar alunos;
- montar relatórios do professor com métricas combinadas.

### `app/models/Quiz.php`
É o model mais rico do sistema de avaliação. Ele cuida de:

- criação de quizzes;
- criação de questões;
- consulta de quizzes por aula, módulo e curso;
- consulta para aluno com tentativas e aprovação;
- gravação de tentativas;
- gravação de respostas;
- cálculo de progresso;
- cálculo de nota final;
- status de quiz obrigatório;
- emissão de certificado se elegível;
- relatórios administrativos e do professor;
- suporte a colunas e schema evolutivo.

### `app/models/Certificate.php`
Camada de regras de certificados:

- sincronizar certificados do curso;
- gerar certificado;
- calcular elegibilidade;
- listar certificados do usuário;
- buscar por código;
- montar payload público;
- montar payload para PDF;
- registrar tentativas de verificação;
- garantir schema e índices.

## 6.6 Pasta `app/services/`

### `app/services/BackupLogService.php`
Registra logs de backup e preferências de backup automático. Também cria as tabelas auxiliares de logs e preferências.

### `app/services/LessonAiTutorService.php`
Implementa o tutor de IA por aula:

- valida acesso;
- usa contexto da aula;
- trabalha com cache;
- evita spam e duplicidade;
- registra logs;
- lida com falhas de API.

### `app/services/LessonContentService.php`
Serviço de conteúdo inteligente da aula. Gera ou recupera material de leitura derivado da aula.

### `app/services/LessonTranscriptService.php`
Serviço de transcrição da aula, especialmente útil para conteúdo em vídeo.

### `app/services/LessonMediaService.php`
Gerencia limpeza e manutenção de mídia associada às aulas, como áudios e arquivos relacionados.

### `app/services/StorageService.php`
Abstrai armazenamento de arquivos usados por backup, mídia e outros artefatos.

## 6.7 Pasta `app/views/`

As views representam as telas do sistema. Em geral, cada arquivo corresponde a uma página ou componente de página.

### `app/views/layout.php`
Layout principal da aplicação. Define HTML base, metadados, navbar, toasts, modal global e inclusão dos CSS/JS.

### `app/views/home.php`
Página inicial pública da plataforma.

### `app/views/login.php`
Tela de login.

### `app/views/registro.php`
Tela de registro de aluno.

### `app/views/registro-professor.php`
Tela de registro de professor/instrutor.

### `app/views/forgot-password.php`
Tela para solicitar recuperação de senha.

### `app/views/reset-password.php`
Tela para redefinir a senha com token válido.

### `app/views/auth-status.php`
Tela/fragmento de retorno visual de estados de autenticação.

### `app/views/cursos.php`
Listagem de cursos disponíveis.

### `app/views/curso-detail.php`
Tela de detalhe de curso, com visão expandida.

### `app/views/course-card.php`
Componente reutilizável para card de curso.

### `app/views/criar-curso.php`
Tela de criação de curso.

### `app/views/editar-curso.php`
Tela de edição de curso.

### `app/views/gerenciar-curso.php`
Tela de gestão do curso pelo professor, normalmente com módulos, aulas e quizzes.

### `app/views/criar-modulo.php`
Formulário/tela para criar módulo.

### `app/views/editar-modulo.php`
Formulário/tela para editar módulo.

### `app/views/criar-aula.php`
Tela de criação de aula.

### `app/views/editar-aula.php`
Tela de edição de aula.

### `app/views/aula.php`
Tela de consumo da aula pelo aluno.

### `app/views/minhas-aulas.php`
Tela de listagem de aulas em contexto do usuário.

### `app/views/criar-quiz.php`
Tela de criação/configuração de quiz.

### `app/views/quiz.php`
Tela de execução e resposta do quiz.

### `app/views/atividades.php`
Tela relacionada às atividades e avaliações.

### `app/views/dashboard-aluno.php`
Painel do aluno com progresso e cursos matriculados.

### `app/views/dashboard-professor.php`
Painel do professor com métricas pedagógicas e operacionais.

### `app/views/dashboard-admin.php`
Painel do administrador com estatísticas gerais.

### `app/views/admin-cursos.php`
Tela administrativa de gestão de cursos.

### `app/views/admin-quizzes.php`
Tela administrativa de gestão de quizzes.

### `app/views/meus-cursos.php`
Tela do professor para os cursos que criou.

### `app/views/meus-alunos.php`
Tela do professor com visão consolidada dos seus alunos.

### `app/views/alunos-curso.php`
Tela detalhada dos alunos de um curso específico.

### `app/views/perfil.php`
Tela de perfil do usuário.

### `app/views/certificacao-info.php`
Tela explicativa sobre certificação.

### `app/views/certificado.php`
Tela de exibição e validação de certificado, tanto para dono quanto para verificação pública.

## 6.8 Pasta `public/js/`

### `public/js/main.js`
JavaScript principal da aplicação. Inicializa:

- navegação;
- toasts e alertas;
- forms com loading;
- exportação de dados;
- experiência de autenticação;
- dropdowns;
- menu mobile;
- confirmações;
- sincronização de progresso;
- construtores e execução de quizzes.

Também oferece `csrfFetch`, importante para segurança em requisições AJAX.

### `public/js/ui.js`
Script de interações gerais da interface:

- mostrar/ocultar senha;
- animação de cards;
- animação de barras de progresso;
- tooltips;
- lazy loading;
- smooth scroll;
- histórico de busca.

### `public/js/pages/home.js`
Interações específicas da página inicial.

### `public/js/pages/dashboard.js`
Interações específicas dos dashboards.

### `public/js/pages/aula.js`
Comportamentos específicos da tela de aula.

### `public/js/pages/aula-modos.js`
Lógica relacionada aos modos de exibição/consumo da aula.

### `public/js/pages/gerenciar-curso.js`
Interações específicas da gestão de curso pelo professor.

### `public/js/pages/quiz.js`
Lógica específica da página de quiz.

### `public/js/pages/ai-chat.js`
Frontend do chat/tutor de IA na experiência da aula.

## 6.9 Pasta `public/css/`

### `public/css/variables.css`
Variáveis CSS globais do design system.

### `public/css/system.css`
Base visual do sistema, com tokens e componentes estruturais.

### `public/css/style.css`
Estilo global principal da plataforma.

### `public/css/responsive.css`
Ajustes de responsividade para diferentes tamanhos de tela.

### `public/css/pages/home.css`
Estilos específicos da home.

### `public/css/pages/dashboard.css`
Estilos dos dashboards.

### `public/css/pages/cursos.css`
Estilos da listagem de cursos.

### `public/css/pages/curso.css`
Estilos da página de detalhe do curso.

### `public/css/pages/criar-curso.css`
Estilos da criação de curso.

### `public/css/pages/gerenciar-curso.css`
Estilos da gestão do curso.

### `public/css/pages/criar-aula.css`
Estilos da criação de aula.

### `public/css/pages/editar-aula.css`
Estilos da edição de aula.

### `public/css/pages/aula.css`
Estilos da visualização de aula.

### `public/css/pages/aula-modos.css`
Estilos dos modos de leitura/consumo da aula.

### `public/css/pages/criar-quiz.css`
Estilos da criação/configuração de quiz.

### `public/css/pages/quiz.css`
Estilos da execução do quiz.

### `public/css/pages/admin.css`
Estilos das páginas administrativas.

### `public/css/pages/perfil.css`
Estilos da página de perfil.

### `public/css/pages/meus-cursos.css`
Estilos da área de cursos do professor.

### `public/css/pages/meus-alunos.css`
Estilos da visão consolidada dos alunos.

### `public/css/pages/alunos-curso.css`
Estilos da página de alunos por curso.

### `public/css/pages/ai-chat.css`
Estilos do tutor de IA/chat.

### `public/css/pages/certificacao-info.css`
Estilos da página informativa de certificação.

### `public/css/pages/certificado.css`
Estilos do certificado em tela.

### `public/css/pages/certificado-pdf.css`
Estilos específicos do certificado quando renderizado para PDF.

## 6.10 Pasta `scripts/`

Esses arquivos são utilitários operacionais e de manutenção.

### `scripts/initialize.php`
Inicialização técnica do sistema em ambiente local ou de suporte.

### `scripts/apply_migration.php`
Aplicação de migrações ou correções de banco.

### `scripts/validate.php`
Validação geral de estrutura/estado do projeto.

### `scripts/diagnostico.php`
Diagnóstico técnico do sistema.

### `scripts/corrigir.php`
Rotina de correção assistida.

### `scripts/maintenance/diagnostico.php`
Versão de diagnóstico dentro do conjunto de manutenção.

### `scripts/maintenance/corrigir.php`
Versão de correção dentro do conjunto de manutenção.

### `scripts/automated_tests.php`
Automação de testes em PHP.

### `scripts/run_tests.ps1`
Script PowerShell para executar testes em ambiente Windows.

### `scripts/check_css.php`
Validação ou inspeção de CSS.

### `scripts/check_portability.php`
Verifica portabilidade do projeto e do pacote de migração/backup.

### `scripts/build_migration_package.php`
Monta pacote de migração ou transporte da plataforma.

### `scripts/auto_backup.php`
Executa backups automáticos usando preferências salvas.

### `scripts/test_smtp.php`
Testa envio por SMTP.

### `scripts/prune_lesson_audio.php`
Limpa áudios antigos ou órfãos das aulas.

### `scripts/fetch-youtube-transcript.js`
Busca transcrição de vídeo do YouTube para apoiar conteúdo da aula.

### `scripts/generate-certificate-pdf.js`
Gera PDF de certificado via Node/Puppeteer.

### `scripts/generate-certificate-qr.js`
Gera QR code associado ao certificado.

## 6.11 Pasta `migrations/`

### `migrations/schema.sql`
Schema principal da base de dados.

### `migrations/000_full_schema_plus_migrations.sql`
Versão consolidada do schema com migrações embutidas.

### `migrations/009_add_video_id_to_lessons.sql`
Migração para adicionar suporte a `video_id` nas aulas.

### `migrations/010_lesson_transcript_and_ai_logs.sql`
Migração para transcrição de aula e logs do tutor de IA.

### `migrations/CORRECAO_SENHAS.sql`
Script de correção relacionado a senhas.

## 6.12 Pasta `storage/`

Essa pasta guarda dados gerados pela plataforma, não código-fonte principal.

### `storage/backups/`
Contém backups gerados pelo sistema:

- por aluno;
- por professor;
- por curso;
- globais.

### `storage/imports/`
Contém áreas temporárias de staging de restauração:

- ZIP recebido;
- manifest;
- checksums;
- JSONs de dados;
- arquivos restauráveis.

## 6.13 Pasta `logs/`

### `logs/app.log`
Log técnico da aplicação.

### `logs/mail-outbox/`
Saídas de email geradas em ambiente de teste/desenvolvimento.

## 6.14 Pastas de dependências externas

### `vendor/`
Dependências PHP instaladas pelo Composer, como PHPMailer. São bibliotecas de terceiros.

### `node_modules/`
Dependências Node.js instaladas pelo npm. São bibliotecas de terceiros usadas por geração de PDF, QR code e transcrição.

Essas pastas não costumam ser explicadas arquivo por arquivo numa defesa porque não representam lógica autoral do projeto.

## 7. Decisões técnicas importantes para defender

Se precisares explicar por que o projeto é sólido, estes pontos são fortes:

- há separação razoável entre controller, model, view e services;
- o sistema usa PDO com prepared statements;
- há proteção CSRF;
- há controle de sessão;
- existe bloqueio de login por tentativas;
- há arquitetura modular de cursos;
- existe avaliação pedagógica com múltiplos tipos de quiz;
- a emissão de certificado depende de elegibilidade real;
- há verificação pública de certificado;
- existe exportação/importação estruturada;
- o sistema possui automação auxiliar com scripts;
- existe suporte a IA para enriquecer a aprendizagem.

## 8. O que mais vale a pena explicar oralmente na defesa

Se quiseres causar uma impressão forte, eu sugeriria destacar estes fluxos:

### Fluxo 1: login e segurança

Explica que o sistema não faz apenas login básico. Ele:

- valida credenciais;
- controla tentativas;
- bloqueia abuso;
- permite reset de senha;
- protege ações críticas com CSRF.

### Fluxo 2: curso modular

Explica que o professor cria um curso que pode funcionar em:

- modo simples com módulo padrão;
- modo multi-módulo com estrutura progressiva.

### Fluxo 3: progresso + quiz + certificado

Esse é um dos melhores fluxos para demonstrar maturidade:

1. o aluno faz aula;
2. se houver quiz obrigatório, precisa ser aprovado;
3. o progresso é recalculado;
4. o curso verifica elegibilidade;
5. o certificado pode ser emitido automaticamente;
6. o certificado pode ser validado publicamente.

### Fluxo 4: backup e restauração

Mostra que a plataforma pensou em portabilidade e continuidade operacional:

- exporta dados estruturados;
- valida checksums;
- restaura com segurança;
- mantém logs.

## 9. Limites e observações honestas

Para defender bem, também é bom mostrar maturidade técnica dizendo o que o projeto faz e o que ainda pode evoluir:

- o `public/index.php` concentra muita responsabilidade e poderia ser dividido futuramente em roteador + handlers;
- parte do schema evolui por `ensureSchema()`, o que é prático, mas pode ser refinado com migrações mais centralizadas;
- existem arquivos utilitários e de suporte que indicam crescimento incremental do sistema;
- há compatibilidade com partes legadas, especialmente em quizzes e estrutura de cursos.

Isso não enfraquece a defesa. Pelo contrário: mostra que entendes a realidade do software.

## 10. Resumo final para memorização rápida

Se precisares resumir a plataforma em poucas frases:

> A Plataforma EAD é um sistema web em PHP/MySQL com arquitetura MVC adaptada, que permite gerir usuários, cursos, módulos, aulas, quizzes, progresso e certificados. O núcleo de execução fica em `public/index.php`, os controllers orquestram os fluxos, os models concentram acesso a dados e regras, as views renderizam a interface e os services tratam funcionalidades avançadas como IA, mídia, certificados e backups. Além do ensino online, a plataforma também oferece segurança, portabilidade de dados e verificação pública de certificados.

---

Se quiseres, no próximo passo eu posso criar uma segunda versão mais estratégica, chamada por exemplo `GUIA_DEFESA_ORAL.md`, com:

- perguntas e respostas para banca;
- explicação de cada fluxo em linguagem simples;
- resumo de 3 minutos, 5 minutos e 10 minutos;
- pontos fortes técnicos para apresentar em voz alta.

---

## 11. Explicação realmente detalhada por arquivo

Nesta segunda parte, a ideia é ir além da descrição curta e explicar como cada arquivo participa do funcionamento do projeto.

Observação importante:

- vou detalhar principalmente os arquivos autorais do projeto;
- arquivos gerados automaticamente, uploads, `vendor/` e `node_modules/` não serão explicados um por um porque são dependências externas ou dados gerados;
- quando um arquivo for mais simples ou de apoio, vou explicar o seu papel no ecossistema do sistema;
- quando o arquivo for central, a explicação será mais profunda.

## 11.1 Arquivos da raiz

### `README.md`

Este é o documento de entrada do projeto. O papel dele é apresentar a plataforma de forma geral para qualquer pessoa que abra o repositório. Em termos práticos, ele normalmente serve para:

- explicar o que o sistema faz;
- listar tecnologias usadas;
- mostrar como instalar;
- resumir funcionalidades;
- orientar alguém que vai rodar o projeto pela primeira vez.

Mesmo não participando da execução do software, este arquivo é importante porque funciona como a “porta de entrada documental” do projeto.

### `SUMMARY.md`

Este arquivo resume o projeto de forma executiva. Ele não é um arquivo de execução, mas ajuda a comunicar o escopo implementado, o estado de conclusão e uma visão de alto nível. É útil para apresentação, revisão rápida e alinhamento sobre o que já existe no sistema.

### `INSTALL.md`

Este arquivo é focado na instalação. O seu papel é guiar a configuração do ambiente, banco e dependências. Para quem pega o projeto depois, ele reduz atrito de onboarding. Em defesa, mostra preocupação com reprodutibilidade.

### `INSTALL_ON_XAMPP.md`

É uma especialização do guia de instalação. Como o projeto foi muito orientado a ambiente local com XAMPP, esse arquivo explica como adaptar o sistema a esse contexto. Ele é importante porque boa parte do fluxo da aplicação assume caminhos e comportamento típicos desse ambiente.

### `DEVELOPMENT.md`

Este arquivo serve mais para manutenção e evolução do sistema. Ele ajuda a equipa a entender convenções, rotinas e decisões do desenvolvimento. Em termos de engenharia, mostra que o projeto não foi pensado só para “funcionar”, mas também para ser trabalhado com continuidade.

### `API.md`

Embora o sistema não seja uma API REST pura, este arquivo documenta comportamentos e pontos de integração relevantes. Isso ajuda a entender o contrato lógico de algumas partes do sistema, especialmente fluxos AJAX e ações internas.

### `QA_CHECKLIST.md`

O papel deste arquivo é apoiar validação funcional. Ele organiza verificações de qualidade que ajudam a testar login, cursos, quizzes, dashboards e outros fluxos do sistema. Em defesa, é um bom indicador de maturidade.

### `SLIDES.md`

É um material de apresentação. Seu papel não é técnico dentro da execução, mas pedagógico: traduz o projeto em narrativa de defesa e apresentação.

### `slides.html`

Cumpre função parecida com `SLIDES.md`, mas em formato navegável no navegador. É útil quando se quer apresentar o projeto visualmente sem depender de outro software de slides.

### `ROTEIRO-DEFESA-PLATAFORMA-EAD.md`

Este arquivo organiza a narrativa da defesa. Ele pega o sistema e transforma em discurso. É importante porque faz a ponte entre o código e a apresentação oral.

### `QUESTIONARIO_DEFESA_FINAL.md`

Funciona como banco de perguntas de defesa mais abrangente. É um artefato de preparação, não de execução. Ajuda a transformar conhecimento técnico em respostas articuladas.

### `QUESTIONARIO_DEFESA_RESUMIDO.md`

É a versão compacta do questionário completo. Serve para revisão rápida e estudo de última hora.

### `CORRIGIR_LOGIN.md`

Este arquivo documenta problemas e/ou correções ligadas ao login. Seu valor é histórico e técnico: mostra que a autenticação foi tratada com atenção.

### `SOLUCAO_LOGIN.md`

Complementa o anterior. Normalmente registra a solução consolidada para o fluxo de login e ajuda a manter memória técnica do problema resolvido.

### `README_DEPLOY.txt`

É um arquivo curto e pragmático para deploy. Em vez de documentação extensa, ele costuma guardar observações rápidas úteis no momento da publicação.

### `Dockerfile`

Este arquivo descreve como o projeto poderia ser empacotado em contêiner. Mesmo que o ambiente principal tenha sido XAMPP, a existência dele mostra uma preocupação com portabilidade de infraestrutura.

### `composer.json`

Esse arquivo define as dependências PHP do sistema. O papel mais importante dele é:

- declarar bibliotecas externas necessárias;
- permitir instalação com Composer;
- organizar autoload por padrão PSR-4.

No caso deste projeto, ele mostra que o backend usa pelo menos `PHPMailer`, o que reforça a presença de fluxos de email.

### `composer.lock`

É o congelamento das versões instaladas pelo Composer. Garante reprodutibilidade do ambiente PHP.

### `package.json`

Esse é o equivalente do ecossistema Node. Ele mostra que Node.js é usado como apoio ao projeto, especialmente para:

- gerar certificado em PDF;
- gerar QR code;
- obter transcrição do YouTube.

Ele não controla o sistema web principal, mas apoia funcionalidades complementares importantes.

### `package-lock.json`

Congela versões do ecossistema Node para manter consistência entre ambientes.

### `gerar_hash.php`

É um arquivo utilitário. Seu objetivo é facilitar geração de hash de senha quando necessário, por exemplo em suporte, manutenção ou criação de contas de teste. Não pertence ao fluxo normal do usuário final, mas ajuda na operação técnica.

### `.env`

É um dos arquivos mais importantes do ponto de vista de configuração. Ele guarda valores variáveis por ambiente, como:

- acesso ao banco;
- URLs base;
- SMTP;
- segredos;
- tokens de endpoints auxiliares.

O sistema foi preparado para ler esse arquivo via `config/env.php`.

### `.env.example`

É o modelo público do `.env`. Ele existe para mostrar quais variáveis precisam ser configuradas sem expor segredos reais.

### `.gitignore`

Define o que não deve ir para o controlo de versão. Isso é crucial para evitar subir:

- segredos;
- uploads;
- logs;
- dependências;
- arquivos temporários.

### `.gitattributes`

Ajuda o Git em aspectos de formatação, fim de linha e tratamento de arquivos. Não afeta o runtime da aplicação, mas ajuda a manter consistência do projeto.

## 11.2 Pasta `config/`

### `config/env.php`

Este arquivo é o núcleo da estratégia de configuração do sistema. Ele lê o arquivo `.env`, interpreta cada linha e injeta os valores em:

- `getenv()`;
- `$_ENV`;
- `$_SERVER`.

Na prática, isso permite que o resto do sistema consulte variáveis de ambiente sem precisar reler o arquivo várias vezes. Ele também expõe funções auxiliares como:

- `env_value()`;
- `env_bool()`;
- `env_int()`.

Ou seja, este arquivo transforma o `.env` num mecanismo de configuração utilizável em toda a aplicação.

### `config/database.php`

Este arquivo cria a conexão PDO com o banco. O funcionamento dele é importante:

1. carrega o ambiente;
2. lê `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`, `DB_NAME`;
3. se estiver em ambiente local/desenvolvimento, aplica defaults amigáveis;
4. valida se a configuração mínima existe;
5. cria a conexão PDO com:
   - `ERRMODE_EXCEPTION`;
   - `FETCH_ASSOC`;
   - prepared statements nativos.

Na prática, este arquivo é o ponto onde a aplicação sai do mundo da configuração e passa a ter acesso real aos dados.

### `config/app.php`

Este arquivo monta o contexto global da aplicação. Ele define constantes críticas como:

- `BASE_URL`;
- `APP_URL`;
- `UPLOADS_DIR`;
- `CERTIFICATE_PDF_SECRET`;
- `CHROME_BIN`;
- `NODE_BIN`.

O que isso significa na prática:

- `BASE_URL` ajuda a construir links e assets;
- `APP_URL` ajuda em URLs absolutas, como certificado público;
- `UPLOADS_DIR` decide onde os uploads ficam fisicamente;
- `CERTIFICATE_PDF_SECRET` protege a renderização do PDF;
- `CHROME_BIN` e `NODE_BIN` ajudam scripts externos a funcionar corretamente.

Ou seja, `config/app.php` adapta o projeto ao ambiente em que está rodando.

### `config/helpers.php`

Este arquivo é uma biblioteca de utilidades compartilhadas. Ele concentra pequenas funções que seriam repetidas em muitos lugares. Pelo código e pelo uso no projeto, ele participa de tarefas como:

- redirecionamento;
- acesso à sessão;
- sanitização;
- validação de email e senha;
- geração de slug;
- transliteração;
- formatação;
- upload de arquivos;
- manipulação de imagens;
- possivelmente CSRF, logs, emails e resolução de uploads.

Na arquitetura do projeto, esse arquivo não é “apenas conveniência”: ele reduz duplicação e padroniza comportamentos transversais.

### `config/autoload.php`

Este arquivo conecta o projeto com o carregamento automático de classes. O fluxo dele é:

1. carrega o ambiente;
2. tenta incluir o autoload do Composer;
3. registra um autoloader simples para procurar classes em:
   - `app/controllers/`;
   - `app/models/`;
   - `app/services/`.

Na prática, ele elimina a necessidade de `require` manual em muitos trechos e torna o código mais limpo.

## 11.3 Pasta `public/`

### `public/index.php`

Este é o arquivo mais importante do sistema. Ele é o front controller da aplicação.

O que ele faz, em ordem lógica:

1. ativa exibição de erros;
2. inicia sessão;
3. carrega banco, app, helpers e autoload;
4. interpreta URLs amigáveis específicas, como certificados;
5. decide a página por `$_GET['page']`;
6. se a requisição for `POST`, chama `processarAcao()`;
7. se for navegação de página, prepara o conteúdo e escolhe a view;
8. renderiza a view dentro do layout;
9. se o layout principal falhar, usa um layout de recuperação.

Além disso, ele contém várias funções centrais:

- detecção de AJAX;
- proteção de exportações;
- validação de CSRF;
- gerenciamento de estado do quiz com tempo limite;
- gerenciamento da ordem visual das perguntas;
- renderização de view;
- renderização de layout;
- processamento central das ações POST.

Esse arquivo concentra muito poder. Em defesa, é correto dizer que ele é o coração do sistema, mas também uma das partes que mais mereceria refatoração futura por excesso de responsabilidade.

### `public/router.php`

Este arquivo é pequeno, mas importante. Ele funciona como ponte entre o servidor e o `index.php`.

Seu comportamento é:

- lê a URL requisitada;
- verifica se o caminho aponta para um arquivo real estático;
- se existir, deixa o servidor servir o arquivo;
- se não existir, encaminha a requisição ao `index.php`.

Em outras palavras, ele ajuda a suportar URLs mais limpas sem quebrar acesso direto a CSS, JS, imagens e outros arquivos reais.

### `public/image.php`

Este arquivo é um endpoint de entrega de imagem com redimensionamento e cache. O fluxo dele é:

1. lê o parâmetro `src`;
2. resolve o caminho real do upload;
3. recebe opções como:
   - largura (`w`);
   - altura (`h`);
   - ajuste (`fit`);
   - qualidade (`q`);
4. decide um diretório de cache;
5. monta uma chave única de cache baseada na imagem e nos parâmetros;
6. se a versão transformada não existir, abre a imagem original;
7. redimensiona conforme `contain` ou `cover`;
8. salva JPEG em cache;
9. devolve a imagem já processada.

Por que isso é importante?

- evita entregar arquivos brutos em todos os contextos;
- melhora performance;
- padroniza miniaturas;
- reduz custo de reprocessamento ao usar cache.

### `public/diagnostico.php`

Hoje este arquivo não executa um diagnóstico público. Ele foi deliberadamente desativado por segurança.

O comportamento atual dele é:

- devolver 404;
- mostrar mensagem de recurso desativado;
- opcionalmente registrar tentativa de acesso.

Isso é importante porque mostra maturidade: um endpoint útil em desenvolvimento foi bloqueado em contexto web para não expor detalhes sensíveis.

### `public/corrigir.php`

Assim como o `diagnostico.php`, este arquivo foi desativado na pasta pública. O comportamento atual é:

- responder 404;
- informar que foi desativado;
- registrar tentativa de acesso.

Isso indica que já existiu ou foi pensado um fluxo de correção exposto na web, mas que foi endurecido por segurança.

### `public/inicializar.php`

Esse arquivo é um caso muito interessante para defesa. Ele mostra a evolução do projeto.

Hoje o comportamento real é:

- responder 404;
- informar que a inicialização via web foi desativada;
- orientar a usar `scripts/initialize.php` via CLI;
- registrar tentativa de acesso.

Mas o próprio arquivo ainda contém, abaixo do `exit`, uma implementação antiga de inicialização web, que:

- recriava banco;
- importava schema;
- ajustava senhas de teste;
- verificava login;
- listava usuários.

Ou seja, este arquivo é ao mesmo tempo:

- um endpoint hoje bloqueado;
- um registro histórico de uma rotina antiga;
- uma evidência de endurecimento da segurança.

### `public/smtp_probe.php`

Este arquivo é um endpoint técnico e protegido para testar envio de email. Ele não faz parte do fluxo do aluno comum.

Como ele funciona:

1. carrega ambiente e app;
2. exige um token secreto (`SMTP_PROBE_TOKEN`);
3. valida um destinatário (`to`);
4. monta um email de teste;
5. chama a função de envio de email;
6. imprime o resultado em texto puro junto com dados de configuração.

É um arquivo útil para diagnosticar infraestrutura de email em produção ou staging.

### `public/migrate_images_to_webp.php`

Este endpoint foi criado para migração controlada de imagens para WebP.

Fluxo:

1. exige token de acesso;
2. consulta tabelas que guardam referências a imagem;
3. ignora arquivos já em WebP;
4. converte a imagem;
5. atualiza a referência no banco;
6. imprime relatório de atualizados, ignorados e erros.

É um endpoint de manutenção, não de uso funcional do aluno.

### `public/restore_uploads.php`

Este endpoint restaura a pasta de uploads a partir de uma origem base, também protegido por token.

Fluxo:

1. valida token;
2. procura `bootstrap_uploads`;
3. garante existência da pasta de destino;
4. percorre os arquivos de origem;
5. copia apenas o que ainda não existe;
6. gera relatório final.

Isso é útil em cenários de recuperação de ambiente, bootstrap ou portabilidade.

## 11.4 Pasta `app/controllers/`

### `app/controllers/AuthController.php`

Este controller controla o ciclo de vida da conta do usuário.

Funções principais:

- cadastrar usuário;
- validar regras de email;
- validar força da senha;
- processar login;
- controlar tentativas falhadas;
- bloquear temporariamente a conta;
- iniciar sessão do usuário;
- solicitar recuperação de senha;
- validar token de reset;
- redefinir senha.

Ele também tem uma responsabilidade interessante: garantir evolução da tabela `users` com campos de segurança, por exemplo:

- `email_verified`;
- `verification_token`;
- `reset_token`;
- `login_attempts`;
- `locked_until`.

Ou seja, não é só um controller de formulário. Ele incorpora uma camada importante de segurança de conta.

### `app/controllers/CourseController.php`

Este controller é a porta de entrada funcional dos cursos.

Ele faz:

- validar se o usuário atual é professor para criar curso;
- encaminhar criação para o model;
- sincronizar estrutura de módulos;
- listar cursos paginados;
- buscar cursos;
- obter um curso completo com:
   - aulas;
   - quizzes;
   - módulos;
   - resumo de certificados;
- matricular aluno;
- atualizar curso;
- deletar curso;
- listar cursos do professor.

O ponto mais importante aqui é que ele não retorna apenas dados crus do curso. Ele monta o “curso em contexto”, incluindo:

- estrutura modular;
- quizzes finais;
- quantidade de alunos;
- visão do certificado do usuário atual.

### `app/controllers/LessonController.php`

Este controller governa o ciclo das aulas.

Funções principais:

- criar aula;
- obter aula;
- listar aulas do curso;
- atualizar aula;
- apagar aula;
- marcar aula como concluída;
- desmarcar aula;
- recalcular progresso do curso;
- integrar transcrição, conteúdo inteligente e mídia.

O detalhe mais importante dele é a regra de negócio: se uma aula tiver quiz obrigatório, o aluno não pode marcá-la como concluída sem aprovação. Isso faz deste controller um ponto central da coerência pedagógica do sistema.

### `app/controllers/QuizController.php`

Este controller orquestra a avaliação.

Ele é responsável por:

- criar quiz;
- validar se o professor é dono do curso;
- diferenciar quiz de aula, módulo e final;
- impedir duplicidade de certos quizzes;
- validar perguntas;
- garantir soma de 20 valores;
- obter quiz com questões;
- corrigir respostas;
- persistir tentativas e resultados.

Ele é um controller rico em regra de negócio, porque a avaliação não é apenas cadastro. Há muita validação estrutural e pedagógica antes de um quiz ser aceito.

### `app/controllers/DashboardController.php`

O papel deste controller é compor dados de interface analítica para cada perfil.

No aluno, ele monta:

- cursos matriculados;
- progresso;
- cursos em andamento;
- cursos concluídos.

No professor, ele monta:

- total de cursos;
- total de alunos;
- total de aulas;
- quizzes;
- média de turma;
- taxa de aprovação;
- perguntas críticas.

No admin, ele agrega estatísticas do sistema.

Ou seja, ele não faz persistência direta; ele compõe visão analítica.

### `app/controllers/AdminController.php`

Este controller concentra ações administrativas.

Ele permite:

- contar usuários;
- contar professores e alunos;
- contar cursos;
- obter resumo de quizzes;
- listar usuários;
- listar cursos;
- listar quizzes administrativamente;
- alterar status de curso;
- apagar usuário;
- gerar relatórios de alunos por curso.

Ele atua como camada de gestão institucional do sistema.

### `app/controllers/ModuleController.php`

Este controller existe para dar identidade própria à estrutura modular.

Ele:

- cria módulo;
- atualiza módulo;
- move módulo para cima/baixo;
- obtém módulo específico;
- valida se o usuário atual é dono do curso.

Ele separa a gestão estrutural do curso da gestão de conteúdo propriamente dita.

### `app/controllers/CertificateController.php`

Este controller liga o mundo do aluno, da verificação pública e do PDF.

Funções:

- sincronizar emissão/revogação de certificados;
- montar dados da página do aluno;
- montar dados da verificação pública;
- emitir token temporário para render de PDF;
- validar token de render;
- gerar URL segura de renderização;
- baixar certificado em PDF.

Ele é importante porque transforma a regra de negócio do certificado em experiência de uso e distribuição segura.

### `app/controllers/ExportController.php`

Este controller é responsável por transformar dados do sistema em pacote exportável.

Ele:

- exporta backup do aluno;
- exporta backup do professor;
- exporta backup global;
- coleta cursos, módulos, aulas, quizzes, progresso, tentativas e certificados;
- monta JSONs estruturados;
- empacota tudo;
- registra logs de backup;
- prepara links de download.

Ele mostra que o projeto pensou em continuidade e portabilidade, não apenas em uso online.

### `app/controllers/ImportController.php`

É o par lógico do `ExportController`.

Ele:

- recebe upload de backup ZIP;
- valida tamanho e extensão;
- extrai com segurança;
- verifica manifest;
- verifica checksums;
- normaliza estrutura legada quando necessário;
- restaura cursos, módulos, aulas, quizzes, progresso, matrículas e certificados;
- registra logs da restauração.

Este controller é um dos mais complexos do projeto, porque mexe com integridade, segurança e compatibilidade histórica.

## 11.5 Pasta `app/models/`

### `app/models/User.php`

Este model encapsula operações de persistência de usuários. Ele lida com:

- busca por email;
- busca por ID;
- verificação de disponibilidade de email;
- criação de usuário;
- atualização de perfil;
- atualização de senha;
- reset token;
- falhas de login;
- listagem e contagem.

Na prática, ele representa a entidade usuário no banco e fornece os métodos base que o `AuthController` utiliza.

### `app/models/Course.php`

Este model representa a entidade curso.

Ele faz:

- inserir curso;
- buscar curso por ID;
- listar cursos por status, paginação e busca;
- listar cursos por professor;
- atualizar curso;
- deletar curso;
- contar cursos;
- garantir a coluna `course_structure`.

Seu papel é persistir o curso e oferecer consultas reutilizáveis para os controllers.

### `app/models/Module.php`

Este model representa a estrutura modular. O seu diferencial é que ele não é só CRUD. Ele também:

- cria módulo padrão quando necessário;
- sincroniza cursos antigos com a nova estrutura modular;
- move módulos por ordem;
- corrige referências de aulas e quizzes quando um curso ainda não estava totalmente modularizado.

É um model de compatibilidade e estrutura.

### `app/models/Lesson.php`

Este model representa a persistência das aulas. Além do CRUD, ele também:

- guarda `module_id`;
- guarda `resumo`;
- guarda transcrição;
- guarda conteúdo inteligente;
- guarda áudio e metadados de storage;
- suporta reordenação.

Ele é o ponto onde a aula deixa de ser apenas “texto” e passa a comportar múltiplos recursos associados.

### `app/models/Enrollment.php`

Este model representa a matrícula do aluno num curso.

Ele faz:

- matricular;
- verificar matrícula;
- obter cursos do aluno;
- obter alunos do curso;
- atualizar progresso;
- marcar conclusão;
- remover matrícula;
- contar alunos;
- montar visão agregada do professor sobre seus alunos.

Na prática, ele é a cola entre usuário e curso, com informação de progresso e conclusão.

### `app/models/Quiz.php`

Este é um dos modelos mais ricos do projeto.

Ele:

- cria quizzes;
- cria perguntas;
- busca quiz com questões;
- lista quizzes por aula, módulo e curso;
- lista quizzes em contexto do aluno;
- grava tentativas;
- grava respostas;
- calcula melhor resultado;
- calcula status de quiz obrigatório;
- recalcula progresso;
- calcula nota final de curso;
- ajuda em emissão de certificado;
- fornece relatórios pedagógicos e administrativos;
- evolui o schema quando necessário.

É um model central de regra acadêmica.

### `app/models/Certificate.php`

Este model governa certificados do ponto de vista de dados e regras.

Ele:

- sincroniza certificados do curso;
- emite certificado de curso e módulo;
- calcula elegibilidade;
- calcula nota;
- procura certificados por código;
- monta payload para exibição pública;
- monta payload para PDF;
- registra verificação;
- revoga certificado quando necessário;
- evolui schema e índices.

É o cérebro da certificação.

## 11.6 Pasta `app/services/`

### `app/services/BackupLogService.php`

Este serviço não é apenas logging simples. Ele:

- cria tabelas auxiliares se necessário;
- registra logs de backup;
- guarda token de acesso ao arquivo;
- persiste preferências de backup automático;
- decide se um usuário está elegível para rodada automática de backup.

Ele traz governança operacional ao subsistema de backup.

### `app/services/LessonAiTutorService.php`

Esse serviço implementa o tutor de IA por aula.

Ele:

- valida usuário e aula;
- verifica acesso do aluno;
- sanitiza pergunta e histórico;
- monta contexto da aula;
- usa cache;
- detecta pergunta duplicada;
- registra logs;
- devolve resposta contextualizada ou fallback.

É um serviço de alto nível e mostra sofisticação funcional do projeto.

### `app/services/LessonContentService.php`

Este serviço é responsável por gerar ou recuperar conteúdo inteligente ligado à aula. Em termos práticos, ele pega o conteúdo da aula e transforma isso em material adicional de apoio.

Seu papel é aumentar a profundidade pedagógica da plataforma.

### `app/services/LessonTranscriptService.php`

Este serviço trata a transcrição da aula, especialmente quando existe vídeo associado. Ele serve como ponte entre o conteúdo audiovisual e a camada textual do sistema.

Isso ajuda tanto em IA quanto em acessibilidade e consulta.

### `app/services/LessonMediaService.php`

Este serviço organiza limpeza e manutenção de mídia associada às aulas. Por exemplo, quando uma aula muda ou é apagada, ele ajuda a remover mídia órfã.

Seu papel é evitar acúmulo de arquivos inúteis e manter consistência física dos recursos.

### `app/services/StorageService.php`

Este serviço abstrai o armazenamento de arquivos. Em vez de espalhar lógica de caminho e escrita por vários pontos do sistema, ele centraliza parte desse comportamento.

Isso é importante para backup, mídia, certificados e portabilidade.

## 11.7 Pasta `app/views/`

As views são a face visível do sistema. Mesmo quando não concentram regra de negócio, cada uma delas é importante porque materializa um caso de uso.

### `app/views/layout.php`

É a moldura principal da aplicação. Ele:

- define HTML base;
- injeta CSS e JS;
- monta navbar;
- mostra flashes/toasts;
- declara modal global;
- controla diferença entre modo normal e PDF.

Toda página importante passa por ele.

### `app/views/home.php`

É a página de entrada institucional. Seu papel é apresentar a plataforma ao visitante, comunicar valor e encaminhar para cursos, login e registro.

### `app/views/login.php`

É a interface do fluxo de autenticação. Ela coleta email e senha e envia para o backend. Embora simples visualmente, é uma view crítica porque materializa o ponto de entrada do usuário autenticado.

### `app/views/registro.php`

É a view de cadastro do aluno. Seu papel é recolher dados iniciais de identidade e credencial.

### `app/views/registro-professor.php`

É similar à anterior, mas voltada ao papel de professor. Isso deixa explícito na interface que existem trilhas diferentes de entrada no sistema.

### `app/views/forgot-password.php`

É a interface de solicitação de recuperação de senha.

### `app/views/reset-password.php`

É a interface de redefinição com token válido. Fecha o ciclo de recuperação.

### `app/views/auth-status.php`

Serve como feedback visual de estados de autenticação ou fluxos relacionados.

### `app/views/cursos.php`

É a listagem do catálogo. Aqui o sistema apresenta vários cursos de forma navegável e pesquisável.

### `app/views/curso-detail.php`

É a visão aprofundada de um curso. Tipicamente mostra descrição, estrutura, módulos, aulas e possivelmente certificados/resumo para o usuário atual.

### `app/views/course-card.php`

É um componente parcial reutilizável para representar cursos em listas. Ele evita duplicar a marcação visual do curso em múltiplas views.

### `app/views/criar-curso.php`

É a interface de criação de curso pelo professor.

### `app/views/editar-curso.php`

É a interface de manutenção do curso já existente.

### `app/views/gerenciar-curso.php`

É uma das views mais estratégicas para o professor. Nela, o professor gerencia a estrutura interna do curso:

- módulos;
- aulas;
- quizzes;
- edição organizacional do conteúdo.

### `app/views/criar-modulo.php`

É a interface dedicada à criação de módulo no curso multi-módulo.

### `app/views/editar-modulo.php`

É a interface de atualização do módulo.

### `app/views/criar-aula.php`

Interface de criação de aula, onde o professor define o conteúdo e o tipo da aula.

### `app/views/editar-aula.php`

Interface de manutenção de aula já criada.

### `app/views/aula.php`

É a view central do consumo de conteúdo pelo aluno. Ela apresenta o conteúdo em si e integra:

- visualização da aula;
- progresso;
- interação com quiz;
- possivelmente chat de IA.

### `app/views/minhas-aulas.php`

É uma tela de apoio de navegação ou listagem de aulas em contexto do usuário.

### `app/views/criar-quiz.php`

É a interface de criação/configuração da avaliação. Aqui o professor define o tipo de quiz e suas perguntas.

### `app/views/quiz.php`

É a view de execução do quiz pelo aluno. Ela consome o estado preparado no backend e mostra perguntas, opções, tempo e envio.

### `app/views/atividades.php`

É uma tela voltada ao agrupamento de atividades e avaliações.

### `app/views/dashboard-aluno.php`

É o painel operacional do aluno. Resume o que ele precisa fazer e onde está no percurso.

### `app/views/dashboard-professor.php`

É o painel analítico do professor, mostrando produção e desempenho dos cursos/alunos.

### `app/views/dashboard-admin.php`

É o painel estratégico do administrador, com visão global do sistema.

### `app/views/admin-cursos.php`

Tela administrativa de cursos, com foco em supervisão e status.

### `app/views/admin-quizzes.php`

Tela administrativa para visão e gestão de avaliações.

### `app/views/meus-cursos.php`

Área do professor para navegar pelos próprios cursos.

### `app/views/meus-alunos.php`

Visão consolidada dos alunos do professor.

### `app/views/alunos-curso.php`

Visão detalhada dos alunos de um curso específico, útil para acompanhamento pedagógico.

### `app/views/perfil.php`

Tela de gestão de perfil pessoal do usuário.

### `app/views/certificacao-info.php`

Página explicativa do modelo de certificação da plataforma.

### `app/views/certificado.php`

View do certificado em si. Ela precisa atender dois contextos:

- dono do certificado;
- verificação pública.

Além disso, a mesma base visual participa da renderização para PDF.

## 11.8 Pasta `public/js/`

### `public/js/main.js`

É o orquestrador JavaScript da interface. Ele inicializa os comportamentos principais da página depois do carregamento. Não é um arquivo de detalhe estético; ele governa comportamento:

- navegação;
- toasts;
- forms com loading;
- exportações;
- quizzes;
- dropdowns;
- menu mobile;
- sincronização de progresso.

Também é nele que aparece o `csrfFetch`, importante para segurança nas chamadas AJAX.

### `public/js/ui.js`

Esse arquivo cuida da camada de experiência visual e microinteração:

- mostrar/ocultar senha;
- animações de card;
- animações de progresso;
- tooltips;
- lazy loading;
- smooth scroll.

Ele é mais focado em sensação de interface do que em lógica acadêmica.

### `public/js/pages/home.js`

É um script específico da home, usado para comportamentos exclusivos da página inicial.

### `public/js/pages/dashboard.js`

Concentra interações específicas dos dashboards.

### `public/js/pages/aula.js`

Concentra comportamentos específicos do consumo da aula, o que é importante porque a tela de aula é uma das mais ricas do sistema.

### `public/js/pages/aula-modos.js`

Dá suporte a modos de visualização ou leitura da aula.

### `public/js/pages/gerenciar-curso.js`

Apoia a experiência de gestão do curso pelo professor, provavelmente em reordenação, formulários e interações estruturais.

### `public/js/pages/quiz.js`

Apoia a experiência do quiz no lado do navegador.

### `public/js/pages/ai-chat.js`

É o lado cliente do tutor de IA, responsável por enviar perguntas, mostrar respostas e gerenciar a experiência do chat.

## 11.9 Pasta `public/css/`

Aqui os arquivos são mais declarativos, mas também merecem explicação funcional.

### `public/css/variables.css`

Centraliza variáveis de design, como cores, espaçamentos e tokens.

### `public/css/system.css`

Define uma base visual e estrutural reutilizável do sistema.

### `public/css/style.css`

É a folha global principal. Dá identidade geral à plataforma.

### `public/css/responsive.css`

Aplica adaptação para diferentes tamanhos de tela.

### `public/css/pages/*.css`

Cada arquivo dessa pasta especializa o visual de uma página concreta. Isso permite que cada contexto do sistema tenha uma apresentação adequada sem poluir o CSS global.

Exemplo:

- `home.css`: home pública;
- `dashboard.css`: dashboards;
- `curso.css`: detalhe de curso;
- `quiz.css`: experiência de avaliação;
- `certificado.css`: exibição do certificado;
- `certificado-pdf.css`: impressão/renderização PDF.

## 11.10 Pasta `scripts/`

Esses arquivos não são usados no fluxo comum do aluno, mas são fundamentais para manutenção, automação e suporte.

### `scripts/initialize.php`

É a forma segura e atual de inicializar o banco via CLI, substituindo a antiga abordagem pública.

### `scripts/apply_migration.php`

Aplica migrações ou correções de schema.

### `scripts/validate.php`

Valida integridade ou consistência de partes do sistema.

### `scripts/diagnostico.php`

Executa diagnóstico técnico fora da pasta pública.

### `scripts/corrigir.php`

Executa rotinas corretivas fora da exposição web.

### `scripts/maintenance/diagnostico.php`

Variante organizada para manutenção.

### `scripts/maintenance/corrigir.php`

Variante organizada para correção em manutenção.

### `scripts/automated_tests.php`

Automatiza parte da validação funcional/técnica.

### `scripts/run_tests.ps1`

Versão de apoio para testes em Windows/PowerShell.

### `scripts/check_css.php`

Ajuda a inspecionar questões ligadas ao CSS.

### `scripts/check_portability.php`

Ajuda a verificar se o projeto ou pacote está portátil entre ambientes.

### `scripts/build_migration_package.php`

Empacota artefatos necessários para migração/transferência.

### `scripts/auto_backup.php`

Executa a rotina de backup automático.

### `scripts/test_smtp.php`

Valida o envio SMTP por linha de comando.

### `scripts/prune_lesson_audio.php`

Remove ou limpa áudios de aula desnecessários.

### `scripts/fetch-youtube-transcript.js`

Obtém transcrições de vídeo para enriquecer o conteúdo da aula.

### `scripts/generate-certificate-pdf.js`

Converte a visualização HTML do certificado em PDF.

### `scripts/generate-certificate-qr.js`

Gera QR code ligado ao certificado.

## 11.11 Pasta `migrations/`

### `migrations/schema.sql`

É o blueprint principal da base de dados.

### `migrations/000_full_schema_plus_migrations.sql`

É uma fotografia consolidada do schema já com evoluções incorporadas.

### `migrations/009_add_video_id_to_lessons.sql`

Mostra que o modelo de aula evoluiu para suportar referência específica de vídeo.

### `migrations/010_lesson_transcript_and_ai_logs.sql`

Mostra a expansão do sistema para transcrição e IA.

### `migrations/CORRECAO_SENHAS.sql`

Registra ajuste ligado a credenciais/senhas.

## 11.12 O que não faz sentido explicar arquivo por arquivo

Há grupos que não são produtivos para defesa detalhada por unidade:

### `vendor/`

São bibliotecas externas. Explica-se o papel geral, não cada arquivo interno.

### `node_modules/`

Também são dependências externas.

### `public/uploads/`

São dados gerados, não código-fonte.

### `storage/backups/`

São artefatos produzidos pelo sistema.

### `storage/imports/`

São áreas de staging de restauração, com dados transitórios.

### `logs/`

São saídas operacionais e de monitoramento.

## 11.13 Como apresentar esta parte na defesa

Se alguém disser “quero entender como os arquivos se conectam”, uma resposta forte é:

> O projeto começa em `public/index.php`, que carrega configuração, sessão e banco. A partir daí ele chama controllers conforme a rota e a ação. Os controllers aplicam as regras de negócio e usam os models para persistência. Quando a funcionalidade é mais especializada, como IA, backup ou mídia, entram os services. No fim, as views renderizam a interface dentro de `app/views/layout.php`. Em paralelo, a pasta `public/js` cuida da interatividade da página, `public/css` cuida da apresentação, `scripts/` cuida da operação técnica e `migrations/` cuida da estrutura da base.

Isso mostra visão sistêmica do código, não só memorização de nomes.

## 12. Raio-X dos arquivos mais densos

Esta secção aprofunda os arquivos que mais concentram regra de negócio e que mais provavelmente serão alvo de perguntas de banca.

Os arquivos escolhidos foram:

- `public/index.php`
- `app/controllers/QuizController.php`
- `app/models/Quiz.php`
- `app/models/Certificate.php`
- `app/controllers/ImportController.php`

## 12.1 Raio-X de `public/index.php`

Este arquivo merece uma leitura especial porque ele não é só “a página inicial do sistema”. Ele é o ponto central de orquestração da aplicação.

### Papel dele

O papel principal do `public/index.php` é funcionar como:

- front controller;
- roteador principal;
- ponto central de bootstrap;
- coordenador de views;
- central de processamento de várias ações POST.

Na prática, quase tudo começa nele.

### Etapa 1: bootstrap do sistema

Logo no início, ele:

- ativa exibição de erros;
- inicia sessão com `session_start()`;
- carrega:
  - banco;
  - configuração da aplicação;
  - helpers;
  - autoload.

Isso significa que, antes mesmo de decidir qual página abrir, ele já monta o ambiente mínimo de funcionamento da aplicação.

### Etapa 2: leitura de URLs amigáveis

O arquivo não depende só de `?page=...`. Ele também interpreta certas rotas amigáveis, especialmente:

- `/certificado/CODIGO`
- `/validar-certificado/CODIGO`
- `/perguntar-ia`

O que ele faz é traduzir essas rotas em parâmetros internos de `$_GET`, para que o restante do sistema continue funcionando de forma uniforme.

Isso é uma decisão prática: o sistema continua simples por dentro, mas ganha URLs mais amigáveis em certas áreas críticas, como certificado público.

### Etapa 3: identificação da página

Depois do bootstrap, ele define:

- a página atual;
- o título;
- o conteúdo HTML bruto;
- se a saída será PDF ou HTML normal.

Essas variáveis servem como contexto global da renderização.

### Etapa 4: tratamento de POST

Se a requisição for `POST`, o arquivo centraliza o fluxo em `processarAcao()`.

Antes disso, ele ainda trata um detalhe importante:

- se o `Content-Type` for JSON, ele lê o corpo cru da requisição;
- decodifica o JSON;
- mistura os dados com `$_POST`.

Isto permite que o backend funcione tanto com formulários tradicionais quanto com chamadas AJAX modernas.

### Etapa 5: funções auxiliares internas

O `index.php` define várias funções internas que sustentam o resto do sistema.

As mais importantes são:

#### `isAjaxRequest()`

Detecta se a chamada foi feita via AJAX. Isso é importante porque o backend às vezes precisa:

- responder JSON;
- evitar redirecionamentos HTML;
- devolver mensagens estruturadas.

#### `abortExportRequest()`

É uma função utilitária para falhas em exportação/backup. Ela decide:

- se devolve JSON com erro;
- ou se guarda mensagem na sessão e redireciona.

Isso evita duplicar tratamento de erro em vários pontos.

#### `validateRouteCsrfOrAbort()`

Valida CSRF em fluxos protegidos e aborta de forma padronizada se o token estiver inválido.

#### `quizTimerSessionKey()`, `getQuizTimerState()`, `clearQuizTimerState()`, `ensureQuizTimerState()`

Essas funções implementam a persistência do relógio do quiz dentro da sessão.

Na prática, isso significa que:

- o quiz com tempo limite não depende só do frontend;
- o backend guarda quando começou;
- o backend sabe quando expira;
- se o usuário recarregar a página, o estado pode ser recuperado.

#### `quizViewStateSessionKey()`, `clearQuizViewState()`, `applyQuizViewState()`

Essas funções garantem consistência na apresentação do quiz.

Quando há:

- embaralhamento de perguntas;
- embaralhamento de respostas;

o sistema salva essa ordem na sessão para que o aluno veja a mesma estrutura durante a tentativa e a correção continue coerente.

Isto é muito importante, porque sem isso a experiência poderia ficar inconsistente entre carregamentos.

### Etapa 6: roteamento de páginas

Depois da preparação, o arquivo entra num grande `switch ($page)`.

É aqui que ele decide:

- qual controller chamar;
- que model complementar usar;
- que dados devem ser preparados;
- qual view deve ser renderizada.

Algumas rotas são simples, como `login` ou `registro`. Outras são densas, como:

- `aula`
- `quiz`
- `certificado`
- `perfil`
- `exportar-dados-*`
- `restaurar-backup`
- `gerenciar-curso`

### Etapa 7: fluxo da rota `aula`

Essa rota é um ótimo exemplo da complexidade real do sistema.

Quando a página da aula é aberta, o `index.php`:

1. exige autenticação;
2. carrega a aula;
3. garante que o curso da aula existe;
4. se a aula for vídeo e ainda não houver transcrição, tenta gerar automaticamente;
5. carrega o curso completo com sua estrutura modular;
6. identifica o módulo atual da aula;
7. valida se o módulo está desbloqueado para o aluno;
8. consulta se a aula já foi concluída;
9. calcula desempenho em quizzes do curso;
10. calcula progresso do curso;
11. carrega lista de aulas do curso;
12. tenta carregar conteúdo inteligente da aula;
13. renderiza a view final.

Ou seja, a rota da aula não é apenas “mostrar conteúdo”. Ela combina:

- segurança;
- transcrição;
- progresso;
- avaliação;
- navegação estrutural;
- IA.

### Etapa 8: fluxo da rota `quiz`

Quando a rota `quiz` é aberta, o arquivo:

1. exige login;
2. carrega o quiz completo;
3. valida se o aluno pode abrir esse quiz;
4. busca histórico de tentativas;
5. busca melhor resultado;
6. lida com reinício de tentativa;
7. aplica ordem fixa de perguntas/respostas via sessão;
8. calcula tentativas restantes;
9. carrega desempenho do curso;
10. prepara timer do quiz;
11. renderiza a view.

Isso mostra que o frontend do quiz depende muito do trabalho prévio do backend.

### Etapa 9: fluxo da rota `certificado`

Essa rota tem pelo menos três comportamentos diferentes:

- validação pública por código;
- visualização privada do certificado do aluno;
- renderização especial para PDF.

Ou seja, uma única rota serve múltiplos modos de uso, e por isso ela é cuidadosamente tratada no arquivo central.

### Etapa 10: renderização

Depois de preparar tudo, o `index.php`:

- pode devolver só o fragmento da view quando `partial=1`;
- pode responder JSON em rotas API simples;
- ou renderizar a página inteira com `renderizarLayout()`.

Se o layout falhar, ele ainda usa `renderizarLayoutFallback()`, que monta uma versão mínima da página para não derrubar completamente a aplicação.

### Etapa 11: `processarAcao()`

Essa função é quase um “roteador de comandos POST”.

Ela:

- identifica a ação pelo campo `acao`;
- define quais ações exigem CSRF;
- responde em JSON ou redirecionamento conforme o contexto;
- executa ações de:
  - autenticação;
  - cursos;
  - módulos;
  - aulas;
  - quizzes;
  - progresso;
  - perfil;
  - backup;
  - restauração;
  - administração.

Em termos de arquitetura, esta função concentra muito poder. Isso é eficiente para um projeto assim, mas também explica por que o `index.php` é um dos pontos mais densos do sistema.

### Conclusão sobre `public/index.php`

Se quiseres resumir bem este arquivo numa defesa:

> O `public/index.php` é o núcleo de execução da plataforma. Ele faz bootstrap, interpreta rotas, processa ações POST, gere estado sensível do quiz, integra controllers e models e por fim renderiza a interface. Ele concentra bastante responsabilidade, o que o torna poderoso, mas também um candidato natural a refatoração futura.

## 12.2 Raio-X de `app/controllers/QuizController.php`

Este controller representa a camada de orquestração do sistema de avaliação.

### Estrutura interna

No construtor, ele injeta:

- PDO;
- model de quiz;
- model de aula;
- model de curso;
- model de módulo.

Isso mostra que o quiz não existe isoladamente. Ele depende fortemente de:

- curso;
- aula;
- módulo;
- persistência.

### Método `criar()`

Este é o método mais importante do controller.

O que ele faz:

1. lê usuário da sessão;
2. normaliza campos básicos, como título e descrição;
3. identifica contexto do quiz:
   - aula;
   - módulo;
   - final;
4. se o quiz estiver associado a uma aula, carrega a aula e herda o `course_id`;
5. valida se o curso existe;
6. valida se o professor é dono do curso;
7. sincroniza estrutura modular do curso;
8. valida regras específicas conforme o tipo.

### Regras por tipo de quiz

#### Quiz de aula

O controller exige:

- aula válida;
- aula pertencente ao curso certo;
- vínculo ao módulo correto da aula.

#### Quiz de módulo

O controller exige:

- curso multi-módulo;
- módulo válido;
- módulo pertencente ao curso.

#### Quiz final

O controller zera `lesson_id` e `module_id`, porque o escopo é o curso inteiro.

### Controle de duplicidade

O método também impede:

- mais de um quiz final por curso;
- mais de um quiz de módulo por módulo;
- mais de um quiz principal de aula por aula.

Essa é uma regra importante porque mantém a estrutura pedagógica consistente.

### Validação das perguntas

O controller percorre as perguntas recebidas e valida:

- enunciado não vazio;
- pelo menos duas alternativas;
- índice da resposta correta válido;
- pontos mínimos por pergunta.

Depois disso, ele converte cada pergunta num formato limpo e padronizado.

### Regra de 20 valores

Esse é um detalhe muito forte do projeto: o controller não aceita qualquer distribuição de pontos. Ele exige que a soma das perguntas feche exatamente 20 valores.

Essa validação reforça consistência da avaliação.

### Persistência transacional

Ao criar o quiz:

1. abre transação;
2. salva o quiz;
3. salva as perguntas uma a uma;
4. se algo falhar, faz rollback;
5. se tudo der certo, faz commit.

Essa escolha é muito importante porque evita quizz criado sem perguntas ou perguntas parcialmente salvas.

### Método `corrigirResposta()`

Esse método trata o lado do aluno.

Fluxo:

1. exige usuário autenticado;
2. carrega o quiz com questões;
3. conta tentativas já usadas;
4. bloqueia se o limite foi atingido;
5. considera tempo gasto e possível tempo esgotado;
6. percorre as questões;
7. valida se todas foram respondidas, exceto em envio automático por timeout;
8. calcula:
   - total correto;
   - pontos obtidos;
   - pontos totais;
   - percentual;
   - nota;
   - aprovado ou não.

Depois disso, ele:

9. salva a tentativa;
10. salva respostas individuais;
11. recalcula nota final do curso;
12. recalcula progresso da matrícula;
13. sincroniza certificados do curso.

Ou seja, responder um quiz tem efeito cascata no sistema inteiro.

### Métodos auxiliares de consulta

O controller também oferece:

- histórico de resultados;
- melhor resultado;
- última tentativa;
- desempenho do aluno no curso;
- análise do professor sobre o curso.

Assim, ele não serve apenas para criar e corrigir, mas também para análise e acompanhamento.

### Conclusão sobre `QuizController`

> O `QuizController` é o coordenador do sistema de avaliação. Ele transforma regras pedagógicas em operações seguras: cria quizzes com validações rigorosas, corrige tentativas, atualiza progresso e dispara sincronização de certificados.

## 12.3 Raio-X de `app/models/Quiz.php`

Se o `QuizController` é o orquestrador, o `Quiz` model é o motor interno da avaliação.

### Papel geral

Ele concentra:

- persistência;
- cálculo de nota;
- cálculo de progresso avaliativo;
- status de aprovação;
- relatórios;
- compatibilidade de schema.

### Método `criar()`

Esse método é mais flexível do que um insert simples.

Ele:

- recebe muitas opções;
- normaliza dificuldade;
- deriva peso com base na dificuldade;
- define nota mínima padrão;
- lê tempo limite;
- lê flags de embaralhamento;
- verifica se certas colunas existem antes de montar o insert.

Esse último ponto é importante: o model se adapta ao schema disponível.

### Método `adicionarQuestao()`

Transforma o array de opções em JSON e grava a pergunta.

Ele também não assume rigidamente que todas as colunas existem: verifica compatibilidade antes de montar o insert.

### Método `obterComQuestoes()`

Esse método carrega o quiz com seu contexto completo:

- dados do quiz;
- curso herdado da aula se necessário;
- título da aula;
- título e ordem do módulo;
- lista de questões;
- soma total de pontos;
- enriquecimento final.

Ou seja, ele devolve um objeto de quiz “pronto para uso”, não apenas uma linha do banco.

### Métodos `listarPorAula()`, `listarPorCurso()`, `listarPorModulo()`

Esses métodos permitem enxergar o quiz em diferentes recortes pedagógicos.

O mais rico é `listarPorCurso()`, porque ele:

- combina quiz de aula, módulo e final;
- usa filtro de curso direto ou indireto;
- ordena por tipo e estrutura modular;
- devolve quizzes enriquecidos.

### Método `salvarTentativa()`

Esse método é importante porque salva a tentativa em duas camadas:

1. `quiz_attempts`
2. `quiz_results` legado

Por que isso importa?

- mantém compatibilidade histórica;
- evita quebrar partes que ainda dependam da tabela antiga.

Também usa transação, o que garante consistência da gravação.

### Método `salvarRespostasTentativa()`

Grava cada resposta da tentativa em `quiz_attempt_answers`.

Isso enriquece muito a análise posterior, porque o sistema passa a saber:

- o que o aluno respondeu;
- em que pergunta;
- se acertou ou não.

### Método `getMandatoryLessonQuizStatus()`

Esse método é um dos mais importantes do vínculo aula-quiz.

Ele:

- busca quizzes obrigatórios da aula;
- consulta melhor nota do aluno em cada um;
- compara com nota mínima;
- devolve:
  - quantos são obrigatórios;
  - quantos foram aprovados;
  - se todos foram aprovados;
  - quais ainda faltam.

É esse método que sustenta a regra de “não concluir aula sem aprovação no quiz obrigatório”.

### Método `getMandatoryLessonQuizApprovalMap()`

Esse método produz um mapa de aprovação por aula dentro de um curso.

Na prática, ele permite que o sistema trate muitas aulas de uma vez, em vez de recalcular uma a uma sempre.

Esse mapa é usado em:

- cálculo de progresso;
- visualização do curso;
- elegibilidade de certificados.

### Método `calculateLessonProgressForCourse()`

Esse método calcula progresso de aulas considerando regras reais:

1. lista todas as aulas do curso;
2. busca as aulas marcadas como concluídas;
3. aplica o mapa de aprovação do quiz obrigatório;
4. remove da contagem efetiva as aulas marcadas como concluídas, mas cujo quiz obrigatório ainda não foi aprovado;
5. calcula percentual final.

Esse ponto é muito importante para defesa, porque mostra que o sistema não trata progresso como algo superficial.

### Método `recalculateEnrollmentProgress()`

Esse método atualiza a matrícula do aluno.

Ele combina:

- progresso de aulas;
- progresso avaliativo;
- elegibilidade final.

Se o curso tem quizzes, ele usa uma ponderação:

- 60% aulas;
- 40% avaliação.

Se o aluno estiver plenamente apto à conclusão, o progresso vira 100%.

Essa é uma regra de negócio forte e diferenciada.

### Método `calcularNotaFinalCurso()`

Esse método é um dos mais sofisticados.

O que ele faz:

1. carrega todos os quizzes do curso;
2. organiza os quizzes por dificuldade;
3. busca melhor resultado do aluno em cada quiz;
4. conta quizzes obrigatórios e aprovados;
5. verifica prova final;
6. calcula média ponderada por dificuldade;
7. devolve:
   - nota final;
   - percentual;
   - total de quizzes;
   - respondidos;
   - obrigatórios;
   - aprovados;
   - existência de prova final;
   - aprovação final.

Ou seja, o model não faz só média simples. Ele aplica uma lógica acadêmica ponderada.

### Método `calcularProgressoAvaliacaoCurso()`

Enquanto `calcularNotaFinalCurso()` mede desempenho, esse método mede progresso avaliativo.

Ele verifica quantos quizzes de cada grupo de dificuldade já foram aprovados e transforma isso num percentual.

### Método `alunoAptoConclusao()`

Esse método junta dois mundos:

- conclusão das aulas;
- aprovação nas avaliações.

Ele devolve se o aluno:

- concluiu aulas;
- concluiu quizzes;
- atingiu nota mínima;
- está de fato apto à conclusão.

Esse método é um pivô lógico entre avaliação e certificação.

### Métodos administrativos e de professor

O model também possui consultas como:

- `listarDesempenhoCursoProfessor()`
- `listarAdministrativo()`
- `contarAdministrativo()`
- `obterResumoAdministrativo()`

Esses métodos mostram que o model não serve apenas o aluno. Ele também alimenta:

- dashboards;
- relatórios;
- gestão administrativa.

### Conclusão sobre `Quiz.php`

> O model `Quiz` é um dos cérebros da plataforma. Ele não apenas persiste avaliações; ele calcula progresso, nota, aprovação, análise pedagógica e ainda garante compatibilidade estrutural do sistema.

## 12.4 Raio-X de `app/models/Certificate.php`

Este model é responsável por transformar dados acadêmicos em certificado oficial.

### Papel geral

Ele controla:

- elegibilidade;
- emissão;
- atualização;
- revogação;
- consulta pública;
- montagem de dados para PDF.

### Método `syncCourseCertificates()`

Esse método é central.

Fluxo:

1. calcula o estado completo do aluno no curso;
2. percorre os módulos;
3. se um módulo estiver elegível, emite ou atualiza o certificado do módulo;
4. se não estiver elegível, remove certificado existente desse módulo;
5. faz o mesmo para o certificado final do curso.

Isto mostra que certificado não é um objeto estático: ele é sincronizado com o estado real do aluno.

### Método `generateCertificate()`

Esse método emite certificado efetivamente.

Ele:

1. valida elegibilidade;
2. verifica se já existe certificado daquele escopo;
3. se existir, atualiza nota;
4. se não existir, gera código único;
5. grava a linha do certificado;
6. devolve o certificado pronto.

O ponto mais interessante é que ele evita duplicidade e mantém atualização da nota.

### Método `buildEligibilitySnapshot()`

Esse é provavelmente o método mais importante do model.

Ele constrói uma “fotografia” completa da situação do aluno no curso.

O que ele faz:

1. carrega curso;
2. carrega aluno;
3. carrega módulos do curso;
4. consulta mapa de aprovação de quizzes obrigatórios de aula;
5. calcula estado de cada módulo;
6. verifica quizzes finais;
7. calcula nota do curso;
8. decide se o certificado final do curso é elegível.

O resultado inclui:

- dados do curso;
- dados do aluno;
- estado de todos os módulos;
- estado do certificado final;
- mensagens interpretáveis;
- certificados existentes.

Na prática, ele produz a visão mais completa do estado acadêmico do aluno.

### Método `buildModuleState()`

Esse método calcula o estado de um módulo específico.

Ele verifica:

- total de aulas;
- quantas foram concluídas de forma efetiva;
- quizzes do módulo;
- quantos foram aprovados;
- se o módulo está elegível;
- nota média do módulo;
- mensagem explicativa;
- certificado de módulo existente.

Esse método é importante porque o sistema não trata módulo apenas como organização visual. O módulo também tem conclusão própria.

### Método `validateEligibility()`

Esse método funciona como filtro interpretativo do snapshot:

- se o escopo for módulo, busca o módulo específico;
- se for curso, devolve o estado do curso.

Ele evita recalcular tudo em múltiplos pontos da aplicação.

### Método `listUserCertificatesForCourse()`

Lista todos os certificados do aluno naquele curso, separando:

- certificado final do curso;
- certificados por módulo.

Essa estrutura é muito útil para exibição em interface.

### Método `findByCode()`

Permite validação pública.

Ele busca o certificado por:

- `certificate_code`
- `codigo_certificado`

e monta um objeto hidratado com dados de:

- aluno;
- curso;
- professor;
- módulo;
- carga horária aproximada;
- links de verificação e QR.

### Método `buildPublicVerificationData()`

Esse método é o coração da verificação pública.

Ele:

1. busca o certificado pelo código;
2. se não existir, registra tentativa inválida;
3. se existir:
   - registra a tentativa de verificação;
   - atualiza contador;
   - devolve dados prontos para a interface pública.

Ou seja, a validação pública também gera trilha operacional.

### Método `buildPdfPayload()`

Esse método transforma o certificado em um pacote pronto para renderização de PDF.

Ele monta:

- título do certificado;
- nome do aluno;
- curso ou módulo;
- data de emissão;
- nota;
- nome do professor;
- carga horária;
- código de verificação;
- URL de verificação;
- URL do QR code;
- nome do ficheiro PDF.

Isso desacopla a lógica da renderização visual.

### Método `hydrateCertificate()`

Esse método é essencial porque transforma uma linha crua do banco em um objeto de certificado mais rico e amigável.

Ele normaliza:

- IDs;
- tipo;
- código;
- nota;
- data;
- nomes;
- carga horária;
- links de verificação;
- QR code.

Na prática, vários pontos do sistema não trabalham com a linha crua do banco, mas com o certificado “hidratado”.

### Método `ensureSchema()`

Esse método garante que a tabela de certificados e colunas relacionadas existam.

Além disso, ele:

- adiciona colunas novas;
- garante índices únicos;
- migra valores entre campos antigos e novos;
- normaliza dados incompletos.

Isso mostra que o módulo de certificados foi evoluindo e o código precisou sustentar essa evolução.

### Conclusão sobre `Certificate.php`

> O model `Certificate` é o núcleo da certificação. Ele não apenas cria um documento: ele calcula elegibilidade, sincroniza o estado acadêmico do aluno, emite, atualiza, revoga, valida publicamente e prepara o certificado para PDF.

## 12.5 Raio-X de `app/controllers/ImportController.php`

Este é um dos arquivos mais complexos do projeto, porque mexe com segurança, integridade, compatibilidade e restauração de dados.

### Papel geral

Ele controla:

- upload do backup;
- validação do ZIP;
- extração segura;
- leitura do manifesto;
- verificação de checksums;
- restauração dos dados;
- adaptação de backups legados.

### Método `uploadBackup()`

Esse método cuida do primeiro contacto com o arquivo enviado.

Ele valida:

- usuário atual;
- existência do upload;
- tamanho máximo;
- extensão `.zip`;
- MIME aceitável.

Depois disso:

- cria um token único;
- prepara uma pasta de staging;
- move o ZIP para a área de importação;
- chama a validação do backup.

Ou seja, ele não restaura nada diretamente. Primeiro ele coloca o backup numa “quarentena controlada”.

### Método `validateBackup()`

Esse método faz a inspeção profunda do backup.

Passos:

1. encontra o arquivo na área de staging;
2. extrai com segurança;
3. se necessário, normaliza estrutura legada;
4. garante presença dos arquivos obrigatórios;
5. lê `manifest.json`;
6. lê `checksums.json`;
7. carrega os documentos JSON;
8. valida manifesto;
9. valida checksums;
10. valida esquema dos documentos;
11. monta um resumo amigável;
12. guarda um preview na sessão.

Isto é muito importante porque a restauração real só acontece depois da validação completa.

### Método `restoreBackup()`

Esse método executa a restauração de fato.

Fluxo:

1. revalida o backup;
2. aplica filtro de escopo:
   - full;
   - user;
   - course;
   - module;
3. abre transação no banco;
4. restaura perfil do proprietário, se aplicável;
5. constrói mapa de usuários;
6. decide o caminho conforme tipo do backup:
   - `course`/`full`: recria entidades;
   - `user`: mapeia entidades já existentes;
7. restaura:
   - cursos;
   - módulos;
   - aulas;
   - quizzes;
   - matrículas;
   - progresso;
   - tentativas;
   - certificados;
8. faz commit;
9. limpa preview de sessão;
10. registra log do restore.

Se algo falhar:

- faz rollback;
- registra erro;
- lança exceção.

Esse fluxo mostra um bom nível de cuidado transacional.

### Método `extractArchiveSafely()`

Este método é crítico para segurança.

Ele:

- abre o ZIP;
- aplica senha se fornecida;
- limpa o diretório de destino;
- cria diretórios necessários;
- percorre cada entrada;
- recusa caminhos inseguros como `../`;
- extrai arquivos individualmente por stream.

Esse detalhe do stream é importante porque o método não depende de extração cega do ZIP. Ele controla arquivo a arquivo.

### Método `normalizeLegacyBackupIfNeeded()`

Esse método mostra maturidade histórica do projeto.

Ele existe para adaptar backups antigos a um formato novo.

O que ele faz:

- detecta se o backup antigo não possui `manifest.json` e `checksums.json`;
- lê a estrutura antiga;
- transforma isso em documentos modernos;
- reescreve:
  - `data/user.json`
  - `data/courses.json`
  - `data/modules.json`
  - `data/lessons.json`
  - `data/quizzes.json`
  - `data/quiz_attempts.json`
  - `data/progress.json`
  - `data/certificates.json`
  - `data/enrollments.json`
- gera manifesto novo;
- gera checksums novos.

Em outras palavras, ele age como uma ponte entre versões do projeto.

### Métodos `assertRequiredStructure()`, `assertManifest()`, `verifyChecksums()`

Esses métodos formam a camada de confiança do restore.

#### `assertRequiredStructure()`

Confirma que os ficheiros mínimos do backup existem.

#### `assertManifest()`

Confirma:

- presença dos campos obrigatórios;
- se o backup pertence ao usuário atual;
- se o papel atual pode restaurar o tipo do backup.

#### `verifyChecksums()`

Recalcula hash dos arquivos e verifica se coincide com o manifesto esperado.

Isto ajuda a evitar restauração de backup corrompido ou adulterado.

### Métodos de restauração por entidade

O controller tem vários métodos especializados:

- `restoreCourses()`
- `restoreModules()`
- `restoreLessons()`
- `restoreQuizzes()`
- `restoreQuizQuestions()`
- `restoreEnrollments()`
- `restoreProgress()`
- `restoreQuizAttempts()`
- `restoreAttemptAnswers()`
- `restoreCertificates()`

Cada um deles faz basicamente:

1. ler o objeto importado;
2. mapear IDs antigos para IDs novos;
3. verificar se já existe equivalente;
4. atualizar se existir;
5. inserir se não existir.

Ou seja, o restore não é cego. Ele tenta reconciliar dados antigos com o estado atual do sistema.

### Método `restoreFileFromBackup()`

Esse método restaura arquivos físicos, como:

- thumbnails;
- avatares;
- arquivos de aula;
- áudios.

Ele:

- valida o caminho relativo;
- garante diretório de upload;
- gera nome novo;
- copia o arquivo para uploads;
- devolve o novo nome.

É a ponte entre o backup lógico e os recursos físicos.

### Conclusão sobre `ImportController`

> O `ImportController` é um dos arquivos mais críticos do projeto porque lida com restauração segura de dados. Ele valida o backup, protege contra estruturas maliciosas, suporta compatibilidade legada, restaura entidades com transação e ainda reconcilia IDs antigos com o estado atual do sistema.

## 12.6 Como usar este raio-x na defesa

Se a banca fizer uma pergunta muito técnica, podes usar uma resposta assim:

> Nos arquivos centrais do sistema, cada camada tem um papel específico. O `public/index.php` coordena o fluxo da aplicação. O `QuizController` transforma regras pedagógicas em ações seguras. O model `Quiz` calcula nota, progresso e aprovação. O model `Certificate` decide elegibilidade e controla emissão e validação pública. E o `ImportController` garante uma restauração segura, compatível e transacional dos dados.

Essa resposta mostra domínio de arquitetura, regras de negócio e implementação real.

## 13. Raio-X complementar de outros arquivos estratégicos

Nesta secção, vou aprofundar outros arquivos muito relevantes para a defesa:

- `app/controllers/AuthController.php`
- `app/controllers/CourseController.php`
- `app/controllers/LessonController.php`
- `app/controllers/DashboardController.php`
- `app/views/layout.php`

Esses arquivos não são tão densos quanto `ImportController` ou `Quiz.php`, mas são centrais para entender a experiência completa da plataforma.

## 13.1 Raio-X de `app/controllers/AuthController.php`

Este controller é responsável pelo ciclo de autenticação e segurança de conta.

### Papel geral

Ele governa:

- registro;
- login;
- recuperação de senha;
- validação de credenciais;
- controle de tentativas falhadas;
- bloqueio temporário;
- normalização de papel do utilizador;
- manutenção de algumas colunas de segurança da tabela `users`.

Ele é um arquivo muito importante porque protege a porta de entrada do sistema.

### Construtor e preparação interna

No construtor, ele:

- recebe o PDO;
- carrega o model `User`;
- chama `ensureUsersSecuritySchema()`.

Isso já mostra um ponto importante: o controller não assume que a base está sempre 100% atualizada. Ele tenta garantir que os campos de segurança da tabela `users` existam.

### Método `ensureUsersSecuritySchema()`

Esse método:

- faz `SHOW COLUMNS FROM users`;
- verifica se existem colunas como:
  - `email_verified`
  - `verification_token`
  - `verification_token_expires_at`
  - `reset_token`
  - `reset_token_expires_at`
  - `login_attempts`
  - `locked_until`
- cria essas colunas se não existirem;
- tenta criar índices;
- atualiza dados legados quando necessário.

Na prática, ele fortalece a segurança da tabela de usuários sem depender apenas de migração manual.

### Método `registrar()`

O fluxo de registro é:

1. normaliza nome, email, senha e papel;
2. valida nome com tamanho mínimo;
3. valida email;
4. valida força da senha;
5. verifica se o email já está em uso;
6. se houver erros, devolve lista consolidada;
7. se estiver tudo certo:
   - gera hash da senha;
   - chama o model para criar o usuário;
   - devolve mensagem de sucesso.

Esse método é importante porque não trata cadastro como algo trivial. Ele impõe regras antes de persistir.

### Método `login()`

Este método é o centro da autenticação.

Fluxo:

1. normaliza email e senha;
2. bloqueia envio vazio;
3. procura utilizador pelo email;
4. se não existir:
   - chama um hash dummy para evitar diferenças visíveis de tempo;
   - registra falha;
   - devolve erro genérico.
5. se existir:
   - verifica se a conta está bloqueada;
   - valida a senha com `password_verify`;
   - se errar:
     - incrementa tentativas;
     - pode bloquear temporariamente;
     - devolve erro genérico;
   - se acertar:
     - zera falhas;
     - rehash da senha se necessário;
     - regenera a sessão;
     - salva `$_SESSION['usuario']`;
     - registra evento de sucesso.

Esse método mostra várias boas práticas:

- não revelar se o email existe ou não;
- usar hash dummy para reduzir diferenças observáveis;
- limitar tentativas;
- regenerar a sessão após login.

### Método `solicitarRecuperacaoSenha()`

Esse método cuida do início do reset.

Fluxo:

1. normaliza email;
2. devolve mensagem genérica mesmo em erro;
3. procura usuário;
4. se existir:
   - gera token seguro;
   - gera hash do token;
   - define expiração;
   - salva no banco;
   - tenta enviar email.

Isto é importante porque protege contra enumeração de contas.

### Método `validarResetToken()`

Confirma:

- se o token existe;
- se o token ainda é válido;
- se o hash confere com um utilizador existente.

### Papel estratégico do `AuthController`

Este controller não é apenas “o arquivo de login”. Ele:

- protege a entrada do sistema;
- endurece a base de usuários;
- centraliza regras de conta;
- registra eventos de autenticação.

### Conclusão sobre `AuthController`

> O `AuthController` controla o ciclo de vida da conta do utilizador, aplicando validação, hashing, bloqueio temporário, recuperação de senha e proteção de sessão. É um dos pilares de segurança do sistema.

## 13.2 Raio-X de `app/controllers/CourseController.php`

Este controller governa o contexto funcional dos cursos.

### Papel geral

Ele coordena:

- criação de curso;
- listagem;
- busca;
- detalhe do curso;
- matrícula;
- atualização;
- remoção;
- estrutura modular.

### Construtor

O construtor injeta:

- PDO;
- model de curso;
- model de matrícula;
- model de módulo.

Isso mostra que o curso, no projeto, nunca é tratado sozinho. Ele sempre conversa com:

- a estrutura do curso;
- os alunos;
- o contexto do professor.

### Método `criar()`

Fluxo:

1. lê o utilizador da sessão;
2. exige que seja professor;
3. valida título e descrição;
4. normaliza a estrutura do curso;
5. chama o model para criar o curso;
6. sincroniza a estrutura modular;
7. se for curso simples, garante módulo padrão.

Esse método é importante porque mostra que “criar curso” já inclui preparar a arquitetura interna dele.

### Método `listar()`

Esse método:

- calcula paginação;
- busca cursos ativos;
- calcula total;
- devolve pacote pronto para a view.

Ou seja, ele entrega o catálogo já preparado para interface.

### Método `buscar()`

Esse método valida o tamanho mínimo do termo e encaminha a busca ao model.

É simples, mas importante porque evita buscas muito pobres ou acidentais.

### Método `obter()`

Esse é o método mais rico do controller.

Quando um curso é carregado, ele:

1. busca o curso no model;
2. carrega aulas;
3. carrega quizzes;
4. sincroniza módulos;
5. monta a estrutura modular;
6. separa quizzes finais;
7. normaliza a informação de estrutura;
8. se houver utilizador autenticado:
   - sincroniza certificados;
   - calcula snapshot de elegibilidade;
   - lista certificados do utilizador;
9. conta total de alunos;
10. devolve o curso enriquecido.

Na prática, esse método constrói o “curso real” usado pela interface, e não apenas um registro da tabela `courses`.

### Método `matricular()`

Fluxo:

1. exige utilizador autenticado;
2. valida se o curso existe;
3. chama o model de matrícula.

Ele é simples, mas é o ponto oficial de entrada do aluno no curso.

### Método `atualizar()`

Valida:

- existência do curso;
- se o utilizador é dono ou admin.

Depois chama o model para atualizar.

### Método `deletar()`

Tem o mesmo espírito:

- valida curso;
- valida dono/admin;
- apaga.

### Método `montarEstruturaModular()`

Este é um dos métodos mais interessantes do controller.

Ele pega:

- módulos;
- aulas;
- quizzes;
- progresso do aluno;
- mapa de aprovação por aula;

e transforma isso numa estrutura navegável para a interface.

Esse método calcula, por módulo:

- aulas do módulo;
- quizzes do módulo;
- progresso;
- desbloqueio;
- conclusão.

Isto faz dele uma ponte importante entre:

- persistência;
- experiência pedagógica.

### Conclusão sobre `CourseController`

> O `CourseController` não trata o curso como um simples cadastro. Ele monta o curso em contexto, com módulos, aulas, quizzes, progresso e certificados, tornando-se o principal orquestrador da experiência acadêmica do curso.

## 13.3 Raio-X de `app/controllers/LessonController.php`

Este controller controla o conteúdo mais concreto da plataforma: a aula.

### Papel geral

Ele governa:

- criação de aula;
- leitura;
- listagem;
- atualização;
- remoção;
- conclusão;
- desmarcação;
- integração com progresso, mídia, transcrição e certificado.

### Método `criar()`

Fluxo:

1. carrega utilizador atual;
2. carrega curso;
3. valida se o utilizador é dono do curso;
4. valida título e conteúdo;
5. sincroniza a estrutura modular do curso;
6. resolve o módulo correto;
7. calcula a ordem da aula dentro do módulo;
8. chama o model para persistir.

Este método mostra que a criação da aula está fortemente ligada à organização interna do curso.

### Método `obter()`

Valida o ID da aula e carrega a entidade pelo model.

Parece simples, mas ele é muito usado nas rotas de:

- edição;
- visualização da aula;
- áudio da aula;
- transcrição;
- quiz contextual.

### Método `obterConteudoLeituraInteligente()`

Este método delega ao `LessonContentService` a geração ou recuperação de conteúdo inteligente.

Isso mostra que a aula não é só um registro estático: ela pode ser expandida pedagogicamente.

### Método `marcarConcluida()`

Este é o método mais importante do controller.

Fluxo:

1. exige utilizador autenticado;
2. carrega a aula;
3. carrega o curso da aula;
4. consulta o status dos quizzes obrigatórios da aula;
5. se não estiver aprovado, bloqueia a conclusão;
6. grava ou atualiza `lesson_progress` com `concluida = 1`;
7. recalcula progresso do curso;
8. sincroniza certificados;
9. devolve resultado com progresso e eventos de certificado.

Esse método é um excelente exemplo de regra de negócio real.

### Método `desmarcarConcluida()`

É o inverso do anterior:

- marca `concluida = 0`;
- recalcula progresso;
- sincroniza certificados.

Isso é importante porque o sistema permite regressão de estado, não apenas avanço.

### Método `atualizar()`

Fluxo:

1. carrega a aula;
2. carrega o curso;
3. valida dono do curso;
4. sincroniza módulos;
5. resolve módulo final;
6. decide como lidar com resumo e áudio;
7. chama o model;
8. se mídia tiver mudado, limpa mídia antiga.

Isto mostra que atualizar aula não é apenas trocar texto, mas também gerir recursos físicos associados.

### Método `deletar()`

Também não é um delete simples:

1. valida aula;
2. valida curso;
3. valida permissão;
4. deleta a aula;
5. chama o serviço de mídia para limpar arquivos associados.

Ou seja, o controller também protege integridade do armazenamento físico.

### Conclusão sobre `LessonController`

> O `LessonController` governa a unidade central de aprendizagem da plataforma. Ele não apenas cria e edita aulas, mas controla conclusão, recalcula progresso, sincroniza certificados e coordena recursos pedagógicos e físicos ligados ao conteúdo.

## 13.4 Raio-X de `app/controllers/DashboardController.php`

Este controller monta as visões analíticas e operacionais de cada perfil.

### Papel geral

Ele não persiste dados diretamente. O papel dele é:

- compor métricas;
- organizar indicadores;
- preparar dados para dashboards.

### Método `dashboardAluno()`

Fluxo:

1. identifica o aluno atual;
2. busca cursos matriculados;
3. percorre cada curso;
4. tenta calcular o estado completo do progresso;
5. em caso de falha, aplica fallback baseado só em aulas;
6. preenche:
   - progresso;
   - próxima aula;
   - aulas totais;
   - aulas concluídas;
7. calcula:
   - cursos em progresso;
   - cursos concluídos;
   - total de cursos.

Este método transforma dados brutos de matrícula em uma visão compreensível da jornada do aluno.

### Método `dashboardProfessor()`

Esse é o mais analítico.

Fluxo:

1. carrega cursos do professor;
2. para cada curso:
   - conta alunos;
   - conta aulas;
   - tenta carregar quizzes;
   - calcula média;
   - calcula taxa de aprovação;
   - agrega perguntas críticas;
3. soma métricas globais do professor;
4. ordena cursos por desempenho;
5. ordena perguntas críticas por incidência;
6. devolve os resumos.

Na prática, esse método faz do dashboard do professor uma ferramenta de acompanhamento pedagógico, não só administrativo.

### Método `dashboardAdmin()`

Ele faz uma agregação de nível institucional:

- estatísticas do sistema;
- usuários;
- cursos.

Serve como visão executiva da plataforma.

### Tratamento de falhas

Um ponto bom desse controller é que ele tenta falhar de forma elegante:

- envolve blocos em `try/catch`;
- registra erro quando necessário;
- devolve estrutura segura mesmo em fallback.

Isso evita dashboards completamente quebrados para o utilizador.

### Conclusão sobre `DashboardController`

> O `DashboardController` transforma dados operacionais em visão analítica. Ele é o arquivo que resume o sistema para cada perfil, convertendo cursos, matrículas, quizzes e progresso em indicadores legíveis.

## 13.5 Raio-X de `app/views/layout.php`

Mesmo sendo uma view, `layout.php` é um arquivo estratégico porque ele controla a moldura de toda a experiência visual da plataforma.

### Papel geral

Ele é responsável por:

- HTML base;
- meta tags;
- CSS principal;
- JavaScript principal;
- barra de navegação;
- toasts de mensagens;
- modal global;
- diferenciação entre modo normal e modo PDF.

### Construção do contexto visual

No topo, ele prepara várias variáveis úteis:

- nome da página;
- classe do `body`;
- token CSRF;
- usuário autenticado;
- nome, papel e foto do usuário;
- flash messages;
- função `assetUrl()` com versionamento por `filemtime`.

Esse detalhe do versionamento é importante porque ajuda a invalidar cache do navegador sempre que um asset é alterado.

### Navegação por perfil

O `layout.php` também decide quais links aparecem conforme o perfil:

- admin vê dashboard, cursos e quizzes administrativos;
- professor vê home, cursos e dashboard;
- aluno vê home, cursos e dashboard;
- visitante vê home, cursos e login.

Isso mostra que a interface participa da separação de papéis, embora a segurança real continue no backend.

### Inclusão condicional de CSS

O arquivo mapeia a página atual para folhas de estilo específicas.

Por exemplo:

- `quiz` carrega `quiz.css`;
- `gerenciar-curso` carrega `gerenciar-curso.css`;
- `certificado` carrega `certificado.css`.

Isso evita um CSS global excessivamente pesado e melhora organização visual do projeto.

### Modo normal versus modo PDF

Esse é um ponto especialmente importante:

- se não for exportação PDF, o layout completo é renderizado;
- se for PDF, ele reduz a estrutura e carrega estilos específicos para impressão.

Ou seja, o mesmo sistema suporta duas experiências:

- navegação web;
- renderização documental.

### Navbar e dropdown da conta

O layout monta:

- logotipo;
- links principais;
- botão de menu mobile;
- dropdown da conta autenticada;
- CTAs para registro quando o utilizador não está autenticado.

Isso faz dele o ponto de padronização da identidade e navegação.

### Flash messages e modal global

O arquivo também injeta:

- toasts de sucesso e erro vindos da sessão;
- container de modal reutilizável.

Isso evita repetir estrutura visual em cada view individual.

### Importância estratégica do `layout.php`

Se o backend é o cérebro, o `layout.php` é a moldura visual unificadora da plataforma.

Sem ele, cada view precisaria:

- repetir HTML base;
- repetir navbar;
- repetir flashes;
- repetir assets;
- repetir estrutura de modal.

Então ele não é apenas “um template”: ele é a espinha dorsal da experiência visual do sistema.

### Conclusão sobre `layout.php`

> O `layout.php` centraliza a identidade visual e estrutural da aplicação. Ele unifica navegação, assets, mensagens, componentes globais e ainda adapta a saída para cenários especiais, como exportação em PDF.

## 13.6 Fecho desta camada final

Com as secções 12 e 13 juntas, a documentação passa a explicar:

- como a aplicação entra e se organiza;
- como autenticação funciona;
- como cursos e aulas são governados;
- como quizzes são criados, corrigidos e conectados ao progresso;
- como certificados são calculados e emitidos;
- como dashboards resumem o sistema;
- como o layout unifica a experiência visual;
- como o restore de backup funciona com segurança.

Se quiseres resumir oralmente o valor desta visão global, uma boa frase é:

> O projeto foi construído em camadas. O `index.php` coordena a execução, os controllers transformam regras do domínio em fluxo, os models concentram persistência e cálculo, os services tratam responsabilidades especializadas e o layout/views tornam isso utilizável para cada perfil do sistema.
