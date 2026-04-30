# 📡 API Reference - Plataforma EAD

## 🔐 Autenticação

### POST - Fazer Login
```
Endpoint: /index.php?page=login (POST)
Parâmetros:
  - action: "login"
  - email: usuario@email.com
  - senha: senha123

Resposta:
  Redirecionamento para dashboard se sucesso
  Redirecionamento para login com erro se falhar
```

### POST - Registrar
```
Endpoint: /index.php?page=registro (POST)
Parâmetros:
  - action: "registrar"
  - nome: João Silva
  - email: joao@email.com
  - senha: senha123
  - confirm_senha: senha123

Resposta:
  Redirecionamento para login se sucesso
  Redirecionamento para registro com erros se falhar
```

### GET - Logout
```
Endpoint: /index.php?page=logout (GET)

Resposta:
  Destroi sessão e redireciona para home
```

---

## 📚 Cursos

### GET - Listar Cursos
```
Endpoint: /index.php?page=cursos
Parâmetros (GET):
  - p: 1 (número da página, opcional)
  - busca: "PHP" (termo de busca, opcional)

Resposta:
  Array com:
    - cursos: Array de cursos
    - pagina: Página atual
    - total_paginas: Total de páginas
    - total: Total de cursos
```

### GET - Detalhe do Curso
```
Endpoint: /index.php?page=curso&id=1
Parâmetros (GET):
  - id: 1 (ID do curso, obrigatório)

Resposta:
  Array com:
    - curso: Dados completos do curso
    - aulas: Array de aulas do curso
    - total_alunos: Número de alunos
```

### POST - Matricular em Curso
```
Endpoint: /index.php (POST)
Parâmetros:
  - action: "matricular_curso"
  - course_id: 1

Resposta:
  Redirecionamento para o curso com mensagem
```

### POST - Criar Curso (Professor)
```
Requisição esperada (não implementada em form)
Parâmetros:
  - action: "criar_curso"
  - titulo: "Introdução ao PHP"
  - descricao: "Aprenda PHP do zero"
  - categoria: "Backend"

Resposta:
  { sucesso: true, id: 1 }
```

---

## 🎬 Aulas

### GET - Visualizar Aula
```
Endpoint: /index.php?page=aula&lesson_id=1&course_id=1
Parâmetros (GET):
  - lesson_id: 1 (ID da aula, obrigatório)
  - course_id: 1 (ID do curso, obrigatório)

Resposta:
  - aula: Dados da aula
  - curso: Dados do curso
  - quizzes: Array de quizzes disponíveis
  - aulas_curso: Outras aulas do curso
```

### POST - Marcar Aula como Concluída
```
Endpoint: /index.php (POST)
Parâmetros:
  - action: "marcar_concluida"
  - lesson_id: 1

Resposta:
  Redirecionamento com mensagem de sucesso
```

---

## 📝 Quizzes

### GET - Carregar Quiz
```
Endpoint: /index.php?page=quiz&quiz_id=1
Parâmetros (GET):
  - quiz_id: 1 (ID do quiz, obrigatório)

Resposta:
  - quiz: Dados do quiz (sem respostas corretas)
  - questoes: Array de questões
```

### POST - Responder Quiz
```
Endpoint: /index.php (POST)
Parâmetros:
  - action: "responder_quiz"
  - quiz_id: 1
  - questao_1: "alternativa A"
  - questao_2: "verdadeiro"
  - questao_3: "resposta escrita"

Resposta:
  $_SESSION['quiz_resultado'] com:
    - sucesso: true
    - score: 8.5
    - total_correto: 2
    - total_questoes: 3
    - respostas: { question_id: { resposta_usuario, correta, ... } }
```

---

## 👥 Dashboard

### GET - Dashboard Aluno
```
Endpoint: /index.php?page=dashboard
Autenticação: Requerida
Resposta:
  - usuario: Dados do usuário
  - cursos: Cursos matriculados
  - total_cursos: Total de cursos
  - em_progresso: Cursos em progresso
  - concluidos: Cursos concluídos
```

### GET - Dashboard Professor
```
Endpoint: /index.php?page=dashboard
Autenticação: Requerida (role: professor)
Resposta:
  - usuario: Dados do usuário
  - cursos: Cursos criados pelo professor
  - total_cursos: Total de cursos
  - total_alunos: Total de alunos
```

### GET - Dashboard Admin
```
Endpoint: /index.php?page=dashboard
Autenticação: Requerida (role: admin)
Resposta:
  - stats: Estatísticas globais
  - usuarios: Todos os usuários
  - cursos: Todos os cursos
  - usuario: Dados do admin
```

---

## 👤 Perfil

### GET - Visualizar Perfil
```
Endpoint: /index.php?page=perfil
Autenticação: Requerida

Resposta:
  - usuario: Dados completos do usuário
```

---

## 🛠️ Variáveis de Sessão

