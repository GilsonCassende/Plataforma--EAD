# Banco Total de Perguntas e Respostas da Plataforma EAD

## 1. Objetivo deste arquivo

Este arquivo é focado exclusivamente em perguntas e respostas sobre o projeto.

Ele foi pensado para cobrir o máximo possível de ângulos de banca:

- visão geral;
- problema e objetivo;
- funcionalidades;
- arquitetura;
- backend;
- frontend;
- banco de dados;
- autenticação;
- segurança;
- cursos, módulos e aulas;
- quizzes;
- progresso;
- certificados;
- dashboards;
- backup, exportação e importação;
- IA;
- qualidade;
- limitações;
- melhorias futuras;
- perguntas difíceis e críticas.

Observação importante:

Não existe como prever literalmente todas as perguntas do universo, mas este arquivo foi montado para cobrir de forma muito ampla praticamente tudo o que normalmente pode ser perguntado sobre este projeto.

---

## 2. Perguntas e respostas gerais

### 1. Qual é o nome do projeto?
Resposta: O nome do projeto é Plataforma EAD.

### 2. O que significa EAD?
Resposta: EAD significa Educação a Distância.

### 3. O que é este projeto?
Resposta: É uma plataforma web de ensino a distância que permite gerir usuários, cursos, módulos, aulas, quizzes, progresso, certificados e backup de dados.

### 4. Qual é o objetivo principal do projeto?
Resposta: O objetivo é centralizar o ciclo principal do ensino online num único sistema, com autenticação, organização de conteúdo, avaliação, progresso e certificação.

### 5. Qual problema o projeto resolve?
Resposta: Resolve a fragmentação do ensino online, reunindo cadastro, acesso, cursos, avaliações, acompanhamento e certificação num ambiente único.

### 6. Qual é o público-alvo da plataforma?
Resposta: Alunos, professores, administradores, escolas, centros de formação e instrutores independentes.

### 7. O projeto é acadêmico ou comercial?
Resposta: Foi desenvolvido em contexto acadêmico, mas com estrutura que pode evoluir para uso real.

### 8. O projeto é apenas um protótipo?
Resposta: Não. Ele já possui regras reais de negócio, fluxo funcional e vários módulos integrados.

### 9. O projeto é mais do que um CRUD?
Resposta: Sim. Além de CRUD, ele implementa autenticação, autorização, progresso, tentativas de quiz, elegibilidade de certificado, validação pública e backup/restauração.

### 10. Qual é o diferencial principal da plataforma?
Resposta: A integração entre conteúdo, avaliação, progresso, certificados verificáveis e portabilidade de dados.

---

## 3. Perguntas sobre motivação e contexto

### 11. Por que escolheste esse tema?
Resposta: Porque educação online é um problema atual, relevante e tecnicamente rico para desenvolver um sistema completo.

### 12. Por que uma plataforma EAD é importante?
Resposta: Porque facilita acesso à aprendizagem, organização pedagógica, acompanhamento do aluno e escalabilidade do ensino.

### 13. Qual foi a motivação para desenvolver este sistema?
Resposta: Criar uma solução funcional que unisse fundamentos de engenharia de software com um domínio real e útil.

### 14. O sistema foi pensado para qual realidade?
Resposta: Para ensino técnico, formação profissional, instituições de ensino e instrutores que precisem gerir cursos online.

### 15. Esse problema existe apenas localmente?
Resposta: Não. É um problema global, porque o ensino digital existe em vários contextos.

### 16. O projeto resolve um problema real?
Resposta: Sim. Ele resolve a necessidade de centralizar e organizar o ensino online.

### 17. O que havia de difícil nesse domínio?
Resposta: Garantir coerência entre conteúdo, avaliação, progresso, permissões e certificação.

### 18. Por que não fazer um projeto mais simples?
Resposta: Porque o objetivo era demonstrar integração entre múltiplos módulos e regras reais de negócio.

---

## 4. Perguntas sobre visão funcional

### 19. O que um aluno pode fazer na plataforma?
Resposta: Registrar-se, fazer login, ver cursos, matricular-se, assistir aulas, marcar conclusão, fazer quizzes, acompanhar progresso e acessar certificados.

### 20. O que um professor pode fazer?
Resposta: Criar cursos, organizar módulos, criar aulas, criar quizzes, editar o próprio conteúdo e acompanhar alunos e desempenho.

### 21. O que um administrador pode fazer?
Resposta: Supervisionar usuários, cursos, quizzes e estatísticas gerais do sistema.

### 22. O sistema possui catálogo de cursos?
Resposta: Sim.

### 23. O sistema possui dashboards?
Resposta: Sim, para aluno, professor e administrador.

### 24. O sistema possui área de perfil?
Resposta: Sim.

### 25. O sistema emite certificados?
Resposta: Sim.

### 26. Os certificados podem ser validados?
Resposta: Sim, por código público.

### 27. O sistema possui backup?
Resposta: Sim.

### 28. O sistema possui restore?
Resposta: Sim.

### 29. O sistema possui exportação de dados?
Resposta: Sim.

### 30. O sistema possui funcionalidades de IA?
Resposta: Sim, ligadas a aulas e apoio ao aluno.

---

## 5. Perguntas sobre arquitetura

