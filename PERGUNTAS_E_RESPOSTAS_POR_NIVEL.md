# Perguntas e Respostas por Nível - Plataforma EAD

## 1. Objetivo deste arquivo

Este arquivo organiza as perguntas e respostas por nível de dificuldade para facilitar estudo e memorização.

Os níveis são:

- básico;
- intermédio;
- avançado;
- banca agressiva.

A lógica é simples:

- no básico, ficas pronto para apresentar o projeto;
- no intermédio, ficas pronto para explicar funcionamento;
- no avançado, ficas pronto para responder tecnicamente;
- na banca agressiva, ficas pronto para perguntas críticas, comparativas e desafiadoras.

---

## 2. Nível Básico

### 1. Qual é o nome do projeto?
Resposta: Plataforma EAD.

### 2. O que é este projeto?
Resposta: É uma plataforma web de ensino a distância.

### 3. O que a plataforma faz?
Resposta: Permite gerir cursos, aulas, quizzes, progresso, usuários e certificados.

### 4. Qual é o principal objetivo do projeto?
Resposta: Centralizar o processo de ensino online num único sistema.

### 5. Quem pode usar a plataforma?
Resposta: Alunos, professores e administradores.

### 6. O que um aluno pode fazer?
Resposta: Ver cursos, matricular-se, assistir aulas, fazer quizzes, acompanhar progresso e obter certificado.

### 7. O que um professor pode fazer?
Resposta: Criar cursos, organizar módulos, criar aulas, criar quizzes e acompanhar os alunos.

### 8. O que um administrador pode fazer?
Resposta: Supervisionar usuários, cursos, quizzes e estatísticas gerais.

### 9. Quais tecnologias foram usadas?
Resposta: PHP, MySQL, HTML, CSS e JavaScript.

### 10. O sistema gera certificado?
Resposta: Sim.

### 11. O sistema tem login?
Resposta: Sim.

### 12. O sistema tem registro?
Resposta: Sim.

### 13. O sistema tem quizzes?
Resposta: Sim.

### 14. O sistema tem dashboards?
Resposta: Sim.

### 15. O sistema tem backup?
Resposta: Sim.

### 16. Qual o problema que a plataforma resolve?
Resposta: Resolve a necessidade de organizar o ensino online em um único ambiente.

### 17. O sistema é só um site de cursos?
Resposta: Não. Ele também controla progresso, avaliação, certificados e gestão de dados.

### 18. O sistema é mais do que um CRUD?
Resposta: Sim, porque possui regras reais de negócio.

### 19. O sistema é útil para quem?
Resposta: Para escolas, centros de formação, professores e alunos.

### 20. Qual o principal diferencial do projeto?
Resposta: A integração entre cursos, avaliação, progresso, certificados verificáveis e backup.

---

## 3. Nível Intermédio

### 21. Qual arquitetura foi usada?
Resposta: MVC adaptado.

### 22. O que significa MVC?
Resposta: Model, View e Controller.

### 23. Onde fica a entrada principal do sistema?
Resposta: Em `public/index.php`.

### 24. O que faz o `public/index.php`?
Resposta: Recebe a requisição, carrega o sistema, processa ações e renderiza as páginas.

### 25. Onde ficam os controllers?
Resposta: Em `app/controllers/`.

### 26. Onde ficam os models?
Resposta: Em `app/models/`.

### 27. Onde ficam as views?
Resposta: Em `app/views/`.

### 28. Onde ficam as configurações?
Resposta: Em `config/`.

### 29. Onde ficam os scripts auxiliares?
Resposta: Em `scripts/`.

### 30. O sistema trabalha com perfis?
Resposta: Sim, aluno, professor e admin.

### 31. Como o sistema sabe o perfil do utilizador?
Resposta: Pelo campo `role` do usuário.

### 32. Como o sistema mantém o login?
Resposta: Através de sessão PHP.

### 33. O sistema protege ações sensíveis?
Resposta: Sim, com sessão, papel e CSRF.

### 34. O aluno pode criar curso?
Resposta: Não.

### 35. O professor pode editar curso de outro professor?
Resposta: Não.

### 36. O curso pode ser de módulo único ou múltiplos módulos?
Resposta: Sim.

### 37. O que é um módulo?
Resposta: É uma subdivisão do curso para organizar o conteúdo.

### 38. O que é uma aula?
Resposta: É a unidade concreta de conteúdo dentro de um curso.

### 39. O que é um quiz?
Resposta: É uma avaliação ligada a aula, módulo ou curso.

### 40. O sistema calcula progresso?
Resposta: Sim.

### 41. O progresso depende só de ver a aula?
Resposta: Não. Em certos casos depende também de aprovação no quiz obrigatório.

### 42. O certificado é automático?
Resposta: Ele pode ser emitido automaticamente quando os critérios são cumpridos.

### 43. O certificado pode ser validado publicamente?
Resposta: Sim.

### 44. O sistema tem exportação de dados?
Resposta: Sim.

### 45. O sistema tem restauração de backup?
Resposta: Sim.

