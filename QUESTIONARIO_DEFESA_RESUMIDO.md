# Questionario Resumido de Defesa - Plataforma EAD

## 1. Como usar este arquivo

Este arquivo resume o essencial do `QUESTIONARIO_DEFESA_FINAL.md`.

Serve para:

- estudar mais rapido;
- memorizar os pontos mais importantes;
- responder com clareza sem se perder no documento completo;
- revisar arquitetura, funcionalidades, seguranca e interface.

Se precisares de detalhes mais profundos, consulta o arquivo completo.

---

## 2. Apresentacao rapida do projeto

### Nome do projeto

Plataforma EAD

### O que e

E uma plataforma web de ensino a distancia que permite cadastro de utilizadores, criacao de cursos, organizacao de aulas e modulos, aplicacao de quizzes, acompanhamento de progresso e emissao de certificados verificaveis.

### Problema que resolve

Ela centraliza num unico sistema o processo principal do ensino online: publicar conteudo, matricular alunos, acompanhar progresso, avaliar aprendizagem e comprovar conclusao.

### Publico-alvo

- alunos;
- professores;
- administradores;
- instituicoes de ensino;
- centros de formacao;
- professores independentes.

### Pitch curto para falar aos jurados

> A nossa Plataforma EAD foi desenvolvida para gerir o processo de ensino online de ponta a ponta. O sistema permite criar cursos, aulas e quizzes, acompanhar progresso, analisar desempenho e emitir certificados verificaveis. A aplicacao segue arquitetura MVC em PHP com MySQL, usando controle por perfis, sessoes, prepared statements com PDO e regras de negocio para progresso, avaliacao e certificacao.

---

## 3. Stack tecnologica

### Backend

- PHP
- PDO
- MySQL

### Frontend

- HTML5
- CSS3
- JavaScript Vanilla

### Infraestrutura

- Apache
- XAMPP

### Recurso extra

- Node.js + Puppeteer para gerar certificado em PDF

### Porque essa stack foi escolhida

- PHP e MySQL sao acessiveis e adequados ao contexto academico;
- MVC melhora a organizacao;
- PDO com prepared statements melhora seguranca;
- JavaScript puro reduz dependencia de frameworks;
- Puppeteer gera PDF visualmente fiel a interface.

---

## 4. Conceitos principais da plataforma

### MVC

O projeto segue o padrao MVC:

- `Models`: acesso e persistencia no banco;
- `Controllers`: regras de negocio e fluxo;
- `Views`: interface apresentada ao utilizador.

### Front controller

O `public/index.php` recebe a requisicao e encaminha para o fluxo correto.

### Sessao

Mantem o utilizador autenticado entre requisicoes.

### Autenticacao

Confirma quem e o utilizador.

### Autorizacao

Controla o que cada perfil pode fazer.

### Regras de negocio

Sao as condicoes que determinam como o sistema deve funcionar, por exemplo: quem pode criar curso, quando o aluno conclui o curso e quando o certificado pode ser emitido.

---

## 5. Estrutura essencial do projeto

### Pastas principais

- `public/`: entrada da aplicacao, CSS, JS e uploads;
- `app/controllers/`: controladores;
- `app/models/`: modelos e logica de dados;
- `app/views/`: telas da plataforma;
- `config/`: configuracoes gerais;
- `scripts/`: scripts auxiliares, como geracao de PDF.

### Ficheiros importantes

- `public/index.php`
- `app/views/layout.php`
- `app/controllers/CourseController.php`
- `app/controllers/LessonController.php`
- `app/controllers/QuizController.php`
- `app/controllers/DashboardController.php`
- `app/controllers/CertificateController.php`
- `scripts/generate-certificate-pdf.js`

---

## 6. Perfis de utilizador

### Aluno

Pode:

- registrar-se;
- fazer login;
- ver cursos;
- matricular-se;
- assistir aulas;
- concluir aulas;
- fazer quizzes;
- acompanhar progresso;
- visualizar certificado quando elegivel.

### Professor

Pode:

- criar cursos;
- editar cursos proprios;
- criar modulos;
- criar aulas;
- criar quizzes;
- acompanhar alunos;
- ver dashboard com estatisticas.

### Administrador

Pode:

- gerir utilizadores;
- gerir cursos;
- supervisionar o sistema;
- aceder a dashboards administrativos.

### Resposta rapida

> O sistema trabalha com tres perfis principais: aluno, professor e administrador. Cada um tem permissoes diferentes para manter seguranca e organizacao.

