# Questionario, Guia e Manual de Defesa Final - Plataforma EAD

## 1. Objetivo deste arquivo

Este documento foi preparado para ajudar a equipa a:

- entender a plataforma de forma completa;
- responder perguntas dos jurados com seguranca;
- justificar escolhas tecnicas e funcionais;
- assumir limitacoes de forma madura;
- saber onde mexer caso seja pedido algum ajuste ao vivo.

Importante:

- este guia foi montado a partir do codigo real do projeto;
- algumas documentacoes antigas do projeto nao refletem 100% o estado atual;
- por isso, na defesa, o ideal e sempre explicar o que esta realmente implementado hoje.

---

## 2. Resumo executivo da plataforma

### Nome do projeto

Plataforma EAD

### O que ela faz

E uma plataforma web de ensino a distancia que permite:

- cadastro e autenticacao de usuarios;
- separacao por perfis: aluno, professor e administrador;
- criacao e gestao de cursos;
- organizacao de cursos em modulo unico ou multiplos modulos;
- criacao e gestao de aulas;
- matricula de alunos;
- acompanhamento de progresso;
- aplicacao de quizzes;
- calculo de nota final;
- emissao de certificados;
- validacao publica de certificados;
- geracao de certificado em PDF.

### Problema que a plataforma resolve

Ela centraliza num unico sistema o ciclo principal do ensino online:

- publicacao de cursos;
- distribuicao de conteudo;
- acompanhamento do aluno;
- avaliacao;
- comprovacao de conclusao.

### Publico-alvo

- instituicoes de ensino;
- centros de formacao;
- professores independentes;
- projetos de capacitacao profissional;
- escolas medias ou tecnicas que desejam digitalizar parte do processo.

---

## 3. Pitch curto para apresentar aos jurados

Se pedirem uma apresentacao rapida, podem usar algo assim:

> A nossa plataforma EAD foi desenvolvida para gerir o processo de ensino online de ponta a ponta. O sistema permite que professores criem cursos e aulas, alunos se matriculem, acompanhem o progresso, realizem quizzes, e ao final recebam certificados verificaveis. A arquitetura segue o padrao MVC em PHP com MySQL, usando controle por perfis, sessoes, prepared statements com PDO e regras de negocio para progresso, avaliacao e certificacao.

---

## 4. Stack tecnologica

### Backend

- PHP
- PDO
- MySQL

### Frontend

- HTML5
- CSS3
- JavaScript Vanilla

### Infraestrutura local

- Apache
- XAMPP

### Recurso extra

- Node.js + Puppeteer para gerar certificados em PDF

### Porque essa stack foi escolhida

- PHP e MySQL sao acessiveis, amplamente ensinados e praticos para projetos academicos;
- MVC melhora organizacao e manutencao;
- PDO com prepared statements melhora seguranca;
- JavaScript puro reduz dependencia de frameworks;
- Puppeteer resolve bem a geracao visual de PDF.

---

## 5. Arquitetura do sistema

### Padrao arquitetural

O projeto segue o padrao MVC:

- `Models`: lidam com banco de dados e regras de persistencia;
- `Controllers`: tratam regras de negocio e fluxo de requisicoes;
- `Views`: exibem o conteudo para o usuario;
- `public/index.php`: funciona como front controller e roteador principal.

### Fluxo de uma requisicao

1. O utilizador acessa uma URL.
2. O `public/index.php` recebe a requisicao.
3. A pagina e identificada por `$_GET['page']` ou por URL amigavel.
4. O controller correspondente processa a regra.
5. O model conversa com o banco.
6. A view e renderizada.
7. A view e encaixada em `app/views/layout.php`.
8. A resposta final e mostrada no navegador.

### Vantagens dessa arquitetura

- separa responsabilidades;
- facilita manutencao;
- facilita testes manuais;
- permite alterar interface sem quebrar regras de negocio;
- ajuda na apresentacao tecnica porque o fluxo fica claro.

---

## 6. Estrutura principal de pastas

### Pasta `public/`

Contem a entrada principal da aplicacao, arquivos CSS, JavaScript e uploads.

Arquivos importantes:

- `public/index.php`
- `public/css/`
- `public/js/`
- `public/uploads/`

### Pasta `app/controllers/`

Contem os controladores da plataforma.

Principais:

- `AuthController.php`
- `CourseController.php`
- `LessonController.php`
- `QuizController.php`
- `DashboardController.php`
- `AdminController.php`
- `CertificateController.php`
- `ModuleController.php`

### Pasta `app/models/`

Contem os modelos de dados.

Principais:

- `User.php`
- `Course.php`
- `Lesson.php`
- `Enrollment.php`
- `Quiz.php`
- `Module.php`
- `Certificate.php`

### Pasta `app/views/`

Contem as interfaces HTML/PHP.

Views mais relevantes:

- `layout.php`
- `home.php`
- `cursos.php`
- `curso-detail.php`
- `aula.php`
- `quiz.php`
- `perfil.php`
- `dashboard-aluno.php`
- `dashboard-professor.php`
- `dashboard-admin.php`
- `certificado.php`

### Pasta `config/`

Contem configuracoes globais.

Arquivos:

- `database.php`
- `app.php`
- `helpers.php`
- `autoload.php`

### Pasta `scripts/`

Contem scripts auxiliares, incluindo geracao de PDF.

Principal:

- `scripts/generate-certificate-pdf.js`

---

## 7. Perfis de utilizador

### 1. Aluno

Pode:

- registrar-se;
- fazer login;
- ver cursos;
- matricular-se;
- assistir aulas;
- marcar aulas como concluidas;
- fazer quizzes;
- acompanhar desempenho;
- visualizar e baixar certificados quando elegivel.

### 2. Professor

Pode:

- criar cursos;
- editar cursos proprios;
- criar modulos;
- criar aulas;
- criar quizzes;
- ver dashboards com dados dos seus cursos;
- acompanhar alunos matriculados.

### 3. Administrador

Pode:

- ver estatisticas globais;
- listar usuarios;
- listar cursos;
- alterar status de cursos;
- apagar usuarios;
- gerir quizzes no painel administrativo.

### Pergunta classica

**Pergunta:** Como o sistema controla o que cada perfil pode fazer?  
**Resposta:** O sistema usa sessao e verificacao de `role`. Depois do login, os dados do utilizador ficam em `$_SESSION['usuario']`. Os controllers verificam autenticacao e permissao antes de executar a acao.

---

## 8. Funcionalidades principais

### Autenticacao

- cadastro de usuario;
- login com verificacao de senha;
- logout;
- sessoes;
- controle de tentativas de login.

### Cursos

- criacao de curso por professor;
- listagem de cursos;
- busca;
- detalhe do curso;
- edicao e remocao;
- status do curso.

### Modulos

- suporte a curso de modulo unico;
- suporte a curso com multiplos modulos;
- ordenacao de modulos;
- bloqueio progressivo por modulo para aluno.

### Aulas

- criacao de aulas por professor;
- aulas associadas a curso e modulo;
- tipos de aula: texto, video, pdf, arquivo;
- progresso por conclusao.

### Quizzes

- quiz de aula;
- quiz de modulo;
- quiz final de curso;
- limite de tentativas;
- correcao automatica;
- tempo limite opcional;
- embaralhamento de perguntas e respostas;
- ocultacao ou exibicao de nota e respostas.

### Certificados

- certificado por modulo;
- certificado final do curso;
- validacao por codigo;
- pagina publica de verificacao;
- exportacao PDF.

### Dashboards

- aluno: progresso e cursos;
- professor: cursos, alunos, media e aprovacao;
- admin: estatisticas gerais.

---

## 9. Banco de dados - visao geral

### Tabelas principais

- `users`
- `courses`
- `lessons`
- `enrollments`
- `quizzes`
- `questions`
- `quiz_results`
- `quiz_attempts`
- `quiz_attempt_answers`
- `lesson_progress`
- `messages`
- `certificates`
- `course_modules`
- `login_attempts`

### Observacao importante para a defesa

O projeto tem duas caracteristicas relevantes:

1. existe um `schema.sql` base;
2. alguns models fazem ajuste automatico do schema em tempo de execucao, criando colunas e tabelas que possam faltar.

### O que isso significa

Se perguntarem sobre migracoes, a resposta honesta e:

> O projeto tem um script base de banco, mas tambem implementa autoajustes em alguns modelos, como `Course`, `Lesson`, `Module`, `Quiz`, `Certificate` e `Auth`, para manter compatibilidade com estruturas anteriores e reduzir falhas de arranque em ambiente local.

### Tabelas e responsabilidades

#### `users`

Guarda:

- nome;
- email;
- senha hash;
- perfil;
- fotografia.

#### `courses`

Guarda:

- titulo;
- descricao;
- professor dono;
- categoria;
- thumbnail;
- status;
- tipo de estrutura do curso.

#### `course_modules`

Guarda:

