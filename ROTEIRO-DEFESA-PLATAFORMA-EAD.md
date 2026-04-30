# Roteiro de Apresentacao - Plataforma EAD

## 1. Abertura da apresentacao

Boa tarde.

O projeto que vou apresentar e uma Plataforma EAD desenvolvida para resolver o problema da gestao de ensino online de forma centralizada, segura e escalavel.

O sistema foi pensado para atender tres perfis principais:

- aluno, que consome cursos, aulas, quizzes e certificados
- professor, que cria cursos, acompanha alunos e gere o conteudo
- administrador, que acompanha a operacao geral da plataforma

O objetivo principal foi construir uma plataforma funcional de ensino online, com foco nao apenas na experiencia do usuario, mas tambem em autenticacao segura, controlo de acesso, acompanhamento pedagogico, certificados e backup dos dados.

Hoje vou mostrar:

1. o problema que a plataforma resolve
2. as funcionalidades principais
3. o fluxo do aluno e do professor
4. a arquitetura tecnica
5. os mecanismos de seguranca
6. os diferenciais, desafios e possibilidades futuras

---

## 2. Problema que a plataforma resolve

Antes deste sistema, muitos processos de ensino online sao feitos de forma dispersa:

- cadastro de utilizadores sem validacao forte
- cursos sem estrutura modular clara
- pouca visibilidade sobre o progresso do aluno
- dificuldade de aplicar quizzes e controlar aprovacao
- ausencia de certificados automatizados
- inexistencia de backup e restauracao dos dados

A minha plataforma resolve isso ao reunir tudo num unico ambiente:

- autenticacao e controlo de acesso
- criacao e organizacao de cursos
- acompanhamento do progresso
- aplicacao de quizzes por aula, modulo e curso
- emissao e verificacao de certificados
- exportacao e restauracao de backups

---

## 3. Visao geral da plataforma

### 3.1 Perfis de utilizador

O sistema trabalha com tres perfis:

- aluno
- professor
- admin

Cada perfil ve apenas o que lhe pertence, com controlo de permissao baseado no papel do utilizador.

### 3.2 Modulos principais

Os modulos mais importantes da plataforma sao:

- autenticacao e seguranca
- gestao de cursos
- gestao de modulos e aulas
- quizzes e avaliacao
- dashboard por perfil
- certificados
- gestao de alunos
- backup e restore

---

## 4. Roteiro pratico do que mostrar ao vivo

## 4.1 Inicio da demonstracao

Fala sugerida:

"Vou comecar mostrando a plataforma do ponto de vista do utilizador. Primeiro, apresento a entrada do sistema, depois o fluxo de cadastro, autenticacao, criacao de conteudo, acompanhamento do aluno e, por fim, as funcionalidades avancadas como certificados e backup."

---

## 4.2 Tela inicial / home

O que mostrar:

- pagina inicial
- identidade visual da plataforma
- navegacao principal
- areas de acesso para aluno e professor

O que dizer:

"A pagina inicial funciona como porta de entrada da plataforma. Ela apresenta o sistema, orienta os diferentes perfis e organiza a navegacao para login, cadastro, cursos e dashboard."

---

## 4.3 Cadastro de aluno e professor

O que mostrar:

- tela de registo do aluno
- tela de registo do professor
- diferenca entre perfis

O que dizer:

"O sistema permite cadastro separado para aluno e professor. Durante o cadastro, o email e validado, a senha precisa obedecer regras minimas de seguranca e a conta nao fica ativa imediatamente: ela entra como pendente ate a confirmacao por email."

Ponto tecnico importante:

- o sistema valida formato do email
- bloqueia emails temporarios ou descartaveis
- verifica dominio com registo MX, quando disponivel
- exige senha com minimo de 8 caracteres, pelo menos uma letra e um numero

---

## 4.4 Confirmacao de email

O que mostrar:

- pagina de confirmacao por codigo
- explicar que o codigo chega por email

O que dizer:

"Depois do cadastro, o sistema envia um codigo de confirmacao por email. A conta so fica ativa depois que este codigo e validado. Isso impede o uso de emails falsos e garante que o utilizador realmente tem acesso ao endereco informado."

Detalhes importantes:

- o codigo de confirmacao tem 6 digitos
- ele expira em 24 horas
- se necessario, o utilizador pode pedir reenvio do codigo

---

## 4.5 Login

O que mostrar:

- pagina de login
- entrada com email e senha

O que dizer:

"No login, o sistema valida as credenciais com seguranca e impede o acesso de contas nao confirmadas. Tambem existe protecao contra tentativas excessivas de login."

Detalhes tecnicos:

- as senhas nao sao guardadas em texto puro
- o sistema usa `password_hash` para guardar a senha
- se o email nao estiver confirmado, o login e negado
- apos 5 tentativas falhadas, a conta fica temporariamente bloqueada por 15 minutos
- ao fazer login com sucesso, a sessao e regenerada para reforcar a seguranca

---

## 4.6 Recuperacao de senha

O que mostrar:

- tela "esqueci a senha"
- tela de redefinicao

O que dizer:

"Se o utilizador esquecer a senha, ele pode solicitar recuperacao. O sistema envia um link temporario por email para redefinir a credencial com seguranca."

Detalhes tecnicos:

- o link de redefinicao so e enviado para contas ja confirmadas
- o token de redefinicao expira em 15 minutos
- o token salvo na base nao fica em texto puro, ele e armazenado em hash
- depois de redefinir a senha, o token e invalidado automaticamente

Observacao importante para a defesa:

"Mesmo quando o email nao existe, o sistema devolve uma resposta generica. Isso evita enumeracao de utilizadores."

---

## 4.7 Dashboard do aluno

O que mostrar:

- painel do aluno
- cursos matriculados
- progresso
- quizzes
- certificados

O que dizer:

"O dashboard do aluno concentra a jornada de aprendizagem. Aqui ele acompanha os cursos em que esta matriculado, o progresso, as avaliacoes realizadas e os certificados ja conquistados."

---

## 4.8 Catalogo e detalhe do curso

O que mostrar:

- lista de cursos
- detalhe de um curso
- estrutura em modulos e aulas

O que dizer:

"Os cursos podem ser organizados em modulo unico ou em multiplos modulos. Essa estrutura permite uma progressao mais clara, com aulas, quizzes por aula, quizzes por modulo e quiz final."

---

## 4.9 Aula e progressao do aluno

O que mostrar:

- pagina da aula
- marcacao de aula concluida
- impacto no progresso

O que dizer:

"Ao concluir aulas, o progresso do aluno e recalculado automaticamente. Quando o curso possui quizzes, a plataforma combina o desempenho nas aulas com o desempenho avaliativo para formar o progresso final."

Detalhe tecnico util:

- se o curso tem quizzes, o progresso final considera conteudo e avaliacao
- se o aluno cumpre os criterios, o curso pode ser marcado como concluido

---

## 4.10 Quizzes e avaliacao

O que mostrar:

- criacao de quiz
- tipos de quiz
- execucao do quiz pelo aluno
- resultado

O que dizer:

"A plataforma suporta quizzes em diferentes niveis: por aula, por modulo e quiz final. O professor define tentativas, nota minima, embaralhamento e outros parametros."

Detalhes importantes:

- quizzes podem ser associados a aulas, modulos ou ao curso inteiro
- o sistema pode embaralhar perguntas e respostas
- controla numero maximo de tentativas
- pode ter tempo limite
- grava historico de tentativas
- calcula melhor resultado
- pode influenciar desbloqueio de etapas seguintes

---

## 4.11 Dashboard do professor

O que mostrar:

- painel do professor
- total de cursos
- total de alunos
- total de aulas
- indicadores de quizzes

O que dizer:

"O dashboard do professor oferece uma visao de gestao. Ele acompanha o alcance dos cursos, a base de alunos, a estrutura publicada e indicadores de avaliacao."