---

## 7. Funcionalidades principais

### Autenticacao

- login;
- logout;
- sessao;
- controle de perfil.

### Cursos

- criacao;
- edicao;
- listagem;
- detalhe;
- matricula.

### Modulos

- organizacao do curso por etapas;
- suporte a curso simples ou modular.

### Aulas

- vinculadas a curso e modulo;
- reordenacao;
- marcacao de conclusao;
- progresso.

### Quizzes

- quiz de aula;
- quiz de modulo;
- quiz final;
- limite de tentativas;
- correcao automatica;
- nota minima;
- historico de tentativas.

### Certificados

- emissao condicionada a elegibilidade;
- validacao publica;
- PDF gerado com Puppeteer.

### Dashboards

- aluno: progresso e cursos;
- professor: alunos, aulas, cursos, desempenho e perguntas criticas;
- admin: visao global.

---

## 8. Regras de negocio mais importantes

Estas sao das partes mais importantes para defender:

### 1. Apenas professor cria curso

Nem todo utilizador pode publicar curso.

### 2. Apenas dono do curso ou admin pode alterar

Isso protege os dados e o trabalho do professor.

### 3. Matricula e unica por aluno e curso

Evita duplicacoes.

### 4. Progresso nao depende apenas de assistir aulas

Em varios casos o aluno tambem precisa cumprir quizzes obrigatorios.

### 5. Curso concluido nao significa apenas chegar ao fim visualmente

O sistema valida criterios de conclusao e aprovacao.

### 6. Certificado nao e apenas clicar e baixar

Ele depende de elegibilidade real.

### 7. Certificado pode ser revogado

Se a elegibilidade deixar de ser valida, o sistema pode retirar validade.

### 8. Cada quiz tem regra propria

Pode haver tentativas maximas, nota minima, embaralhamento e obrigatoriedade.

### 9. Nota final pode ser ponderada

O sistema considera regras de avaliacao, nao apenas uma acao isolada.

### Resposta rapida

> O nosso sistema nao funciona como um CRUD simples. Ele aplica regras de negocio reais para progresso, avaliacao e certificacao.

---

## 9. Seguranca essencial

### O que existe no projeto

- senhas com hash;
- PDO com prepared statements;
- controlo de sessao;
- controle de permissao por perfil;
- verificacoes no servidor;
- protecao CSRF;
- escape de saida com `htmlspecialchars`;
- sanitizacao em pontos de HTML dinamico.

### Como responder se perguntarem se o sistema e seguro

> Sim, dentro do escopo academico o sistema implementa camadas importantes de seguranca, como hash de senha, prepared statements com PDO, controlo de permissao, sessao e CSRF. Nao dizemos que e seguranca absoluta, mas ha preocupacao tecnica real.

### Respostas curtas

#### Como evitam SQL Injection?

Com PDO e prepared statements.

#### Como guardam senhas?

Com hash, nunca em texto puro.

#### Como controlam permissoes?

Com verificacao de sessao, papel do utilizador e regras no backend.

#### Existe CSRF?

Sim. O sistema trabalha com token CSRF nas requisicoes.

---

## 10. Frontend e interface

### Como a interface foi pensada

Foi pensada para ser clara, responsiva e funcional, sem depender de frameworks pesados.

### Pontos principais

- layout centralizado em `layout.php`;
- cabecalho fixo com `position: sticky`;
- menu muda conforme autenticacao e perfil;
- menu mobile com botao hamburguer;
- modal global reutilizavel;
- feedback com alertas e notificacoes;
- barras de progresso;
- contadores animados no dashboard;
- carregamento parcial em modais com `fetch`;
- CSS geral e CSS por pagina;
- JavaScript global e JavaScript por pagina.

### Como fizeram o cabecalho fixo?

Usando CSS com `position: sticky`, `top: 0` e `z-index` alto.

### Como fizeram os contadores?

O backend envia os dados para a view e o JavaScript anima os numeros no navegador.

### Isso e tempo real?

Nao por socket. E uma interface dinamica com atualizacao visual no carregamento da pagina e em eventos especificos.

### Como abriram os modais?

Com um modal global no layout, controlado por JavaScript, que recebe titulo, corpo e rodape dinamicamente.

### Porque usar JavaScript puro?

Porque resolvia bem a interatividade necessaria sem aumentar a complexidade do projeto.

### Como a interface conversa com o MVC?

As views mostram a informacao, os controllers preparam os dados e o JavaScript so melhora a interacao.

