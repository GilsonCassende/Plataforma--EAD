# Plataforma EAD — Resumo da Primeira Etapa

---

---
marp: true
theme: default
paginate: true
backgroundColor: '#ffffff'
class: lead

---

# Slide 1 — Título
Plataforma EAD — Primeira Etapa

- Autor: (seu nome)
- Data: 13 de novembro de 2025

Note: Apresente objetivo do projeto (EAD em PHP/MySQL com MVC). Tempo estimado: 60–90s.

---

# Slide 2 — Objetivo do Projeto
- Construir uma plataforma de ensino a distância (EAD)
- Arquitetura: MVC (PHP + MySQL) rodando no XAMPP
- Público-alvo: alunos, professores e administradores

Note: Explique escopo da primeira etapa (MVP com autenticação, cursos, aulas, quizzes e dashboards).

---

# Slide 3 — Arquitetura
- Estrutura de pastas: `public/`, `app/controllers/`, `app/models/`, `app/views/`, `config/`, `migrations/`
- Entrada e roteamento: `public/index.php` (param `page`)
- Banco: MySQL via PDO (`config/database.php`)

Note: Mostre diagrama simples (Controller → Model → View). Diga que o código foi escrito sem frameworks externos.

---

# Slide 4 — Autenticação e Permissões
- Registro e login com `password_hash()` / `password_verify()`
- Papéis: `aluno`, `professor`, `admin`
- Sessões em `$_SESSION['usuario']`
- Helpers: `AuthController::estaAutenticado()`, `exigirAutenticacao()`, `verificarPermissao()`

Note: Segurança básica implementada (hash de senha, prepared statements). Mostrar credenciais de teste (admin@ead.com / senha123).

---

# Slide 5 — Cursos
- CRUD de cursos (criar, listar, editar, excluir)
- Campos principais: `titulo`, `descricao`, `teacher_id`, `categoria`, `thumbnail`, `status`
- Busca por palavra-chave e paginação
- Model: `app/models/Course.php`

Note: Professores podem criar e gerenciar seus cursos; admin tem privilégios adicionais.

---

# Slide 6 — Aulas
- CRUD de aulas com tipos: `video`, `pdf`, `texto`, `arquivo`
- Ordenação por campo `ordem`
- Visualização em `aula.php` com suporte a embed de vídeo/PDF/texto
- Model: `app/models/Lesson.php`

Note: Explique que a aula pode conter URL ou upload simples, e que há botão para marcar aula como concluída.

---

# Slide 7 — Matrículas & Progresso
- Alunos podem se matricular em cursos (`enrollments`)
- Progresso armazenado em porcentagem no registro de matrícula
- Métodos: `matricular()`, `estaMatriculado()`, `atualizarProgresso()`, `marcarConcluido()`
- View: seção de progresso no dashboard do aluno

Note: Mostre como a conclusão de aulas atualiza o progresso do curso.

---

# Slide 8 — Quizzes
- Criação de quizzes vinculados a aulas
- Questões: múltipla escolha, V/F e dissertativas (opções armazenadas em JSON)
- Correção automática e armazenamento de resultados (`quiz_results`)
- Controle de tentativas e cálculo de pontuação
- Model: `app/models/Quiz.php`, Controller: `app/controllers/QuizController.php`

Note: Explique fluxo: professor cria quiz → aluno responde → plataforma corrige e guarda histórico.

---

# Slide 9 — Dashboards
- Dashboard do Aluno: cursos matriculados, progresso, últimos resultados
- Dashboard do Professor: cursos próprios, número de alunos, gerenciar conteúdo
- Dashboard do Admin: estatísticas do sistema, gerenciamento de usuários/cursos
- Views: `dashboard-aluno.php`, `dashboard-professor.php`, `dashboard-admin.php`

Note: Mostre captura de tela ou explique onde navegar no menu.

---

# Slide 10 — Frontend & Usabilidade
- Layout responsivo: `public/css/style.css` + `responsive.css`
- Interatividade: `public/js/main.js`, `public/js/ui.js`
- Componentes: navbar, cards de curso, formulários, toasts, barras de progresso

Note: Destaque que o design é mobile-first e usa variáveis CSS para tema.

---

# Slide 11 — Segurança
- PDO com prepared statements (`ATTR_EMULATE_PREPARES => false`)
- Senhas com BCRYPT
- Sanitização de entradas (`filter_var`, `htmlspecialchars`) em controllers e views
- Verificações de permissão antes de ações sensíveis

Note: Reforce proteção contra SQL Injection e armazenamento seguro de senhas.

---

# Slide 12 — Scripts de Apoio (Desenvolvimento)
- `public/diagnostico.php` — checagens rápidas do banco e hash
- `public/corrigir.php` — atualiza hashes de teste automaticamente
- `public/inicializar.php` — opcional: reseta e importa `migrations/schema.sql` (aviso: apaga dados)

Note: Esses scripts ajudam a preparar demo local com dados de teste.

---

# Slide 13 — O que falta / Próximos passos
- Integração de pagamentos (Stripe/PayPal)
- Geração de certificados em PDF
- Upload/transcodificação de vídeo com CDN
- Notificações por email e real-time (chat)
- API REST pública e testes automatizados

Note: Apresente roadmap curto/médio prazo e prioridades para produção.

---

# Slide 14 — Demonstração (Roteiro rápido)
1. Acesse: `http://localhost/Plataforma-EAD/public/index.php`
2. Login como admin: `admin@ead.com / senha123`
3. Criar um curso (menu professor/admin)
4. Adicionar aula ao curso
5. Matricular um aluno e entrar como aluno
6. Fazer um quiz e ver resultado salvo

Note: Tempo estimado: 6–8 minutos para demo ao vivo.

---

# Slide 15 — Observações Técnicas Finais
- Código modular e pronto para extensões
- Boas práticas aplicadas (segurança, separação de responsabilidades)
- Arquivo de schema em `migrations/schema.sql` com tabelas principais

Note: Termine com convite para perguntas e proposta de próximos entregáveis.

---

# FIM — Perguntas
Obrigado! Perguntas? 👇

Note: Se quiser, eu gero slides em HTML (Reveal.js) ou PowerPoint (.pptx). Basta pedir.