---

## 4.12 Criacao e gestao de cursos

O que mostrar:

- criacao de curso
- gestao modular
- criacao de aulas
- criacao de quizzes

O que dizer:

"O professor consegue criar e manter toda a estrutura do curso: titulo, descricao, modulos, aulas, quizzes e organizacao da trilha de aprendizagem."

---

## 4.13 Gestao de alunos

O que mostrar:

- pagina "Meus Alunos"
- pagina de alunos por curso
- progresso por aluno

O que dizer:

"A plataforma tambem oferece acompanhamento pedagogico. O professor consegue ver quantos cursos cada aluno frequenta com ele, o progresso medio, quizzes aprovados, atividade recente e certificacao."

Tambem pode dizer:

"Na pagina por curso, a visao e mais detalhada por turma. Na pagina 'Meus Alunos', a visao e consolidada por aluno."

---

## 4.14 Certificados

O que mostrar:

- geracao de certificado
- validacao publica
- codigo de verificacao

O que dizer:

"Quando o aluno cumpre os criterios de conclusao, a plataforma libera o certificado. Esse certificado pode ser validado publicamente atraves de um codigo unico."

Detalhes tecnicos:

- existe rota publica de verificacao
- o certificado tem codigo unico
- o sistema pode gerar versao para impressao e PDF

---

## 4.15 Backup e restore

O que mostrar:

- perfil do aluno
- perfil do professor
- botao de backup
- fluxo de restauracao

O que dizer:

"Um diferencial forte da plataforma e o sistema de backup e restauracao. O utilizador pode exportar os seus dados em estrutura organizada e, se permitido, restaurar os dados de forma validada."

Pontos tecnicos importantes:

- o backup gera `manifest.json`
- separa os dados em ficheiros estruturados
- gera `checksums` para integridade
- pode incluir password opcional no ZIP
- o restore valida estrutura, ownership e integridade
- o sistema suporta backup de aluno, de professor, de curso e global

---

## 5. Arquitetura tecnica do sistema

Fala sugerida:

"Do ponto de vista tecnico, a plataforma foi desenvolvida em PHP com organizacao em camadas, separando controllers, models, views e ficheiros de configuracao."

### 5.1 Estrutura do projeto

O sistema esta organizado em:

- `app/controllers`
- `app/models`
- `app/views`
- `config`
- `public`

### 5.2 Principais controllers

- `AuthController`: cadastro, login, confirmacao de email, recuperacao de senha
- `CourseController`: cursos e estrutura modular
- `LessonController`: progresso em aulas
- `QuizController`: execucao e avaliacao de quizzes
- `DashboardController`: dashboards por perfil
- `CertificateController`: emissao e validacao de certificados
- `ExportController`: backup e exportacao
- `ImportController`: validacao e restauracao

### 5.3 Principais models

- `User`
- `Course`
- `Enrollment`
- `Lesson`
- `Module`
- `Quiz`
- `Certificate`

---

## 6. Seguranca da plataforma

Esta e uma das partes mais importantes da defesa.

### 6.1 Seguranca no cadastro

- validacao de email
- bloqueio de emails descartaveis
- verificacao do dominio de email
- senha forte obrigatoria
- conta fica pendente ate confirmacao

### 6.2 Seguranca no login

- senha verificada por hash
- login negado para contas nao confirmadas
- bloqueio temporario apos varias tentativas
- regeneracao da sessao apos autenticacao

### 6.3 Seguranca de formularios

- protecao CSRF com token
- validacao do token nas requisicoes POST

### 6.4 Seguranca de recuperacao de senha

- token temporario
- token guardado em hash
- expiracao curta
- invalidacao apos uso
- resposta generica para evitar descoberta de emails validos

### 6.5 Seguranca de backup e restore

- validacao da estrutura do ZIP
- validacao de checksums
- validacao de ownership
- bloqueio de restauracao indevida
- controlo de acesso por perfil

