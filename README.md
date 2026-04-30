# 🎓 Plataforma EAD - Sistema de Ensino a Distância

Uma **plataforma completa de Educação a Distância** (EAD) desenvolvida em **PHP, MySQL, HTML5 e CSS3** com arquitetura **MVC** profissional, 100% responsiva e pronta para rodar no **XAMPP**.

## 📋 Características

### ✅ Funcionalidades Implementadas

#### 🔐 **Autenticação & Autorização**
- ✓ Cadastro de usuários com validação
- ✓ Login seguro com `password_hash()` e `password_verify()`
- ✓ Sistema de sessões (`$_SESSION`)
- ✓ Controle de permissões por role (Admin, Professor, Aluno)
- ✓ Proteção contra SQL Injection (PDO + Prepared Statements)

#### 📚 **Gestão de Cursos**
- ✓ CRUD completo de cursos
- ✓ Criação e edição por professores
- ✓ Sistema de categorias
- ✓ Upload de thumbnails
- ✓ Listagem com paginação
- ✓ Busca por palavras-chave

#### 🎬 **Gestão de Aulas**
- ✓ Criação de aulas (texto, vídeo, PDF, arquivos)
- ✓ Ordenação de aulas
- ✓ Suporte a vídeos YouTube e MP4
- ✓ Controle de progresso por aula
- ✓ Marcação de conclusão

#### 📝 **Sistema de Quizzes**
- ✓ Criação de quizzes com múltiplas questões
- ✓ Tipos de questões: Múltipla Escolha, Verdadeiro/Falso, Dissertativa
- ✓ Sistema de pontuação automática
- ✓ Limite de tentativas
- ✓ Armazenamento de resultados
- ✓ Histórico de desempenho

#### 👥 **Dashboards Específicos**
- ✓ Dashboard do Aluno (cursos, progresso, notas)
- ✓ Dashboard do Professor (gerenciar cursos e alunos)
- ✓ Dashboard do Admin (estatísticas e gerenciamento)

#### 🎯 **Painéis do Aluno**
- ✓ Ver cursos matriculados
- ✓ Visualizar aulas e progresso
- ✓ Realizar quizzes
- ✓ Ver notas e desempenho
- ✓ Barra de progresso animada

#### 👨‍🏫 **Painéis do Professor**
- ✓ Criar e editar cursos
- ✓ Enviar vídeos, PDFs e textos
- ✓ Criar quizzes com questões
- ✓ Gerenciar alunos matriculados
- ✓ Ver estatísticas de participação

#### ⚙️ **Painéis Admin**
- ✓ Gerenciar usuários (alunos, professores, admins)
- ✓ Gerenciar cursos
- ✓ Ver estatísticas globais
- ✓ Deletar/editar usuários e cursos

#### 🎨 **Interface & UX**
- ✓ Design moderno e limpo
- ✓ 100% responsivo (Mobile-First)
- ✓ Navbar com busca integrada
- ✓ Cards animados
- ✓ Barras de progresso animadas
- ✓ Alertas e notificações
- ✓ Tema claro e agradável
- ✓ Compatível com todos os navegadores

#### 🔒 **Segurança**
- ✓ PDO com Prepared Statements
- ✓ Senhas criptografadas com BCRYPT
- ✓ Sanitização de inputs
- ✓ Proteção CSRF (estrutura preparada)
- ✓ Validação de permissões em todas as ações

## 🗂️ Estrutura de Pastas

