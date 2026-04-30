<?php

class StorageService
{
    private string $disk;
    private string $rootPath;

    public function __construct(?string $disk = null)
    {
        $configuredDisk = $disk ?: (function_exists('env_value') ? (string)env_value('STORAGE_DISK', 'local') : 'local');
        $this->disk = in_array($configuredDisk, ['local', 's3', 'gdrive'], true) ? $configuredDisk : 'local';
        $base = dirname(__DIR__, 2) . '/storage';

        if ($this->disk === 'local') {
            $this->rootPath = $this->resolveWritableRootPath([
                $base . '/backups',
                rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-storage-backups',
            ]);
        } else {
            $this->rootPath = $this->resolveWritableRootPath([
                $base . '/remote/' . $this->disk,
                rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-storage-' . $this->disk,
            ]);
        }

        $this->ensureDirectory($this->rootPath);
    }

    public function getDisk(): string
    {
        return $this->disk;
    }

    public function storeFile(string $sourcePath, string $key): array
    {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('Arquivo de origem não encontrado para armazenamento.');
        }

        $key = $this->sanitizeKey($key);
        $targetPath = $this->rootPath . '/' . $key;
        $this->ensureDirectory(dirname($targetPath));

        if (!@copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Não foi possível salvar o backup no storage configurado.');
        }

        return $this->buildDescriptor($key, $targetPath);
    }

    public function storeContents(string $contents, string $key): array
    {
        $key = $this->sanitizeKey($key);
        $targetPath = $this->rootPath . '/' . $key;
        $this->ensureDirectory(dirname($targetPath));

        if (@file_put_contents($targetPath, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Não foi possível gravar o conteúdo no storage configurado.');
        }

        return $this->buildDescriptor($key, $targetPath);
    }

    public function resolvePath(string $key): string
    {
        $key = $this->sanitizeKey($key);
        return $this->rootPath . '/' . $key;
    }

    public function exists(string $key): bool
    {
        return is_file($this->resolvePath($key));
    }

    public function read(string $key): string
    {
        $path = $this->resolvePath($key);
        $contents = @file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('Não foi possível ler o backup solicitado no storage.');
        }

        return $contents;
    }

    public function getDescriptor(string $key): array
    {
        $path = $this->resolvePath($key);
        if (!is_file($path)) {
            throw new RuntimeException('Arquivo não encontrado no storage configurado.');
        }

        return $this->buildDescriptor($this->sanitizeKey($key), $path);
    }

    private function buildDescriptor(string $key, string $path): array
    {
        return [
            'disk' => $this->disk,
            'key' => $key,
            'path' => $path,
            'size' => (int)(@filesize($path) ?: 0),
            'mime' => $this->detectMime($path),
            'provider_reference' => $this->disk === 'local' ? $path : $this->disk . '://' . $key,
        ];
    }

    private function detectMime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)(finfo_file($finfo, $path) ?: 'application/octet-stream');
                finfo_close($finfo);
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    private function sanitizeKey(string $key): string
    {
        $key = str_replace('\\', '/', trim($key));
        $key = ltrim($key, '/');
        if ($key === '' || str_contains($key, '../')) {
            throw new RuntimeException('Chave de storage inválida.');
        }

        return $key;
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException('Não foi possível preparar o diretório de storage.');
        }

        if (!is_writable($path)) {
            @chmod($path, 0777);
        }

        if (!is_writable($path)) {
            throw new RuntimeException('O diretório de storage não possui permissão de escrita.');
        }
    }

    private function resolveWritableRootPath(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($this->canUseDirectory($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Não foi possível localizar um diretório de storage com permissão de escrita.');
    }

    private function canUseDirectory(string $path): bool
    {
        if (is_dir($path)) {
            if (!is_writable($path)) {
                @chmod($path, 0777);
            }

            return is_writable($path);
        }

        $parent = dirname($path);
        if (!is_dir($parent)) {
            if (!@mkdir($parent, 0777, true) && !is_dir($parent)) {
                return false;
            }
            @chmod($parent, 0777);
        }

        if (!is_writable($parent)) {
            @chmod($parent, 0777);
        }

        if (!is_writable($parent)) {
            return false;
        }

        if (!@mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }

        @chmod($path, 0777);
        return is_writable($path);
    }
}
