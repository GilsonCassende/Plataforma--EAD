# 🆘 SOLUÇÃO RÁPIDA - Problema de Login

## ⚠️ Seu Problema

Você está recebendo "email ou senha inválidos" mesmo com as credenciais corretas.

## ✅ SOLUÇÃO PASSO A PASSO

### Passo 1: Executar Diagnóstico

Abra seu navegador e acesse:

```
http://localhost/Plataforma-EAD/public/diagnostico.php
```

**Isto irá mostrar:**
- ✅ Se o banco está conectando
- ✅ Quantos usuários existem
- ✅ Se o admin foi encontrado
- ✅ Se a senha está correta

### Passo 2: Corrigir Automaticamente

Se o diagnóstico apontou erro, acesse:

```
http://localhost/Plataforma-EAD/public/corrigir.php
```

**Este script irá:**
1. ✅ Gerar uma nova hash válida para "senha123"
2. ✅ Atualizar TODOS os usuários no banco
3. ✅ Testar se o login funciona
4. ✅ Mostrar os usuários atualizados

### Passo 3: Fazer Login

Após a correção, acesse:

```
http://localhost/Plataforma-EAD/public/index.php?page=login
```

Use as credenciais:

| Email | Senha |
|-------|-------|
| **admin@ead.com** | **senha123** |

---

## 🆘 Se Ainda Não Funcionar

Siga este checklist:

### 1️⃣ XAMPP está rodando?
- [ ] Apache (Verde)
- [ ] MySQL (Verde)

Se não estão verdes, clique em "Start"

### 2️⃣ Banco de dados existe?

Abra phpMyAdmin:
```
http://localhost/phpmyadmin
```

Procure pela database `ead_platform` na lista à esquerda.

**Se não existe:**
- Importe o arquivo: `migrations/schema.sql`

**Como importar:**
1. Abra phpMyAdmin
2. Clique em "Importar"
3. Selecione o arquivo `migrations/schema.sql`
4. Clique em "Executar"

### 3️⃣ O banco tem dados?

No phpMyAdmin:
1. Selecione `ead_platform`
2. Clique na tabela `users`
3. Deve mostrar 6 usuários (1 admin, 2 professores, 3 alunos)

**Se está vazio:**
- Reimporte o arquivo `migrations/schema.sql`
- Depois execute `corrigir.php`

### 4️⃣ Verificar hash manualmente

No phpMyAdmin SQL execute:
```sql
SELECT email, senha_hash FROM users LIMIT 1;
```

A coluna `senha_hash` deve começar com `$2y$10$` ou `$2a$10$`

---

## 📝 Resumen Rápido

```
┌─────────────────────────────────────┐
│ 1. Acesse: diagnostico.php          │
│ 2. Se erro, acesse: corrigir.php    │
│ 3. Faça login com admin@ead.com     │
└─────────────────────────────────────┘
```

---

## 🔗 Links Diretos

- 🔍 **Diagnóstico:** http://localhost/Plataforma-EAD/public/diagnostico.php
- 🔧 **Corrigir:** http://localhost/Plataforma-EAD/public/corrigir.php
- 📊 **phpMyAdmin:** http://localhost/phpmyadmin
- 🚀 **Login:** http://localhost/Plataforma-EAD/public/index.php?page=login

---

**Tente agora! Isto deve resolver o problema! ✨**