```
Plataforma-EAD/
├── public/
│   ├── index.php                 (Roteador principal)
│   ├── css/
│   │   ├── style.css             (Estilos globais)
│   │   └── responsive.css        (Media queries)
│   ├── js/
│   │   ├── main.js               (Funcionalidades)
│   │   └── ui.js                 (Interações UI)
│   └── uploads/                  (Arquivos enviados)
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── CourseController.php
│   │   ├── LessonController.php
│   │   ├── QuizController.php
│   │   ├── DashboardController.php
│   │   └── AdminController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Course.php
│   │   ├── Lesson.php
│   │   ├── Quiz.php
│   │   └── Enrollment.php
│   └── views/
│       ├── layout.php            (Template base)
│       ├── home.php
│       ├── login.php
│       ├── registro.php
│       ├── cursos.php
│       ├── curso-detail.php
│       ├── aula.php
│       ├── quiz.php
│       ├── perfil.php
│       ├── dashboard-aluno.php
│       ├── dashboard-professor.php
│       └── dashboard-admin.php
├── config/
│   └── database.php              (Configuração MySQL)
├── migrations/
│   └── schema.sql                (Script de criação do BD)
└── README.md                     (Este arquivo)
```

## 💾 Banco de Dados

### Tabelas Criadas

- **users**: Usuários do sistema (aluno, professor, admin)
- **courses**: Cursos disponíveis
- **lessons**: Aulas dentro de cursos
- **enrollments**: Matrículas de alunos em cursos
- **quizzes**: Quizzes das aulas
- **questions**: Questões dos quizzes
- **quiz_results**: Resultados das respostas dos alunos
- **lesson_progress**: Progresso em aulas
- **messages**: Mensagens entre usuários
- **certificates**: Certificados de conclusão

## 🚀 Instalação e Configuração

### Pré-requisitos
- XAMPP instalado (PHP 7.4+, MySQL 5.7+)
- Git (opcional)

### Passo 1: Preparar o Banco de Dados

