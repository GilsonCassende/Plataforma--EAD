# Guia de Defesa Oral e Banco Amplo de Perguntas e Respostas

## 1. Objetivo deste arquivo

Este documento foi criado para te ajudar a defender a Plataforma EAD com segurança, clareza e domínio técnico.

Ele foi organizado para servir como:

- roteiro de fala;
- material de revisão rápida;
- banco amplo de perguntas e respostas;
- apoio para responder banca técnica, funcional e de gestão.

Observação honesta:

Não existe como prever literalmente todas as perguntas possíveis do mundo, mas este arquivo cobre de forma muito abrangente os tipos de perguntas que normalmente aparecem numa defesa deste projeto.

---

## 2. Resposta curta para apresentar o projeto

> A Plataforma EAD é um sistema web de ensino a distância desenvolvido em PHP, MySQL, HTML, CSS e JavaScript, com arquitetura MVC adaptada. O sistema permite autenticação de usuários, controle por perfis, criação e gestão de cursos, módulos, aulas, quizzes, acompanhamento de progresso, emissão e validação pública de certificados, além de exportação, importação e backup de dados.

---

## 3. Resposta média para apresentar o projeto

> O projeto consiste numa plataforma EAD completa, pensada para digitalizar o ciclo principal do ensino online. O aluno pode registrar-se, entrar na plataforma, matricular-se em cursos, assistir aulas, realizar quizzes e obter certificados quando cumprir os critérios de conclusão. O professor pode criar cursos, organizar módulos, cadastrar aulas, montar avaliações e acompanhar o desempenho dos alunos. O administrador acompanha a operação global do sistema. Do ponto de vista técnico, o projeto usa PHP com PDO, MySQL, views em PHP, JavaScript para interações no frontend e Node.js em recursos específicos, como a geração de certificados em PDF.

---

## 4. Roteiro de defesa oral

## 4.1 Abertura

Podes começar assim:

> Boa tarde. O projeto que apresento é a Plataforma EAD, desenvolvida para gerir o processo de ensino online de forma centralizada. O sistema cobre autenticação, cursos, módulos, aulas, quizzes, progresso, certificados e funcionalidades de portabilidade de dados, como backup e restauração. A aplicação foi implementada em PHP e MySQL, com organização em controllers, models, views e serviços auxiliares.

## 4.2 Problema que o sistema resolve

> O sistema resolve a fragmentação comum do ensino online. Em vez de usar várias ferramentas separadas para cadastro, publicação de conteúdo, avaliação e certificação, a plataforma reúne tudo num único ambiente. Isso melhora organização, rastreabilidade, controlo de acesso e acompanhamento pedagógico.

## 4.3 Objetivo principal

> O objetivo principal foi construir uma plataforma funcional de EAD que não fosse apenas visual, mas que tivesse regras de negócio reais, como autenticação, progressão do aluno, avaliação, elegibilidade para certificado e gestão segura dos dados.

## 4.4 Público-alvo

> O público-alvo inclui alunos, professores, centros de formação, instituições de ensino e instrutores independentes que precisem de um sistema para publicar cursos e acompanhar aprendizagem.

## 4.5 Stack tecnológica

> No backend foi utilizado PHP com PDO e MySQL. No frontend foram usados HTML, CSS e JavaScript puro. O sistema roda bem em ambiente Apache/XAMPP. Em recursos complementares, como a geração de PDF do certificado e a transcrição de conteúdo, foram utilizados scripts Node.js.

## 4.6 Arquitetura

> O projeto segue uma arquitetura MVC adaptada. Os controllers fazem a orquestração dos fluxos, os models concentram persistência e parte das regras de negócio, as views exibem as páginas e os services lidam com funções especializadas, como IA, storage, transcrição e logs de backup. O arquivo `public/index.php` funciona como front controller central da aplicação.

## 4.7 Demonstração sugerida

Ordem recomendada:

1. mostrar a home;
2. mostrar login e papéis;
3. mostrar catálogo de cursos;
4. entrar no detalhe de um curso;
5. mostrar área do professor;
6. mostrar criação de curso, módulo, aula e quiz;
7. mostrar área do aluno;
8. mostrar progresso;
9. mostrar quiz e resultado;
10. mostrar certificado;
11. mostrar validação pública do certificado;
12. citar backup/exportação/importação.

## 4.8 Encerramento

> Em resumo, a Plataforma EAD foi construída para resolver uma necessidade real de ensino online, combinando organização de conteúdo, avaliação, segurança, progresso, certificação e portabilidade de dados. O projeto também foi estruturado para ser evolutivo, permitindo melhorias futuras sem perder a base arquitetural já construída.

---

## 5. Perguntas e respostas por tema

## 5.1 Perguntas gerais sobre o projeto

### 1. Qual é o nome do projeto?
Resposta: O nome do projeto é Plataforma EAD.

### 2. O que significa EAD?
Resposta: EAD significa Educação a Distância.

### 3. O que a plataforma faz?
Resposta: Ela permite gerir o processo de ensino online, incluindo usuários, cursos, módulos, aulas, quizzes, progresso, certificados e backups.

### 4. Qual é o principal objetivo do projeto?
Resposta: Criar um ambiente centralizado de ensino online com regras reais de autenticação, progressão, avaliação e certificação.