### 31. Qual arquitetura foi usada?
Resposta: MVC adaptado.

### 32. O que é MVC?
Resposta: É a separação entre Model, View e Controller.

### 33. Onde fica a entrada principal do sistema?
Resposta: Em `public/index.php`.

### 34. O que faz o `public/index.php`?
Resposta: Carrega o sistema, processa requisições, interpreta a rota, chama a lógica necessária e renderiza a página.

### 35. Onde ficam os controllers?
Resposta: Em `app/controllers/`.

### 36. Onde ficam os models?
Resposta: Em `app/models/`.

### 37. Onde ficam as views?
Resposta: Em `app/views/`.

### 38. Onde ficam os serviços especializados?
Resposta: Em `app/services/`.

### 39. Onde ficam as configurações?
Resposta: Em `config/`.

### 40. Onde ficam as migrações?
Resposta: Em `migrations/`.

### 41. Onde ficam os scripts de apoio?
Resposta: Em `scripts/`.

### 42. Onde ficam os arquivos públicos da web?
Resposta: Em `public/`.

### 43. O projeto usa um roteador formal?
Resposta: O roteamento principal está concentrado em `public/index.php`, com apoio de `public/router.php`.

### 44. O sistema usa views renderizadas no servidor?
Resposta: Sim.

### 45. O sistema é monolítico?
Resposta: Sim, é um monólito web modularizado.

### 46. O projeto usa orientação a objetos?
Resposta: Sim.

### 47. O projeto usa serviços além de model e controller?
Resposta: Sim, para funcionalidades especializadas.

### 48. O sistema é bem separado em camadas?
Resposta: Em grande parte sim, embora o `public/index.php` concentre bastante fluxo.

### 49. Qual arquivo mais crítico da arquitetura?
Resposta: `public/index.php`.

### 50. Qual ponto arquitetural mais sensível?
Resposta: A centralização de muita responsabilidade no front controller.

---

## 6. Perguntas sobre stack tecnológica

### 51. Quais tecnologias foram usadas no backend?
Resposta: PHP e MySQL com PDO.

### 52. Quais tecnologias foram usadas no frontend?
Resposta: HTML5, CSS3 e JavaScript puro.

### 53. Qual servidor é usado normalmente?
Resposta: Apache, normalmente em XAMPP.

### 54. O sistema usa Composer?
Resposta: Sim.

### 55. O sistema usa npm/Node?
Resposta: Sim, em scripts auxiliares específicos.

### 56. Que dependência principal existe no Composer?
Resposta: `phpmailer/phpmailer`.

### 57. Que dependências principais existem no Node?
Resposta: `puppeteer-core`, `qrcode` e `youtube-transcript`.

### 58. Por que usar PHP?
Resposta: Porque é adequado ao contexto web, amplamente conhecido, acessível e bom para aplicações como esta.

### 59. Por que usar MySQL?
Resposta: Porque é robusto, amplamente adotado e integra bem com PHP.

### 60. Por que usar JavaScript puro?
Resposta: Para manter simplicidade e mostrar domínio dos fundamentos web sem depender de frameworks.

### 61. Por que usar Puppeteer?
Resposta: Para gerar PDF do certificado com fidelidade visual.

### 62. Por que usar PHPMailer?
Resposta: Para envio de emails, como recuperação de senha e fluxos auxiliares.

### 63. Por que não usar Laravel?
Resposta: Porque o objetivo também era mostrar domínio direto dos fundamentos do backend e da arquitetura.

### 64. Por que não usar React ou Vue?
Resposta: Porque o foco do projeto estava em construir uma base web funcional sólida sem aumentar a complexidade inicial.

---

## 7. Perguntas sobre backend

### 65. Como o backend está organizado?
Resposta: Em controllers, models, services e arquivos de configuração.

### 66. Como o sistema acessa a base de dados?
Resposta: Através de PDO.

### 67. Onde a conexão PDO é criada?
Resposta: Em `config/database.php`.

### 68. Como as variáveis de ambiente são carregadas?
Resposta: Por `config/env.php`.

### 69. O projeto tem autoload?
Resposta: Sim, em `config/autoload.php`.

### 70. O autoload usa Composer?
Resposta: Sim, quando o autoload do Composer existe.

### 71. O projeto também usa autoload próprio?
Resposta: Sim, para classes em controllers, models e services.

### 72. O backend devolve apenas HTML?
Resposta: Não. Também devolve JSON em chamadas AJAX.

### 73. O backend trata POST e GET?
Resposta: Sim.

### 74. Onde as ações POST são processadas?
Resposta: Na função `processarAcao()` em `public/index.php`.

### 75. O sistema valida permissões no backend?
Resposta: Sim.

### 76. O sistema valida CSRF no backend?
Resposta: Sim.

### 77. O sistema tem tratamento de exceção?
Resposta: Sim, em vários pontos da aplicação.

### 78. O sistema tem fallback de layout?
Resposta: Sim, existe um modo de recuperação caso o layout principal falhe.

### 79. O backend é extensível?
Resposta: Sim, há base suficiente para expansão.

### 80. O backend já possui serviços especializados?
Resposta: Sim.

---

## 8. Perguntas sobre frontend

