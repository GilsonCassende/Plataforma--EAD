# Instruções para executar a Plataforma EAD em outro computador (XAMPP)

Este guia descreve passo-a-passo como colocar o projeto `Plataforma-EAD` para rodar em um computador Windows que já tenha o XAMPP instalado.

Pré-requisitos
- Windows (testado com Windows 10/11)
- XAMPP instalado (Apache + MySQL / MariaDB + PHP)
- Acesso administrativo para copiar arquivos para `C:\xampp\htdocs\`
- Navegador moderno

Resumo rápido
1. Copiar a pasta do projeto para `C:\xampp\htdocs\Plataforma-EAD`
2. Iniciar o XAMPP (Apache e MySQL)
3. Criar o banco de dados e importar `migrations/schema.sql`
4. Ajustar `config/database.php` se necessário
5. Acessar `http://localhost/Plataforma-EAD/public/index.php`

Passo a passo detalhado

1) Copiar os arquivos do projeto
- Se você já tem a pasta compactada (zip) copiar/descompactar para `C:\xampp\htdocs\Plataforma-EAD`.
  - Exemplo PowerShell (supondo `Plataforma-EAD.zip` no `Downloads`):

```powershell
cd $HOME\Downloads
Expand-Archive Plataforma-EAD.zip -DestinationPath C:\xampp\htdocs\Plataforma-EAD -Force
```

- Se for clonar via Git, clone na pasta `htdocs`:

```powershell
cd C:\xampp\htdocs
git clone <repo-url> Plataforma-EAD
```

2) Iniciar XAMPP
- Abra o Painel de Controle do XAMPP e inicie **Apache** e **MySQL** (botões "Start").
- Verifique que as portas necessárias (80/443 para Apache e 3306 para MySQL) não estão ocupadas.

3) Criar banco de dados e importar schema
- A forma mais simples: usar o phpMyAdmin (http://localhost/phpmyadmin)
  - Criar database: `ead_platform` (ou qualquer outro nome, mas lembre de atualizar `config/database.php`).
  - Importar `migrations/schema.sql` (menu Importar → escolher o arquivo → Executar).

- Alternativa via CLI (PowerShell):
  - Abra PowerShell como Administrador e execute:

```powershell
cd C:\xampp\htdocs\Plataforma-EAD\migrations
# Cria DB e importa (substitua user/pass se diferente)
# Cria database
& 'C:\\xampp\\mysql\\bin\\mysql.exe' -u root -e "CREATE DATABASE IF NOT EXISTS ead_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# Importa schema
& 'C:\\xampp\\mysql\\bin\\mysql.exe' -u root ead_platform < schema.sql
```

4) Ajustar `config/database.php` (se necessário)
- Abra `config/database.php` e verifique as constantes:
  - `DB_HOST` (normalmente `localhost`)
  - `DB_USER` (normalmente `root` no XAMPP)
  - `DB_PASS` (no XAMPP padrão é vazio `''`)
  - `DB_NAME` (por padrão `ead_platform`)

Exemplo (padrão XAMPP):
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ead_platform');
```

Se você escolheu outro nome de database ou outro usuário, atualize aqui.

5) Permissões e PHP settings (Windows)
- Normalmente em Windows não é necessário alterar permissões. Garanta que o diretório e arquivos estão acessíveis pelo Apache.
- Verifique se as extensões PHP necessárias estão habilitadas (em `php.ini`):
  - `pdo_mysql` (essencial)
  - `mbstring` (recomendado)
  - `openssl` (opcional, para futuras integrações)
- No XAMPP Panel clique em Config > PHP (php.ini) e verifique as linhas `extension=pdo_mysql` não estejam comentadas.

6) Scripts úteis (opcionais)
- Se o banco foi importado mas as senhas de teste ainda estiverem com placeholder, rode o script de correção:
  - `http://localhost/Plataforma-EAD/public/corrigir.php` — atualiza as hashes de teste para `senha123` e testa login.
- Se quiser resetar e reimportar o schema automaticamente (apaga dados):
  - `http://localhost/Plataforma-EAD/public/inicializar.php` (use com cuidado — exclui banco atual)
- Para diagnóstico rápido:
  - `http://localhost/Plataforma-EAD/public/diagnostico.php`

7) Acessar a aplicação
- Abra: `http://localhost/Plataforma-EAD/public/index.php`
- Credenciais de teste (após importar/schema corrigido):
  - Admin: `admin@ead.com` / `senha123`
  - Professores / Alunos: conforme inseridos no schema

8) Problemas comuns e soluções
- Página em branco / erro de conexão:
  - Verifique Apache e MySQL estão rodando.
  - Verifique `config/database.php` credenciais.
  - Habilite display_errors temporariamente em `php.ini` para ver mensagens (apenas em dev): `display_errors = On`.

- Erro: "session_start(): Ignoring session_start() because a session is already active"
  - É normal se há múltiplos `session_start()` sem checagem; a versão atual do projeto tem proteção. Se ocorrer, certifique que a sessão é iniciada apenas em `public/index.php`.

- Erro de permissões em uploads:
  - Em Windows, verifique se a pasta `uploads/` existe e o Apache tem permissão de escrita. Se não, crie ela manualmente.

9) Configurar Virtual Host (opcional, mais profissional)
- Para acessar como `http://plataforma-ead.local` configure Apache VirtualHost e adicione entrada no `C:\Windows\System32\drivers\etc\hosts`.
- Exemplo (httpd-vhosts.conf):
```
<VirtualHost *:80>
    ServerName plataforma-ead.local
    DocumentRoot "C:/xampp/htdocs/Plataforma-EAD/public"
    <Directory "C:/xampp/htdocs/Plataforma-EAD/public">
        Require all granted
        AllowOverride All
    </Directory>
</VirtualHost>
```
- Depois adicione no hosts:
```
127.0.0.1 plataforma-ead.local
```
- Reinicie Apache.

10) Recomendação para produção
- Não rodar com `display_errors` ativo.
- Use senha forte para o DB e crie um usuário com permissões limitadas.
- Considere mover `config/database.php` para uso de variáveis de ambiente no deploy.

11) Verificar integridade após instalação
- Faça login com `admin@ead.com` / `senha123` e navegue até Dashboard.
- Crie um curso, adicione ao menos uma aula e um quiz para testar os fluxos.
- Teste matrícula com usuário aluno e verifique progresso.

12) Contato e debugging
- Se algo falhar, cole aqui o erro exato mostrado na tela (ou copie o conteúdo do `apache error log` em `C:\xampp\apache\logs\error.log`).

---

Arquivo criado: `INSTALL_ON_XAMPP.md`

Se quiser, eu também:
- Gero um script PowerShell automatizado que copia os arquivos e importa o schema (posso criar `install.ps1`),
- Gero instruções para criar um instalador ZIP com tudo já preparado,
- Ou crio um pequeno vídeo/roteiro passo-a-passo para sua apresentação.

Diga qual dessas opções prefere que eu faça a seguir.