### 5. Que problema o projeto resolve?
Resposta: Resolve a dispersão de ferramentas e processos no ensino online, reunindo gestão acadêmica, conteúdo, avaliação e certificação num único sistema.

### 6. Quem pode usar a plataforma?
Resposta: Alunos, professores, administradores, centros de formação e instituições de ensino.

### 7. O sistema é apenas acadêmico ou pode ser usado na prática?
Resposta: Ele foi desenvolvido em contexto acadêmico, mas possui estrutura suficiente para evoluir para uso prático real com melhorias de produção.

### 8. O projeto é um MVP ou uma plataforma já avançada?
Resposta: Ele começou com uma base de MVP, mas cresceu e hoje já possui módulos avançados como certificados públicos, quizzes estruturados, backup, restore e apoio por IA.

### 9. Quais são os módulos mais fortes do projeto?
Resposta: Autenticação, gestão de cursos, aulas, quizzes, progresso, certificados e backup/restauração.

### 10. O projeto tem diferenciais?
Resposta: Sim. Entre eles estão certificação com validação pública, elegibilidade real, backup estruturado, importação/restauração e tutor de IA por aula.

## 5.2 Perguntas sobre motivação e contexto

### 11. Por que escolheste fazer uma plataforma EAD?
Resposta: Porque educação online é um problema real e muito relevante. A plataforma permite aplicar vários conceitos importantes de engenharia de software num domínio prático e rico.

### 12. Por que esse problema é importante?
Resposta: Porque o ensino online exige organização, controlo, acessibilidade, escalabilidade e confiança, especialmente em avaliação e certificação.

### 13. Qual foi a motivação principal?
Resposta: Construir um sistema completo, útil e tecnicamente desafiador, que fosse além de um CRUD simples.

### 14. O sistema foi pensado para qual realidade?
Resposta: Foi pensado para contextos de formação profissional, ensino técnico, instrutores independentes e ambientes educacionais que precisem de digitalização.

### 15. O projeto atende um problema local ou global?
Resposta: Atende ambos. O problema de gestão de ensino online existe em qualquer contexto educacional.

## 5.3 Perguntas sobre requisitos e funcionalidades

### 16. Quais são as principais funcionalidades?
Resposta: Cadastro, login, cursos, módulos, aulas, quizzes, matrículas, progresso, dashboards, certificados, validação pública, exportação, importação e backup.

### 17. O aluno pode se matricular sozinho?
Resposta: Sim. O aluno autenticado pode matricular-se em cursos.

### 18. O professor pode criar seus próprios cursos?
Resposta: Sim. O professor pode criar, editar e gerir os próprios cursos.

### 19. O administrador também pode gerir cursos?
Resposta: Sim. O administrador pode supervisionar, listar e alterar status dos cursos.

### 20. O sistema permite organização por módulos?
Resposta: Sim. O curso pode ser de módulo único ou de múltiplos módulos.

### 21. O sistema permite diferentes tipos de aula?
Resposta: Sim. As aulas podem ser de vídeo, PDF, texto ou arquivo.

### 22. O sistema tem avaliações?
Resposta: Sim. Possui quizzes de aula, de módulo e quiz final do curso.

### 23. O sistema gera certificado?
Resposta: Sim. Gera certificados quando o aluno cumpre os critérios de elegibilidade.

### 24. O certificado é só visual ou pode ser validado?
Resposta: Pode ser validado publicamente por código único.

### 25. O sistema registra progresso?
Resposta: Sim. Registra o progresso por aula e por curso.

### 26. O aluno pode ver o próprio histórico?
Resposta: Sim. O aluno pode acompanhar cursos, progresso, quizzes e certificados.

### 27. O professor consegue ver desempenho dos alunos?
Resposta: Sim. O dashboard do professor mostra métricas dos cursos e desempenho agregado.

### 28. O sistema tem painel administrativo?
Resposta: Sim. Existe dashboard e áreas administrativas para cursos, quizzes e usuários.

### 29. O sistema possui importação e exportação?
Resposta: Sim. Há exportação e restauração estruturadas de dados.

### 30. O sistema possui backup automático?
Resposta: Sim, há estrutura para preferências de backup e execução automática.

## 5.4 Perguntas sobre arquitetura

### 31. Qual padrão arquitetural foi usado?
Resposta: MVC adaptado, com controllers, models, views e services auxiliares.

### 32. O que significa MVC?
Resposta: Model-View-Controller. É uma forma de separar persistência, interface e controle do fluxo.

### 33. Onde fica a entrada principal do sistema?
Resposta: Em `public/index.php`.

### 34. O `public/index.php` faz o quê?
Resposta: Inicializa sessão, carrega configurações, interpreta rota, processa ações `POST`, chama controllers e renderiza views.

### 35. Onde ficam os controllers?
Resposta: Em `app/controllers/`.

### 36. Onde ficam os models?
Resposta: Em `app/models/`.

### 37. Onde ficam as views?
Resposta: Em `app/views/`.

### 38. Onde ficam os serviços especializados?
Resposta: Em `app/services/`.

### 39. Onde ficam configurações e bootstrap?
Resposta: Em `config/`.