### 6.6 Seguranca geral de acesso

- controle por perfil de utilizador
- professor so gere os proprios cursos
- aluno so acede ao proprio progresso e aos proprios dados
- logs de eventos importantes

---

## 7. Diferenciais do projeto

### 7.1 Diferenciais funcionais

- fluxo completo de aluno, professor e admin
- quizzes em varios niveis
- certificados com validacao
- acompanhamento detalhado de progresso
- backups estruturados e restore

### 7.2 Diferenciais tecnicos

- autenticação com confirmacao por email
- recuperacao segura de senha
- protecao contra brute force
- tokens em hash
- CSRF
- estrutura modular do codigo

---

## 8. Dificuldades encontradas

Fala sugerida:

"Durante o desenvolvimento, os maiores desafios foram garantir a seguranca da autenticacao, estruturar a progressao dos cursos por modulos e quizzes, e criar um sistema de backup que nao fosse apenas exportacao simples, mas um processo validavel e restauravel."

Pode acrescentar:

- organizacao dos papeis de acesso
- integracao de email
- persistencia do progresso
- relacao entre curso, modulo, aula e quiz
- garantias de integridade no backup

---

## 9. Melhorias futuras

Pode falar como evolucao do projeto:

- notificacoes em tempo real
- analytics mais avancado
- integracao com pagamentos
- aulas ao vivo
- integracao com armazenamento externo real
- relatorios administrativos mais completos
- autenticação multifator

---

## 10. Encerramento

Fala sugerida:

"Em conclusao, esta plataforma nao foi pensada apenas como um site de cursos, mas como um sistema EAD completo, com autenticacao segura, gestao de aprendizagem, avaliacao, certificacao e protecao dos dados. O projecto mostra nao apenas a parte visual, mas tambem preocupacao com arquitetura, integridade e seguranca, que sao essenciais em sistemas reais."

"Obrigado. Agora estou disponivel para responder as perguntas."

---

# Perguntas e respostas possiveis da banca

## 1. Sobre o objetivo do sistema

### Pergunta
Qual e o objetivo principal da plataforma?

### Resposta
O objetivo principal e centralizar o processo de ensino online, permitindo cadastro seguro, gestao de cursos, acompanhamento de alunos, aplicacao de quizzes, emissao de certificados e protecao dos dados com backup e restore.

### Pergunta
Que problema concreto esta plataforma resolve?

### Resposta
Ela resolve a fragmentacao comum em ambientes de ensino online, onde autenticacao, conteudo, avaliacao e acompanhamento costumam ficar dispersos. Aqui tudo esta unificado num unico sistema.

---

## 2. Sobre perfis de utilizador

### Pergunta
Quais sao os perfis existentes no sistema?

### Resposta
Sao tres: aluno, professor e administrador.

### Pergunta
Como o sistema diferencia o que cada um pode fazer?

### Resposta
O sistema usa controlo de acesso por perfil. Cada utilizador tem um `role`, e as rotas e funcionalidades verificam esse papel antes de permitir a acao.

### Pergunta
Um professor consegue ver cursos de outro professor?

### Resposta
Nao. O sistema verifica a propriedade do curso antes de mostrar dados de gestao, alunos ou acoes sensiveis.

---

## 3. Sobre cadastro

### Pergunta
O que acontece quando um utilizador se cadastra?

### Resposta
O sistema valida nome, email e senha. Se estiver tudo correto, cria a conta em estado pendente, gera um codigo de confirmacao, guarda o hash desse codigo na base e envia o codigo por email.

### Pergunta
A conta fica ativa imediatamente?

### Resposta
Nao. A conta so fica ativa depois da confirmacao do email.

### Pergunta
Por que isso e importante?

### Resposta
Porque evita contas falsas, garante que o utilizador realmente controla o email informado e melhora a confiabilidade do sistema.

### Pergunta
O sistema aceita qualquer email?