### 81. Como o frontend está organizado?
Resposta: Em views PHP, CSS global e CSS por página, além de JS global e JS por página.

### 82. Onde ficam os estilos?
Resposta: Em `public/css/`.

### 83. Onde ficam os scripts?
Resposta: Em `public/js/`.

### 84. Há CSS por página?
Resposta: Sim.

### 85. Há JavaScript por página?
Resposta: Sim.

### 86. O layout é único?
Resposta: Sim, o layout principal está em `app/views/layout.php`.

### 87. O frontend é responsivo?
Resposta: Sim.

### 88. O frontend usa fetch?
Resposta: Sim, inclusive com helper para CSRF.

### 89. O que faz `csrfFetch`?
Resposta: Adiciona token CSRF e credenciais às requisições fetch.

### 90. O sistema tem notificações visuais?
Resposta: Sim, como toasts e alertas.

### 91. O sistema tem modal global?
Resposta: Sim, no layout.

### 92. O sistema tem menu mobile?
Resposta: Sim.

### 93. O sistema tem animações?
Resposta: Sim, especialmente em UI e progresso.

### 94. O sistema possui lazy loading de imagens?
Resposta: Sim, há suporte em `public/js/ui.js`.

### 95. O frontend tem sanitização de fragmentos HTML?
Resposta: Sim, há utilitários para isso.

---

## 9. Perguntas sobre banco de dados

### 96. Qual banco de dados foi utilizado?
Resposta: MySQL.

### 97. Onde está o schema principal?
Resposta: Em `migrations/schema.sql`.

### 98. Quais são as tabelas principais?
Resposta: `users`, `courses`, `lessons`, `enrollments`, `quizzes`, `questions`, `quiz_attempts`, `quiz_attempt_answers`, `lesson_progress`, `certificates` e outras auxiliares.

### 99. O banco tem chave primária?
Resposta: Sim, nas tabelas principais.

### 100. O banco tem chaves estrangeiras?
Resposta: Sim.

### 101. O banco tem índices?
Resposta: Sim.

### 102. Há unicidade em matrículas?
Resposta: Sim, para impedir matrícula duplicada do mesmo aluno no mesmo curso.

### 103. Como os quizzes se relacionam com o curso?
Resposta: Diretamente ou via aula, conforme o tipo de quiz.

### 104. Como os certificados se relacionam com o aluno?
Resposta: Por `user_id`.

### 105. Como os certificados se relacionam com o curso?
Resposta: Por `course_id`.

### 106. O banco guarda progresso?
Resposta: Sim, tanto detalhado quanto agregado.

### 107. O banco guarda tentativas de quiz?
Resposta: Sim.

### 108. O banco guarda respostas por pergunta?
Resposta: Sim.

### 109. O banco guarda logs de IA?
Resposta: Sim.

### 110. O banco guarda logs de backup?
Resposta: Sim, por tabelas auxiliares criadas pelo serviço de backup.

### 111. O banco foi pensado para evolução?
Resposta: Sim, inclusive com migrações e `ensureSchema()` em partes do código.

### 112. O que significa migração neste projeto?
Resposta: Alterações versionadas no esquema do banco para adaptar a estrutura de dados.

### 113. O que é `000_full_schema_plus_migrations.sql`?
Resposta: Um schema consolidado com migrações incorporadas.

### 114. O que é `009_add_video_id_to_lessons.sql`?
Resposta: Migração que adiciona suporte a `video_id`.

### 115. O que é `010_lesson_transcript_and_ai_logs.sql`?
Resposta: Migração ligada à transcrição de aulas e logs do tutor de IA.

---

## 10. Perguntas sobre autenticação

### 116. O sistema possui login?
Resposta: Sim.

### 117. O sistema possui logout?
Resposta: Sim.

### 118. O sistema possui registro?
Resposta: Sim.

### 119. O sistema possui recuperação de senha?
Resposta: Sim.

### 120. Como o login funciona?
Resposta: O usuário informa email e senha, o sistema valida as credenciais e cria a sessão.

### 121. Onde a lógica de autenticação está?
Resposta: Principalmente em `app/controllers/AuthController.php`.

### 122. Como a senha é validada?
Resposta: Com `password_verify`.

### 123. Como a senha é armazenada?
Resposta: Em hash.

### 124. O sistema controla tentativas de login?
Resposta: Sim.

### 125. O sistema bloqueia abuso de login?
Resposta: Sim, com bloqueio temporário após excesso de falhas.

### 126. O sistema regenera o ID da sessão?
Resposta: Sim, após login bem-sucedido.

### 127. O sistema possui token de reset?
Resposta: Sim.

### 128. O token expira?
Resposta: Sim.

### 129. O sistema guarda o token de reset diretamente?
Resposta: Guarda o hash do token.

### 130. O sistema possui verificação de email?
Resposta: A estrutura existe no projeto, embora o cadastro atual ative a conta de forma automática.

### 131. Como o sistema sabe quem está autenticado?
Resposta: Pela sessão `$_SESSION['usuario']`.

### 132. Que dados ficam na sessão?
Resposta: ID, nome, email, papel e, quando aplicável, fotografia.

---

## 11. Perguntas sobre autorização e perfis

### 133. Quais perfis existem?
Resposta: `aluno`, `professor` e `admin`.