### 40. Onde ficam scripts de manutenção?
Resposta: Em `scripts/`.

### 41. Por que usar MVC nesse projeto?
Resposta: Porque melhora separação de responsabilidades, manutenção, leitura do sistema e evolução futura.

### 42. O projeto usa framework?
Resposta: Não no núcleo web. A base foi construída de forma própria em PHP, usando bibliotecas auxiliares específicas apenas onde necessário.

### 43. Isso é vantagem ou desvantagem?
Resposta: É vantagem no contexto acadêmico porque mostra domínio dos fundamentos. Em produção, um framework poderia facilitar padronização e testes mais amplos.

### 44. Como as páginas são escolhidas?
Resposta: Principalmente por `$_GET['page']` e também por suporte a algumas URLs amigáveis.

### 45. O sistema também responde AJAX?
Resposta: Sim. Ele detecta requisições AJAX e devolve JSON quando apropriado.

## 5.5 Perguntas sobre backend

### 46. Qual linguagem foi usada no backend?
Resposta: PHP.

### 47. Por que escolheste PHP?
Resposta: Porque é uma linguagem muito usada no desenvolvimento web, acessível, madura, adequada ao contexto acadêmico e suficiente para este tipo de sistema.

### 48. Como o sistema acessa a base de dados?
Resposta: Através de PDO.

### 49. Por que usar PDO?
Resposta: Porque oferece prepared statements, portabilidade e uma forma mais segura e organizada de trabalhar com banco de dados.

### 50. O sistema usa orientação a objetos?
Resposta: Sim. Os controllers, models e services são organizados em classes.

### 51. Como o autoload funciona?
Resposta: O projeto usa `config/autoload.php`, que carrega o autoload do Composer quando existe e também registra um autoloader simples para controllers, models e services.

### 52. O projeto lê variáveis de ambiente?
Resposta: Sim. O arquivo `config/env.php` carrega o `.env` e disponibiliza os valores para o sistema.

### 53. Como a ligação com o banco é criada?
Resposta: Em `config/database.php`, que monta a conexão PDO com base nas variáveis de ambiente.

### 54. O backend está separado do frontend?
Resposta: Parcialmente. Existe separação lógica entre backend e apresentação, mas as views ainda são renderizadas no próprio PHP.

### 55. O sistema é monolítico?
Resposta: Sim. É um monólito web modularizado.

## 5.6 Perguntas sobre frontend

### 56. Quais tecnologias foram usadas no frontend?
Resposta: HTML5, CSS3 e JavaScript puro.

### 57. Por que não usar framework frontend?
Resposta: Porque o objetivo era dominar a base web clássica, reduzir complexidade inicial e manter a solução mais direta para o escopo do projeto.

### 58. O sistema é responsivo?
Resposta: Sim. Existem estilos globais e responsivos em `public/css/`.

### 59. Onde ficam os scripts JavaScript?
Resposta: Em `public/js/` e `public/js/pages/`.

### 60. O que faz o `public/js/main.js`?
Resposta: Inicializa comportamentos gerais como navegação, toasts, formulários, confirmação, progresso, quizzes e exportações.

### 61. O que faz o `public/js/ui.js`?
Resposta: Trata interações gerais de interface, como mostrar senha, animações, barras de progresso, tooltips e comportamentos visuais.

### 62. O frontend consome JSON?
Resposta: Sim, em várias ações AJAX.

### 63. A interface foi feita para quais perfis?
Resposta: Para visitante, aluno, professor e administrador.

### 64. Há páginas específicas por contexto?
Resposta: Sim. Há páginas específicas para dashboards, cursos, quizzes, gestão do professor, perfil, certificado e administração.

## 5.7 Perguntas sobre banco de dados

### 65. Qual banco foi usado?
Resposta: MySQL.

### 66. Por que MySQL?
Resposta: Porque é maduro, amplamente usado, bem integrado com PHP e adequado ao tipo de dados do sistema.

### 67. Quais são as tabelas principais?
Resposta: `users`, `courses`, `lessons`, `enrollments`, `quizzes`, `questions`, `quiz_attempts`, `quiz_attempt_answers`, `lesson_progress`, `certificates` e outras auxiliares.

### 68. Onde está o schema principal?
Resposta: Em `migrations/schema.sql`.

### 69. O banco tem relacionamentos?
Resposta: Sim. Há chaves estrangeiras entre usuários, cursos, aulas, quizzes, matrículas, progresso e certificados.

### 70. Há restrições de integridade?
Resposta: Sim. Existem `foreign keys`, índices, unicidade e regras que evitam duplicidade em cenários importantes como matrícula.

### 71. O projeto usa migrações?
Resposta: Sim. Há arquivos em `migrations/` e também alguns ajustes evolutivos por `ensureSchema()` em classes.

### 72. O que significa `ensureSchema()`?
Resposta: É uma estratégia em que o próprio código verifica se certas colunas ou índices existem e, se necessário, os cria.

### 73. Isso é bom?
Resposta: É útil para evolução incremental e compatibilidade, embora numa arquitetura mais madura todas as mudanças pudessem estar centralizadas apenas em migrações formais.