### Resposta
Nao. Ele valida o formato do email, bloqueia dominios descartaveis e, quando disponivel, verifica se o dominio possui registo MX para recebimento.

---

## 4. Sobre confirmacao por email

### Pergunta
Quando o email de confirmacao e enviado?

### Resposta
Ele e enviado logo apos o cadastro ser validado com sucesso.

### Pergunta
E se o utilizador nao receber o email?

### Resposta
Existe um fluxo de reenvio do codigo de confirmacao.

### Pergunta
O que o utilizador recebe?

### Resposta
Ele recebe um codigo numerico de 6 digitos para digitar na pagina de confirmacao.

### Pergunta
O codigo expira?

### Resposta
Sim. O codigo expira em 24 horas.

### Pergunta
O codigo fica guardado em texto puro na base?

### Resposta
Nao. O sistema guarda apenas o hash do codigo, nao o codigo em texto puro.

---

## 5. Sobre login

### Pergunta
Como o login e feito?

### Resposta
O utilizador informa email e senha. O sistema procura o utilizador, verifica se esta bloqueado, valida a senha com hash, verifica se o email ja foi confirmado e so depois cria a sessao.

### Pergunta
A senha esta guardada em texto puro?

### Resposta
Nao. A senha e guardada com `password_hash`, que e uma funcao segura para armazenamento de credenciais.

### Pergunta
O que acontece se a senha estiver errada varias vezes?

### Resposta
Apos 5 tentativas falhadas, a conta fica bloqueada temporariamente por 15 minutos.

### Pergunta
Por que isso foi implementado?

### Resposta
Para reduzir risco de ataques de forca bruta.

### Pergunta
Uma conta nao confirmada consegue fazer login?

### Resposta
Nao. O login e negado ate que o email seja confirmado.

### Pergunta
O sistema faz algo com a sessao apos o login?

### Resposta
Sim. Ele regenera o ID da sessao para reduzir risco de fixation de sessao.

---

## 6. Sobre recuperacao de senha

### Pergunta
Quando o email de recuperacao de senha e enviado?

### Resposta
Quando o utilizador usa a funcionalidade "esqueci a senha" e a conta existe e ja esta confirmada.

### Pergunta
O sistema diz se o email existe ou nao?

### Resposta
Nao de forma explicita. A resposta e generica exatamente para evitar enumeracao de utilizadores.

### Pergunta
O que e enviado no email de recuperacao?

### Resposta
Um link temporario para redefinir a senha.

### Pergunta
Qual o tempo de expiracao do link?

### Resposta
15 minutos.

### Pergunta
O token de reset e salvo em texto puro?

### Resposta
Nao. O token tambem e convertido em hash antes de ser guardado na base.

### Pergunta
O que acontece depois de redefinir a senha?

### Resposta
O token e invalidado, as tentativas de login sao zeradas e o bloqueio temporario e removido, se existir.

---

## 7. Sobre seguranca geral

### Pergunta
Como voce protege os formularios contra CSRF?

### Resposta
O sistema gera um token CSRF aleatorio na sessao e injeta esse token nos formularios. Nas requisicoes POST, o token e validado com comparacao segura.

### Pergunta
Como o sistema trata logs?

### Resposta
Eventos importantes sao registados, como login, falhas, confirmacao de email, pedidos de recuperacao e operacoes de backup. Isso ajuda na auditoria e no diagnostico.

### Pergunta
Existe protecao contra XSS?

### Resposta
Sim. As saídas usam escape HTML, e ha funcoes auxiliares para sanitizar conteudo HTML antes de inserir na interface.

### Pergunta
Existe controlo de permissao por rotas?

### Resposta
Sim. O sistema exige autenticacao e, em varias rotas, tambem verifica o perfil do utilizador antes de permitir acesso.

---

## 8. Sobre cursos

### Pergunta
Como os cursos sao estruturados?

### Resposta
Eles podem funcionar em modo de modulo unico ou multiplos modulos. Cada curso pode ter aulas, quizzes associados a aulas, quizzes por modulo e quiz final.