### 134. Onde esses perfis são guardados?
Resposta: No campo `role` da tabela `users`.

### 135. O aluno pode criar curso?
Resposta: Não.

### 136. O professor pode criar curso?
Resposta: Sim.

### 137. O admin pode gerir cursos?
Resposta: Sim.

### 138. O professor pode apagar curso de outro professor?
Resposta: Não.

### 139. O admin pode alterar status de curso?
Resposta: Sim.

### 140. O aluno pode acessar o dashboard de admin?
Resposta: Não.

### 141. Como a permissão é validada?
Resposta: No backend, antes da ação sensível ser executada.

### 142. O sistema confia apenas na interface para impedir acesso?
Resposta: Não. O controle principal deve estar no backend.

### 143. O admin pode apagar a própria conta?
Resposta: O `AdminController` impede apagar a própria conta.

---

## 12. Perguntas sobre segurança

### 144. Como o sistema evita SQL Injection?
Resposta: Com prepared statements via PDO.

### 145. Como o sistema evita exposição de senha?
Resposta: Armazenando apenas hash.

### 146. O sistema tem proteção CSRF?
Resposta: Sim.

### 147. O sistema sanitiza entrada?
Resposta: Sim.

### 148. O sistema escapa saída HTML?
Resposta: Sim.

### 149. O sistema valida upload?
Resposta: Sim, por tamanho, extensão e MIME.

### 150. O sistema valida permissão antes de editar?
Resposta: Sim.

### 151. O sistema possui logs?
Resposta: Sim.

### 152. O sistema tem proteção para fetch AJAX?
Resposta: Sim, com `csrfFetch`.

### 153. O sistema protege rotas sensíveis?
Resposta: Sim, por autenticação, papel e CSRF.

### 154. O sistema trata URLs amigáveis com segurança?
Resposta: Sim, dentro do fluxo controlado do front controller.

### 155. Há risco por concentrar muita lógica no index?
Resposta: Sim, mas a centralização foi parcialmente mitigada por funções, controllers e validações.

### 156. O sistema protege contra upload de arquivo inconsistente?
Resposta: Sim, há conferência de MIME e extensão.

### 157. O sistema protege contra repetição abusiva no tutor de IA?
Resposta: Sim, há controle de duplicidade e rate limiting lógico.

### 158. O sistema protege links de PDF?
Resposta: Sim, com token temporário.

---

## 13. Perguntas sobre cursos

### 159. O que é um curso no sistema?
Resposta: A entidade principal de aprendizagem, criada por um professor.

### 160. Que campos principais um curso tem?
Resposta: Título, descrição, professor, categoria, estrutura, thumbnail e status.

### 161. Como um curso é criado?
Resposta: Via `CourseController`, que valida o professor e chama o model.

### 162. O professor pode escolher a estrutura do curso?
Resposta: Sim, módulo único ou múltiplos módulos.

### 163. O curso pode ter thumbnail?
Resposta: Sim.

### 164. O curso pode ser editado?
Resposta: Sim.

### 165. O curso pode ser apagado?
Resposta: Sim.

### 166. O curso pode ser buscado?
Resposta: Sim.

### 167. O curso pode ser listado com paginação?
Resposta: Sim.

### 168. O sistema conta alunos por curso?
Resposta: Sim.

### 169. O aluno precisa estar logado para se matricular?
Resposta: Sim.

### 170. O sistema diferencia curso ativo e inativo?
Resposta: Sim.

### 171. O admin pode mudar esse status?
Resposta: Sim.

---

## 14. Perguntas sobre módulos

### 172. O que é um módulo?
Resposta: Um agrupador de conteúdo dentro de um curso.

### 173. Um curso precisa de módulo?
Resposta: Sim, mesmo em curso simples o sistema garante um módulo padrão.

### 174. O que é módulo padrão?
Resposta: É um módulo criado ou garantido automaticamente para cursos simples.

### 175. Por que isso é importante?
Resposta: Porque evita aulas soltas e mantém consistência estrutural.

### 176. O professor pode criar vários módulos?
Resposta: Sim, em cursos multi-módulo.

### 177. O professor pode mover módulos?
Resposta: Sim.

### 178. O sistema sincroniza curso legado com módulo?
Resposta: Sim.

### 179. O módulo tem ordem?
Resposta: Sim.

### 180. O módulo pode ser editado?
Resposta: Sim.

---

## 15. Perguntas sobre aulas

### 181. O que é uma aula?
Resposta: É a unidade concreta de conteúdo que o aluno consome.

### 182. A aula pertence a quê?
Resposta: A um curso e a um módulo.

### 183. Que tipos de aula existem?
Resposta: Vídeo, PDF, texto e arquivo.

### 184. A aula pode ter resumo?
Resposta: Sim.

### 185. A aula pode ter transcrição?
Resposta: Sim.

### 186. A aula pode ter áudio?
Resposta: Sim.

### 187. Quem pode criar aula?
Resposta: O professor dono do curso.

### 188. Quem pode editar aula?
Resposta: O professor dono do curso.

### 189. Quem pode apagar aula?
Resposta: O professor dono do curso.

### 190. O aluno pode marcar aula como concluída?
Resposta: Sim.