### 74. O banco armazena senha em texto puro?
Resposta: Não. Armazena o hash da senha.

### 75. O banco guarda tentativas de quiz?
Resposta: Sim. Guarda tentativas e respostas detalhadas.

## 5.8 Perguntas sobre autenticação e autorização

### 76. Como o login funciona?
Resposta: O sistema recebe email e senha, procura o usuário, valida com `password_verify`, controla falhas, regenera sessão e guarda o usuário em `$_SESSION`.

### 77. Como a sessão é usada?
Resposta: Depois do login, os dados essenciais do usuário ficam em `$_SESSION['usuario']`.

### 78. O sistema diferencia aluno, professor e admin?
Resposta: Sim. Isso é controlado pelo campo `role`.

### 79. Como o sistema restringe ações?
Resposta: Faz verificações de autenticação e de papel do usuário antes de executar ações sensíveis.

### 80. O usuário pode acessar qualquer rota só mudando a URL?
Resposta: Não deveria. Os controllers validam permissão antes de executar ações protegidas.

### 81. O login tem proteção contra força bruta?
Resposta: Sim. Há contagem de tentativas e bloqueio temporário.

### 82. O sistema tem recuperação de senha?
Resposta: Sim. Usa token temporário de redefinição.

### 83. Os tokens de reset são permanentes?
Resposta: Não. Expiram após um tempo configurado.

### 84. O sistema usa confirmação de email obrigatória?
Resposta: O código atual mostra suporte completo a segurança de conta e reset; o cadastro está configurado para ativação automática, embora a estrutura de verificação exista no projeto.

### 85. O sistema protege formulários contra CSRF?
Resposta: Sim. Ações sensíveis passam por validação de token CSRF.

## 5.9 Perguntas sobre segurança

### 86. Como o sistema evita SQL Injection?
Resposta: Com prepared statements via PDO.

### 87. Como o sistema protege senhas?
Resposta: Com hashing usando as funções nativas de senha do PHP.

### 88. Como o sistema protege ações sensíveis no frontend?
Resposta: Inclui token CSRF e usa `csrfFetch` em requisições AJAX.

### 89. O sistema sanitiza dados?
Resposta: Sim. Usa funções auxiliares de sanitização e escape na exibição.

### 90. Há validação de permissões?
Resposta: Sim. Antes de criar, editar ou apagar recursos sensíveis.

### 91. Há controle de sessão?
Resposta: Sim. Sessões são iniciadas no bootstrap e regeneradas após login.

### 92. O sistema evita exposição excessiva de erro?
Resposta: Em parte sim. Há logs e tratamento de exceções. Em produção ainda seria possível endurecer ainda mais a política de erro.

### 93. O sistema possui logging?
Resposta: Sim. Há logs de aplicação e logs específicos, como os do tutor de IA e dos backups.

### 94. Existe risco por concentrar fluxo em `index.php`?
Resposta: Sim, em termos de responsabilidade excessiva. Funciona, mas pode ser melhor modularizado futuramente.

### 95. O upload de arquivos é validado?
Resposta: Sim. Há validação por extensão, tamanho e verificação de MIME.

## 5.10 Perguntas sobre cursos

### 96. Como um curso é criado?
Resposta: O professor autenticado envia os dados do curso, o controller valida a permissão e o model grava no banco.

### 97. Que dados um curso possui?
Resposta: Título, descrição, professor responsável, categoria, thumbnail, status e estrutura do curso.

### 98. O curso pode estar em diferentes estados?
Resposta: Sim. Por exemplo `ativo`, `inativo` e `rascunho`.

### 99. O que é `course_structure`?
Resposta: É o campo que define se o curso é de módulo único ou multi-módulo.

### 100. O professor pode editar qualquer curso?
Resposta: Não. Apenas os próprios cursos. O admin tem poderes mais amplos.

### 101. O professor pode apagar curso?
Resposta: Sim, se for o dono. O admin também pode.

### 102. O sistema lista cursos com paginação?
Resposta: Sim.

### 103. O sistema permite busca de cursos?
Resposta: Sim. Há busca textual.

### 104. O aluno vê todos os cursos?
Resposta: Ele pode ver o catálogo de cursos disponíveis, respeitando status e visibilidade.

### 105. O sistema conta alunos por curso?
Resposta: Sim.

## 5.11 Perguntas sobre módulos

### 106. O que é um módulo no sistema?
Resposta: É uma unidade de organização do curso que agrupa aulas e eventualmente quizzes de módulo.

### 107. Por que usar módulos?
Resposta: Para estruturar o curso em etapas mais claras e pedagógicas.

### 108. O sistema suporta curso sem múltiplos módulos?
Resposta: Sim. Nesse caso ele opera com módulo padrão.

### 109. O que é módulo padrão?
Resposta: É um módulo automaticamente garantido para cursos simples, evitando que aulas fiquem sem estrutura.

### 110. O professor pode criar muitos módulos?
Resposta: Sim, se o curso estiver configurado como multi-módulo.

### 111. O sistema permite mover módulos?
Resposta: Sim. Há reordenação.

### 112. O módulo tem título e descrição?
Resposta: Sim.

### 113. O sistema migra dados legados para módulos?
Resposta: Sim. O `Module` model possui lógica de sincronização para compatibilidade.