### 46. O professor vê desempenho dos alunos?
Resposta: Sim.

### 47. O admin vê estatísticas globais?
Resposta: Sim.

### 48. O sistema tem frontend responsivo?
Resposta: Sim.

### 49. O sistema usa JavaScript no frontend?
Resposta: Sim.

### 50. O sistema usa Node em partes específicas?
Resposta: Sim, por exemplo para PDF e transcrição.

---

## 4. Nível Avançado

### 51. Como o sistema acessa a base de dados?
Resposta: Através de PDO.

### 52. Por que usar PDO?
Resposta: Porque oferece prepared statements e melhor segurança.

### 53. Como o sistema evita SQL Injection?
Resposta: Com prepared statements.

### 54. Como o sistema armazena senhas?
Resposta: Em hash.

### 55. Como o sistema valida senha no login?
Resposta: Com `password_verify`.

### 56. Como o sistema impede abuso de login?
Resposta: Controlando tentativas e aplicando bloqueio temporário.

### 57. Como o sistema protege formulários?
Resposta: Com token CSRF.

### 58. Como o sistema trata requisições AJAX?
Resposta: Detecta AJAX e responde com JSON quando necessário.

### 59. O sistema usa autoload?
Resposta: Sim, em `config/autoload.php`.

### 60. O sistema carrega `.env`?
Resposta: Sim, via `config/env.php`.

### 61. O sistema tem suporte a URL amigável?
Resposta: Sim, em alguns fluxos, especialmente certificados.

### 62. Onde a lógica de autenticação está centralizada?
Resposta: Em `AuthController.php`.

### 63. Onde a lógica de cursos está centralizada?
Resposta: Em `CourseController.php` e `Course.php`.

### 64. Onde a lógica de aulas está centralizada?
Resposta: Em `LessonController.php` e `Lesson.php`.

### 65. Onde a lógica de quizzes está centralizada?
Resposta: Em `QuizController.php` e `Quiz.php`.

### 66. Onde a lógica de certificados está centralizada?
Resposta: Em `CertificateController.php` e `Certificate.php`.

### 67. Onde a lógica de importação está centralizada?
Resposta: Em `ImportController.php`.

### 68. Onde a lógica de exportação está centralizada?
Resposta: Em `ExportController.php`.

### 69. O projeto usa services?
Resposta: Sim.

### 70. Por que usar services?
Resposta: Para separar responsabilidades mais especializadas, como IA, mídia, storage e logs.

### 71. O que faz `LessonAiTutorService`?
Resposta: Responde perguntas do aluno com base no contexto da aula.

### 72. O que faz `LessonContentService`?
Resposta: Gera ou recupera conteúdo inteligente derivado da aula.

### 73. O que faz `LessonTranscriptService`?
Resposta: Trabalha com transcrição da aula.

### 74. O que faz `StorageService`?
Resposta: Abstrai o armazenamento de arquivos.

### 75. O que faz `BackupLogService`?
Resposta: Registra logs de backup e preferências.

### 76. O sistema guarda tentativas detalhadas de quiz?
Resposta: Sim, em `quiz_attempts`.

### 77. O sistema guarda respostas detalhadas de quiz?
Resposta: Sim, em `quiz_attempt_answers`.

### 78. O sistema ainda tem compatibilidade com estrutura legada de quiz?
Resposta: Sim, em `quiz_results`.

### 79. O sistema usa migrações?
Resposta: Sim.

### 80. O sistema também usa `ensureSchema()`?
Resposta: Sim, em várias classes para evolução incremental do schema.

### 81. Isso é uma vantagem?
Resposta: É útil para compatibilidade e evolução gradual.

### 82. Isso também pode ser uma limitação?
Resposta: Sim, porque centralizar tudo apenas em migrações formais seria mais previsível em alguns cenários.

### 83. Como o sistema decide elegibilidade do certificado?
Resposta: Com base no snapshot de conclusão e aprovação calculado pelo model de certificados.

### 84. O sistema pode revogar certificado?
Resposta: Sim.

### 85. O certificado em PDF é protegido?
Resposta: Sim, por token temporário.

### 86. O sistema valida backup antes de restaurar?
Resposta: Sim.

### 87. O backup usa checksums?
Resposta: Sim.

### 88. O restore impede caminhos inseguros dentro do ZIP?
Resposta: Sim.

### 89. O sistema suporta restauração parcial?
Resposta: Sim, conforme escopo.

### 90. O tutor de IA possui cache e proteção contra repetição?
Resposta: Sim.

---

## 5. Nível Banca Agressiva

### 91. Se o sistema é MVC, por que o `public/index.php` está tão grande?
Resposta: Porque ele centraliza o front controller e parte do roteamento e das ações. Isso funciona, mas é um ponto claro de refatoração futura.

### 92. Isso não é uma falha arquitetural?
Resposta: É uma limitação arquitetural reconhecida, não uma falha que invalide o sistema. O restante do projeto já possui boa modularização em controllers, models e services.