### Pergunta
Por que a estrutura modular e importante?

### Resposta
Porque melhora a organizacao pedagogica, facilita a progressao do aluno e permite regras de desbloqueio por etapa.

---

## 9. Sobre progresso do aluno

### Pergunta
Como o progresso e calculado?

### Resposta
O sistema acompanha conclusao de aulas e, se o curso tiver quizzes, tambem considera o desempenho nas avaliacoes. Assim, o progresso final representa melhor a aprendizagem do aluno.

### Pergunta
O progresso muda automaticamente?

### Resposta
Sim. Ao concluir aulas ou realizar quizzes, o sistema recalcula o progresso.

### Pergunta
Quando o curso e considerado concluido?

### Resposta
Quando o aluno atinge os criterios definidos pela plataforma, incluindo progresso e aprovacao nos quizzes exigidos, dependendo da configuracao do curso.

---

## 10. Sobre quizzes

### Pergunta
Que tipos de quizzes existem?

### Resposta
Quiz de aula, quiz de modulo e quiz final.

### Pergunta
O sistema guarda historico de tentativas?

### Resposta
Sim. Ele guarda tentativas, pontuacao, percentual, numero da tentativa, tempo gasto, aprovacao e data de realizacao.

### Pergunta
O quiz pode ter tempo limite?

### Resposta
Sim. O sistema suporta tempo limite.

### Pergunta
Pode limitar tentativas?

### Resposta
Sim. O professor pode configurar tentativas maximas.

### Pergunta
Pode embaralhar perguntas e respostas?

### Resposta
Sim. Existem opcoes para embaralhamento de perguntas e de respostas.

### Pergunta
Como o sistema decide se o aluno foi aprovado?

### Resposta
Com base na pontuacao obtida comparada com a nota minima definida para o quiz.

---

## 11. Sobre certificados

### Pergunta
Quando o certificado e gerado?

### Resposta
Quando o aluno cumpre os criterios de conclusao definidos para o curso ou modulo.

### Pergunta
Como se valida um certificado?

### Resposta
Cada certificado possui um codigo unico e uma rota de verificacao publica. Assim, qualquer pessoa pode validar a autenticidade.

### Pergunta
O certificado pode ser descarregado?

### Resposta
Sim. O sistema suporta visualizacao e geracao para download, incluindo PDF.

---

## 12. Sobre backup e restore

### Pergunta
Que tipos de backup a plataforma suporta?

### Resposta
Backup de aluno, backup de professor, backup por curso, backup de dados de alunos e backup global.

### Pergunta
O backup e apenas um JSON simples?

### Resposta
Nao. Ele usa uma estrutura profissional com `manifest`, ficheiros de dados separados, anexos e `checksums` para validacao de integridade.

### Pergunta
Como a integridade do backup e garantida?

### Resposta
O sistema gera hashes SHA-256 e valida os checksums antes da restauracao.

### Pergunta
O backup pode ser protegido?

### Resposta
Sim. Pode ser gerado com password opcional e encriptacao do ZIP.

### Pergunta
Como o restore evita problemas?

### Resposta
Ele valida a estrutura do pacote, o manifesto, a integridade e a propriedade dos dados antes de restaurar.

### Pergunta
Um utilizador pode restaurar backup de outro?

### Resposta
Nao. O sistema valida ownership antes da restauracao.

### Pergunta
Existe backup automatico?

### Resposta
Sim. O sistema foi preparado para rotinas automaticas, historico de backups e envio por email, conforme configuracao.

---

## 13. Sobre envio de email

### Pergunta
Em que momentos a plataforma envia email?

### Resposta
Principalmente em tres situacoes:

- confirmacao de conta apos cadastro
- reenvio de confirmacao
- recuperacao de senha

Tambem pode haver notificacoes ligadas a backup, dependendo da configuracao.

### Pergunta
Como o email e enviado?

### Resposta
A plataforma usa configuracao SMTP e a biblioteca PHPMailer.