## 5.12 Perguntas sobre aulas

### 114. O que é uma aula no sistema?
Resposta: É a unidade de conteúdo consumida pelo aluno dentro de um curso e módulo.

### 115. Que tipos de aula existem?
Resposta: Vídeo, PDF, texto e arquivo.

### 116. Uma aula pertence a quê?
Resposta: A um curso e, normalmente, a um módulo.

### 117. Como a aula é criada?
Resposta: O professor envia os dados, o controller valida posse do curso e o model salva.

### 118. Uma aula pode ter resumo?
Resposta: Sim.

### 119. Uma aula pode ter áudio?
Resposta: Sim. O sistema possui campos e serviços ligados a mídia de aula.

### 120. Uma aula pode ter transcrição?
Resposta: Sim.

### 121. O aluno pode marcar a aula como concluída?
Resposta: Sim.

### 122. Essa conclusão é automática?
Resposta: Nem sempre. Em aulas com quiz obrigatório, a conclusão depende de aprovação.

### 123. O professor pode editar aula?
Resposta: Sim, se for dono do curso.

### 124. O professor pode apagar aula?
Resposta: Sim, se for dono do curso.

### 125. O sistema reordena aulas?
Resposta: Sim.

## 5.13 Perguntas sobre quizzes

### 126. O que é um quiz no sistema?
Resposta: É uma avaliação associada a uma aula, a um módulo ou ao curso inteiro.

### 127. Quais tipos de quiz existem?
Resposta: Quiz de aula, quiz de módulo e quiz final.

### 128. O professor pode criar vários quizzes finais para o mesmo curso?
Resposta: Não. O controller impede duplicidade desse tipo.

### 129. O professor pode criar vários quizzes de módulo para o mesmo módulo?
Resposta: Não. Também há controle de unicidade.

### 130. O professor pode criar vários quizzes de aula para a mesma aula?
Resposta: Não, para o quiz principal da aula.

### 131. Como as perguntas são guardadas?
Resposta: Em `questions`, ligadas ao quiz.

### 132. O sistema suporta múltipla escolha?
Resposta: Sim.

### 133. O sistema guarda as alternativas como quê?
Resposta: Como JSON.

### 134. O sistema guarda tentativas?
Resposta: Sim. Em `quiz_attempts`.

### 135. O sistema guarda resposta por resposta?
Resposta: Sim. Em `quiz_attempt_answers`.

### 136. Há limite de tentativas?
Resposta: Sim. O professor pode configurar.

### 137. Há nota mínima?
Resposta: Sim.

### 138. Há tempo limite?
Resposta: Sim.

### 139. O sistema embaralha perguntas?
Resposta: Sim, se configurado.

### 140. O sistema embaralha respostas?
Resposta: Sim, se configurado.

### 141. O sistema mostra nota imediatamente?
Resposta: Pode mostrar, dependendo da configuração do quiz.

### 142. O sistema calcula melhor resultado?
Resposta: Sim.

### 143. O quiz influencia o progresso?
Resposta: Sim, especialmente quando é obrigatório ou faz parte da lógica de conclusão.

### 144. O quiz pode impedir conclusão da aula?
Resposta: Sim, no caso de quiz obrigatório da aula.

### 145. O quiz pode influenciar certificado?
Resposta: Sim. A elegibilidade do certificado depende do estado acadêmico do aluno.

## 5.14 Perguntas sobre progresso e conclusão

### 146. Como o progresso do aluno é calculado?
Resposta: O sistema combina aulas concluídas e estado das avaliações obrigatórias.

### 147. O progresso fica onde?
Resposta: Parte detalhada fica em `lesson_progress`, e a visão agregada em `enrollments.progress`.

### 148. O progresso é recalculado depois de quê?
Resposta: Depois de conclusão/desmarcação de aula e depois de eventos relevantes ligados a quizzes.

### 149. O curso pode ser concluído mesmo sem passar no quiz final?
Resposta: Não, quando a regra de elegibilidade exige aprovação nas avaliações finais.

### 150. O sistema recalcula progresso automaticamente?
Resposta: Sim.

### 151. O progresso do dashboard do aluno é só visual?
Resposta: Não. Ele é baseado em dados reais do curso.

## 5.15 Perguntas sobre certificados

### 152. Quando o certificado é emitido?
Resposta: Quando o aluno cumpre os critérios de elegibilidade definidos pelo sistema.

### 153. Que critérios são esses?
Resposta: Conclusão dos módulos relevantes, aprovação em quizzes necessários e, quando aplicável, aprovação do quiz final.

### 154. O sistema emite certificado de curso e de módulo?
Resposta: Sim.

### 155. O certificado tem código único?
Resposta: Sim.

### 156. Esse código serve para quê?
Resposta: Para validação pública do certificado.

### 157. O certificado pode ser consultado sem login?
Resposta: Sim, pela validação pública com código.

### 158. O certificado pode ser exportado para PDF?
Resposta: Sim.

### 159. Como o PDF é gerado?
Resposta: O sistema monta uma página segura e usa Puppeteer para renderizar o PDF.

### 160. O certificado pode ser revogado?
Resposta: Sim. Se o estado de elegibilidade deixar de ser válido, o sistema pode remover certificados daquele contexto.

