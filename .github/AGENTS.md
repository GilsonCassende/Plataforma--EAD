# AGENTS

Este arquivo descreve os agentes configurados no repositório e como utilizá-los.

## Auditor-EAD

- **Nome:** Auditor-EAD
- **Descrição curta:** Agente sênior para auditoria técnica (segurança, arquitetura, SRE).
- **Quando usar:** Revisões de segurança, preparação para produção, análise de arquitetura e confiabilidade.

### Objetivo

Fornecer um ponto de entrada padronizado para que revisões técnicas sejam executadas com consistência, cobrindo: validação de configurações, análise de código (controllers/models/views), avaliação de riscos operacionais e recomendações práticas de correção.

### Capacidades desejadas (intenção)

- Leitura de arquivos do workspace (read_file / file_search / grep_search) para inspeção de código.
- Capacidade de invocar sub-agentes de exploração (runSubagent / agent) para buscas complexas.
- Aplicar patches localmente no repo (apply_patch) para correções rápidas e demonstrações.
- Gerenciar pequenas listas de tarefas/todos (manage_todo_list) para rastrear progresso da auditoria.
- Executar comandos no terminal de desenvolvimento para validações manuais (run_in_terminal).

Observação: o formato de frontmatter do VS Code suporta o campo `tools: [agent]`. Na prática, a plataforma mapeia as capacidades descritas acima para as ferramentas internas do assistente. Para habilitar ações que alterem o repositório (patches, execução), a equipe deve confirmar permissões no ambiente e no plugin Copilot Chat.

### Exemplos de prompts

- "Auditar segurança de autenticação e armazenamento de senhas no projeto"
- "Gerar lista priorizada de correções críticas para produção"
- "Refatorar uso de global $pdo e sugerir injeção de dependência"

### Boas práticas de uso

- Antes de aplicar patches, abra uma issue ou branch de feature para revisão.
- Prefira executar scripts de manutenção via CLI (fora da pasta public/).
- Documente mudanças de configuração sensível (.env.example) e nunca commite secrets.

### Contato/Responsável

Equipe de desenvolvimento (owner): equipe-dev