- modulos do curso;
- ordem;
- se e modulo padrao.

#### `lessons`

Guarda:

- curso;
- modulo;
- titulo;
- descricao;
- tipo;
- conteudo;
- arquivo;
- video;
- ordem.

#### `enrollments`

Guarda:

- matricula do aluno;
- curso;
- progresso;
- datas de inscricao e conclusao.

#### `quizzes`

Guarda:

- aula, curso e modulo relacionados;
- tipo de quiz;
- dificuldade;
- peso;
- nota minima;
- tentativas;
- tempo limite;
- configuracoes de exibicao.

#### `questions`

Guarda:

- perguntas;
- opcoes;
- resposta correta;
- explicacao;
- pontos.

#### `quiz_attempts`

Guarda:

- tentativa do aluno;
- pontuacao;
- percentual;
- tempo;
- aprovacao.

#### `quiz_attempt_answers`

Guarda:

- respostas por questao em cada tentativa.

#### `lesson_progress`

Guarda:

- se uma aula foi concluida;
- quando foi concluida.

#### `certificates`

Guarda:

- tipo de certificado;
- codigo de verificacao;
- nota;
- data de emissao.

#### `login_attempts`

Guarda:

- tentativas de login por email;
- momento da ultima tentativa;
- IP.

---

## 10. Regras de negocio mais importantes

### 1. Apenas professor cria curso

Essa regra esta no `CourseController`.

### 2. Apenas dono do curso ou admin pode alterar curso

Isso evita que um professor altere curso de outro.

### 3. Aula pertence a curso e modulo

Mesmo cursos antigos ou simples recebem modulo padrao para manter a estrutura consistente.

### 4. Matricula e unica por aluno e curso

A tabela `enrollments` tem chave unica para evitar duplicidade.

### 5. Progresso do curso nao depende so de aula

Quando ha quizzes, o progresso combina:

- 60% aulas;
- 40% desempenho em avaliacao.

### 6. Curso so e dado como concluido quando os criterios forem atendidos

O aluno precisa:

- concluir aulas;
- cumprir regras de quizzes;
- ser aprovado em obrigatorios;
- ser aprovado no quiz final, quando existir.

### 7. Certificado nao e simplesmente "clicar e baixar"

O certificado so existe se o aluno estiver elegivel segundo as regras de negocio.

### 8. Se o aluno perder elegibilidade, certificado pode ser revogado

O model de certificado sincroniza estado atual com criterios reais.

### 9. Cada quiz possui numero maximo de tentativas

Se o aluno atingir o limite, nao pode continuar.

### 10. A nota final do curso e ponderada

A nota final considera grupos de dificuldade e pesos definidos no sistema.

---

## 11. Seguranca implementada

### O que existe

- `password_hash()` para guardar senhas;
- `password_verify()` no login;
- PDO com prepared statements;
- validacao de permissao;
- sessao para autenticacao;
- token CSRF nas views e helpers;
- limitacao de tentativas de login;
- sanitizacao e escape em varios pontos;
- verificacao de extensao e MIME em uploads;
- validacao de URL para render de certificado.

### Como responder se perguntarem se o sistema e seguro

Resposta equilibrada:

> O sistema implementa boas praticas importantes para o nivel do projeto: hash de senha, prepared statements, controle por sessao e papel, validacao de upload, CSRF helper e rate limiting de login. Ainda assim, seguranca e um processo continuo, entao numa versao de producao seria recomendavel ampliar auditoria, logs estruturados, middleware mais centralizado, testes de seguranca e hardening do servidor.

### Pergunta classica

**Pergunta:** Como voces evitaram SQL Injection?  
**Resposta:** Usamos PDO com prepared statements em models e controllers para separar query dos dados informados pelo utilizador.

### Pergunta classica

**Pergunta:** As senhas sao guardadas em texto puro?  
**Resposta:** Nao. As senhas sao guardadas com hash usando `password_hash`, e verificadas com `password_verify`.

### Pergunta classica

**Pergunta:** Como evitam que um aluno aceda area de professor?  
**Resposta:** O sistema valida a sessao e o `role` antes da acao. Mesmo que a URL seja digitada manualmente, o controller verifica permissao.

---

## 12. Páginas e rotas mais importantes

As rotas sao controladas principalmente por `public/index.php`.

### Paginas GET importantes

- `page=home`
- `page=login`
- `page=registro`
- `page=registro-professor`
- `page=cursos`
- `page=curso&id=...`
- `page=aula&lesson_id=...&course_id=...`
- `page=quiz&quiz_id=...`
- `page=dashboard`
- `page=meus-cursos`
- `page=meus-alunos`
- `page=minhas-aulas`
- `page=perfil`
- `page=criar-curso`
- `page=criar-modulo`
- `page=criar-aula`
- `page=criar-quiz`
- `page=editar-curso`
- `page=editar-aula`
- `page=gerenciar-curso`
- `page=alunos-curso`
- `page=admin-cursos`
- `page=admin-quizzes`
- `page=certificado`
- `certificado/CODIGO` para verificacao publica

### Acoes POST importantes

- `login`
- `registrar`
- `matricular_curso`
- `atualizar_perfil`
- `alterar_senha`
- `criar_curso`
- `atualizar_curso`
- `deletar_curso`
- `criar_modulo`
- `atualizar_modulo`
- `mover_modulo`
- `criar_aula`
- `atualizar_aula`
- `deletar_aula`
- `reordenar_aulas`
- `criar_quiz`
- `deletar_quiz`
- `adicionar_questao`
- `deletar_questao`
- `marcar_concluida`
- `desmarcar_concluida`
- `responder_quiz`
- `atualizar_progresso`
- `restaurar_matricula`
- `remover_matricula`

---

## 13. Pontos fortes reais do projeto

Se quiserem defender com firmeza, estes sao pontos muito bons do sistema:

### 1. Nao e apenas um CRUD

O sistema tem regras reais de aprendizagem:

- progressao;
- trilha por modulo;
- avaliacao;
- certificacao.

### 2. Existe separacao por perfis

Isso mostra preocupacao com permissao e experiencia diferente por utilizador.

### 3. O quiz e relativamente rico

Tem:

- limite de tentativas;
- tempo limite;
- embaralhamento;
- nota minima;
- ocultacao de nota e respostas;
- historico;
- tentativa detalhada.

### 4. O certificado e verificavel

Isso agrega valor academico e credibilidade.

### 5. O professor recebe analise de desempenho

Nao e so publicar conteudo; ha leitura de resultado.

### 6. O sistema tenta ser resiliente em ambiente local

Varios models fazem `ensureSchema`, ajudando a manter compatibilidade quando o banco ainda nao esta totalmente atualizado.

---

## 14. Limitacoes reais e como assumir com maturidade

Nenhum projeto academico fica perfeito. O importante e responder bem.

### Limitacoes honestas

#### 1. O roteamento ainda e centralizado num unico `index.php`

Isso funciona bem para o escopo atual, mas pode crescer demais com o tempo.

#### 2. Parte da evolucao do banco ocorre em runtime

E pratico para o desenvolvimento, mas em ambiente grande o ideal seria pipeline formal de migracoes versionadas.

#### 3. Nem toda seguranca esta centralizada em middleware

Muitas validacoes estao corretas, mas distribuidas em controllers e helpers.

#### 4. O projeto depende de ambiente local especifico para PDF

A geracao do certificado depende de Node, Chrome e Puppeteer.

#### 5. Ainda faltariam testes automatizados mais robustos

O projeto tem scripts auxiliares, mas ainda pode crescer em testes unitarios e integrados.

### Resposta pronta

> Reconhecemos que o sistema ainda tem espaco para amadurecer em migracoes formais, middleware e testes automatizados. Mesmo assim, para o escopo do projeto, ja entrega funcionalidades relevantes, regras de negocio consistentes e uma base bem organizada para evolucao futura.

---

## 15. Perguntas e respostas - visao geral do projeto

### 1. Qual foi a motivacao do projeto?

Queremos resolver a necessidade de digitalizar o processo de ensino, acompanhamento e certificacao numa unica plataforma web.

### 2. Qual e o principal diferencial da plataforma?

Ela integra cursos, aulas, quizzes, progresso, dashboards e certificados verificaveis, em vez de oferecer apenas listagem de conteudo.

### 3. Que problema concreto ela resolve?

Resolve o problema de gestao fragmentada do ensino online, centralizando conteudo, avaliacao e comprovacao de conclusao.

### 4. Quem sao os atores principais do sistema?

Aluno, professor e administrador.

### 5. O sistema e web ou desktop?

E uma aplicacao web, desenvolvida para rodar em servidor Apache com PHP e MySQL.

### 6. O sistema funciona so localmente?

No estado atual, ele foi preparado e testado principalmente em ambiente local, mas a arquitetura permite publicacao em servidor adequado com os ajustes de ambiente.

### 7. Porque escolheram PHP e MySQL?