### 191. A marcação é gravada no banco?
Resposta: Sim, em `lesson_progress`.

### 192. O aluno pode desmarcar aula?
Resposta: Sim.

### 193. O sistema recalcula o progresso após marcar aula?
Resposta: Sim.

### 194. Se houver quiz obrigatório, a aula pode ser concluída sem aprovação?
Resposta: Não.

### 195. O sistema reordena aulas?
Resposta: Sim.

### 196. Onde a lógica da aula está?
Resposta: Principalmente em `LessonController` e `Lesson` model.

---

## 16. Perguntas sobre quizzes

### 197. O que é um quiz?
Resposta: É uma avaliação do sistema.

### 198. Que tipos de quiz existem?
Resposta: De aula, de módulo e final.

### 199. Onde a lógica principal está?
Resposta: Em `QuizController.php` e `Quiz.php`.

### 200. Quem pode criar quiz?
Resposta: O professor dono do curso.

### 201. O quiz pode estar ligado a uma aula?
Resposta: Sim.

### 202. O quiz pode estar ligado a um módulo?
Resposta: Sim.

### 203. O quiz pode estar ligado ao curso inteiro?
Resposta: Sim, no caso de quiz final.

### 204. O quiz possui tentativas máximas?
Resposta: Sim.

### 205. O quiz possui nota mínima?
Resposta: Sim.

### 206. O quiz possui pontuação total?
Resposta: Sim.

### 207. No projeto atual, a soma das perguntas precisa fechar quanto?
Resposta: 20 valores.

### 208. O quiz pode ter tempo limite?
Resposta: Sim.

### 209. O quiz pode embaralhar perguntas?
Resposta: Sim.

### 210. O quiz pode embaralhar respostas?
Resposta: Sim.

### 211. O quiz pode mostrar respostas ao aluno?
Resposta: Sim, conforme configuração.

### 212. O quiz pode mostrar nota ao aluno?
Resposta: Sim, conforme configuração.

### 213. Como as perguntas são guardadas?
Resposta: Na tabela `questions`.

### 214. Como as respostas do aluno são guardadas?
Resposta: Em `quiz_attempt_answers`.

### 215. O sistema guarda histórico de tentativas?
Resposta: Sim.

### 216. O sistema calcula melhor resultado?
Resposta: Sim.

### 217. O sistema impede duplicar quiz final?
Resposta: Sim.

### 218. O sistema impede duplicar quiz de módulo?
Resposta: Sim.

### 219. O sistema impede duplicar quiz principal de aula?
Resposta: Sim.

### 220. O quiz influencia a progressão?
Resposta: Sim.

### 221. O quiz influencia certificação?
Resposta: Sim.

### 222. O sistema também mantém compatibilidade com resultados legados?
Resposta: Sim, através de `quiz_results` além das tentativas detalhadas.

---

## 17. Perguntas sobre progresso

### 223. O que é progresso no sistema?
Resposta: É a medida do avanço do aluno no curso.

### 224. Onde o progresso é guardado?
Resposta: Detalhadamente em `lesson_progress` e agregado em `enrollments.progress`.

### 225. O progresso é baseado só em assistir aulas?
Resposta: Não. Ele também considera aprovação em quizzes obrigatórios quando aplicável.

### 226. O progresso é recalculado automaticamente?
Resposta: Sim.

### 227. O dashboard do aluno mostra progresso real?
Resposta: Sim.

### 228. O curso pode chegar a 100%?
Resposta: Sim, quando os critérios são cumpridos.

### 229. O progresso pode diminuir?
Resposta: Sim, se o aluno desmarcar conclusão ou perder elegibilidade em algum contexto.

### 230. O progresso é importante para quê?
Resposta: Para acompanhamento pedagógico e para a lógica de conclusão/certificação.

---

## 18. Perguntas sobre certificados

### 231. O sistema emite certificado?
Resposta: Sim.

### 232. O certificado é automático?
Resposta: Ele pode ser emitido automaticamente quando os critérios forem cumpridos.

### 233. O sistema emite certificado de módulo?
Resposta: Sim.

### 234. O sistema emite certificado de curso?
Resposta: Sim.

### 235. Quem decide a elegibilidade?
Resposta: O model `Certificate`, com base no estado real do aluno.

### 236. O que é elegibilidade?
Resposta: É a condição de o aluno cumprir os requisitos para receber o certificado.

### 237. Que requisitos costumam ser usados?
Resposta: Conclusão dos módulos e aprovação nas avaliações necessárias, incluindo quiz final quando aplicável.

### 238. O certificado tem código único?
Resposta: Sim.

### 239. O certificado pode ser validado publicamente?
Resposta: Sim.

### 240. O certificado pode ser baixado em PDF?
Resposta: Sim.

### 241. Como o PDF é gerado?
Resposta: Através de HTML renderizado e convertido com Puppeteer.

### 242. O PDF usa um link seguro?
Resposta: Sim.

### 243. O certificado pode ser revogado?
Resposta: Sim, se o estado do aluno deixar de atender a elegibilidade.

### 244. O sistema guarda nota no certificado?
Resposta: Sim.

### 245. O certificado guarda curso e aluno?
Resposta: Sim.

### 246. O certificado guarda professor?
Resposta: Sim, ao hidratar os dados.

---