### $_SESSION['usuario']
```php
[
  'id' => 1,
  'nome' => 'João Silva',
  'email' => 'joao@email.com',
  'role' => 'aluno' | 'professor' | 'admin'
]
```

### $_SESSION['mensagem']
```php
"Ação realizada com sucesso"
// Exibida uma vez e depois deletada
```

### $_SESSION['erro']
```php
"Erro ao processar a requisição"
// Exibida uma vez e depois deletada
```

### $_SESSION['quiz_resultado']
```php
[
  'sucesso' => true,
  'score' => 8.5,
  'total_correto' => 2,
  'total_questoes' => 3,
  'respostas' => [ ... ]
]
```

---

## 🔗 URLs Úteis

### Públicas (sem autenticação)
| Página | URL |
|--------|-----|
| Home | `/index.php` ou `/index.php?page=home` |
| Login | `/index.php?page=login` |
| Registrar | `/index.php?page=registro` |
| Cursos | `/index.php?page=cursos` |
| Detalhes Curso | `/index.php?page=curso&id=1` |

### Privadas (requer autenticação)
| Página | URL | Roles |
|--------|-----|-------|
| Dashboard | `/index.php?page=dashboard` | aluno, professor, admin |
| Perfil | `/index.php?page=perfil` | aluno, professor, admin |
| Aula | `/index.php?page=aula&lesson_id=1&course_id=1` | aluno (matriculado) |
| Quiz | `/index.php?page=quiz&quiz_id=1` | aluno (matriculado) |

---

## ✅ HTTP Status Codes

```
200 - OK (Sucesso)
302 - Redirect (Redirecionamento)
403 - Forbidden (Acesso negado)
404 - Not Found (Página não encontrada)
500 - Server Error (Erro interno)
```

---

## 🔒 Campos de Segurança

Todos os dados vindos de `$_GET` e `$_POST` são:
- ✅ Validados
- ✅ Sanitizados
- ✅ Escapados em queries
- ✅ Verificados quanto a permissões

---

## 📦 Modelos de Dados

### User
```php
[
  'id' => 1,
  'nome' => 'João Silva',
  'email' => 'joao@email.com',
  'role' => 'aluno', // aluno, professor, admin
  'fotografia' => 'caminho/foto.jpg',
  'created_at' => '2024-11-12 10:30:00',
  'updated_at' => '2024-11-12 10:30:00'
]
```

### Course
```php
[
  'id' => 1,
  'titulo' => 'Introdução ao PHP',
  'descricao' => 'Aprenda PHP do zero',
  'teacher_id' => 1,
  'professor_nome' => 'João Silva',
  'thumbnail' => 'caminho/thumb.jpg',
  'categoria' => 'Backend',
  'status' => 'ativo', // ativo, inativo, rascunho
  'total_alunos' => 42,
  'created_at' => '2024-11-12 10:30:00'
]
```

### Lesson
```php
[
  'id' => 1,
  'course_id' => 1,
  'titulo' => 'Variáveis em PHP',
  'descricao' => 'Aprenda sobre variáveis',
  'tipo' => 'video', // video, pdf, texto, arquivo
  'conteudo' => 'conteúdo em texto',
  'url_arquivo' => 'caminho/arquivo.mp4',
  'ordem' => 1,
  'created_at' => '2024-11-12 10:30:00'
]
```

### Quiz
```php
[
  'id' => 1,
  'lesson_id' => 1,
  'titulo' => 'Quiz da Lição 1',
  'descricao' => 'Teste seus conhecimentos',
  'tentativas_maximas' => 3,
  'pontos_totais' => 10,
  'questoes' => [ /* ... */ ]
]
```

### Question
```php
[
  'id' => 1,
  'quiz_id' => 1,
  'texto' => 'Qual é a resposta?',
  'tipo' => 'multipla', // multipla, verdadeiro_falso, dissertativa
  'opcoes' => ['A', 'B', 'C', 'D'], // JSON array
  'resposta_correta' => 'A',
  'ordem' => 1
]
```

---

## 🧪 Testando a API

### Com cURL (Terminal)
```bash
# Listar cursos
curl http://localhost/Plataforma-EAD/public/index.php?page=cursos

# Fazer login
curl -X POST http://localhost/Plataforma-EAD/public/index.php \
  -d "action=login&email=admin@ead.com&senha=senha123"
```

### Com Postman
1. Abra Postman
2. Crie uma requisição POST
3. URL: `http://localhost/Plataforma-EAD/public/index.php`
4. Body (form-data):
   - action: login
   - email: admin@ead.com
   - senha: senha123
5. Clique em Send

---

## 📋 Relatórios (Admin)

### GET - Alunos por Curso
```
Requisição:
  GET /index.php?page=relatorio_alunos&course_id=1

Resposta:
  [
    'curso' => { ... },
    'alunos' => [ ... ],
    'total' => 42
  ]
```

---

**Última Atualização**: 12 de Novembro de 2025  
**Versão da API**: 1.0.0