Porque oferecem simplicidade, ampla documentacao, facilidade de instalacao, boa produtividade academica e sao suficientes para o escopo do projeto.

### 8. Porque nao usaram framework como Laravel?

Porque o objetivo tambem era demonstrar dominio das bases da arquitetura MVC e da construcao de um sistema organizado sem depender de framework pesado.

### 9. A plataforma e escalavel?

Para o escopo atual, sim. Para escala muito maior, recomendariamos modularizar melhor rotas, servicos, migracoes, cache e testes.

### 10. O sistema esta pronto para producao?

Ele esta funcional e consistente para demonstracao e evolucao, mas para producao plena ainda seria ideal reforcar deploy, observabilidade, testes e seguranca operacional.

---

## 16. Perguntas e respostas - arquitetura e codigo

### 1. Onde comeca a aplicacao?

Em `public/index.php`, que inicia sessao, carrega configuracoes e faz o roteamento.

### 2. Como o MVC aparece no projeto?

Controllers processam a regra, models acessam o banco e views exibem os dados.

### 3. Como as views recebem os dados?

Os controllers preparam os dados e a funcao de renderizacao injeta esses dados na view correspondente.

### 4. Porque existe um `layout.php`?

Para padronizar cabecalho, rodape, CSS, JS, mensagens flash e estrutura geral.

### 5. Como a navegação muda por perfil?

O `layout.php` monta links diferentes conforme o `role` do utilizador autenticado.

### 6. Onde estao as regras de curso?

Principalmente em `CourseController.php` e `Course.php`.

### 7. Onde estao as regras de aula?

Principalmente em `LessonController.php` e `Lesson.php`.

### 8. Onde estao as regras de quiz?

Em `QuizController.php` e `Quiz.php`.

### 9. Onde estao as regras de certificado?

Em `CertificateController.php` e `Certificate.php`.

### 10. O sistema usa orientacao a objetos?

Sim. Controllers e models sao classes PHP com responsabilidades bem definidas.

---

## 17. Perguntas e respostas - autenticacao e autorizacao

### 1. Como funciona o login?

O utilizador informa email e senha. O sistema procura o email, valida a senha com `password_verify`, e grava os dados principais em `$_SESSION['usuario']`.

### 2. Como funciona o logout?

O sistema destrói a sessao e redireciona para a pagina inicial.

### 3. Como evitam brute force?

Existe controle de tentativas em `login_attempts`, com bloqueio temporario apos varias falhas.

### 4. O utilizador pode escolher qualquer papel no registro?

O sistema suporta papeis definidos, mas a logica normaliza para perfis permitidos e o fluxo pode ser controlado pelas paginas disponiveis.

### 5. Como validam permissao?

Com verificacoes de sessao e `role` no controller antes de executar a acao.

### 6. O professor pode editar curso de outro professor?

Nao. O controller compara o professor logado com o `teacher_id` do curso.

### 7. O admin pode alterar cursos?

Sim, especialmente em operacoes administrativas como mudanca de status.

### 8. Ha protecao de sessao?

Ha uso de sessao para autenticar o utilizador e restringir acoes por perfil.

### 9. Existe CSRF?

O projeto possui helpers para token CSRF e formularios usam esse token. E uma estrutura importante ja prevista no sistema.

### 10. Porque isso e importante?

Porque autenticacao sem autorizacao forte nao protege o sistema. E preciso saber quem entrou e o que aquela pessoa pode fazer.

---

## 18. Perguntas e respostas - cursos, modulos e aulas

### 1. Um curso pode ter varios modulos?

Sim. O sistema suporta `single_module` e `multi_module`.

### 2. O que acontece se o curso for simples?

O sistema cria ou usa um modulo padrao para manter a compatibilidade da estrutura.

### 3. Como as aulas sao organizadas?

Por curso, modulo e ordem.

### 4. Quais tipos de aula existem?

- video
- pdf
- texto
- arquivo

### 5. O professor pode reordenar aulas?

Sim. Existe logica para reordenacao no sistema.

### 6. Como o aluno conclui uma aula?

Marcando a aula como concluida, o que grava em `lesson_progress`.

### 7. O progresso e atualizado automaticamente?

Sim. Ao marcar ou desmarcar aula, o sistema recalcula o progresso do curso.

### 8. O aluno pode aceder qualquer modulo?

Nem sempre. Em contexto de aluno, o sistema trabalha com desbloqueio progressivo.

### 9. Porque usar modulos?

Porque ajudam a estruturar melhor o curso, organizar progressao e aplicar certificados parciais.

### 10. Porque isso e relevante na defesa?

Porque mostra que o projeto pensa no desenho pedagogico, nao apenas no armazenamento de paginas.

---

## 19. Perguntas e respostas - quizzes e avaliacao

### 1. Que tipos de quiz existem?

- quiz de aula;
- quiz de modulo;
- quiz final.

### 2. O quiz e corrigido automaticamente?

Sim. O sistema compara a resposta enviada com a resposta correta e calcula pontuacao.

### 3. Existe historico de tentativas?

Sim. As tentativas sao guardadas em `quiz_attempts` e as respostas em `quiz_attempt_answers`.

### 4. Existe limite de tentativas?

Sim. Cada quiz tem `tentativas_maximas`.

### 5. O quiz pode ter tempo limite?

Sim. O model e a view suportam tempo limite e envio automatico ao esgotar o tempo.

### 6. O quiz pode embaralhar perguntas?

Sim.

### 7. O quiz pode embaralhar respostas?

Sim.

### 8. O professor pode ocultar respostas corretas?

Sim. Existe configuracao para mostrar ou ocultar respostas e nota.

### 9. Como a nota final do curso e calculada?

Ela considera os quizzes do curso, agrupando por dificuldade e aplicando pesos na composicao final.

### 10. O aluno so precisa responder ou precisa ser aprovado?

Precisa ser aprovado de acordo com a nota minima e regras de obrigatoriedade.

### 11. O que significa quiz obrigatorio?

Significa que ele entra como requisito para conclusao e certificacao.

### 12. Pode existir mais de um quiz final no mesmo curso?

O controller evita duplicidade de quiz final por curso.

### 13. Pode existir mais de um quiz de modulo no mesmo modulo?

O controller evita essa duplicidade.

### 14. Pode existir mais de um quiz de aula na mesma aula?

O controller tambem evita essa duplicidade.

### 15. Porque essa decisao e boa?

Porque simplifica a logica pedagogica, a navegacao e o calculo de desempenho.

---

## 20. Perguntas e respostas - progresso e certificacao

### 1. Como o sistema sabe que um curso foi concluido?

Ele junta progresso das aulas com desempenho em quizzes e verifica se todos os criterios foram satisfeitos.

### 2. O progresso e sempre 100% quando termina as aulas?

Nao necessariamente. Se houver quizzes, a avaliacao tambem influencia o progresso.

### 3. Pode haver certificado sem quiz final?

No caso de modulo ou em alguns cenarios de estrutura simples, a elegibilidade depende da configuracao do curso e dos criterios implementados.

### 4. O certificado e gerado automaticamente?

Ele e sincronizado automaticamente quando o aluno passa a cumprir os criterios.

### 5. O certificado pode ser validado por terceiros?

Sim. Existe verificacao publica por codigo.

### 6. O que garante autenticidade do certificado?

Cada certificado possui codigo unico e pagina de verificacao publica.

### 7. O PDF e um print simples?

Nao exatamente. O sistema renderiza a pagina do certificado e gera um PDF com Puppeteer.

### 8. Porque usar Puppeteer?

Porque permite gerar PDF visualmente consistente a partir da interface HTML/CSS.

### 9. O certificado final depende de que?

Da conclusao dos modulos e da aprovacao nos quizzes finais.

### 10. Existem certificados parciais?

Sim, por modulo.

---

## 21. Perguntas e respostas - dashboard e analise

### 1. O que o aluno ve no dashboard?

Cursos matriculados, progresso, cursos concluidos e em progresso.

### 2. O que o professor ve no dashboard?

Cursos criados, total de alunos, total de aulas, total de quizzes, media de quiz e taxa de aprovacao.

### 3. O que o admin ve?

Estatisticas globais, usuarios, cursos e dados administrativos.

### 4. Ha analise pedagogica para o professor?

Sim. O dashboard do professor trabalha com desempenho por curso e perguntas criticas.

### 5. O que sao perguntas criticas?

Sao perguntas que concentram maior numero de erros e ajudam a identificar dificuldades da turma.

### 6. Porque isso e interessante?

Porque transforma a plataforma em ferramenta de acompanhamento, nao apenas de publicacao.

---

## 22. Perguntas e respostas - frontend e experiencia do utilizador

### 1. A plataforma e responsiva?

O projeto foi estruturado com CSS responsivo e views adaptadas para diferentes tamanhos de ecra.

### 2. Existe navegação diferenciada?

