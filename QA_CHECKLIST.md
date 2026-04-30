# QA Checklist — Plataforma EAD (Painel do Professor)

Este checklist ajuda na validação manual das funcionalidades que implementamos.

## 1) Preparação
- [ ] Iniciar Apache e MySQL (XAMPP).
- [ ] Importar `migrations/schema.sql` se necessário.
- [ ] Acessar: `http://localhost/Plataforma-EAD/public/index.php` e fazer login como professor.

## 2) Modais e formulários
- Criar Curso (modal)
  - [ ] No `Dashboard` clicar em **+ Criar Novo Curso** → modal abre com formulário.
  - [ ] Preencher título, descrição e enviar (com e sem thumbnail).
  - [ ] Verificar notificação de sucesso e redirecionamento/refresh conforme resposta.
  - [ ] Testar acesso direto: `?page=criar-curso` exibe o formulário full-page.

- Criar Aula (modal)
  - [ ] Em `Gerenciar Curso` clicar **+ Adicionar Aula** → modal abre com formulário.
  - [ ] Enviar aula com tipo `video`, `pdf` e `texto` e verificar upload (se aplicável).

- Criar Quiz (modal)
  - [ ] Em `Gerenciar Curso` clicar **+ Criar Quiz** → modal abre com formulário.
  - [ ] Criar quiz e confirmar que aparece na lista de quizzes.

- Envio via AJAX
  - [ ] Enviar o formulário dentro do modal — confirmar que `submitFormAjax` mostra notificações (sucesso/erro).
  - [ ] Verificar cabeçalhos: `X-CSRF-Token` enviado e resposta JSON quando apropriado.

## 3) Uploads e validações
- [ ] Enviar thumbnail grandes (>5MB) — deve rejeitar com erro de validação.
- [ ] Enviar formatos inválidos (ex.: .exe) — deve rejeitar.
- [ ] Verificar arquivo enviado aparece em `public/uploads/` e URL é acessível.

## 4) Segurança
- [ ] Testar CSRF: enviar requisição POST sem token X-CSRF-Token — servidor deve rejeitar.
- [ ] Testar proteção por role: usuário aluno não deve acessar páginas do professor.

## 5) Dashboard e visualizações
- Contadores
  - [ ] Conferir que os contadores (`Cursos`, `Alunos`, `Aulas`, `Atividades`) mostram números correctos e animam.
- Gráfico/Distribuição
  - [ ] Verificar barras de distribuição renderizam proporcionalmente aos valores.
  - [ ] Verificar responsividade em mobile (redimensionar janela).

## 6) UX e Responsividade
- [ ] Testar comportamento do modal no mobile (fechar por overlay e ESC funciona).
- [ ] Verificar botões e espaçamentos no painel do professor.

## 7) Fluxos funcionais
- Criar curso → Gerenciar curso → Adicionar aula → Criar quiz → Editar quiz
  - [ ] Confirmar cada etapa funciona e dados aparecem em listas.
- Acesso aluno
  - [ ] Matricular aluno (ou usar conta de aluno) e verificar progresso e quizzes.

## 8) Logs e erros
- [ ] Conferir `logs/app.log` para entradas relevantes após ações (criar curso, falha de login, uploads).
- [ ] Verificar erros PHP/Apache no `xampp` caso algo falhe.

## Notas finais
- Se algo falhar, capture console do navegador e a resposta da requisição (Network tab).
- Recomendado: testar em navegadores Chrome/Firefox e em modo responsivo.

---
Gerado automaticamente pelo assistente — 27/11/2025
