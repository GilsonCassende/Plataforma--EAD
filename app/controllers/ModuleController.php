<?php

/**
 * Controller: ModuleController
 * Gerencia criação, edição e ordenação de módulos.
 */

class ModuleController
{
    private $pdo;
    private $moduleModel;
    private $courseModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        require_once __DIR__ . '/../models/Module.php';
        require_once __DIR__ . '/../models/Course.php';
        $this->moduleModel = new Module($pdo);
        $this->courseModel = new Course($pdo);
    }

    public function criar($courseId, $titulo, $descricao = '')
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $curso = $this->courseModel->obterPorId($courseId);

        if (!$curso || (int)($curso['teacher_id'] ?? 0) !== (int)($usuario['id'] ?? 0)) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para alterar este curso'];
        }

        if (($curso['course_structure'] ?? 'single_module') !== 'multi_module') {
            return ['sucesso' => false, 'mensagem' => 'Este curso usa módulo único e não permite novos módulos'];
        }

        return $this->moduleModel->criar((int)$courseId, $titulo, $descricao);
    }

    public function atualizar($moduleId, $titulo, $descricao = '')
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $module = $this->moduleModel->obterPorId($moduleId);
        if (!$module) {
            return ['sucesso' => false, 'mensagem' => 'Módulo não encontrado'];
        }

        $curso = $this->courseModel->obterPorId((int)$module['course_id']);
        if (!$curso || (int)($curso['teacher_id'] ?? 0) !== (int)($usuario['id'] ?? 0)) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para alterar este módulo'];
        }

        return $this->moduleModel->atualizar((int)$moduleId, $titulo, $descricao);
    }

    public function mover($moduleId, $direction)
    {
        $usuario = $_SESSION['usuario'] ?? null;
        $module = $this->moduleModel->obterPorId($moduleId);
        if (!$module) {
            return ['sucesso' => false, 'mensagem' => 'Módulo não encontrado'];
        }

        $curso = $this->courseModel->obterPorId((int)$module['course_id']);
        if (!$curso || (int)($curso['teacher_id'] ?? 0) !== (int)($usuario['id'] ?? 0)) {
            return ['sucesso' => false, 'mensagem' => 'Você não tem permissão para reordenar módulos'];
        }

        if (!in_array($direction, ['up', 'down'], true)) {
            return ['sucesso' => false, 'mensagem' => 'Direção inválida'];
        }

        return $this->moduleModel->mover((int)$moduleId, $direction);
    }

    public function obter($moduleId)
    {
        $module = $this->moduleModel->obterPorId($moduleId);
        if (!$module) {
            return ['sucesso' => false, 'mensagem' => 'Módulo não encontrado'];
        }

        return ['sucesso' => true, 'modulo' => $module];
    }
}
