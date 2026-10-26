<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/UserRepository.php';

// Controlador padre: lo que comparten UserController y TaskController
class MainController {
    protected UserRepository $userRepo;
    protected ?User $user = null; // el usuario que ha iniciado sesión

    public function __construct() {
        $this->userRepo = new UserRepository();
    }

    // Cada controlador hijo decide qué hacer con cada acción
    public function run(string $action) {}

    // Redirige a otra página y corta la ejecución
    protected function redirigir(string $url) {
        header('Location: ' . $url);
        exit();
    }

    // Redirige al login si no hay sesión o si el usuario ya no existe en la BD.
    // Si todo está bien, guarda el usuario en $this->user
    protected function soloLogueado() {
        if (isset($_SESSION['user_id'])) {
            $this->user = $this->userRepo->getById($_SESSION['user_id']);
        }
        if (!$this->user) {
            session_unset();
            $this->redirigir('index.php?c=user&action=login');
        }
    }
}
?>