Sim. O menu muda conforme autenticacao e perfil.

### 3. Existe pagina inicial atrativa?

Sim. A home tem carrossel, busca e destaque de cursos.

### 4. O quiz foi pensado para boa usabilidade?

Sim. A interface mostra progresso, tentativas, nota minima, temporizador e feedback.

### 5. O certificado tambem tem experiencia visual propria?

Sim. Existe view especifica e CSS especifico, inclusive para PDF.

### 6. Como fizeram para deixar o cabecalho fixo?

O cabecalho foi mantido visivel com CSS usando `position: sticky`, `top: 0` e `z-index` alto. Assim, ele acompanha a navegacao sem sair do topo e melhora a experiencia em paginas longas.

### 7. Porque escolheram `sticky` em vez de `fixed`?

Porque `sticky` mantem o comportamento mais natural dentro do fluxo da pagina e reduz a necessidade de compensacoes extras no layout. Foi uma escolha para simplificar a interface e preservar legibilidade.

### 8. Como fizeram os contadores do dashboard?

Os valores sao preparados no backend e enviados para a view em atributos `data-*`. Depois o JavaScript le esses dados e anima os numeros na interface para dar uma percepcao visual mais dinamica.

### 9. Esses contadores sao em tempo real mesmo?

Nao no sentido de WebSocket ou atualizacao instantanea do servidor a cada segundo. O que existe e leitura de dados atuais carregados pela pagina e atualizacoes pontuais por JavaScript em alguns fluxos, o que ja deixa a interface bastante reativa.

### 10. Como responder se o jurado insistir na expressao "tempo real"?

Uma boa resposta e: tecnicamente nao usamos tempo real por socket. O sistema trabalha com atualizacao dinamica no carregamento da pagina e em eventos especificos, o que para o utilizador passa sensacao de resposta imediata.

### 11. Como os modais sao abertos?

Existe um modal global no layout principal. O JavaScript controla abertura e fecho removendo a classe `hidden`, preenchendo titulo, corpo e rodape, e bloqueando o scroll do `body` enquanto o modal estiver ativo.

### 12. Como o conteudo entra dentro do modal?

Em varios casos o sistema carrega fragmentos HTML com `fetch`, normalmente com `partial=1`, sanitiza esse conteudo e injeta no corpo do modal. Isso evita recarregar a pagina inteira e reaproveita formularios existentes.

### 13. Como os modais sao fechados?

Eles podem ser fechados no botao de fechar, no clique fora da caixa principal ou pela tecla `Escape`. Isso melhora usabilidade e segue um padrao esperado pelo utilizador.

### 14. Porque usar modais em vez de abrir sempre outra pagina?

Porque para operacoes rapidas, como criar ou editar, o modal reduz interrupcao no fluxo. O utilizador continua no mesmo contexto visual e executa a acao de forma mais rapida.

### 15. Como trataram a seguranca ao carregar conteudo em modal?

O projeto aplica sanitizacao do HTML antes de inserir no modal e tambem usa protecao CSRF nas requisicoes. Alem disso, as validacoes mais importantes continuam no servidor.

### 16. Como o menu muda conforme o tipo de utilizador?

No layout, os links do menu sao montados em PHP com base no estado da sessao e no papel do utilizador. Assim, aluno, professor e administrador veem navegacao coerente com as suas permissoes.

### 17. Como fizeram o menu mobile?

Foi usado um botao hamburguer com JavaScript para adicionar ou remover classes no menu. O estado aberto tambem atualiza atributos de acessibilidade como `aria-expanded`.

### 18. Como fizeram o dropdown da conta?

Foi criado um botao que alterna a visibilidade do menu secundario da conta. O JavaScript abre no clique e fecha automaticamente quando o utilizador clica fora da area do dropdown.

### 19. Como fizeram a busca instantanea em listas, como aulas?

O JavaScript escuta o evento de digitacao no campo de pesquisa, compara o texto com o titulo dos itens e oculta os que nao correspondem. Isso melhora filtragem sem depender de recarregar a pagina.

### 20. Como fizeram a reordenacao de aulas?

Foi usado `drag and drop` no frontend para reorganizar os cards visualmente. Depois, quando o utilizador confirma, a nova ordem e enviada ao servidor para persistencia.

### 21. Como mostram feedback visual ao utilizador?

A interface usa alertas, notificacoes, animacoes de progresso, estados visuais nos cards e atualizacao de numeros. Isso ajuda o utilizador a perceber rapidamente se uma acao funcionou ou falhou.

### 22. Como animaram barras de progresso?

As barras usam CSS com largura baseada em variaveis e o JavaScript atualiza o valor de progresso. Em alguns casos ha animacao quando o elemento entra em vista, o que melhora a leitura visual.

### 23. Como fizeram para a interface parecer mais profissional?

Houve padronizacao de layout, tipografia, paleta de cores, componentes reutilizaveis e estados visuais consistentes. O objetivo foi transmitir clareza, hierarquia e confianca.

### 24. Porque escolheram JavaScript Vanilla em vez de framework?

Porque o escopo do projeto permitia boa interatividade sem adicionar complexidade extra. Isso tambem facilitou integracao com PHP renderizado no servidor e manteve a curva de manutencao mais simples.

### 25. Como evitaram que o frontend virasse algo desorganizado?

A organizacao foi feita separando scripts globais, scripts por pagina, estilos gerais e estilos especificos por pagina. Isso reduz acoplamento e facilita manutencao.

### 26. Como a interface se integra com o backend sem virar uma SPA?

A base continua server-rendered em MVC, mas algumas interacoes usam JavaScript para enriquecer a experiencia. Ou seja, o backend continua central e o frontend entra para melhorar usabilidade.

### 27. Se perguntarem "o que voce faria para melhorar ainda mais a interface?", o que responder?

Uma resposta madura e: eu evoluiria acessibilidade, padronizacao de componentes, testes visuais, melhor feedback assicrono e talvez uma camada maior de componentes reutilizaveis sem perder a simplicidade atual.

### 28. Como garantiram alguma acessibilidade na interface?

Foram usados atributos como `aria-label`, `aria-expanded`, `aria-hidden` e `role` em elementos interativos e de apoio. Nao e uma implementacao perfeita de acessibilidade, mas houve preocupacao real em tornar a interface mais compreensivel.

### 29. Onde aparecem exemplos de acessibilidade no projeto?

No menu mobile, nos dropdowns, nos modais, nas barras de progresso, no canvas do dashboard e em varios botoes com descricao semantica. Isso mostra que a interface nao foi pensada apenas visualmente.

### 30. Como fizeram os estados de carregamento?

Quando um fragmento vai ser carregado para o modal, o sistema abre primeiro um estado visual de carregamento com spinner e mensagem. Isso evita que o utilizador pense que a plataforma travou.

### 31. Como evitaram recarregar a pagina toda em certas operacoes?

Em acoes mais pontuais, como abrir formularios rapidos, o sistema usa carregamento parcial com `fetch`. Assim, a interface fica mais fluida e o utilizador nao perde o contexto atual.

### 32. Como trataram mensagens de sucesso e erro na interface?

Foi usada uma logica de notificacoes visuais e alertas para confirmar acoes, informar falhas e orientar o utilizador. Isso melhora comunicacao entre sistema e utilizador.

### 33. Como fizeram para a interface reagir ao progresso do aluno?

Quando uma aula e concluida, o JavaScript atualiza barras, textos de progresso e alguns estados visuais sem exigir uma navegacao completa. Isso da sensacao de resposta imediata.

### 34. Como representam o progresso visualmente?

O progresso aparece em barras, percentagens e estados como concluido ou em andamento. Isso facilita compreensao rapida da situacao do aluno dentro do curso.

### 35. Como a interface ajuda o professor no dia a dia?

Ela concentra contadores, atalhos rapidos, comparativos, desempenho de quizzes e acessos diretos para criar curso, aula e quiz. Ou seja, nao e apenas bonita, e operacional.

### 36. Como fizeram o grafico do dashboard do professor?

Os dados sao montados em JavaScript e o grafico e renderizado na area do dashboard quando a biblioteca esta disponivel. A ideia foi tornar os indicadores mais visuais sem complicar a arquitetura.

### 37. Porque usar cards na interface?

Porque cards ajudam a separar conteudos, organizar prioridades visuais e facilitar leitura em dashboards e listagens. E um padrao util para interfaces com muitos blocos de informacao.

### 38. Como organizaram o CSS para nao ficar confuso?

Existe um CSS base do sistema e CSS especifico por pagina. Isso permite manter consistencia global sem perder liberdade para ajustar telas com necessidades diferentes.

### 39. Como organizaram o JavaScript para nao virar um ficheiro caotico?

Ha scripts globais para comportamentos compartilhados e scripts especificos por pagina para regras mais locais. Essa separacao melhora manutencao e leitura.

### 40. Como equilibraram estetica e funcionalidade?

