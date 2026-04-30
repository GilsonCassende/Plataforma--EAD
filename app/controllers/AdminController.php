<?php
/**
 * Controller: AdminController
 * Gerencia operações administrativas
 */

class AdminController {
    private $pdo;
    private $userModel;
    private $courseModel;
    private $quizModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/User.php';
        require_once __DIR__ . '/../models/Course.php';
        require_once __DIR__ . '/../models/Quiz.php';
        $this->userModel = new User($pdo);
        $this->courseModel = new Course($pdo);
        $this->quizModel = new Quiz($pdo);
    }

    /**
     * Obter estatísticas do sistema
     */
    public function obterEstatisticas() {
        $stats = [
            'total_usuarios' => $this->userModel->contar(),
            'total_alunos' => $this->userModel->contar('aluno'),
            'total_professores' => $this->userModel->contar('professor'),
            'total_cursos' => $this->courseModel->contar(),
            'total_cursos_ativos' => $this->courseModel->contar('ativo'),
            'total_quizzes' => $this->quizModel->obterResumoAdministrativo()['total_quizzes'] ?? 0
        ];

        return $stats;
    }

    /**
     * Listar todos os usuários
     */
    public function listarUsuarios($role = null) {
        return $this->userModel->listar($role);
    }

    /**
     * Listar todos os cursos
     */
    public function listarCursos() {
        return $this->courseModel->listar(null);
    }

    public function listarCursosPaginados($pagina = 1, $porPagina = 12, $busca = '', $status = null) {
        $pagina = max(1, (int)$pagina);
        $porPagina = max(1, (int)$porPagina);
        $offset = ($pagina - 1) * $porPagina;

        $cursos = $this->courseModel->listar($status, $porPagina, $offset, $busca);
        $total = $this->courseModel->contar($status, $busca);

        return [
            'cursos' => $cursos,
            'pagina' => $pagina,
            'total' => $total,
            'total_paginas' => max(1, (int)ceil($total / $porPagina)),
            'busca' => $busca,
            'status' => $status
        ];
    }

    public function listarQuizzesPaginados($pagina = 1, $porPagina = 12, $busca = '', $tipo = null)
    {
        $pagina = max(1, (int)$pagina);
        $porPagina = max(1, (int)$porPagina);
        $offset = ($pagina - 1) * $porPagina;

        $quizzes = $this->quizModel->listarAdministrativo($porPagina, $offset, $busca, $tipo);
        $total = $this->quizModel->contarAdministrativo($busca, $tipo);

        return [
            'quizzes' => $quizzes,
            'pagina' => $pagina,
            'total' => $total,
            'total_paginas' => max(1, (int)ceil($total / $porPagina)),
            'busca' => $busca,
            'tipo' => $tipo
        ];
    }

    public function obterResumoQuizzes()
    {
        return $this->quizModel->obterResumoAdministrativo();
    }

    /**
     * Deletar usuário
     */
    public function deletarUsuario($user_id) {
        if ($user_id == $_SESSION['usuario']['id']) {
            return ['sucesso' => false, 'mensagem' => 'Você não pode deletar sua própria conta'];
        }

        if ($this->userModel->deletar($user_id)) {
            return ['sucesso' => true, 'mensagem' => 'Usuário deletado'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro ao deletar usuário'];
    }

    /**
     * Ativar/Desativar curso
     */
    public function alterarStatusCurso($course_id, $status) {
        if (!in_array($status, ['ativo', 'inativo', 'rascunho'])) {
            return ['sucesso' => false, 'mensagem' => 'Status inválido'];
        }

        $curso = $this->courseModel->obterPorId($course_id);

        if ($this->courseModel->atualizar($course_id, $curso['titulo'], $curso['descricao'], $curso['categoria'], $status)) {
            return ['sucesso' => true, 'mensagem' => 'Status alterado'];
        }

        return ['sucesso' => false, 'mensagem' => 'Erro'];
    }

    /**
     * Obter relatório de alunos por curso
     */
    public function relatorioAlunosPorCurso($course_id) {
        require_once __DIR__ . '/../models/Enrollment.php';
        $enrollmentModel = new Enrollment($this->pdo);
        $alunos = $enrollmentModel->obterAlunosCurso($course_id);

        return [
            'curso' => $this->courseModel->obterPorId($course_id),
            'alunos' => $alunos,
            'total' => count($alunos)
        ];
    }
}
?>