### 161. Há segurança no link de renderização do PDF?
Resposta: Sim. O controller usa token temporário para controlar a renderização.

### 162. O certificado guarda nota?
Resposta: Sim, há suporte a nota/grade.

### 163. O certificado mostra professor e curso?
Resposta: Sim, a hidratação do certificado busca essas informações.

## 5.16 Perguntas sobre dashboards

### 164. O que o aluno vê no dashboard?
Resposta: Cursos matriculados, progresso, status de aprendizagem e dados do próprio percurso.

### 165. O que o professor vê no dashboard?
Resposta: Cursos criados, número de alunos, total de aulas, quizzes, médias e indicadores pedagógicos.

### 166. O que o admin vê no dashboard?
Resposta: Estatísticas globais, usuários, cursos e visão administrativa do sistema.

### 167. O dashboard do professor tem análise de desempenho?
Resposta: Sim. Há métricas como média, taxa de aprovação e perguntas críticas.

### 168. O dashboard do aluno mostra cursos concluídos?
Resposta: Sim.

### 169. O dashboard do admin serve para quê?
Resposta: Para supervisão global do ecossistema da plataforma.

## 5.17 Perguntas sobre importação, exportação e backup

### 170. O sistema exporta dados?
Resposta: Sim.

### 171. O que pode ser exportado?
Resposta: Dados do aluno, do professor e backups mais amplos do sistema.

### 172. O sistema gera ZIP?
Resposta: Sim.

### 173. O que vai dentro do backup?
Resposta: Manifesto, checksums, documentos JSON estruturados e arquivos associados, como mídia relevante.

### 174. O que é o manifest?
Resposta: É o documento que descreve o tipo do backup, escopo e metadados necessários para restauração.

### 175. O que são checksums?
Resposta: São hashes usados para validar integridade dos arquivos do backup.

### 176. O sistema valida o backup antes de restaurar?
Resposta: Sim.

### 177. O sistema protege contra ZIP malicioso?
Resposta: Sim. Há validações de estrutura e segurança de caminhos.

### 178. Há logs de backup?
Resposta: Sim.

### 179. Há preferências de backup automático?
Resposta: Sim.

### 180. O sistema restaura dados legados?
Resposta: Sim, há tratamento de compatibilidade para backups legados.

## 5.18 Perguntas sobre IA e serviços extras

### 181. O sistema tem inteligência artificial?
Resposta: Sim. Há um tutor de IA por aula e suporte a conteúdo inteligente.

### 182. O tutor de IA serve para quê?
Resposta: Para responder dúvidas do aluno com base no conteúdo da aula e no contexto pedagógico disponível.

### 183. O sistema gera transcrição?
Resposta: Sim, há suporte a transcrição de aulas em vídeo.

### 184. O sistema gera conteúdo derivado da aula?
Resposta: Sim. Existe serviço para conteúdo inteligente de leitura.

### 185. O tutor de IA guarda logs?
Resposta: Sim.

### 186. Há cache para IA?
Resposta: Sim, o serviço possui mecanismos de cache e proteção contra repetição.

### 187. Isso é um diferencial do projeto?
Resposta: Sim, porque adiciona suporte pedagógico além do CRUD tradicional.

## 5.19 Perguntas sobre arquivos específicos

### 188. Para que serve `config/env.php`?
Resposta: Para carregar as variáveis do ambiente.

### 189. Para que serve `config/database.php`?
Resposta: Para criar a conexão com a base de dados.

### 190. Para que serve `config/app.php`?
Resposta: Para definir constantes globais como URLs, diretórios, segredo do PDF e binários.

### 191. Para que serve `config/helpers.php`?
Resposta: Para funções utilitárias reutilizadas em várias partes do sistema.

### 192. Para que serve `public/router.php`?
Resposta: Para auxiliar o roteamento em ambiente de servidor embutido ou mapeamento amigável.

### 193. Para que serve `app/views/layout.php`?
Resposta: Para ser o template principal de renderização das páginas.

### 194. Para que serve `app/controllers/QuizController.php`?
Resposta: Para orquestrar criação, consulta, correção e fluxo dos quizzes.

### 195. Para que serve `app/models/Quiz.php`?
Resposta: Para persistir quizzes, tentativas, respostas e regras ligadas à avaliação.

### 196. Para que serve `scripts/generate-certificate-pdf.js`?
Resposta: Para renderizar o certificado em PDF.

### 197. Para que serve `scripts/fetch-youtube-transcript.js`?
Resposta: Para obter transcrição de conteúdo em vídeo.

### 198. Para que serve `app/services/BackupLogService.php`?
Resposta: Para registrar logs de backup e gerir preferências de backup automático.

### 199. Para que serve `app/services/LessonAiTutorService.php`?
Resposta: Para processar perguntas do aluno sobre uma aula.

### 200. Para que serve `DOCUMENTACAO_COMPLETA_PLATAFORMA.md`?
Resposta: Para explicar estruturalmente o funcionamento geral do projeto por pasta e por arquivo.

## 5.20 Perguntas sobre qualidade e manutenção