A preocupacao nao foi apenas em deixar bonito, mas em tornar o sistema claro, navegavel e eficiente. Por isso a identidade visual anda junto com fluxo, feedback e hierarquia de informacao.

### 41. Como a interface conversa com a arquitetura MVC?

As views mostram os dados, os controllers preparam o fluxo e os models garantem persistencia. O frontend entra como camada de apresentacao e interacao, sem roubar a responsabilidade do backend.

### 42. Se perguntarem "isso e SPA?", como responder?

Nao. O projeto continua sendo uma aplicacao MVC renderizada no servidor. O que existe sao melhoramentos pontuais com JavaScript para deixar a experiencia mais fluida.

### 43. Como fizeram para reutilizar componentes visuais?

O layout principal centraliza cabecalho, rodape, modal global, CSS e scripts comuns. Alem disso, componentes como cards e padroes visuais sao reaproveitados em varias views.

### 44. Como lidaram com descricoes grandes na interface?

Em alguns pontos foram usados mecanismos de expandir e recolher texto. Isso ajuda a manter a tela limpa sem esconder informacao importante.

### 45. Como lidaram com imagens e desempenho visual?

Em componentes de curso ha carregamento com `loading=\"lazy\"`, o que ajuda a reduzir custo inicial de renderizacao e melhora percepcao de desempenho.

### 46. Como justificam o uso de animacoes?

As animacoes foram usadas com moderacao para orientar o utilizador e destacar mudancas de estado, como contadores, progresso e entrada de cards. Nao foram colocadas apenas por efeito estetico.

### 47. Como evitaram inseguranca ao inserir HTML dinamico?

Antes de inserir conteudo vindo de fragmentos, o sistema faz uma sanitizacao para remover elementos perigosos e atributos inseguros. Isso reduz risco no frontend.

### 48. O frontend sozinho decide regras importantes?

Nao. O frontend melhora interacao, mas as validacoes centrais continuam no servidor. Isso e importante para seguranca e consistencia.

### 49. Qual foi a principal preocupacao de UX na plataforma?

Foi permitir que cada perfil entendesse rapidamente o que fazer, onde clicar e como acompanhar o proprio estado no sistema. Por isso a navegacao, os indicadores e os atalhos receberam bastante atencao.

### 50. Se pedirem uma justificacao rapida da interface, o que dizer?

Pode responder assim: a interface foi pensada para ser clara, responsiva e funcional, apoiando o fluxo do aluno, do professor e do administrador sem depender de frameworks pesados.

### 51. Resposta mais oral para "como fizeram o cabecalho fixo?"

Pode falar assim:

> Para manter a navegacao sempre acessivel, usamos CSS com comportamento sticky no topo. Entao mesmo em paginas longas o cabecalho continua visivel e melhora a usabilidade.

### 52. Resposta mais oral para "como fizeram os contadores?"

Pode falar assim:

> Os numeros sao preparados no backend, enviados para a view e depois o JavaScript faz a animacao desses valores. Entao o dado vem do servidor, mas a apresentacao fica mais dinamica no navegador.

### 53. Resposta mais oral para "como abriram os modais?"

Pode falar assim:

> Nos deixamos um modal global no layout e o JavaScript so preenche esse modal com o conteudo necessario. Em varios casos carregamos so um fragmento da pagina, o que deixa a experiencia mais rapida.

### 54. Resposta mais oral para "porque a interface ficou organizada?"

Pode falar assim:

> Porque separamos bem o que e estilo global, o que e estilo por pagina e o que e comportamento JavaScript geral ou especifico. Isso ajudou a manter consistencia sem misturar tudo.

### 55. Resposta mais oral para "porque nao usaram framework frontend?"

Pode falar assim:

> Para este projeto, JavaScript puro ja resolvia bem a interatividade necessaria. Preferimos manter a integracao simples com PHP e MVC em vez de aumentar complexidade sem necessidade.

---

## 23. Perguntas e respostas - banco de dados e modelagem

### 1. Porque separar `quiz_attempts` de `quiz_attempt_answers`?

Porque uma tentativa e o evento geral, e as respostas sao os detalhes por pergunta. Isso melhora organizacao e analise.

### 2. Porque existe `quiz_results` e tambem `quiz_attempts`?

Existe compatibilidade com estrutura anterior. O sistema atual trabalha mais fortemente com `quiz_attempts`, mas ainda alimenta `quiz_results` como legado.

### 3. Porque usar chave unica em matricula?

Para evitar matriculas duplicadas do mesmo aluno no mesmo curso.

### 4. Porque usar chave estrangeira?

Para manter integridade referencial entre usuarios, cursos, aulas, quizzes e certificados.

### 5. Porque criar `course_modules`?

Para permitir evolucao da plataforma de curso simples para curso modular sem quebrar o resto do sistema.

### 6. O banco esta totalmente normalizado?

Em grande parte sim para o escopo, embora algumas decisoes tenham sido influenciadas por compatibilidade e evolucao incremental.

---

## 24. Perguntas e respostas - melhorias futuras

### 1. O que voces melhorariam numa proxima versao?

- migracoes formais versionadas;
- middleware mais centralizado;
- testes automatizados;
- API REST dedicada;
- notificacoes por email;
- relatorios exportaveis;
- comentarios e foruns;
- streaming de video mais robusto;
- auditoria administrativa;
- deploy em nuvem.

### 2. O que seria prioridade tecnica?

Migracoes formais, testes e centralizacao de autorizacao.

### 3. O que seria prioridade funcional?

Comunicacao aluno-professor, relatorios e analytics mais completos.

### 4. O que seria prioridade de experiencia?

Melhorias em onboarding, notificacoes e acessibilidade.

---

## 25. Perguntas que os jurados podem fazer sobre decisoes tecnicas

### Porque usar PDO e nao `mysqli` direto?

PDO oferece prepared statements de forma elegante, melhor portabilidade e uma camada mais organizada para acesso a dados.

### Porque manter JavaScript puro?

Para reduzir complexidade, dependencia e curva de aprendizagem, mantendo foco no dominio da logica principal.

### Porque gerar PDF fora do PHP?

Porque a renderizacao visual do certificado em HTML/CSS fica mais fiel com Puppeteer.

### Porque o roteador esta em um so arquivo?

Porque para o escopo atual simplifica a navegacao e a demonstracao. Em versoes maiores, seria interessante modularizar melhor as rotas.

### Porque o banco se autoajusta em alguns models?

Para facilitar compatibilidade com ambientes locais e evolucoes incrementais do schema durante o desenvolvimento.

### Isso nao e uma gambiarra?

Resposta madura:

> E uma solucao pragmatica para o contexto do projeto. Em producao ou em equipas maiores, o ideal seria substituir por migracoes versionadas e pipeline formal.

---

## 26. Perguntas capciosas e boas respostas

### 1. "Se a internet falhar, a plataforma continua?"

Se o servidor local e banco estiverem funcionais, a aplicacao continua a operar no ambiente local. O que depende de infraestrutura externa pode ser afetado.

### 2. "Se um aluno tentar burlar o sistema pelo navegador?"

As regras principais sao verificadas no servidor, nao apenas na interface. Isso reduz manipulacoes via frontend.

### 3. "Se o professor apagar um curso, o que acontece?"

Como ha relacoes com chave estrangeira e `ON DELETE CASCADE` em varios pontos, os dados relacionados podem ser removidos junto, preservando integridade.

### 4. "Qual a diferenca entre progresso e aprovacao?"

Progresso mede o quanto o aluno percorreu a trilha. Aprovacao mede se ele atingiu os criterios academicos.

### 5. "O sistema foi feito pensando em negocio ou so em codigo?"

Foi pensado nos dois: perfis diferentes, estrutura pedagogica, progresso, avaliacao, certificacao e analise mostram preocupacao com uso real.

### 6. "O que voces fariam se tivessem mais tempo?"

Melhorariamos deploy, testes, modularizacao do roteamento e relatórios pedagogicos.

### 7. "Qual a parte mais complexa do projeto?"

Uma boa resposta e:

> A parte mais complexa foi alinhar progresso, regras de quizzes, desbloqueio por modulo e emissao de certificado sem criar inconsistencias entre aprendizado, aprovacao e visualizacao final.

---

## 27. Respostas curtas para usar sob pressao

### O projeto usa MVC?

Sim. `index.php` roteia, controllers tratam regra, models persistem dados e views renderizam a interface.

### Como guardam senhas?

Com hash, usando `password_hash`.

### Como evitam SQL Injection?

Com PDO e prepared statements.

### Como controlam permissoes?

Por sessao e `role`.

### O professor cria quiz?

Sim, desde que seja dono do curso.

### O aluno recebe certificado automaticamente?

Recebe apenas se cumprir os criterios de elegibilidade.

### O certificado pode ser verificado?

Sim, por codigo unico em rota publica.

### O sistema suporta modulos?