### 93. Por que não usar um framework maduro?
Resposta: Porque o objetivo acadêmico também era demonstrar domínio dos fundamentos e construção da base arquitetural sem depender de abstrações prontas.

### 94. O sistema está pronto para produção?
Resposta: Está funcional e relativamente robusto, mas uma produção plena exigiria reforços em testes, observabilidade, deploy e hardening.

### 95. Se faltam testes automatizados fortes, por que considerar o projeto sólido?
Resposta: Porque ele já demonstra integração real de múltiplos módulos e regras de negócio consistentes. A ampliação dos testes é uma evolução importante, mas não anula a solidez funcional já alcançada.

### 96. Como tu provas que isto não é só interface bonita?
Resposta: Porque o sistema possui lógica real de autenticação, tentativas de login, progressão, tentativas de quiz, elegibilidade e revogação de certificados, exportação e restauração estruturada.

### 97. Como tu provas que o certificado é real e não apenas um download?
Resposta: Porque ele depende de critérios de elegibilidade calculados a partir do estado do aluno e ainda pode ser validado publicamente por código.

### 98. Como tu provas que o projeto não é só CRUD?
Resposta: Porque há regras de progressão, controle de tentativas, análise de aprovação, sincronização de certificados, verificação pública, logs e backup estruturado.

### 99. Qual é o maior mérito técnico do projeto?
Resposta: A integração coerente entre conteúdo, avaliação, progresso, certificação e portabilidade de dados.

### 100. Qual é o maior desafio técnico do projeto?
Resposta: Garantir consistência entre aulas, quizzes, progresso e certificação sem perder clareza na arquitetura.

### 101. Se a banca disser que o código evoluiu de forma incremental e por isso ficou híbrido, o que respondes?
Resposta: Concordo. E isso é natural em software real. O importante é que a compatibilidade foi tratada no próprio sistema e as regras centrais continuam consistentes.

### 102. Se a banca perguntar o que seria a primeira refatoração, o que respondes?
Resposta: Separar mais claramente roteamento, handlers e renderização hoje concentrados em `public/index.php`.

### 103. Se a banca perguntar o que mais aprendeste, o que respondes?
Resposta: Aprendi a integrar arquitetura web, modelagem de dados, segurança, regras de negócio e evolução incremental num sistema funcional.

### 104. Se a banca perguntar o que faria diferente desde o início, o que respondes?
Resposta: Eu desenharia desde cedo uma camada de roteamento mais modular e uma política de migrações ainda mais centralizada.

### 105. Se a banca disser que faltam integrações externas, o que respondes?
Resposta: Sim, ainda há espaço para integrações futuras como pagamentos, notificações e APIs mais amplas, mas o núcleo funcional da plataforma já está implementado.

### 106. Se a banca perguntar se o projeto demonstra engenharia de software, o que respondes?
Resposta: Sim, porque há separação por camadas, regras de negócio explícitas, modelagem relacional, segurança aplicada, modularização funcional e consciência de evolução futura.

### 107. Se a banca perguntar onde está a originalidade, o que respondes?
Resposta: Na forma como os módulos foram integrados, nas regras de progressão e certificação e nas soluções implementadas para um domínio real de ensino online.

### 108. Se a banca perguntar por que teu projeto merece aprovação, o que respondes?
Resposta: Porque ele demonstra capacidade de analisar um problema real, modelar a solução, implementá-la com arquitetura e regras consistentes e ainda reconhecer limitações e caminhos de evolução.

---

## 6. Perguntas-relâmpago para revisar rápido

### 109. Qual o principal arquivo do sistema?
Resposta: `public/index.php`.

### 110. Qual o padrão arquitetural?
Resposta: MVC adaptado.

### 111. Qual o backend?
Resposta: PHP.

### 112. Qual o banco?
Resposta: MySQL.

### 113. Como o sistema protege consultas?
Resposta: PDO com prepared statements.

### 114. Como protege senha?
Resposta: Hash.

### 115. Como protege formulários?
Resposta: CSRF.

### 116. Perfis do sistema?
Resposta: Aluno, professor e admin.

### 117. O certificado é verificável?
Resposta: Sim.

### 118. O sistema tem IA?
Resposta: Sim.

### 119. O sistema tem backup?
Resposta: Sim.

### 120. Principal limitação?
Resposta: `public/index.php` muito centralizador e necessidade de mais testes automatizados.

---

## 7. Como estudar este arquivo

Sugestão prática:

1. Memoriza primeiro o nível básico.
2. Depois domina o intermédio para falares com naturalidade.
3. Estuda o avançado para perguntas técnicas.
4. Treina o bloco de banca agressiva em voz alta.

---

## 8. Fecho curto para defesa

> A Plataforma EAD é um sistema funcional e tecnicamente consistente, com autenticação, cursos, módulos, aulas, quizzes, progresso, certificados e backup. O projeto demonstra não só interface, mas regras reais de negócio, integração entre módulos, segurança aplicada e capacidade de evolução arquitetural.

