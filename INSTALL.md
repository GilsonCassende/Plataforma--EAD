# 🚀 GUIA RÁPIDO DE INSTALAÇÃO

## ⚡ Instalação em 5 Minutos

### 1️⃣ Preparar Banco de Dados (1 minuto)

**Opção A - Via phpMyAdmin (Recomendado)**
```
1. Abra http://localhost/phpmyadmin
2. Clique em "Nova" ou "Create"
3. Digite: ead_platform
4. Clique em "Criar"
5. Vá para a aba "SQL"
6. Abra o arquivo: migrations/schema.sql
7. Copie TODO o conteúdo
8. Cole no phpMyAdmin
9. Clique em "Executar"
```

**Opção B - Via Terminal (PowerShell/CMD)**
```powershell
cd C:\xampp\mysql\bin
mysql -u root < C:\xampp\htdocs\Plataforma-EAD\migrations\schema.sql
```

### 2️⃣ Iniciar XAMPP (1 minuto)

**Opção A - Control Panel**
- Abra `C:\xampp\xampp-control.exe`
- Clique em **Start** para Apache
- Clique em **Start** para MySQL

**Opção B - Terminal**
```powershell
cd C:\xampp
apache_start.bat
mysql_start.bat
```

### 3️⃣ Acessar a Plataforma (1 minuto)

Abra no navegador:
```
http://localhost/Plataforma-EAD/public/index.php
```

### 4️⃣ Fazer Login (1 minuto)

Use uma das contas de teste:

**Admin:**
- Email: `admin@ead.com`
- Senha: `senha123`

**Professor:**
- Email: `joao@ead.com`
- Senha: `senha123`

**Aluno:**
- Email: `carlos@ead.com`
- Senha: `senha123`

### 5️⃣ Explorar (1 minuto)

- ✅ Clique em **Dashboard** para ver seu painel
- ✅ Vá em **Cursos** para ver todos os cursos
- ✅ Clique em um curso para ver aulas
- ✅ Faça um quiz para testar

---

## ❌ Se Algo Não Funcionar

### Erro: "Conexão Recusada"
```
✓ Verifique se Apache e MySQL estão rodando
✓ Abra o XAMPP Control Panel e clique "Start"
✓ Espere 3 segundos e tente novamente
```

### Erro: "Banco de Dados Não Encontrado"
```
✓ Verifique se o banco "ead_platform" existe
✓ Vá em phpMyAdmin > Verifique "ead_platform"
✓ Se não existir, execute schema.sql novamente
```

### Erro: "Class Not Found"
```
✓ Verifique se as pastas têm os arquivos corretos
✓ Verifique os caminhos em public/index.php
✓ Reinicie o servidor
```

### Login Não Funciona
```
✓ Certifique-se que digitou corretamente
✓ Email: admin@ead.com (com arroba)
✓ Senha: senha123 (sem maiúsculas)
✓ Limpe o cache (Ctrl + Shift + Delete)
```

---

## 📁 Estrutura Criada

```
✅ Plataforma-EAD/
   ├─ public/
   │  ├─ index.php ...................... Arquivo principal
   │  ├─ css/
   │  │  ├─ style.css ................... Estilos gerais
   │  │  └─ responsive.css .............. Media queries
   │  └─ js/
   │     ├─ main.js ..................... JS principal
   │     └─ ui.js ....................... Interações UI
   │
   ├─ app/
   │  ├─ controllers/ ................... Lógica da aplicação
   │  ├─ models/ ........................ Banco de dados
   │  └─ views/ ......................... HTML das páginas
   │
   ├─ config/
   │  ├─ database.php ................... Conexão MySQL
   │  └─ helpers.php .................... Funções úteis
   │
   ├─ migrations/
   │  └─ schema.sql ..................... Scripts do BD
   │
   ├─ README.md ......................... Documentação principal
   ├─ DEVELOPMENT.md .................... Guia de desenvolvimento
   └─ INSTALL.md ....................... Este arquivo
```

---

## 🎯 Próximos Passos

### Para Alunos:
1. Crie um novo usuário em "Registrar"
2. Explore os cursos disponíveis
3. Se matricule em um curso
4. Comece a assistir aulas

### Para Professores:
1. Faça login com `joao@ead.com`
2. Vá ao Dashboard
3. Clique em "+ Novo Curso"
4. Crie seu primeiro curso
5. Adicione aulas e quizzes

### Para Administradores:
1. Faça login com `admin@ead.com`
2. Vá ao Dashboard
3. Explore as opções de gerenciamento
4. Veja estatísticas

---

## 🔍 Verificação Final

- [ ] XAMPP rodando (Apache + MySQL verde)
- [ ] phpMyAdmin acessível
- [ ] Banco `ead_platform` criado
- [ ] Arquivo `index.php` acessível
- [ ] Login funcionando
- [ ] Dashboard carregando

---

## 💡 Dicas

✨ **Para Melhor Performance:**
- Use Google Chrome ou Edge (mais rápido)
- Feche outras abas/programas
- Limpe o cache do navegador

✨ **Para Desenvolvimento:**
- Use Visual Studio Code
- Instale a extensão "PHP Intelephense"
- Abra a pasta Plataforma-EAD no VS Code

✨ **Para Produção:**
- Use um servidor real (Hostinger, Heroku, AWS)
- Configure SSL/HTTPS
- Use banco de dados remoto
- Configure variáveis de ambiente

---

## 📞 Suporte Rápido

| Problema | Solução |
|----------|---------|
| Página em branco | Verifique erros em: `C:\xampp\apache\logs\error.log` |
| 404 Not Found | Acesse via `index.php?page=home` |
| Estilo não carrega | Limpe cache (Ctrl + Shift + Delete) |
| Login lento | Reinicie MySQL |

---

## ✅ Tudo Pronto!

Sua plataforma EAD está 100% funcional e pronta para usar.

**Divirta-se! 🎉**

Qualquer dúvida, consulte:
- `README.md` - Documentação geral
- `DEVELOPMENT.md` - Guia técnico
- Código com comentários nas pastas

---

**Versão**: 1.0.0 | **Data**: 12 de Novembro de 2025