Sim, curso de modulo unico e multi-modulo.

### O quiz tem limite?

Sim, por tentativas e opcionalmente por tempo.

### O progresso considera so aulas?

Nao. Se houver quizzes, a avaliacao tambem conta.

---

## 28. Guia de alteracoes ao vivo - mapa rapido

Se os jurados pedirem mudancas ao vivo, estes sao os arquivos principais.

### Se pedirem alterar texto, menu, layout ou navbar

Abrir:

- `app/views/layout.php`

### Se pedirem alterar home

Abrir:

- `app/views/home.php`
- `public/css/pages/home.css`
- `public/js/pages/home.js`

### Se pedirem alterar listagem ou detalhe de curso

Abrir:

- `app/views/cursos.php`
- `app/views/curso-detail.php`
- `app/controllers/CourseController.php`
- `app/models/Course.php`

### Se pedirem alterar aula

Abrir:

- `app/views/aula.php`
- `app/controllers/LessonController.php`
- `app/models/Lesson.php`

### Se pedirem alterar quiz

Abrir:

- `app/views/quiz.php`
- `app/controllers/QuizController.php`
- `app/models/Quiz.php`
- `public/js/pages/quiz.js`

### Se pedirem alterar dashboard

Abrir:

- `app/controllers/DashboardController.php`
- `app/views/dashboard-aluno.php`
- `app/views/dashboard-professor.php`
- `app/views/dashboard-admin.php`

### Se pedirem alterar certificados

Abrir:

- `app/controllers/CertificateController.php`
- `app/models/Certificate.php`
- `app/views/certificado.php`
- `public/css/pages/certificado.css`
- `public/css/pages/certificado-pdf.css`
- `scripts/generate-certificate-pdf.js`

### Se pedirem alterar regras de permissao

Abrir:

- `app/controllers/AuthController.php`
- `config/helpers.php`

### Se pedirem alterar banco

Abrir:

- `migrations/schema.sql`
- models com `ensureSchema`

---

## 29. Como reagir se pedirem alteracoes ao vivo

### Estrategia recomendada

1. Repetir o pedido do jurado em voz alta.
2. Dizer em que parte do sistema a alteracao fica.
3. Abrir primeiro controller ou view correta.
4. Explicar o impacto antes de alterar.
5. Fazer a mudanca minima e segura.
6. Testar o fluxo na hora.

### Exemplo de fala

> Essa alteracao fica na camada de apresentacao, entao vou primeiro abrir a view correspondente. Se a regra de negocio tambem for afetada, ajusto o controller ou model para manter consistencia.

---

## 30. Alteracoes ao vivo mais provaveis e onde mexer

### 1. "Mudem o nome de um botao"

Provavel arquivo:

- view correspondente, como `home.php`, `curso-detail.php` ou `layout.php`

### 2. "Adicionem um campo ao formulario"

Provavel fluxo:

- view do formulario;
- controller que recebe;
- model que persiste;
- schema se o dado precisar ir para o banco.

### 3. "Mudem a regra para mais tentativas no quiz"

Abrir:

- `app/controllers/QuizController.php`
- `app/models/Quiz.php`

### 4. "Agora o quiz final precisa de nota minima diferente"

Abrir:

- `app/models/Quiz.php`
- possivelmente a tela de criacao do quiz

### 5. "Queremos tirar o certificado por modulo"

Abrir:

- `app/models/Certificate.php`
- `app/views/curso-detail.php`
- `app/views/quiz.php`

### 6. "Queremos mudar a logica de progresso"

Abrir:

- `app/controllers/LessonController.php`
- `app/controllers/DashboardController.php`
- `app/models/Quiz.php`

### 7. "Queremos exibir mais dados no dashboard do professor"

Abrir:

- `app/controllers/DashboardController.php`
- `app/views/dashboard-professor.php`

### 8. "Queremos que admin aprove professor antes de publicar curso"

Mudanca envolveria:

- banco para estado de aprovacao;
- controller de curso;
- dashboard admin;
- views de professor e admin.

---

## 31. Simulacao de perguntas praticas com respostas

### Pergunta

"Mostrem onde o sistema calcula o progresso."

### Resposta

O calculo principal esta no `LessonController`, especialmente na rotina de recálculo do progresso do curso. O dashboard tambem consulta e atualiza esse estado para exibir ao aluno.

### Pergunta

"Mostrem onde o certificado e emitido."

### Resposta

A orquestracao esta no `CertificateController`, mas a regra de elegibilidade e emissao esta no model `Certificate`, que sincroniza certificados com o estado atual do aluno.

### Pergunta

"Mostrem onde o quiz e corrigido."

### Resposta

A correcao parte do `QuizController`, no processamento da submissao das respostas. O model `Quiz` guarda tentativas, respostas e calcula nota final.

### Pergunta

"Mostrem onde o professor e impedido de editar curso de outro professor."

### Resposta

Essa validacao aparece no `CourseController`, comparando o utilizador autenticado com o `teacher_id` do curso.

---

## 32. O que nao devem dizer na defesa

Evitem frases como:

- "acho que funciona assim";
- "essa parte eu nao vi";
- "isso o sistema nao faz, mas talvez desse";
- "foi o colega que fez, eu nao sei explicar";
- "esta tudo perfeito".

Prefiram:

- "essa regra esta aqui";
- "o sistema faz dessa forma por este motivo";
- "essa parte ja esta implementada";
- "essa melhoria ainda nao esta completa, mas a base esta pronta";
- "a decisao foi essa por causa do escopo e do tempo".

---

## 33. Discurso tecnico equilibrado para impressionar sem exagerar

Podem usar frases assim:

- "Optamos por MVC para separar responsabilidades e facilitar manutencao."
- "Usamos prepared statements com PDO para reduzir risco de SQL Injection."
- "A regra de progresso foi pensada para refletir nao apenas consumo de conteudo, mas tambem desempenho academico."
- "A certificacao foi desenhada com validacao publica para agregar confiabilidade."
- "A plataforma foi evoluida de forma incremental, inclusive com mecanismos de compatibilidade de schema."

---

## 34. Roteiro de demonstracao ideal

### Ordem sugerida

1. Home
2. Cadastro ou login
3. Listagem de cursos
4. Detalhe de curso
5. Matricula
6. Acesso a aula
7. Marcacao de conclusao
8. Quiz
9. Dashboard do aluno
10. Dashboard do professor
11. Certificado e verificacao publica
12. Area administrativa

### Porque essa ordem e boa

Porque mostra o ciclo completo:

- descoberta;
- inscricao;
- aprendizagem;
- avaliacao;
- conclusao;
- administracao.

---

## 35. Checklist final antes da defesa

- Confirmar que Apache e MySQL estao ativos.
- Confirmar que o banco carregou corretamente.
- Testar login de aluno, professor e admin.
- Testar acesso a um curso.
- Testar marcacao de aula concluida.
- Testar um quiz.
- Testar visualizacao de certificado.
- Testar rota publica de verificacao do certificado.
- Confirmar que CSS e imagens estao a carregar.
- Confirmar que o ambiente de PDF funciona, se forem demonstrar PDF.

---

## 36. Resumo final para memorizar

Se precisarem decorar uma versao curtissima:

> A Plataforma EAD e um sistema web em PHP e MySQL com arquitetura MVC. Ela possui autenticacao por perfis, gestao de cursos, modulos, aulas e quizzes, acompanhamento de progresso, dashboards por papel e emissao de certificados verificaveis com PDF. As principais preocupacoes tecnicas do projeto foram organizacao do codigo, controle de permissao, integridade dos dados, avaliacao do aluno e capacidade de evolucao futura.

---

## 37. Fecho de defesa

Se pedirem consideracoes finais:

> Este projeto mostra que conseguimos sair do CRUD basico e construir uma plataforma com fluxo real de ensino online, incluindo regras academicas, perfis distintos, avaliacao, progresso e certificacao. Alem do resultado funcional, o projeto nos permitiu praticar arquitetura MVC, modelagem de base de dados, seguranca basica, organizacao de codigo e raciocinio de evolucao do sistema.

---

## 38. Perguntas e respostas de conhecimento geral de programacao relacionadas ao projeto

Esta secao serve para o caso de os jurados sairem do sistema em si e comecarem a perguntar conceitos gerais de programacao, desenvolvimento web, banco de dados e engenharia de software com base no que voces usaram.

### 1. O que e programacao?

Programacao e o processo de criar instrucoes para que o computador execute tarefas de forma logica e automatizada.

### 2. O que e um algoritmo?

E uma sequencia finita e organizada de passos para resolver um problema.

### 3. O que e uma linguagem de programacao?

E uma forma estruturada de escrever instrucoes que o computador pode interpretar ou executar.

### 4. Qual a diferenca entre logica de programacao e linguagem de programacao?

Logica de programacao e a forma de pensar a solucao. Linguagem de programacao e a ferramenta usada para escrever essa solucao.

