<?php
$pageName = preg_replace('/[^a-z0-9\-]/i', '-', (string)($_GET['page'] ?? 'home'));
$bodyClass = 'app-shell page-' . strtolower($pageName) . (!empty($pdfExport) ? ' page-pdf-export' : '');
$csrfToken = function_exists('gerar_csrf') ? gerar_csrf() : '';
$usuarioAtual = $_SESSION['usuario'] ?? null;
$estaAutenticado = !empty($usuarioAtual);
$usuarioNome = (string)($usuarioAtual['nome'] ?? 'Conta');
$usuarioRole = (string)($usuarioAtual['role'] ?? 'visitante');
$usuarioFoto = (string)($usuarioAtual['fotografia'] ?? '');
$roleLabel = [
    'admin' => 'Administrador',
    'professor' => 'Professor',
    'aluno' => 'Aluno',
];
$flashMensagem = $_SESSION['mensagem'] ?? null;
$flashErro = $_SESSION['erro'] ?? null;
unset($_SESSION['mensagem'], $_SESSION['erro']);
$publicRoot = realpath(__DIR__ . '/../../public');
$assetUrl = static function (string $relativePath) use ($publicRoot): string {
    $relativePath = ltrim($relativePath, '/');
    $diskPath = $publicRoot . '/' . $relativePath;
    $version = is_file($diskPath) ? (string) filemtime($diskPath) : (string) time();
    return BASE_URL . '/' . $relativePath . '?v=' . rawurlencode($version);
};
$debugCssEnabled = isset($_GET['debug_css']) && $_GET['debug_css'] === '1';
$metaDescription = trim((string)($meta_description ?? ''));
$metaRobots = trim((string)($meta_robots ?? 'index,follow'));
$ogTitle = trim((string)($meta_og_title ?? (string)$titulo));
$ogDescription = trim((string)($meta_og_description ?? $metaDescription));
$ogUrl = trim((string)($meta_og_url ?? ''));
$ogType = trim((string)($meta_og_type ?? 'website'));
$ogImage = trim((string)($meta_og_image ?? ''));

$navLinks = [];
if ($estaAutenticado) {
    if ($usuarioRole === 'admin') {
        $navLinks = [
            ['label' => 'Dashboard', 'href' => BASE_URL . '/index.php?page=dashboard', 'page' => 'dashboard'],
            ['label' => 'Cursos', 'href' => BASE_URL . '/index.php?page=admin-cursos', 'page' => 'admin-cursos'],
            ['label' => 'Quizzes', 'href' => BASE_URL . '/index.php?page=admin-quizzes', 'page' => 'admin-quizzes'],
        ];
    } elseif ($usuarioRole === 'professor') {
        $navLinks = [
            ['label' => 'Início', 'href' => BASE_URL . '/index.php', 'page' => 'home'],
            ['label' => 'Cursos', 'href' => BASE_URL . '/index.php?page=cursos', 'page' => 'cursos'],
            ['label' => 'Dashboard', 'href' => BASE_URL . '/index.php?page=dashboard', 'page' => 'dashboard'],
        ];
    } else {
        $navLinks = [
            ['label' => 'Início', 'href' => BASE_URL . '/index.php', 'page' => 'home'],
            ['label' => 'Cursos', 'href' => BASE_URL . '/index.php?page=cursos', 'page' => 'cursos'],
            ['label' => 'Dashboard', 'href' => BASE_URL . '/index.php?page=dashboard', 'page' => 'dashboard'],
        ];
    }
} else {
    $navLinks = [
        ['label' => 'Início', 'href' => BASE_URL . '/index.php', 'page' => 'home'],
        ['label' => 'Cursos', 'href' => BASE_URL . '/index.php?page=cursos', 'page' => 'cursos'],
        ['label' => 'Login', 'href' => BASE_URL . '/index.php?page=login', 'page' => 'login'],
    ];
}