## 19. Perguntas sobre dashboards

### 247. Que dashboards existem?
Resposta: De aluno, professor e administrador.

### 248. O que o aluno vê?
Resposta: Cursos, progresso e percurso pessoal.

### 249. O que o professor vê?
Resposta: Métricas dos cursos, alunos, quizzes, médias e indicadores pedagógicos.

### 250. O que o administrador vê?
Resposta: Estatísticas globais e supervisão do sistema.

### 251. O professor vê perguntas críticas?
Resposta: Sim, o sistema calcula isso em algumas métricas de avaliação.

### 252. O professor vê taxa de aprovação?
Resposta: Sim.

### 253. O admin vê total de usuários?
Resposta: Sim.

### 254. O admin vê total de cursos?
Resposta: Sim.

### 255. O admin vê total de quizzes?
Resposta: Sim.

---

## 20. Perguntas sobre backup, exportação e importação

### 256. O sistema exporta dados?
Resposta: Sim.

### 257. O sistema importa dados?
Resposta: Sim.

### 258. O sistema gera backup em ZIP?
Resposta: Sim.

### 259. O sistema valida backup antes de restaurar?
Resposta: Sim.

### 260. O sistema usa manifest?
Resposta: Sim.

### 261. O sistema usa checksums?
Resposta: Sim.

### 262. Para que servem os checksums?
Resposta: Para verificar integridade.

### 263. O sistema registra logs de backup?
Resposta: Sim.

### 264. O sistema possui preferências de backup?
Resposta: Sim.

### 265. O sistema possui auto backup?
Resposta: Sim, existe suporte para isso.

### 266. O restore é seguro?
Resposta: Ele foi pensado para ser seguro, com validações estruturais, checksums e verificações contra caminhos inseguros.

### 267. O sistema suporta restauração parcial?
Resposta: Sim, dependendo do escopo.

### 268. O sistema suporta dados legados?
Resposta: Sim.

### 269. O backup é só do banco?
Resposta: Não. Também pode incluir arquivos associados e estrutura organizada.

### 270. O backup pode ser de aluno ou professor?
Resposta: Sim.

---

## 21. Perguntas sobre IA

### 271. O projeto usa IA?
Resposta: Sim.

### 272. Onde a IA aparece?
Resposta: No tutor de IA por aula e no conteúdo inteligente associado às aulas.

### 273. O que faz o tutor de IA?
Resposta: Responde perguntas do aluno com base no contexto da aula.

### 274. O que faz o serviço de conteúdo inteligente?
Resposta: Gera ou recupera material de apoio derivado da aula.

### 275. O que faz o serviço de transcrição?
Resposta: Obtém transcrição do conteúdo em vídeo.

### 276. O sistema registra perguntas feitas à IA?
Resposta: Sim.

### 277. O sistema possui cache para IA?
Resposta: Sim.

### 278. O sistema evita perguntas duplicadas em sequência?
Resposta: Sim.

### 279. O sistema valida se o aluno tem acesso à aula antes de usar IA?
Resposta: Sim.

### 280. Isso é um diferencial?
Resposta: Sim.

---

## 22. Perguntas sobre arquivos do projeto

### 281. Para que serve `config/env.php`?
Resposta: Para carregar variáveis de ambiente.

### 282. Para que serve `config/database.php`?
Resposta: Para criar a conexão PDO.

### 283. Para que serve `config/app.php`?
Resposta: Para definir constantes de ambiente e diretórios.

### 284. Para que serve `config/helpers.php`?
Resposta: Para funções utilitárias gerais.

### 285. Para que serve `config/autoload.php`?
Resposta: Para autoload do projeto.

### 286. Para que serve `public/index.php`?
Resposta: Para ser o front controller principal.

### 287. Para que serve `public/router.php`?
Resposta: Para apoiar roteamento amigável.

### 288. Para que serve `app/views/layout.php`?
Resposta: Para ser o layout base de todas as páginas.

### 289. Para que serve `AuthController.php`?
Resposta: Para cadastro, login, reset e segurança de conta.

### 290. Para que serve `CourseController.php`?
Resposta: Para gerir os fluxos dos cursos.

### 291. Para que serve `LessonController.php`?
Resposta: Para gerir aulas e progresso por aula.

### 292. Para que serve `QuizController.php`?
Resposta: Para gerir criação, consulta e correção de quizzes.

### 293. Para que serve `DashboardController.php`?
Resposta: Para montar os dashboards dos perfis.

### 294. Para que serve `AdminController.php`?
Resposta: Para operações administrativas.

### 295. Para que serve `ModuleController.php`?
Resposta: Para criar, editar e reordenar módulos.

### 296. Para que serve `CertificateController.php`?
Resposta: Para emissão, visualização, validação pública e PDF de certificados.

### 297. Para que serve `ExportController.php`?
Resposta: Para exportar backups e pacotes de dados.

### 298. Para que serve `ImportController.php`?
Resposta: Para validar e restaurar backups.

### 299. Para que serve `User.php`?
Resposta: Para operações de dados ligadas aos usuários.

### 300. Para que serve `Course.php`?
Resposta: Para operações de persistência dos cursos.

### 301. Para que serve `Module.php`?
Resposta: Para persistência e sincronização da estrutura modular.