### 5. O que e um sistema web?

E um sistema acessado por navegador, executado em servidor e consumido via HTTP.

### 6. O que e frontend?

E a parte visivel e interativa do sistema, com que o utilizador entra em contacto diretamente.

### 7. O que e backend?

E a parte responsavel por regras de negocio, autenticacao, processamento e comunicacao com banco de dados.

### 8. No vosso projeto, o que e frontend?

HTML, CSS, JavaScript e as views em `app/views/`.

### 9. No vosso projeto, o que e backend?

PHP, controllers, models, autenticacao, regras de quiz, progresso e certificados.

### 10. O que e um servidor?

E o ambiente que recebe requisicoes e entrega respostas para o cliente.

### 11. O que e um cliente?

E quem consome o sistema, normalmente o navegador do utilizador.

### 12. O que e HTTP?

E o protocolo usado para comunicacao entre cliente e servidor na web.

### 13. O que e uma requisicao?

E o pedido enviado pelo cliente ao servidor.

### 14. O que e uma resposta?

E o retorno do servidor para o cliente, podendo ser HTML, JSON, ficheiros ou outros dados.

### 15. O que e GET?

E um metodo HTTP normalmente usado para buscar ou visualizar dados.

### 16. O que e POST?

E um metodo HTTP normalmente usado para enviar dados e executar acoes, como login, cadastro ou criacao.

### 17. Como isso aparece no vosso projeto?

As paginas usam GET para navegação e POST para acoes como login, matricula, criar aula, responder quiz e atualizar dados.

### 18. O que e uma variavel?

E um espaco nomeado usado para armazenar um valor temporariamente.

### 19. O que e uma constante?

E um valor definido uma vez para ser reutilizado sem mudanca, como `BASE_URL` e `APP_URL`.

### 20. O que e uma funcao?

E um bloco reutilizavel de codigo que executa uma tarefa especifica.

### 21. O que e uma classe?

E uma estrutura da programacao orientada a objetos que agrupa dados e comportamentos relacionados.

### 22. O que e um objeto?

E uma instancia de uma classe.

### 23. O vosso projeto usa orientacao a objetos?

Sim. Os controllers e models sao implementados como classes.

### 24. O que e encapsulamento?

E o principio de agrupar dados e comportamentos relacionados numa classe e controlar o acesso ao que e interno.

### 25. O que e heranca?

E quando uma classe pode aproveitar caracteristicas de outra. No vosso projeto, a organizacao principal esta mais focada em composicao e separacao de responsabilidades do que em heranca pesada.

### 26. O que e polimorfismo?

E a capacidade de usar a mesma interface ou ideia com comportamentos diferentes dependendo do contexto.

### 27. O que e abstracao?

E representar apenas o que e essencial para resolver um problema, escondendo detalhes desnecessarios.

### 28. O que e modularizacao?

E dividir o sistema em partes menores e mais organizadas.

### 29. Como isso aparece no vosso projeto?

A aplicacao esta separada em controllers, models, views, configuracoes, scripts e assets.

### 30. O que e MVC?

E um padrao que separa o sistema em Model, View e Controller.

### 31. Porque MVC e importante?

Porque melhora organizacao, manutencao, escalabilidade e clareza do fluxo.

### 32. O que e um model?

E a camada que lida com dados e persistencia.

### 33. O que e uma view?

E a camada de apresentacao ao utilizador.

### 34. O que e um controller?

E a camada que recebe a requisicao, aplica regras e coordena models e views.

### 35. O que e uma rota?

E a forma como o sistema identifica qual pagina ou acao deve executar.

### 36. O que e um roteador?

E o mecanismo que direciona a requisicao para a parte correta do sistema.

### 37. Onde isso acontece no vosso projeto?

No `public/index.php`.

### 38. O que e uma sessao?

E um mecanismo para manter informacoes do utilizador entre varias requisicoes.

### 39. Porque a sessao e importante?

Porque HTTP e stateless, ou seja, nao lembra sozinho quem e o utilizador entre uma pagina e outra.

### 40. O que e autenticacao?

E o processo de confirmar a identidade do utilizador.

### 41. O que e autorizacao?

E o processo de definir o que aquele utilizador autenticado pode fazer.

### 42. Qual a diferenca entre autenticacao e autorizacao?

Autenticacao responde "quem e voce?". Autorizacao responde "o que voce pode fazer?".

### 43. O que e hash?

E uma transformacao de um valor em outro valor codificado, usada para proteger senhas.

### 44. Porque nao guardar senha em texto puro?

Porque isso compromete a seguranca do utilizador e do sistema.

### 45. O que e banco de dados?

E um sistema de armazenamento organizado de informacoes.

### 46. O que e um SGBD?

E um Sistema de Gestao de Banco de Dados, como o MySQL.

### 47. O que e uma tabela?

E uma estrutura que organiza dados em linhas e colunas.

### 48. O que e uma chave primaria?

E um identificador unico de cada registo de uma tabela.

### 49. O que e uma chave estrangeira?

E um campo que referencia uma tabela relacionada, criando integridade entre dados.

### 50. O que e integridade referencial?

E a garantia de que as relacoes entre tabelas permanecem validas.

### 51. O que e normalizacao?

E o processo de organizar o banco para reduzir redundancia e melhorar consistencia.

### 52. O que e uma consulta SQL?

E um comando usado para buscar, inserir, atualizar ou apagar dados.

### 53. O que e `SELECT`?

E o comando SQL usado para consultar dados.

### 54. O que e `INSERT`?

E o comando usado para inserir dados.

### 55. O que e `UPDATE`?

E o comando usado para atualizar dados.

### 56. O que e `DELETE`?

E o comando usado para remover dados.

### 57. O que e um indice no banco?

E uma estrutura que ajuda a acelerar pesquisas e consultas.

### 58. Porque indices sao importantes?

Porque melhoram desempenho quando ha muitas consultas.

### 59. O que e SQL Injection?

E uma vulnerabilidade em que dados maliciosos alteram o comportamento de uma consulta SQL.

### 60. Como se evita SQL Injection?

Com prepared statements, validacao de entradas e boas praticas de acesso a dados.

### 61. O que e PDO?

E a camada de acesso a base de dados usada em PHP para trabalhar com prepared statements e conexoes de forma mais segura e organizada.

### 62. O que e um prepared statement?

E uma consulta preparada onde os dados do utilizador sao enviados separadamente da estrutura SQL.

### 63. O que e validacao de dados?

E o processo de verificar se os dados recebidos sao corretos, completos e esperados.

### 64. O que e sanitizacao?

E o processo de limpar ou tratar dados para reduzir riscos e inconsistencias.

### 65. O que e escape de saida?

E a protecao aplicada ao mostrar dados na interface, por exemplo com `htmlspecialchars`.

### 66. O que e XSS?

E uma vulnerabilidade em que codigo malicioso pode ser injetado e executado no navegador.

### 67. Como reduzir risco de XSS?

Escapando saida, validando entradas e evitando inserir HTML nao confiavel diretamente.

### 68. O que e CSRF?

E um ataque em que uma requisicao indevida e enviada em nome de um utilizador autenticado.

### 69. Como reduzir CSRF?

Com token CSRF nos formularios e validacao no servidor.

### 70. O que e upload de ficheiros?

E o envio de ficheiros do cliente para o servidor.

### 71. Que cuidados existem em upload?

- validar extensao;
- validar tipo MIME;
- limitar tamanho;
- renomear ficheiro;
- guardar em local controlado.

### 72. O que e responsividade?

E a capacidade da interface adaptar-se a diferentes tamanhos de ecra.

### 73. Porque responsividade importa?

Porque os utilizadores podem aceder por computador, tablet ou telemovel.

### 74. O que e DOM?

E a estrutura em arvore que representa a pagina HTML no navegador.

### 75. O que e JavaScript no projeto?

E a camada que melhora interatividade da interface, como quiz, componentes visuais e comportamentos dinamicos.

### 76. O que e um evento em programacao web?

E uma acao detectada pela interface, como clique, submissao de formulario ou mudanca de campo.

### 77. O que e depuracao?

E o processo de identificar, entender e corrigir erros.

### 78. O que e um bug?

E um erro de implementacao, logica ou comportamento.

### 79. O que e manutencao de software?

E o trabalho continuo de corrigir, melhorar e evoluir o sistema depois de construido.

### 80. O que e escalabilidade?

E a capacidade de um sistema crescer mantendo desempenho e organizacao.

### 81. O que e desempenho?

E a eficiencia com que o sistema responde, processa e consome recursos.

### 82. O que e refatoracao?

E reorganizar ou melhorar o codigo sem mudar o comportamento funcional esperado.

### 83. O que e reutilizacao de codigo?

E aproveitar blocos, funcoes ou estruturas em varios pontos para reduzir repeticao.

### 84. Onde ha reutilizacao no vosso projeto?