### Pergunta
Se o SMTP falhar, o que acontece?

### Resposta
O sistema regista a falha em log e pode usar modo de fallback conforme configuracao.

---

## 14. Sobre arquitetura e manutencao

### Pergunta
Por que voce separou controllers, models e views?

### Resposta
Para melhorar organizacao, manutencao e reutilizacao do codigo. Cada camada tem uma responsabilidade clara.

### Pergunta
Essa plataforma pode crescer?

### Resposta
Sim. A estrutura modular permite adicionar novas funcionalidades sem reescrever o sistema inteiro.

### Pergunta
Ela esta pronta para producao real?

### Resposta
Ela ja implementa varias preocupacoes de ambiente real, como autenticacao segura, recuperacao de senha, logging, controlo de acesso e backup. Para producao total, eu ainda destacaria melhorias futuras como MFA, observabilidade mais avancada e maior automacao operacional.

---

## 15. Perguntas tecnicas mais duras que podem surgir

### Pergunta
Por que guardar hash do token e nao o token puro?

### Resposta
Porque, se a base de dados for exposta, o atacante nao consegue usar diretamente os tokens de confirmacao ou reset. O valor util enviado ao utilizador nao fica armazenado em texto puro.

### Pergunta
Por que usar resposta generica na recuperacao de senha?

### Resposta
Para impedir que uma pessoa descubra quais emails estao cadastrados no sistema.

### Pergunta
Por que usar `session_regenerate_id` no login?

### Resposta
Para reduzir o risco de fixation de sessao, garantindo que a sessao autenticada tenha um identificador novo.

### Pergunta
Por que usar bloqueio temporario e nao bloqueio definitivo?

### Resposta
Porque bloqueio temporario reduz ataques de forca bruta sem penalizar permanentemente o utilizador legitimo.

### Pergunta
Como evitar que um aluno aceda a areas administrativas?

### Resposta
As rotas verificam autenticacao e perfil. Sem o `role` correto, o acesso e negado.

### Pergunta
Como voce protege os dados sensiveis dos backups?

### Resposta
Com controlo de acesso, validacao de propriedade, integridade por checksum e possibilidade de protecao por password no pacote exportado.

### Pergunta
O sistema suporta auditoria?

### Resposta
Sim. Ele regista eventos importantes em log, como autenticacao, falhas e operacoes criticas.

---

## 16. Resposta curta para quando a banca pedir "resuma a seguranca do sistema"

Pode responder assim:

"A seguranca da plataforma foi tratada em varias camadas: validacao forte no cadastro, confirmacao obrigatoria por email, senha com hash, bloqueio por tentativas de login, tokens temporarios em hash para recuperacao de senha, protecao CSRF nos formularios, controlo de acesso por perfil, sanitizacao de conteudo e logs de auditoria. Alem disso, os backups incluem verificacao de integridade e validacao de ownership antes do restore."

---

## 17. Resposta curta para quando a banca pedir "resuma o sistema em 1 minuto"

Pode responder assim:

"A plataforma EAD e um sistema completo de ensino online com tres perfis: aluno, professor e administrador. Ela permite cadastro seguro com confirmacao por email, login protegido, recuperacao de senha, criacao de cursos modulares, aulas, quizzes, dashboards de acompanhamento, emissao de certificados e backup dos dados. Tecnicamente, foi organizada em controllers, models e views, com preocupacao real com seguranca, integridade e escalabilidade."

---

## 18. Dica final para a sua defesa

Durante a apresentacao:

- fale com calma
- apresente primeiro o problema, depois a solucao
- mostre a visao do utilizador antes da parte tecnica
- quando falar de seguranca, use exemplos concretos
- quando a banca fizer perguntas, responda em tres passos:

1. o que e
2. por que foi feito
3. que beneficio traz

Exemplo:

"A confirmacao por email foi implementada para garantir que o email pertence ao utilizador. Isso melhora a confiabilidade da plataforma e evita contas falsas."