1. Abra o **phpMyAdmin** (http://localhost/phpmyadmin)
2. Crie um novo banco de dados chamado `ead_platform`
3. Importe o arquivo `migrations/schema.sql`:
   - Vá para a aba **SQL**
   - Copie todo o conteúdo de `schema.sql`
   - Clique em **Executar**

**OU** execute pelo terminal:

```bash
mysql -u root -p ead_platform < migrations/schema.sql
```

### Passo 2: Configurar Senhas de Teste

Para os dados de teste inseridos, a senha padrão é: **`senha123`**

Para gerar hashes corretos, use:

```php
<?php
echo password_hash('senha123', PASSWORD_BCRYPT);
?>
```

Depois atualize no banco de dados:

```sql
UPDATE users SET senha_hash = '$2y$10$...' WHERE email = 'admin@ead.com';
```

### Passo 3: Iniciar o Servidor

```bash
# No XAMPP Control Panel, clique em "Start" para Apache e MySQL
# Ou via terminal:
cd C:\xampp
apache_start.bat
mysql_start.bat

# Acesse: http://localhost/Plataforma-EAD/public/index.php
```

### Passo 4: Contas de Teste

**Administrador:**
- Email: `admin@ead.com`
- Senha: `senha123`

**Professor:**
- Email: `joao@ead.com` ou `maria@ead.com`
- Senha: `senha123`

**Aluno:**
- Email: `carlos@ead.com`, `ana@ead.com` ou `bruno@ead.com`
- Senha: `senha123`

## 🔧 Configuração de Ambiente

Edite o arquivo `config/database.php` para alterar credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');           // Altere se tiver senha
define('DB_NAME', 'ead_platform');
```

## 📖 Como Usar

### Para Alunos

1. **Registrar**: Clique em "Registrar" na página inicial
2. **Buscar Cursos**: Acesse "Cursos" e explore os disponíveis
3. **Se Matricular**: Clique em "Ver Curso" > "Se Matricular Agora"
4. **Assistir Aulas**: Acesse o curso matriculado e clique em "Ir para o Curso"
5. **Fazer Quiz**: Após cada aula, responda os quizzes
6. **Ver Progresso**: No dashboard, visualize seu progresso por curso

### Para Professores

1. **Login**: Use as credenciais de professor
2. **Criar Curso**: No dashboard, clique em "+ Novo Curso"
3. **Adicionar Aulas**: No gerenciador do curso, insira aulas (vídeo, PDF, texto)
4. **Criar Quiz**: Para cada aula, crie quizzes com questões
5. **Gerenciar Alunos**: Veja quem se matriculou e o progresso deles
6. **Editar Conteúdo**: Atualize aulas e quizzes a qualquer momento

### Para Administradores

1. **Login**: Use as credenciais de admin
2. **Dashboard**: Visualize estatísticas globais
3. **Gerenciar Usuários**: Crie, edite ou delete usuários
4. **Gerenciar Cursos**: Controle todos os cursos da plataforma
5. **Ativar/Desativar**: Altere status de cursos

## 🎯 Endpoints Principais

| Página | URL |
|--------|-----|
| Home | `/index.php?page=home` |
| Login | `/index.php?page=login` |
| Registrar | `/index.php?page=registro` |
| Todos Cursos | `/index.php?page=cursos` |
| Detalhe Curso | `/index.php?page=curso&id=1` |
| Aula | `/index.php?page=aula&lesson_id=1&course_id=1` |
| Quiz | `/index.php?page=quiz&quiz_id=1` |
| Dashboard | `/index.php?page=dashboard` |
| Perfil | `/index.php?page=perfil` |
| Logout | `/index.php?page=logout` |

## 🔐 Segurança

### Implementações

✅ **Proteção contra SQL Injection**
```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
```

✅ **Senhas Criptografadas**
```php
$hash = password_hash($senha, PASSWORD_BCRYPT);
password_verify($senha, $hash);
```

✅ **Sanitização de Inputs**
```php
$nome = htmlspecialchars($nome);
$email = filter_var($email, FILTER_SANITIZE_EMAIL);
```

✅ **Controle de Permissões**
```php
AuthController::exigirPermissao(['professor', 'admin']);
```

## 📱 Responsividade

- **Desktop**: 100% funcional
- **Tablet**: Layout adaptado (768px breakpoint)
- **Mobile**: Mobile-first design (480px breakpoint)
- **Todas as imagens**: Otimizadas e escaláveis

## 🎨 Tema e Cores

- **Primário**: `#667eea` (Azul Roxa)
- **Secundário**: `#764ba2` (Roxo)
- **Sucesso**: `#28a745` (Verde)
- **Perigo**: `#dc3545` (Vermelho)
- **Fundo**: `#f8f9fa` (Cinza Claro)

## 📦 Dependências

- PHP 7.4+
- MySQL 5.7+
- Navegador moderno (Chrome, Firefox, Safari, Edge)

**Nenhuma dependência externa!** Projeto puro vanilla.

## 🐛 Troubleshooting

### Erro: "PDOException: SQLSTATE[HY000]"
- Verifique se MySQL está rodando
- Confirme credenciais em `config/database.php`
- Verifique se banco `ead_platform` existe

### Erro: "Class not found"
- Verifique se os caminhos estão corretos em `index.php`
- Confirme que os arquivos de models e controllers existem

### Arquivo de upload não funciona
- Verifique permissões da pasta `public/uploads/` (755)
- Confirme se a pasta existe

## 🚀 Próximos Passos / Melhorias Futuras

- [ ] Autenticação por OAuth (Google, GitHub)
- [ ] Chat em tempo real entre professor-aluno
- [ ] Certificados em PDF automáticos
- [ ] Pagamentos integrados
- [ ] API REST para apps mobile
- [ ] Testes automatizados
- [ ] Dashboard com gráficos
- [ ] Notificações por email
- [ ] Fórum por curso
- [ ] Comentários em aulas

## 📝 Licença

Este projeto é de código aberto e pode ser usado livremente para fins educacionais.

## 👨‍💻 Autor

Desenvolvido como uma plataforma EAD profissional completa.

## 📞 Suporte

Para dúvidas ou problemas:
1. Verifique este README
2. Revise o código de exemplo
3. Consulte a estrutura do banco de dados

---

**Status**: ✅ Completo e Funcional  
**Última Atualização**: 12 de Novembro de 2025  
**Versão**: 1.0.0