### 201. O sistema é fácil de manter?
Resposta: Relativamente sim, porque há separação por camadas e módulos. Ainda assim, há pontos que podem ser melhor modularizados, como o `index.php`.

### 202. O código está organizado?
Resposta: Sim, de forma suficiente para o escopo, com controllers, models, views e services.

### 203. Há sinais de crescimento incremental?
Resposta: Sim. Isso aparece em recursos legados, migrações e métodos `ensureSchema()`.

### 204. O sistema pode crescer?
Resposta: Sim. A base já suporta expansão.

### 205. Que melhoria arquitetural farias primeiro?
Resposta: Separaria ainda mais o roteamento e os handlers do `public/index.php`.

### 206. Que melhoria de engenharia seria importante?
Resposta: Aumentar cobertura de testes automatizados e consolidar migrações de schema.

### 207. O sistema é acoplado?
Resposta: Em alguns pontos sim, sobretudo no fluxo central, mas no geral há uma boa separação funcional.

### 208. O sistema tem serviços especializados?
Resposta: Sim. Isso já reduz o acoplamento em módulos mais complexos.

### 209. O sistema tem componentes legados?
Resposta: Sim, especialmente na evolução de quizzes e estrutura de cursos.

### 210. Isso é um problema?
Resposta: Não necessariamente. É normal em software real em evolução. O importante é entender e controlar a compatibilidade.

## 5.21 Perguntas sobre testes e validação

### 211. O projeto foi testado?
Resposta: Sim, houve validação funcional, além de scripts de apoio e documentos de checklist.

### 212. Que tipo de teste foi mais usado?
Resposta: Principalmente testes funcionais/manuais e scripts auxiliares de verificação.

### 213. O sistema tem scripts de teste?
Resposta: Sim, existem scripts como `scripts/automated_tests.php` e outros utilitários de diagnóstico.

### 214. O que validaste manualmente?
Resposta: Fluxos como login, criação de curso, criação de aulas, quizzes, progresso, certificados e administração.

### 215. Há checklist de QA?
Resposta: Sim, existe `QA_CHECKLIST.md`.

## 5.22 Perguntas sobre limitações

### 216. O sistema está pronto para produção?
Resposta: Ele está funcional e relativamente robusto para demonstração e evolução, mas para produção plena ainda recomendaria reforços de testes, observabilidade, hardening e refinamento arquitetural.

### 217. Qual limitação técnica mais visível?
Resposta: O `public/index.php` concentra bastante responsabilidade.

### 218. Qual limitação funcional podes citar?
Resposta: Ainda há espaço para ampliar integrações externas, testes automatizados e governança mais forte de deploy.

### 219. O sistema depende de ambiente local?
Resposta: Ele foi muito trabalhado para XAMPP/local, mas tem elementos que permitem adaptação a outros ambientes.

### 220. O que melhorarias no módulo de autenticação?
Resposta: Poderia ampliar MFA, trilhas de auditoria mais completas e políticas adicionais de segurança.

### 221. O que melhorarias no módulo de cursos?
Resposta: Poderia adicionar workflow editorial, publicação agendada e versionamento de conteúdo.

### 222. O que melhorarias nos quizzes?
Resposta: Banco maior de questões, rubricas mais avançadas e relatórios analíticos ainda mais ricos.

### 223. O que melhorarias nos certificados?
Resposta: Template mais configurável, QR padronizado em todas as variantes e integração com assinatura digital.

### 224. O que melhorarias no backup?
Resposta: Criptografia obrigatória opcional mais forte, agendamento mais sofisticado e armazenamento remoto configurável.

## 5.23 Perguntas sobre decisões técnicas

### 225. Por que usar PHP puro e não Laravel?
Resposta: Porque o objetivo acadêmico incluía demonstrar domínio direto dos fundamentos da web, fluxo MVC e backend sem depender de um framework completo.

### 226. Por que usar JavaScript puro?
Resposta: Para reduzir complexidade e mostrar domínio das bases da interação web.

### 227. Por que usar Puppeteer no certificado?
Resposta: Porque ele mantém fidelidade visual ao HTML/CSS e resolve bem o problema de geração de PDF.

### 228. Por que usar JSON nas opções do quiz?
Resposta: Porque as alternativas têm estrutura variável e o JSON facilita armazenar listas no contexto da pergunta.

### 229. Por que separar services dos models?
Resposta: Porque certas responsabilidades, como IA, storage e backup, já extrapolam simples persistência em banco.

### 230. Por que manter compatibilidade com estrutura legada?
Resposta: Porque software real evolui, e a compatibilidade evita quebra de dados antigos.

## 5.24 Perguntas comparativas

### 231. Qual a diferença entre curso, módulo e aula?
Resposta: Curso é a unidade principal, módulo é um agrupador dentro do curso e aula é a unidade concreta de conteúdo consumida pelo aluno.

### 232. Qual a diferença entre quiz de aula, de módulo e final?
Resposta: O quiz de aula avalia uma aula específica, o de módulo avalia uma etapa do curso e o final avalia a conclusão global.

### 233. Qual a diferença entre progresso e nota?
Resposta: Progresso mede avanço no percurso; nota mede desempenho nas avaliações.