### 302. Para que serve `Lesson.php`?
Resposta: Para persistência das aulas.

### 303. Para que serve `Enrollment.php`?
Resposta: Para matrículas e progresso agregado.

### 304. Para que serve `Quiz.php`?
Resposta: Para persistência e regras de avaliação.

### 305. Para que serve `Certificate.php`?
Resposta: Para regras e persistência dos certificados.

### 306. Para que serve `BackupLogService.php`?
Resposta: Para logs e preferências de backup.

### 307. Para que serve `LessonAiTutorService.php`?
Resposta: Para o assistente de IA da aula.

### 308. Para que serve `LessonContentService.php`?
Resposta: Para conteúdo inteligente da aula.

### 309. Para que serve `LessonTranscriptService.php`?
Resposta: Para transcrição.

### 310. Para que serve `StorageService.php`?
Resposta: Para abstrair armazenamento de arquivos.

---

## 23. Perguntas sobre qualidade, manutenção e evolução

### 311. O sistema está organizado?
Resposta: Sim, de forma modular por pastas e responsabilidades.

### 312. O sistema é fácil de manter?
Resposta: Relativamente sim, embora alguns pontos mereçam refatoração futura.

### 313. Qual parte seria a primeira a refatorar?
Resposta: `public/index.php`.

### 314. O código está pronto para crescer?
Resposta: Sim, com melhorias graduais.

### 315. O sistema demonstra evolução incremental?
Resposta: Sim.

### 316. Isso é ruim?
Resposta: Não. É normal em software real.

### 317. O projeto tem documentação?
Resposta: Sim, há vários arquivos de apoio e documentação criados para entendimento e defesa.

### 318. O projeto tem checklist de qualidade?
Resposta: Sim.

### 319. O projeto tem scripts auxiliares?
Resposta: Sim.

### 320. O projeto tem logs?
Resposta: Sim.

### 321. O sistema tem testes automatizados fortes?
Resposta: Ainda não no nível ideal.

### 322. Isso enfraquece o projeto?
Resposta: Não invalida o projeto, mas é um ponto legítimo de evolução.

### 323. O sistema tem pontos legados?
Resposta: Sim.

### 324. O sistema demonstra maturidade de regras de negócio?
Resposta: Sim.

### 325. O projeto já é suficientemente complexo para defesa?
Resposta: Sim, claramente.

---

## 24. Perguntas sobre limitações

### 326. Qual é a maior limitação técnica do projeto?
Resposta: A centralização excessiva de fluxo em `public/index.php`.

### 327. Qual limitação de engenharia existe?
Resposta: A necessidade de ampliar testes automatizados e refinar separação de responsabilidades em alguns pontos.

### 328. Qual limitação de produção existe?
Resposta: Ainda seriam recomendáveis melhorias adicionais de deploy, observabilidade e hardening.

### 329. O projeto depende de ambiente local?
Resposta: Foi muito pensado para ambiente local/XAMPP, mas pode ser adaptado.

### 330. O sistema poderia ser mais desacoplado?
Resposta: Sim.

### 331. O sistema poderia usar framework?
Resposta: Sim, em uma evolução futura.

### 332. O sistema poderia melhorar a governança de migrações?
Resposta: Sim.

### 333. O sistema poderia expandir analytics?
Resposta: Sim.

### 334. O sistema poderia reforçar segurança?
Resposta: Sim, como qualquer software em evolução.

### 335. Reconhecer limitações é ruim na defesa?
Resposta: Não. Mostra maturidade.

---

## 25. Perguntas sobre melhorias futuras

### 336. O que implementarias primeiro numa próxima fase?
Resposta: Mais testes automatizados, refatoração do roteamento central e melhorias de observabilidade.

### 337. Poderia integrar pagamentos?
Resposta: Sim.

### 338. Poderia ter API REST?
Resposta: Sim.

### 339. Poderia ter app mobile?
Resposta: Sim.

### 340. Poderia ter notificações em tempo real?
Resposta: Sim.

### 341. Poderia ter chat mais avançado?
Resposta: Sim.

### 342. Poderia ter analytics mais ricos para professores?
Resposta: Sim.

### 343. Poderia ter autenticação multifator?
Resposta: Sim.

### 344. Poderia ter certificados com assinatura digital?
Resposta: Sim.

### 345. Poderia ter versionamento de conteúdo?
Resposta: Sim.

---

## 26. Perguntas difíceis e críticas

### 346. Por que teu projeto não é só uma cópia de plataformas já existentes?
Resposta: Porque ele foi construído com foco em demonstrar entendimento técnico, integração real dos módulos e decisões próprias de arquitetura e regras de negócio.

### 347. O que faz teu projeto ser realmente teu?
Resposta: A implementação concreta, a modelagem, as regras de progressão, avaliação, certificação, backup e a evolução arquitetural feita no próprio código.

### 348. Se eu disser que o `index.php` está grande demais, o que respondes?
Resposta: Concordo. É uma limitação reconhecida e seria um dos primeiros pontos de refatoração numa próxima etapa.

### 349. Se eu disser que faltam testes, o que respondes?
Resposta: É uma crítica válida. O projeto já tem validação funcional e scripts de apoio, mas ampliar testes automatizados é uma evolução importante.