### Resposta curta sobre a interface

> A interface foi desenhada para ser clara, responsiva e operacional. O backend continua central no MVC e o frontend entra para melhorar usabilidade, feedback visual e fluidez.

---

## 11. Dashboard e analise

### O que o aluno ve

- cursos matriculados;
- progresso;
- andamento;
- cursos concluidos.

### O que o professor ve

- total de cursos;
- total de alunos;
- total de aulas;
- atividades;
- desempenho de quizzes;
- media e taxa de aprovacao;
- perguntas com mais erros.

### O que o admin ve

- dados globais do sistema;
- utilizadores;
- cursos;
- visao administrativa.

### Porque o dashboard do professor e forte na defesa

Porque mostra que a plataforma nao serve apenas para publicar conteudo. Ela tambem apoia analise e acompanhamento pedagogico.

---

## 12. Quizzes e avaliacao

### Tipos de quiz

- quiz de aula;
- quiz de modulo;
- quiz final.

### O que o sistema faz

- corrige automaticamente;
- guarda tentativas;
- aplica limite de tentativas;
- pode usar tempo limite;
- pode embaralhar perguntas e respostas;
- pode definir nota minima;
- pode ocultar respostas corretas.

### Pergunta forte de defesa

#### O aluno so precisa responder ou precisa ser aprovado?

Ele precisa cumprir as regras do quiz, o que pode incluir aprovacao minima, dependendo da configuracao.

#### Porque isso e bom?

Porque transforma o quiz em avaliacao real, e nao apenas formalidade.

---

## 13. Progresso e certificacao

### Como o sistema sabe que o curso foi concluido?

Verificando progresso, aulas concluidas e criterios de avaliacao exigidos.

### O progresso depende so de aulas?

Nao. Pode depender tambem de quizzes obrigatorios.

### O certificado e automatico?

Ele e liberado quando o aluno se torna elegivel segundo as regras do sistema.

### O certificado pode ser verificado?

Sim. Ha validacao publica.

### O PDF e um print simples?

Nao. Ele e gerado de forma controlada com HTML/CSS e Puppeteer.

### Porque usar Puppeteer?

Porque produz um PDF visualmente consistente com a interface.

### Resposta curta

> O certificado nao e apenas baixar um ficheiro. Ele depende de elegibilidade, pode ser validado e o PDF e gerado com controle visual real.

---

## 14. Banco de dados - o que mais importa saber

### Tabelas principais

- utilizadores;
- cursos;
- modulos;
- aulas;
- matriculas;
- quizzes;
- tentativas de quiz;
- respostas de tentativas;
- certificados.

### Conceitos importantes

#### Chave primaria

Identifica unicamente um registo.

#### Chave estrangeira

Liga tabelas relacionadas.

#### Integridade referencial

Garante coerencia entre os dados relacionados.

#### Normalizacao

Organiza os dados para reduzir redundancia.

### Perguntas provaveis

#### Porque separar `quiz_attempts` de `quiz_attempt_answers`?

Porque uma tabela guarda a tentativa como evento e a outra guarda o detalhe de cada resposta.

#### Porque existe chave unica na matricula?

Para impedir que o mesmo aluno se matricule duas vezes no mesmo curso.

#### Porque existem modulos no banco?

Para permitir cursos estruturados por etapas, sem quebrar a logica geral da plataforma.

---

## 15. Pontos fortes reais do projeto

Estas ideias valorizam muito a defesa:

- nao e apenas um CRUD;
- ha separacao por perfis;
- o quiz e rico em regras;
- o progresso nao e simplista;
- o certificado e verificavel;
- o professor recebe analise de desempenho;
- a interface foi organizada e reutilizavel;
- existe preocupacao com seguranca;
- a arquitetura facilita manutencao.

### Frase boa para usar

> Um ponto forte do projeto e que ele combina gestao, avaliacao, progresso e certificacao numa arquitetura organizada, em vez de se limitar a cadastro e listagem de dados.

---

## 16. Limitacoes reais para assumir com maturidade

### Limitacoes honestas

- nao esta pronto para producao em larga escala;
- ainda depende de ambiente local;
- nao usa migracoes formais robustas;
- testes automatizados ainda sao limitados;
- seguranca pode evoluir mais;
- pode haver melhoria futura em acessibilidade e escalabilidade.

### Como assumir isso bem

> O projeto esta forte para o escopo academico e demonstra organizacao, regras de negocio e capacidade de evolucao, embora ainda existam melhorias naturais para um ambiente de producao real.

