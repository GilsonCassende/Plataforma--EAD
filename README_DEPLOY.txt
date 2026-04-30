Arquivo ZIP gerado: Plataforma-EAD-deploy.zip
Localização: C:\xampp\htdocs\Plataforma-EAD-deploy.zip

Instruções rápidas para usar este ZIP em outro computador com XAMPP:
1) Copie `Plataforma-EAD-deploy.zip` para o novo PC (por pen-drive, rede, etc).
2) No novo PC, extraia o conteúdo para `C:\xampp\htdocs\Plataforma-EAD`.
3) Abra o Painel XAMPP e inicie Apache e MySQL.
4) Importe `migrations/schema.sql` via phpMyAdmin em `http://localhost/phpmyadmin` (ou usar `install.ps1` se preferir).
5) Ajuste `config/database.php` se as credenciais do MySQL forem diferentes.
6) Acesse `http://localhost/Plataforma-EAD/public/index.php`.

Script útil (PowerShell) para extrair e importar (executar como Administrador):

```powershell
# Extrair ZIP
Expand-Archive -Path 'C:\caminho\para\Plataforma-EAD-deploy.zip' -DestinationPath 'C:\xampp\htdocs\Plataforma-EAD' -Force

# Importar schema (ajuste usuário/senha se diferente)
& 'C:\xampp\mysql\bin\mysql.exe' -u root ead_platform < 'C:\xampp\htdocs\Plataforma-EAD\migrations\schema.sql'
```

Observações:
- Antes de importar, verifique se o banco `ead_platform` já existe; caso exista e contenha dados, o import pode falhar por conflitos.
- Se preferir não sobrescrever dados, crie uma nova database no phpMyAdmin e altere `config/database.php`.

Gerado automaticamente pelo utilitário de empacotamento do projeto.
