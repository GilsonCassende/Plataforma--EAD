# 📖 Guia de Desenvolvimento - Plataforma EAD

## 🏗️ Arquitetura MVC

A aplicação segue o padrão **Model-View-Controller**:

### **Models** (`app/models/`)
Responsáveis pela lógica de dados e banco de dados:
- `User.php` - Operações com usuários
- `Course.php` - Operações com cursos
- `Lesson.php` - Operações com aulas
- `Quiz.php` - Operações com quizzes
- `Enrollment.php` - Operações com matrículas

### **Controllers** (`app/controllers/`)
Lógica de negócio e requisições:
- `AuthController.php` - Autenticação e login
- `CourseController.php` - Gestão de cursos
- `LessonController.php` - Gestão de aulas
- `QuizController.php` - Gestão de quizzes
- `DashboardController.php` - Painéis
- `AdminController.php` - Funcionalidades admin

### **Views** (`app/views/`)
Apresentação (HTML):
- `layout.php` - Template base
- `home.php` - Página inicial
- `login.php`, `registro.php` - Autenticação
- `curso-detail.php` - Detalhes do curso
- `aula.php` - Visualização de aula
- `quiz.php` - Interface do quiz
- `dashboard-*.php` - Painéis específicos

## 🔄 Fluxo de Requisição

```
1. Usuario acessa URL
         ↓
2. index.php recebe requisição
         ↓
3. Router (switch) determina página
         ↓
4. Controller processa lógica
         ↓
5. Model interage com banco
         ↓
6. View renderiza HTML
         ↓
7. Layout envolve conteúdo
         ↓
8. Página é exibida ao usuário
```

## 🛠️ Como Adicionar uma Nova Funcionalidade

### Exemplo: Adicionar sistema de comentários

#### 1. **Criar Model** (`app/models/Comment.php`)
```php
<?php
class Comment {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function criar($user_id, $lesson_id, $texto) {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (user_id, lesson_id, texto) VALUES (?, ?, ?)'
        );
        return $stmt->execute([$user_id, $lesson_id, $texto]);
    }
    
    public function listarPorAula($lesson_id) {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nome FROM comments c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.lesson_id = ? ORDER BY c.created_at DESC'
        );
        $stmt->execute([$lesson_id]);
        return $stmt->fetchAll();
    }
}
?>
```

#### 2. **Criar Controller** (`app/controllers/CommentController.php`)
```php
<?php
class CommentController {
    private $pdo;
    private $commentModel;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Comment.php';
        $this->commentModel = new Comment($pdo);
    }
    
    public function criar($lesson_id, $texto) {
        $usuario = $_SESSION['usuario'] ?? null;
        
        if (!$usuario) {
            return ['sucesso' => false, 'mensagem' => 'Faça login'];
        }
        
        return $this->commentModel->criar($usuario['id'], $lesson_id, $texto);
    }
}
?>
```

#### 3. **Adicionar rota** em `public/index.php`
```php
case 'comentarios':
    AuthController::exigirAutenticacao();
    $commentController = new CommentController($pdo);
    $comentarios = $commentController->listar($_GET['lesson_id'] ?? 0);
    echo json_encode($comentarios);
    exit;
    break;
```

#### 4. **Criar View parcial** (`app/views/components/comentarios.php`)
```php
<section class="comentarios">
    <h3>Comentários</h3>
    <!-- Renderizar comentários -->
</section>
```

#### 5. **Incluir na View principal** (`aula.php`)
```php
<?php include 'components/comentarios.php'; ?>
```

## 📊 Banco de Dados

### Criar Nova Tabela

```sql
CREATE TABLE IF NOT EXISTS nova_tabela (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titulo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);
```

### Executar Script

```bash
mysql -u root -p ead_platform < novo_script.sql
```

## 🎨 Convenções de Código

### Nomes de Variáveis
```php
$nomeVariavel       // camelCase para variáveis
$_SESSION['usuario'] // UPPERCASE com underscore para constantes/globals
```

### Nomes de Funções
```php
public function criarUsuario()        // camelCase com verbo
public function obterPorId()
public function atualizarStatus()
public function deletarCurso()
```

### Nomes de Classes
```php
class UserController        // PascalCase
class CourseModel
class AuthException
```

### Nomes de Arquivos
```php
User.php            // PascalCase
AuthController.php
user-profile.php    // kebab-case para views
login-form.php
```

## 🔐 Segurança - Checklist

Ao adicionar novos recursos:

- [ ] Usar PDO com Prepared Statements
- [ ] Validar entrada do usuário
- [ ] Sanitizar output com `htmlspecialchars()`
- [ ] Verificar autenticação com `AuthController::exigirAutenticacao()`
- [ ] Verificar permissões com `AuthController::exigirPermissao()`
- [ ] Usar `password_hash()` para senhas
- [ ] Escapar strings em SQL
- [ ] Validar tipos de dados

## 📝 Exemplo Seguro

```php
<?php
// ✅ CORRETO
$email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$usuario = $stmt->fetch();

echo htmlspecialchars($usuario['nome']);

// ❌ ERRADO (SQL Injection)
$email = $_GET['email'];
$sql = "SELECT * FROM users WHERE email = '$email'";
$result = $pdo->query($sql);
?>
```

## 🧪 Testando Localmente

### Teste de Login
1. Acesse http://localhost/Plataforma-EAD/public/index.php?page=login
2. Use: `admin@ead.com` / `senha123`
3. Você deve estar no dashboard

### Teste de Cursos
1. Acesse http://localhost/Plataforma-EAD/public/index.php?page=cursos
2. Clique em "Ver Detalhes"
3. Clique em "Se Matricular Agora"
4. Vá para o dashboard e veja o curso

### Teste de Quiz
1. Entre em um curso
2. Acesse uma aula com quiz
3. Clique em "Fazer Quiz"
4. Responda todas as questões
5. Veja seu resultado

## 🐛 Debug

### Ativar Error Reporting

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Usar var_dump()

```php
<?php
echo '<pre>';
var_dump($dados);
echo '</pre>';
?>
```

### Log de Erros

```php
error_log("Erro: " . print_r($dados, true));
// Verificar em: C:\xampp\apache\logs\error.log
```

## 📦 Estrutura de Resposta JSON

```php
// Sucesso
['sucesso' => true, 'mensagem' => 'OK', 'dados' => $dados]

// Erro
['sucesso' => false, 'mensagem' => 'Erro ocorreu', 'erro' => $detalhes]
```

## 🚀 Performance

### Otimizações Implementadas

- Cache de assets (CSS, JS)
- Compressão GZIP
- Lazy loading de imagens
- Paginação de resultados
- Índices no banco de dados

### Melhorias Futuras

- [ ] Cache de queries (Redis)
- [ ] CDN para assets
- [ ] Minificação de CSS/JS
- [ ] Service Workers
- [ ] Database indexing otimizado

## 📚 Recursos Úteis

- **PHP Docs**: https://www.php.net/
- **MySQL Docs**: https://dev.mysql.com/doc/
- **MDN (HTML/CSS/JS)**: https://developer.mozilla.org/
- **W3Schools**: https://www.w3schools.com/

## 🤝 Contribuindo

1. Faça fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

---

**Última Atualização**: 12 de Novembro de 2025