### 234. Qual a diferença entre autenticação e autorização?
Resposta: Autenticação identifica quem é o usuário; autorização define o que ele pode fazer.

### 235. Qual a diferença entre exportação e backup?
Resposta: Backup é uma forma de exportação voltada à preservação e restauração estruturada dos dados.

## 5.25 Perguntas difíceis de banca

### 236. Se eu disser que isto ainda não é um SaaS completo, o que respondes?
Resposta: Concordo parcialmente. O projeto não foi vendido como SaaS finalizado, mas como uma plataforma funcional e robusta, com vários elementos reais de sistema educacional e uma base sólida para evolução.

### 237. Se eu disser que o `index.php` está grande demais?
Resposta: Concordo. É uma limitação arquitetural reconhecida. Ele centraliza muito fluxo e seria um dos primeiros pontos de refatoração numa fase seguinte.

### 238. Se eu disser que faltam testes automatizados mais fortes?
Resposta: É uma crítica justa. O projeto já possui validações e scripts auxiliares, mas uma próxima evolução importante seria ampliar testes automatizados e pipeline de qualidade.

### 239. Se eu disser que o sistema ainda está em transição entre versões?
Resposta: Sim, e isso foi tratado com compatibilidade em models e migrações. Isso mostra um cenário realista de evolução do software, não apenas uma versão estática.

### 240. Se eu perguntar o maior mérito técnico do projeto?
Resposta: Eu diria que é a combinação entre regras reais de progressão e certificação com uma arquitetura que já integra segurança, avaliação, certificado público e portabilidade de dados.

### 241. Se eu perguntar o maior desafio técnico?
Resposta: Coordenar regras de negócio entre cursos, aulas, quizzes, progresso e certificados sem perder coerência entre experiência do aluno e persistência dos dados.

### 242. Se eu perguntar o que tu mais aprendeste?
Resposta: Aprendi principalmente a integrar arquitetura web, modelagem de dados, segurança, regras de negócio e evolução incremental de software num sistema com vários perfis e fluxos reais.

### 243. Se eu perguntar por que teu projeto é mais do que um CRUD?
Resposta: Porque ele não só cria, lê, edita e apaga dados. Ele implementa autenticação, autorização, progressão acadêmica, tentativas de quiz, cálculo de elegibilidade, emissão de certificado, validação pública, backup e restauração.

### 244. Se eu perguntar o que aconteceria se o aluno perdesse elegibilidade?
Resposta: O sistema de certificados já foi pensado para sincronização e revogação quando o estado deixa de cumprir os critérios.

### 245. Se eu perguntar como defender a originalidade do teu projeto?
Resposta: A originalidade está na integração concreta dos módulos, na lógica real de progressão e certificação, e na forma como o sistema foi estruturado e evoluído a partir de necessidades reais do domínio.

## 5.26 Perguntas sobre futuro do projeto

### 246. O que implementarias a seguir?
Resposta: Mais testes automatizados, refatoração do roteamento, pagamentos, notificações, analytics mais profundos e melhoria da governança de deploy.

### 247. O sistema pode virar API?
Resposta: Sim. A lógica já está relativamente modularizada, o que facilitaria expor endpoints REST no futuro.

### 248. Pode integrar pagamentos?
Resposta: Sim. É uma evolução natural para monetização de cursos.

### 249. Pode integrar videoaulas externas?
Resposta: Sim. Já há suporte a vídeos e transcrição, então integrações podem crescer.

### 250. Pode ter app mobile?
Resposta: Sim. O backend pode futuramente servir um app móvel, especialmente se a camada de API for expandida.

---

## 6. Respostas ultra curtas para memorização

### O que é o projeto?
Uma plataforma de ensino online com cursos, aulas, quizzes, progresso e certificados.

### Qual a arquitetura?
MVC adaptado com controllers, models, views e services.

### Principal arquivo?
`public/index.php`.

### Banco?
MySQL.

### Backend?
PHP com PDO.

### Frontend?
HTML, CSS e JavaScript puro.

### Perfis?
Aluno, professor e admin.

### Diferencial?
Certificado público, elegibilidade real, backup/restore e IA por aula.

### Segurança?
PDO, prepared statements, hash de senha, sessão, CSRF e permissão por papel.

### Regra forte?
Não basta “assistir”. O sistema cruza progresso com avaliação para liberar certificados.

---

## 7. Como responder quando não souberes

Se a banca fizer uma pergunta muito específica e tu travares, usa uma estrutura segura:

> Pelo que está implementado hoje no sistema, a lógica segue este caminho...

ou:

> Nesta versão do projeto, isso foi tratado da seguinte forma...

ou:

> Esse ponto é uma limitação reconhecida, e a evolução que eu faria seria...

Isso mostra maturidade, sinceridade e domínio do contexto.

---

## 8. Fecho pronto para a banca

> Em conclusão, a Plataforma EAD não foi pensada apenas como interface, mas como um sistema com regras reais de ensino online. Ela integra autenticação, gestão de conteúdo, avaliação, progresso, certificação e portabilidade de dados, usando uma base arquitetural organizada e evolutiva. O projeto ainda pode crescer, mas já demonstra claramente capacidade de análise, modelagem, implementação e integração de múltiplos módulos num domínio educacional real.

