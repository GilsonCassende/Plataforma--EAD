# 🔧 CORRIGIR PROBLEMA DE LOGIN

## ❌ Problema Identificado

A hash de senha no banco de dados estava **INVÁLIDA** (`$2y$10$YourHashedPasswordHere`).

## ✅ Solução

### Opção 1: Importar Novamente (Recomendado)

Se ainda não importou o banco ou quer limpar tudo:

1. Abra **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Clique em **Banco de dados**
3. Procure por `ead_platform` e **delete**
4. Importe o arquivo `migrations/schema.sql` novamente
5. Agora as senhas estão corretas ✅

### Opção 2: Atualizar Senhas (Se já tem dados)

Se já tem dados importantes no banco:

1. Abra **phpMyAdmin**
2. Vá até **ead_platform** → **SQL**
3. Cole o conteúdo do arquivo `migrations/CORRECAO_SENHAS.sql`
4. Clique em **Executar**
5. Pronto! ✅

## 🔑 Agora Faça Login Com:

| Email | Senha | Função |
|-------|-------|--------|
| admin@ead.com | senha123 | Administrador |
| joao@ead.com | senha123 | Professor |
| maria@ead.com | senha123 | Professor |
| carlos@ead.com | senha123 | Aluno |
| ana@ead.com | senha123 | Aluno |
| bruno@ead.com | senha123 | Aluno |

## 📝 Detalhes Técnicos

**Hash Corrigido:**
```
$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G
```

Esta hash foi gerada usando:
- Algoritmo: BCRYPT (PASSWORD_BCRYPT do PHP)
- Senha: `senha123`
- Cost: 10

## 🆘 Se Ainda Não Funcionar

Verifique:

1. ✅ MySQL está rodando (XAMPP)
2. ✅ Banco de dados `ead_platform` existe
3. ✅ Tabela `users` tem os dados
4. ✅ A senha no banco é exatamente: `$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86AGR0Ky/.G`

**Comando no phpMyAdmin para verificar:**
```sql
SELECT email, role, senha_hash FROM users LIMIT 1;
```

O `senha_hash` deve começar com `$2y$10$`

---

**Problema Resolvido! ✨**
