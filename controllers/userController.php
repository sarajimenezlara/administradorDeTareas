<?php
require_once __DIR__ . '/mainController.php';

// Todo lo de los usuarios: login, registro y cerrar sesión
class UserController extends MainController {

    // Decide qué método ejecutar según la acción de la URL
    public function run(string $action) {
        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'register':
                $this->register();
                break;
            case 'logout':
                $this->logout();
                break;
            default:
                $this->login();
                break;
        }
    }

    private function iniciarSesion(int $id, string $nombre) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['user_nombre'] = $nombre;
    }

    public function login() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $this->userRepo->getByNombre(trim($_POST['nombre'] ?? ''));
            if ($user && password_verify($_POST['contraseña'] ?? '', $user->getContraseña())) {
                $this->iniciarSesion($user->getId(), $user->getNombre());
                $this->redirigir('index.php?c=task&action=index');
            } else {
                $error = "Nombre o contraseña incorrectos";
            }
        }
        require_once 'view/loginView.phtml';
    }

    public function register() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $contraseña = $_POST['contraseña'] ?? '';

            if ($nombre === '' || $contraseña === '') {
                $error = "El nombre y la contraseña son obligatorios.";
            } elseif ($this->userRepo->getByNombre($nombre)) {
                $error = "Ese nombre de usuario ya existe.";
            } else {
                $user = new User(0, $nombre, $contraseña);
                $nuevoId = $this->userRepo->create($user);
                if ($nuevoId) {
                    $this->iniciarSesion($nuevoId, $nombre);
                    $this->redirigir('index.php?c=task&action=index');
                } else {
                    $error = "Error al registrar el usuario.";
                }
            }
        }
        require_once 'view/registerView.phtml';
    }

    public function logout() {
        session_destroy();
        $this->redirigir('index.php?c=user&action=login');
    }
}
?>