$pageCssMap = [
    'home' => ['home'],
    'dashboard' => ['dashboard', 'admin'],
    'cursos' => ['cursos'],
    'curso' => ['curso'],
    'aula' => ['aula'],
    'quiz' => ['quiz'],
    'certificado' => ['certificado'],
    'certificacao-profissional' => ['certificacao-info'],
    'certificado-pdf' => ['certificado-pdf'],
    'gerenciar-curso' => ['gerenciar-curso'],
    'alunos-curso' => ['alunos-curso'],
    'criar-curso' => ['criar-curso'],
    'criar-aula' => ['criar-aula'],
    'criar-quiz' => ['criar-quiz'],
    'criar_quiz' => ['criar-quiz'],
    'meus-cursos' => ['meus-cursos'],
    'meus-alunos' => ['meus-alunos'],
    'perfil' => ['perfil'],
    'admin-cursos' => ['admin'],
    'admin-quizzes' => ['admin'],
];
$pageCssFiles = $pageCssMap[$pageName] ?? [];
?><!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars((string)$titulo, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php if ($metaDescription !== ''): ?>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <meta name="robots" content="<?php echo htmlspecialchars($metaRobots, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($ogTitle !== ''): ?>
    <meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($ogDescription !== ''): ?>
    <meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($ogUrl !== ''): ?>
    <meta property="og:url" content="<?php echo htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:alt" content="Certificado verificado na Plataforma EAD">
    <?php endif; ?>
    <meta property="og:type" content="<?php echo htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="Plataforma EAD">
    <?php if (empty($pdfExport)): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl('css/system.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl('css/style.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl('css/responsive.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php foreach ($pageCssFiles as $cssPage): ?>
        <?php $cssPath = __DIR__ . '/../../public/css/pages/' . $cssPage . '.css'; ?>
        <?php if (is_file($cssPath)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl('css/pages/' . $cssPage . '.css'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($debugCssEnabled): ?>
    <style>body{background:red !important;}</style>
    <?php endif; ?>
    <?php else: ?>
    <?php $pdfCss = $pageName === 'certificado-pdf' ? 'css/pages/certificado-pdf.css' : 'css/pages/certificado.css'; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl('css/system.css'), ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetUrl($pdfCss), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8'); ?>">
<?php if (empty($pdfExport)): ?>
    <header class="navbar">
        <div class="navbar-container">
            <a class="navbar-logo" href="<?php echo BASE_URL; ?>/index.php">
                <span class="navbar-logo-mark">E</span>
                <span class="navbar-logo-copy">
                    <strong>EAD Platform</strong>
                    <small>Aprendizado profissional</small>
                </span>
            </a>

            <button class="hamburger" type="button" aria-expanded="false" aria-label="Abrir menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="nav-menu" aria-label="Principal">
                <div class="nav-links">
                    <?php foreach ($navLinks as $link): ?>
                        <a class="nav-item<?php echo ($pageName === $link['page'] || ($pageName === 'home' && $link['page'] === 'home')) ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="nav-cta-group">
                    <?php if ($estaAutenticado): ?>
                        <div class="nav-dropdown">
                            <button class="btn-ghost nav-user-chip nav-account-chip dropdown-toggle" type="button" aria-expanded="false">
                                <span class="nav-user-meta">
                                    <small>Minha conta</small>
                                    <strong><?php echo htmlspecialchars($usuarioNome, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </span>
                                <span class="nav-user-avatar">
                                    <?php if ($usuarioFoto !== ''): ?>
                                        <img src="<?php echo htmlspecialchars(upload_image_url($usuarioFoto, ['w' => 96, 'h' => 96, 'fit' => 'cover', 'q' => 80]), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($usuarioNome, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars(mb_strtoupper(mb_substr($usuarioNome, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </span>
                            </button>
                            <div class="dropdown-menu">
                                <a href="<?php echo BASE_URL; ?>/index.php?page=perfil">Meu perfil</a>
                                <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard">Dashboard</a>
                                <a href="<?php echo BASE_URL; ?>/index.php?page=logout" data-confirm-link="Deseja realmente sair?">Sair</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a class="btn-ghost nav-role-cta nav-role-cta--teacher" href="<?php echo BASE_URL; ?>/index.php?page=registro-professor">Tornar-se instrutor</a>
                        <a class="btn-ghost nav-role-cta nav-role-cta--student" href="<?php echo BASE_URL; ?>/index.php?page=registro">Tornar-se aluno</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
<?php endif; ?>

<?php if (!empty($pdfExport)): ?>
    <?php echo $conteudo; ?>
<?php else: ?>
    <main class="main-container">
        <div class="container">
            <?php if (!empty($flashMensagem)): ?>
                <div class="server-flash-payload" data-server-toast data-toast-type="success" hidden><?php echo htmlspecialchars((string)$flashMensagem, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if (!empty($flashErro)): ?>
                <div class="server-flash-payload" data-server-toast data-toast-type="error" hidden><?php echo htmlspecialchars((string)$flashErro, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
        </div>
        <?php echo $conteudo; ?>
    </main>

    <div id="app-modal" class="modal-overlay hidden" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Fechar modal">&times;</button>
            </div>
            <div id="modal-body" class="modal-body"></div>
            <div id="modal-footer" class="modal-footer"></div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($pdfExport)): ?>
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <a class="footer-logo" href="<?php echo BASE_URL; ?>/index.php">Plataforma EAD</a>
                <p>Ensino online com cursos, aulas, quizzes e certificados em um único ambiente.</p>
            </div>

            <div class="footer-columns">
                <div class="footer-column">
                    <h3>Navegação</h3>
                    <a href="<?php echo BASE_URL; ?>/index.php">Início</a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=cursos">Cursos</a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard">Dashboard</a>
                </div>
                <div class="footer-column">
                    <h3>Conta</h3>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=perfil">Perfil</a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=login">Login</a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=registro">Registro</a>
                </div>
                <div class="footer-column">
                    <h3>Suporte</h3>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=certificado">Certificados</a>
                    <a href="<?php echo BASE_URL; ?>/index.php?page=dashboard">Painel</a>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Plataforma EAD</p>
                <p class="footer-meta">Experiência estável mesmo em modo de recuperação.</p>
            </div>
        </div>
    </footer>
<?php endif; ?>

<?php if (empty($pdfExport)): ?>
    <script src="<?php echo htmlspecialchars($assetUrl('js/ui.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="<?php echo htmlspecialchars($assetUrl('js/main.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php
    $pageScript = __DIR__ . '/../../public/js/pages/' . $pageName . '.js';
    if (is_file($pageScript)):
    ?>
    <script src="<?php echo htmlspecialchars($assetUrl('js/pages/' . $pageName . '.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>