Em helpers, layout base, models e padroes de controller.

### 85. O que e acoplamento?

E o nivel de dependencia entre partes do sistema.

### 86. O que e coesao?

E o grau em que uma parte do sistema faz bem uma responsabilidade especifica.

### 87. Porque baixa dependencia e boa?

Porque facilita manutencao, testes e evolucao.

### 88. O que e compatibilidade retroativa?

E a capacidade de continuar a funcionar com estruturas ou dados mais antigos.

### 89. Como isso aparece no vosso projeto?

Nos `ensureSchema`, que ajudam a adaptar o banco em cenarios de evolucao incremental.

### 90. O que e uma migracao de banco?

E uma alteracao versionada na estrutura da base de dados.

### 91. O projeto usa migracoes formais?

Tem schema base e scripts, mas parte da evolucao do banco tambem e feita por verificacoes automaticas em runtime.

### 92. O que e um log?

E o registo de eventos ou erros do sistema para acompanhamento e diagnostico.

### 93. O vosso projeto tem logs?

Sim. Existe helper de log e ficheiro em `logs/app.log`.

### 94. O que e um ambiente de desenvolvimento?

E o ambiente onde o sistema e construido e testado localmente.

### 95. O que e um ambiente de producao?

E o ambiente real onde o sistema fica disponivel aos utilizadores finais.

### 96. Porque separar desenvolvimento de producao?

Porque configuracoes, seguranca, desempenho e visibilidade de erros devem ser diferentes.

### 97. O que e uma dependencia?

E uma biblioteca ou ferramenta externa usada pelo projeto.

### 98. Que dependencia externa importante o projeto usa?

`puppeteer-core`, para gerar PDFs do certificado.

### 99. O que e versionamento de codigo?

E o controlo historico das alteracoes feitas no projeto.

### 100. Porque versionamento importa?

Porque ajuda a organizar trabalho em equipa, recuperar historico e acompanhar evolucao do sistema.

### 101. O que e documentacao tecnica?

E o conjunto de explicacoes que ajudam a entender, usar, manter e evoluir o sistema.

### 102. Porque documentacao importa na defesa?

Porque mostra maturidade do projeto e dominio daquilo que foi construido.

### 103. O que e regra de negocio?

E a condicao que traduz como o sistema deve funcionar segundo o objetivo do dominio.

### 104. No vosso projeto, deem exemplo de regra de negocio.

Um aluno so recebe certificado se cumprir criterios de conclusao e aprovacao.

### 105. O que e teste de software?

E o processo de verificar se o sistema faz o que deveria fazer.

### 106. Que tipos de teste existem?

- teste unitario;
- teste de integracao;
- teste funcional;
- teste manual.

### 107. O que e teste manual?

E quando o programador ou utilizador verifica o comportamento do sistema executando fluxos reais.

### 108. O que e teste de integracao?

E quando varias partes do sistema sao testadas juntas, como controller, model e banco.

### 109. O que e API?

E uma interface de comunicacao entre sistemas ou partes de um sistema.

### 110. O vosso projeto e totalmente API-based?

Nao. Ele e principalmente uma aplicacao MVC renderizada no servidor, embora possa evoluir para ter API mais formal no futuro.

### 111. O que e JSON?

E um formato leve de troca de dados muito usado em sistemas web.

### 112. O que e um ficheiro `.md`?

E um ficheiro Markdown, usado para documentacao estruturada e facil de ler.

### 113. Porque e bom documentar em Markdown?

Porque e simples, portavel, legivel e muito usado em projetos de software.

### 114. O que e separacao de responsabilidades?

E dividir o sistema para que cada parte faca uma funcao clara.

### 115. Porque isso e importante no vosso projeto?

Porque facilita entender onde mexer se os jurados pedirem uma alteracao ao vivo.

### 116. O que e robustez?

E a capacidade de o sistema continuar funcional mesmo perante erros, entradas inesperadas ou cenarios incompletos.

### 117. Onde existe robustez no projeto?

Na validacao de permissao, no controlo de tentativas, nas verificacoes de schema e na sincronizacao de certificados.

### 118. O que e manutenibilidade?

E o grau de facilidade com que o sistema pode ser entendido, corrigido ou melhorado.

### 119. O que mostra que o vosso projeto e manutenivel?

A separacao em camadas, a organizacao de pastas, os controllers especificos e os models especializados.

### 120. Qual e a ligacao entre teoria e vosso projeto?

O projeto aplica conceitos classicos de programacao e engenharia de software, como algoritmos, MVC, banco relacional, autenticacao, autorizacao, validacao, persistencia, responsividade e separacao de responsabilidades.

---

## 39. Mini bloco de respostas rapidas de teoria

### O que e algoritmo?

Sequencia de passos para resolver um problema.

### O que e MVC?

Separacao entre Model, View e Controller.

### O que e SQL Injection?

Manipulacao maliciosa de consultas SQL.

### Como evitam SQL Injection?

PDO com prepared statements.

### O que e autenticacao?

Confirmar identidade do utilizador.

### O que e autorizacao?

Controlar o que ele pode fazer.

### O que e sessao?

Mecanismo para manter estado do utilizador entre requisicoes.

### O que e chave estrangeira?

Campo que liga tabelas relacionadas.

### O que e hash de senha?

Forma segura de guardar senha sem texto puro.

### O que e responsividade?

Adaptacao da interface a varios tamanhos de ecra.

---

## 40. Como responder quando nao sei a resposta exata

### 1. Qual e a melhor postura se eu nao souber?

Seja honesto, mantenha calma e responda com metodo. E melhor mostrar raciocinio tecnico do que inventar.

### 2. O que eu nunca devo fazer?

Nao inventar nomes de funcoes, nao afirmar algo que nao tem certeza e nao discutir com o jurado sem base.

### 3. Frase curta para ganhar tempo sem parecer perdido

> Deixe-me responder com base no que esta implementado hoje no sistema.

### 4. Frase para quando eu sei a ideia, mas nao lembro o detalhe

> O principio tecnico eu consigo explicar, mas prefiro nao cravar esse detalhe sem confirmar no codigo.

### 5. Frase para quando eu nao sei mesmo

> Neste ponto especifico eu nao vou arriscar uma resposta imprecisa. O que eu posso afirmar com seguranca e o objetivo e o comportamento dessa parte no sistema.

### 6. Frase para redirecionar para algo que eu sei

> Posso nao lembrar agora o nome exato da funcao ou da implementacao, mas consigo explicar a logica usada e porque essa solucao foi adotada.

### 7. Como responder pergunta muito tecnica sobre frontend?

> Na interface, a nossa preocupacao principal foi usabilidade, organizacao e integracao com o backend MVC. Posso explicar o fluxo funcional e, se quiser, detalho depois o mecanismo tecnico.

### 8. Como responder se me perguntarem algo que pode estar diferente no codigo atual?

> Para ser rigoroso, eu prefiro responder pelo estado atual do codigo. Algumas ideias evoluiram ao longo do projeto, entao o mais correto e defender o que esta implementado hoje.

### 9. Como transformar desconhecimento em maturidade?

Em vez de dizer apenas "nao sei", diga o que sabe, o que nao quer afirmar sem confirmar e qual seria o caminho tecnico para validar.

### 10. Estrutura ideal de resposta quando estiver inseguro

Pode usar este modelo:

1. dizer o que sabe com certeza;
2. assumir com honestidade o que nao lembra;
3. explicar a logica geral;
4. mostrar como validaria no codigo ou na arquitetura.

### 11. Exemplo pratico

> Nao quero afirmar esse detalhe sem confirmar, mas a logica geral dessa parte e a seguinte: o backend prepara os dados, a view renderiza a estrutura e o JavaScript melhora a interacao no navegador.

### 12. Como encerrar uma resposta incompleta sem parecer fraco?

> O ponto principal aqui e que a funcionalidade esta resolvida de forma coerente com a arquitetura do projeto, mesmo que eu nao esteja a citar agora o detalhe exato de implementacao.

### 13. Como responder de forma mais natural, sem soar decorado?

Fale em tres passos: objetivo, como foi feito e porque essa escolha fez sentido. Essa estrutura soa mais humana do que repetir definicoes fechadas.

### 14. Modelo oral rapido para perguntas tecnicas da interface

> A ideia aqui foi melhorar a experiencia do utilizador sem complicar a arquitetura. Entao o backend continua a fornecer os dados e o JavaScript entra para deixar a interacao mais fluida.

### 15. Modelo oral rapido para perguntas sobre uma escolha especifica

> Escolhemos essa abordagem porque resolvia o problema com menos complexidade e mantinha o projeto mais facil de manter.

### 16. Modelo oral rapido para quando eu lembrar da logica, mas nao do nome tecnico

> Eu nao quero forcar o nome tecnico agora, mas consigo explicar a funcao dessa parte dentro do sistema e o resultado que ela entrega.