---

## 17. Melhorias futuras

### Prioridades tecnicas

- migracoes versionadas;
- testes automatizados;
- middleware mais centralizado;
- melhor estrutura de logs;
- deploy mais profissional.

### Prioridades funcionais

- notificacoes;
- relatorios exportaveis;
- foruns ou comentarios;
- streaming mais robusto;
- mais analise pedagogica.

### Prioridades de experiencia

- mais acessibilidade;
- mais padronizacao visual;
- componentes ainda mais reutilizaveis;
- feedback assincrono mais rico.

---

## 18. Perguntas essenciais com respostas curtas

### O projeto usa MVC?

Sim. A separacao entre model, view e controller organiza o sistema e facilita manutencao.

### Onde a aplicacao comeca?

Em `public/index.php`.

### O sistema e web ou desktop?

E um sistema web.

### Porque usaram PHP e MySQL?

Porque sao tecnologias acessiveis, conhecidas e adequadas para o escopo.

### Porque nao usaram Laravel ou outro framework?

Porque o objetivo era construir e defender a logica principal com uma base mais controlada e didatica.

### O professor pode editar curso de outro professor?

Nao, salvo permissao administrativa.

### O aluno recebe certificado automaticamente?

So quando cumpre os criterios de elegibilidade.

### O progresso considera apenas aulas?

Nao. Pode considerar tambem quizzes obrigatorios.

### O sistema suporta modulos?

Sim.

### O certificado pode ser verificado?

Sim, publicamente.

### O quiz tem limite de tentativas?

Sim, dependendo da configuracao.

### O frontend sozinho decide regras importantes?

Nao. As regras principais ficam no backend.

---

## 19. Mini bloco de conceitos para memorizar

### Algoritmo

Sequencia de passos para resolver um problema.

### MVC

Separacao entre model, view e controller.

### SQL Injection

Manipulacao maliciosa de consultas SQL.

### Prepared statement

Forma segura de executar consultas parametrizadas.

### Autenticacao

Confirmar identidade do utilizador.

### Autorizacao

Controlar o que ele pode fazer.

### Sessao

Mecanismo para manter estado entre requisicoes.

### Hash de senha

Forma segura de guardar senha sem texto puro.

### Responsividade

Capacidade da interface adaptar-se a varios tamanhos de ecra.

### CSRF

Ataque em que uma requisicao indevida e enviada em nome do utilizador autenticado.

### XSS

Injecao de conteudo malicioso na interface.

---

## 20. Como responder quando nao souberes algo

### Melhor postura

Ser honesto e responder com metodo.

### Nunca fazer

- inventar;
- afirmar sem certeza;
- discutir sem base;
- dizer algo que contradiz o codigo real.

### Frases boas para usar

> Vou responder com base no que esta implementado hoje no sistema.

> O principio tecnico eu consigo explicar, mas prefiro nao cravar esse detalhe sem confirmar no codigo.

> Neste ponto especifico eu nao vou arriscar uma resposta imprecisa. O que posso afirmar com seguranca e o comportamento dessa parte no sistema.

### Estrutura segura de resposta

1. dizer o que sabes com certeza;
2. admitir o que nao lembras;
3. explicar a logica geral;
4. mostrar como validarias no codigo.

---

## 21. Resumo final para memorizar

Se tiveres pouco tempo, memoriza isto:

> A Plataforma EAD e um sistema web em PHP e MySQL com arquitetura MVC. Ela possui autenticacao por perfis, gestao de cursos, modulos, aulas e quizzes, acompanhamento de progresso, dashboards por papel e emissao de certificados verificaveis em PDF. O projeto aplica regras de negocio reais para progresso, avaliacao e certificacao, usa PDO com prepared statements, sessoes, controlo de permissao e organizacao por camadas. O frontend usa HTML, CSS e JavaScript puro para melhorar a experiencia sem transformar o sistema numa SPA. Os pontos fortes sao organizacao, seguranca dentro do escopo, analise pedagogica e integracao entre ensino, avaliacao e certificacao.

---

## 22. Ligacao com o arquivo completo

Este resumo foi feito a partir de:

- [QUESTIONARIO_DEFESA_FINAL.md](/opt/lampp/htdocs/Plataforma-EAD/QUESTIONARIO_DEFESA_FINAL.md)

Para estudo rapido, usa este arquivo.
Para preparacao profunda e respostas detalhadas, usa o arquivo completo.
