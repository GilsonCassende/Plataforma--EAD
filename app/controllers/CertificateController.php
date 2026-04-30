<?php

/**
 * Controller: CertificateController
 * Orquestra emissão, visualização do aluno, verificação pública e PDF.
 */
class CertificateController
{
    private $pdo;
    private $certificateModel;
    private const PDF_TOKEN_TTL = 300;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        try {
            $modelPath = __DIR__ . '/../models/Certificate.php';
            if (!is_file($modelPath)) {
                throw new RuntimeException('Model de certificado não encontrado.');
            }

            require_once $modelPath;
            $this->certificateModel = new Certificate($pdo);
        } catch (Throwable $exception) {
            $this->logControllerError('construct', $exception);
            throw $exception;
        }
    }

    public function syncCourseCertificates($userId, $courseId)
    {
        try {
            return $this->certificateModel->syncCourseCertificates((int)$userId, (int)$courseId);
        } catch (Throwable $exception) {
            $this->logControllerError('syncCourseCertificates', $exception);
            return ['issued' => [], 'revoked' => 0, 'state' => []];
        }
    }

    public function getStudentCertificatePageData($userId, $courseId, $type = 'course', $moduleId = null)
    {
        try {
            $snapshot = $this->certificateModel->buildEligibilitySnapshot((int)$userId, (int)$courseId);
            $certificate = $this->certificateModel->getOwnedCertificate((int)$userId, (int)$courseId, $type, $moduleId);

            return [
                'mode' => 'owner',
                'requested_type' => $type,
                'requested_module_id' => $moduleId !== null ? (int)$moduleId : null,
                'snapshot' => $snapshot,
                'certificate' => $certificate ? $this->certificateModel->findByCode($certificate['certificate_code'] ?? '') : null,
            ];
        } catch (Throwable $exception) {
            $this->logControllerError('getStudentCertificatePageData', $exception);
            return [
                'mode' => 'owner',
                'requested_type' => $type,
                'requested_module_id' => $moduleId !== null ? (int)$moduleId : null,
                'snapshot' => [],
                'certificate' => null,
            ];
        }
    }

    public function getPublicVerificationData($code)
    {
        try {
            return $this->certificateModel->buildPublicVerificationData($code);
        } catch (Throwable $exception) {
            $this->logControllerError('getPublicVerificationData', $exception);
            return [
                'valid' => false,
                'code' => trim((string)$code),
                'message' => 'Não foi possível validar o certificado no momento.',
            ];
        }
    }

    public function listUserCertificatesForCourse($userId, $courseId)
    {
        try {
            return $this->certificateModel->listUserCertificatesForCourse((int)$userId, (int)$courseId);
        } catch (Throwable $exception) {
            $this->logControllerError('listUserCertificatesForCourse', $exception);
            return ['course' => null, 'modules' => []];
        }
    }

    public function downloadOwnedCertificatePdf($userId, $courseId, $type = 'course', $moduleId = null)
    {
        try {
            $metadata = $this->getOwnedCertificatePdfMetadata($userId, $courseId, $type, $moduleId);
            if (!$metadata) {
                return null;
            }

            return [
                'content' => $this->downloadCertificatePdfFromRenderUrl($metadata['render_url']),
                'filename' => $metadata['filename'],
                'certificate' => $metadata['certificate'],
            ];
        } catch (Throwable $exception) {
            $this->logControllerError('downloadOwnedCertificatePdf', $exception);
            return null;
        }
    }

    public function buildOwnedCertificatePdfDownloadUrl($userId, $courseId, $type = 'course', $moduleId = null)
    {
        $renderUrl = $this->buildOwnedCertificatePdfRenderUrl((int)$userId, (int)$courseId, $type, $moduleId);

        return BASE_URL . '/index.php?page=certificado-pdf&url=' . rawurlencode($renderUrl);
    }

    public function issuePdfRenderToken($userId, $courseId, $type = 'course', $moduleId = null, $expiresAt = null)
    {
        try {
            $expiresAt = $expiresAt ?: (time() + self::PDF_TOKEN_TTL);
            $payload = $this->buildPdfTokenPayload((int)$userId, (int)$courseId, $type, $moduleId, (int)$expiresAt);

            return [
                'expires' => (int)$expiresAt,
                'token' => hash_hmac('sha256', $payload, CERTIFICATE_PDF_SECRET),
            ];
        } catch (Throwable $exception) {
            $this->logControllerError('issuePdfRenderToken', $exception);
            return ['expires' => 0, 'token' => ''];
        }
    }

    public function validatePdfRenderToken($userId, $courseId, $type, $moduleId, $expiresAt, $token)
    {
        try {
            $userId = (int)$userId;
            $courseId = (int)$courseId;
            $moduleId = $moduleId !== null ? (int)$moduleId : null;
            $expiresAt = (int)$expiresAt;
            $token = trim((string)$token);

            if ($userId <= 0 || $courseId <= 0 || $expiresAt < time() || $token === '') {
                return false;
            }

            $expected = $this->issuePdfRenderToken($userId, $courseId, $type, $moduleId, $expiresAt);
            if (empty($expected['token']) || !hash_equals($expected['token'], $token)) {
                return false;
            }

            return $this->certificateModel->getOwnedCertificate($userId, $courseId, $type, $moduleId) ?: false;
        } catch (Throwable $exception) {
            $this->logControllerError('validatePdfRenderToken', $exception);
            return false;
        }
    }

    public function buildOwnedCertificatePdfRenderUrl($userId, $courseId, $type = 'course', $moduleId = null)
    {
        try {
            $token = $this->issuePdfRenderToken((int)$userId, (int)$courseId, $type, $moduleId);
            if (empty($token['token'])) {
                return '';
            }

            $query = [
                'page' => 'certificado',
                'course_id' => (int)$courseId,
                'type' => $type === 'module' ? 'module' : 'course',
                'pdf_render' => 1,
                'pdf_user_id' => (int)$userId,
                'pdf_expires' => (int)$token['expires'],
                'pdf_token' => $token['token'],
            ];

            if ($type === 'module' && $moduleId !== null) {
                $query['module_id'] = (int)$moduleId;
            }

            return rtrim(APP_URL, '/') . '/index.php?' . http_build_query($query);
        } catch (Throwable $exception) {
            $this->logControllerError('buildOwnedCertificatePdfRenderUrl', $exception);
            return '';
        }
    }

    public function downloadCertificatePdfFromRenderUrl($url)
    {
        try {
            if (!$this->isAllowedCertificateRenderUrl($url)) {
                throw new RuntimeException('URL de renderização do certificado inválida.');
            }

            return $this->renderPdfWithPuppeteer($url);
        } catch (Throwable $exception) {
            $this->logControllerError('downloadCertificatePdfFromRenderUrl', $exception);
            throw $exception;
        }
    }

    public function getOwnedCertificatePdfMetadata($userId, $courseId, $type = 'course', $moduleId = null)
    {
        try {
            $certificate = $this->certificateModel->getOwnedCertificate((int)$userId, (int)$courseId, $type, $moduleId);
            if (!$certificate) {
                return null;
            }

            $hydrated = $this->certificateModel->findByCode($certificate['certificate_code'] ?? '');
            $certificateData = $hydrated ?: $certificate;
            $payload = $this->certificateModel->buildPdfPayload($certificateData);

            return [
                'filename' => $payload['filename'],
                'certificate' => $certificateData,
                'render_url' => $this->buildOwnedCertificatePdfRenderUrl((int)$userId, (int)$courseId, $type, $moduleId),
            ];
        } catch (Throwable $exception) {
            $this->logControllerError('getOwnedCertificatePdfMetadata', $exception);
            return null;
        }
    }

    private function renderPdfWithPuppeteer($url)
    {
        $scriptPath = realpath(__DIR__ . '/../../scripts/generate-certificate-pdf.js');
        if ($scriptPath === false) {
            throw new RuntimeException('Script do Puppeteer não encontrado.');
        }

        $tempBase = tempnam(sys_get_temp_dir(), 'cert_pdf_');
        if ($tempBase === false) {
            throw new RuntimeException('Não foi possível criar arquivo temporário para o PDF.');
        }

        $pdfPath = $tempBase . '.pdf';
        @unlink($tempBase);

        $runtimeDir = $this->createUniqueRuntimeDirectory();

        try {
            $command = escapeshellcmd('/usr/bin/env')
                . ' -u LD_LIBRARY_PATH'
                . ' HOME=' . escapeshellarg($runtimeDir)
                . ' XDG_CONFIG_HOME=' . escapeshellarg($runtimeDir . DIRECTORY_SEPARATOR . '.config')
                . ' XDG_CACHE_HOME=' . escapeshellarg($runtimeDir . DIRECTORY_SEPARATOR . '.cache')
                . ' XDG_DATA_HOME=' . escapeshellarg($runtimeDir . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share')
                . ' TMPDIR=' . escapeshellarg($runtimeDir . DIRECTORY_SEPARATOR . 'tmp')
                . ' '
                . escapeshellarg((string)$this->resolveNodeBinary())
                . ' '
                . escapeshellarg($scriptPath)
                . ' --url '
                . escapeshellarg((string)$url)
                . ' --output '
                . escapeshellarg($pdfPath)
                . ' --chrome '
                . escapeshellarg((string)CHROME_BIN)
                . ' --user-data-dir '
                . escapeshellarg($runtimeDir . DIRECTORY_SEPARATOR . 'chrome-profile');

            $result = $this->executeCommandSafely($command);
            $output = $result['output'];
            $exitCode = $result['exit_code'];

            if ($exitCode !== 0 || !is_file($pdfPath)) {
                @unlink($pdfPath);
                throw new RuntimeException('Falha ao gerar o PDF via Puppeteer: ' . trim(implode("\n", $output)));
            }

            $content = (string)file_get_contents($pdfPath);
            @unlink($pdfPath);

            if ($content === '') {
                throw new RuntimeException('O PDF foi gerado vazio.');
            }

            return $content;
        } finally {
            $this->removeDirectoryRecursively($runtimeDir);
        }
    }

    private function buildPdfTokenPayload($userId, $courseId, $type, $moduleId, $expiresAt)
    {
        return implode('|', [
            'certificate-pdf',
            (int)$userId,
            (int)$courseId,
            $type === 'module' ? 'module' : 'course',
            $moduleId !== null ? (int)$moduleId : 0,
            (int)$expiresAt,
        ]);
    }

    private function resolveNodeBinary()
    {
        return defined('NODE_BIN') && NODE_BIN !== '' ? NODE_BIN : 'node';
    }

    private function isAllowedCertificateRenderUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return false;
        }

        $target = parse_url($url);
        $app = parse_url(rtrim(APP_URL, '/') . '/index.php');

        if (!is_array($target) || !is_array($app)) {
            return false;
        }

        $targetHost = strtolower((string)($target['host'] ?? ''));
        $appHost = strtolower((string)($app['host'] ?? ''));
        $targetPath = (string)($target['path'] ?? '');
        $appPath = (string)($app['path'] ?? '');

        if ($targetHost === '' || $appHost === '' || $targetHost !== $appHost || $targetPath !== $appPath) {
            return false;
        }

        parse_str((string)($target['query'] ?? ''), $query);

        return ($query['page'] ?? '') === 'certificado'
            && (string)($query['pdf_render'] ?? '') === '1'
            && !empty($query['course_id'])
            && !empty($query['pdf_user_id'])
            && !empty($query['pdf_expires'])
            && !empty($query['pdf_token']);
    }

    private function executeCommandSafely($command)
    {
        if (!$this->isExecAvailable()) {
            throw new RuntimeException('A execução de comandos está desabilitada no servidor.');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $output = [];
        $exitCode = 1;
        exec($command . ' 2>&1', $output, $exitCode);

        return [
            'output' => is_array($output) ? $output : [],
            'exit_code' => (int)$exitCode,
        ];
    }

    private function ensureRuntimeDirectory($runtimeDir)
    {
        $directories = [
            $runtimeDir,
            $runtimeDir . DIRECTORY_SEPARATOR . '.config',
            $runtimeDir . DIRECTORY_SEPARATOR . '.cache',
            $runtimeDir . DIRECTORY_SEPARATOR . '.local',
            $runtimeDir . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share',
            $runtimeDir . DIRECTORY_SEPARATOR . 'tmp',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException('Não foi possível preparar o diretório temporário do Puppeteer.');
            }

            @chmod($directory, 0777);
        }
    }

    private function createUniqueRuntimeDirectory()
    {
        $baseDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'plataforma-ead-puppeteer';
        $this->ensureRuntimeDirectory($baseDir);

        $uniqueDir = $baseDir . DIRECTORY_SEPARATOR . 'run-' . bin2hex(random_bytes(8));
        $this->ensureRuntimeDirectory($uniqueDir);

        $profileDir = $uniqueDir . DIRECTORY_SEPARATOR . 'chrome-profile';
        if (!is_dir($profileDir) && !@mkdir($profileDir, 0777, true) && !is_dir($profileDir)) {
            throw new RuntimeException('Não foi possível preparar o perfil temporário do Chrome.');
        }

        @chmod($profileDir, 0777);

        return $uniqueDir;
    }

    private function removeDirectoryRecursively($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectoryRecursively($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }

    private function isExecAvailable()
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        return !in_array('exec', $disabled, true);
    }

    private function logControllerError($context, Throwable $exception)
    {
        error_log('CertificateController::' . $context . ' ' . $exception->getMessage());
        if (function_exists('registrar_log')) {
            registrar_log('exception', 'CertificateController::' . $context . ' ' . $exception->getMessage());
        }
    }
}