### 350. Se eu disser que o sistema ainda não está ideal para produção?
Resposta: Concordo em parte. Ele está funcional e estruturado, mas uma versão de produção plena exigiria reforços adicionais.

### 351. Se eu perguntar o maior mérito técnico do projeto?
Resposta: A integração entre múltiplos módulos com regras reais de negócio, especialmente quizzes, progresso, certificados e backup.

### 352. Se eu perguntar o maior desafio técnico?
Resposta: Manter coerência entre estrutura de curso, avaliações, progresso e elegibilidade de certificado.

### 353. Se eu perguntar o que aprendeste com este projeto?
Resposta: Aprendi a integrar arquitetura web, banco de dados, segurança, regras de negócio e evolução incremental num sistema funcional.

### 354. Se eu disser que isso parece um sistema em evolução e não final?
Resposta: Sim, e isso é realista. O projeto demonstra um software funcional e evolutivo, o que é comum em sistemas reais.

### 355. Se eu perguntar por que a banca deve valorizar este projeto?
Resposta: Porque ele demonstra domínio de arquitetura, persistência, segurança, organização de conteúdo, avaliação, progressão, certificação e portabilidade de dados num único produto.

---

## 27. Perguntas comparativas e conceituais

### 356. Qual a diferença entre autenticação e autorização?
Resposta: Autenticação identifica o usuário; autorização define o que ele pode fazer.

### 357. Qual a diferença entre curso e módulo?
Resposta: Curso é a entidade principal; módulo é uma subdivisão interna do curso.

### 358. Qual a diferença entre módulo e aula?
Resposta: Módulo agrupa; aula entrega o conteúdo concreto.

### 359. Qual a diferença entre quiz de aula e quiz final?
Resposta: O quiz de aula avalia conteúdo pontual; o quiz final avalia o encerramento do curso.

### 360. Qual a diferença entre progresso e nota?
Resposta: Progresso mede avanço; nota mede desempenho.

### 361. Qual a diferença entre exportação e restauração?
Resposta: Exportação gera o pacote de dados; restauração lê e reintroduz os dados no sistema.

### 362. Qual a diferença entre backup e simples download de dados?
Resposta: Backup é estruturado, verificável e pensado para reimportação.

### 363. Qual a diferença entre certificado de módulo e de curso?
Resposta: Um comprova a conclusão de uma parte do curso, o outro comprova a conclusão global.

---

## 28. Perguntas de defesa oral rápidas

### 364. Resume o projeto em uma frase.
Resposta: É uma plataforma web de ensino online com autenticação, cursos, avaliações, progresso e certificados verificáveis.

### 365. Qual o principal arquivo do sistema?
Resposta: `public/index.php`.

### 366. Qual o principal padrão arquitetural?
Resposta: MVC adaptado.

### 367. Qual a principal linguagem de backend?
Resposta: PHP.

### 368. Qual o banco usado?
Resposta: MySQL.

### 369. Quantos perfis existem?
Resposta: Três: aluno, professor e admin.

### 370. O certificado é real ou só visual?
Resposta: É baseado em elegibilidade real e pode ser validado publicamente.

### 371. O sistema tem segurança básica?
Resposta: Sim, com prepared statements, hash de senha, sessão, CSRF e validação de permissão.

### 372. O sistema tem diferencial?
Resposta: Sim, sobretudo em certificação, backup e IA por aula.

### 373. O sistema pode crescer?
Resposta: Sim.

---

## 29. Perguntas para ensaio agressivo de banca

### 374. Onde exatamente está a regra que impede aluno de criar curso?
Resposta: No fluxo de controller, especialmente na validação do papel do usuário em `CourseController`.

### 375. Onde exatamente está a regra de login?
Resposta: Em `AuthController::login()`.

### 376. Onde exatamente está a regra de conclusão com quiz obrigatório?
Resposta: Em `LessonController::marcarConcluida()`, usando consulta ao status do quiz obrigatório.

### 377. Onde exatamente está a regra de elegibilidade do certificado?
Resposta: Em `Certificate::buildEligibilitySnapshot()` e `Certificate::validateEligibility()`.

### 378. Onde exatamente está a lógica de criação do quiz?
Resposta: Em `QuizController::criar()` e `Quiz::criar()`.

### 379. Onde exatamente a base de dados é conectada?
Resposta: Em `config/database.php`.

### 380. Onde exatamente o token CSRF é validado?
Resposta: No processamento das ações em `public/index.php`.

### 381. Onde exatamente o PDF é preparado?
Resposta: Em `CertificateController` e no script `scripts/generate-certificate-pdf.js`.

### 382. Onde exatamente o backup é montado?
Resposta: Em `ExportController`.

### 383. Onde exatamente o restore é validado?
Resposta: Em `ImportController`.

---

## 30. Fecho pronto para responder à banca

Se te perguntarem no final “por que este projeto merece ser aprovado?”, podes responder:

> Porque ele demonstra não apenas interface, mas um sistema completo com arquitetura definida, autenticação, autorização, modelagem de dados, regras reais de negócio, avaliação, progresso, certificados e portabilidade de dados. Além disso, o projeto mostra capacidade de evolução, consciência das limitações e domínio técnico sobre o que foi implementado.

