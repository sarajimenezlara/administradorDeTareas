<?php
require_once __DIR__ . '/mainController.php';
require_once __DIR__ . '/../models/TaskRepository.php';
require_once __DIR__ . '/../models/EtiquetaRepository.php';

// Todo lo de las tareas: lista, crear, editar, etiquetas, compartir, marcar como hecha y quitar
class TaskController extends MainController {
    private TaskRepository $taskRepo;
    private EtiquetaRepository $etiquetaRepo;

    public function __construct() {
        parent::__construct(); // crea el userRepo del MainController
        $this->taskRepo = new TaskRepository();
        $this->etiquetaRepo = new EtiquetaRepository();
    }

    // Decide qué método ejecutar según la acción de la URL
    public function run(string $action) {
        // Todas las páginas de tareas necesitan haber iniciado sesión
        $this->soloLogueado();

        switch ($action) {
            case 'index':
                $this->index();
                break;
            case 'nueva':
                $this->nuevaTarea();
                break;
            case 'editar':
                $this->editarTarea();
                break;
            case 'compartir':
                $this->compartirTarea();
                break;
            case 'completar':
                $this->completarTarea();
                break;
            case 'quitar':
                $this->quitarTarea();
                break;
            default:
                $this->index();
                break;
        }
    }

    // Vuelve a la lista de tareas
    private function volverALista() {
        $this->redirigir('index.php?c=task&action=index');
    }

    // Convierte el texto "clase, urgente, examen" en ['clase', 'urgente', 'examen'].
    // Quita los espacios de los lados, las vacías y las repetidas, y corta las de más de 50 letras
    private function separarEtiquetas(string $texto): array {
        $etiquetas = [];
        foreach (explode(',', $texto) as $nombre) {
            $nombre = trim($nombre);
            // Si tiene más de 50 letras se queda con las 50 primeras (la /u es para que cuente bien las tildes)
            $nombre = preg_replace('/^(.{50}).+$/u', '$1', $nombre);
            if ($nombre !== '' && !in_array($nombre, $etiquetas)) {
                $etiquetas[] = $nombre;
            }
        }
        return $etiquetas;
    }

    // Guarda en la BD las etiquetas de una tarea
    private function guardarEtiquetas(int $tarea_id, array $nombres) {
        foreach ($nombres as $nombre) {
            $this->etiquetaRepo->create(new Etiqueta(0, $tarea_id, $nombre));
        }
    }

    public function index() {
        // ?etiqueta=nombre filtra la lista; si no viene o está vacía, se ven todas
        $etiquetaActual = trim($_GET['etiqueta'] ?? '');
        if ($etiquetaActual === '') {
            $etiquetaActual = null;
        }

        $tareas = $this->taskRepo->getByUsuario($this->user->getId(), $etiquetaActual);
        // A cada tarea le cargamos sus etiquetas para poder pintarlas en la tarjeta
        foreach ($tareas as $task) {
            $task->setEtiquetas($this->etiquetaRepo->getByTarea($task->getId()));
        }

        // Carga las tareas en el usuario y la vista las saca de ahí
        $this->user->setTareas($tareas);
        $user = $this->user;
        require_once 'view/mainView.phtml';
    }

    public function nuevaTarea() {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $prioridad = $_POST['prioridad'] ?? 'media';

            if ($titulo === '') {
                $error = "El título es obligatorio.";
            } elseif (!array_key_exists($prioridad, Task::PRIORIDADES)) {
                $error = "Prioridad no válida.";
            } else {
                $task = new Task(
                    0,
                    $_SESSION['user_id'],
                    $titulo,
                    $descripcion !== '' ? $descripcion : null,
                    $prioridad
                );
                // create devuelve el id de la tarea nueva, que hace falta para guardar sus etiquetas
                $tarea_id = $this->taskRepo->create($task);
                $this->guardarEtiquetas($tarea_id, $this->separarEtiquetas($_POST['etiquetas'] ?? ''));
                $this->volverALista();
            }
        }
        require_once 'view/newTaskView.phtml';
    }

    public function editarTarea() {
        $error = null;
        $id = (int)($_GET['id'] ?? 0);
        $task = $this->taskRepo->getById($id, $_SESSION['user_id']);

        // Si la tarea no existe o el usuario no puede usarla, vuelve a la lista
        if (!$task) {
            $this->volverALista();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $prioridad = $_POST['prioridad'] ?? 'media';

            if ($titulo === '') {
                $error = "El título es obligatorio.";
            } elseif (!array_key_exists($prioridad, Task::PRIORIDADES)) {
                $error = "Prioridad no válida.";
            } else {
                $task->setTitulo($titulo);
                $task->setDescripcion($descripcion !== '' ? $descripcion : null);
                $task->setPrioridad($prioridad);
                $this->taskRepo->update($task, $_SESSION['user_id']);
                // Las etiquetas se cambian todas de golpe: se borran las que tenía y se guardan las del formulario.
                // Así las que se han quitado del texto desaparecen y las nuevas se añaden
                $this->etiquetaRepo->deleteByTarea($task->getId());
                $this->guardarEtiquetas($task->getId(), $this->separarEtiquetas($_POST['etiquetas'] ?? ''));
                $this->volverALista();
            }
        }
        $task->setEtiquetas($this->etiquetaRepo->getByTarea($task->getId()));
        require_once 'view/editTaskView.phtml';
    }

    // Comparte una tarea con otro usuario. Solo puede hacerlo quien la creó
    public function compartirTarea() {
        $error = null;
        $id = (int)($_GET['id'] ?? 0);
        $task = $this->taskRepo->getById($id, $_SESSION['user_id']);

        // getById ya comprueba que el usuario pueda usarla, pero para compartir además tiene que ser el creador
        if (!$task || $task->getUsuarioId() !== $this->user->getId()) {
            $this->volverALista();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $otro = $this->userRepo->getByNombre($nombre);

            if ($nombre === '') {
                $error = "Escribe el nombre de un usuario.";
            } elseif (!$otro) {
                $error = "Ese usuario no existe.";
            } elseif ($otro->getId() === $this->user->getId()) {
                $error = "No puedes compartirte una tarea a ti.";
            } else {
                $this->taskRepo->compartir($task->getId(), $otro->getId());
                $this->volverALista();
            }
        }
        $compartidaCon = $this->taskRepo->getCompartidaCon($task->getId());
        require_once 'view/shareTaskView.phtml';
    }

    // Marca una tarea como hecha o la vuelve a dejar pendiente
    public function completarTarea() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            // El formulario manda '1' (marcar como hecha) o '0' (dejar pendiente); esto lo convierte a true/false
            $completada = ($_POST['completada'] ?? '1') === '1';
            $this->taskRepo->setCompletada($id, $_SESSION['user_id'], $completada);
        }
        $this->volverALista();
    }

    // Quita de la lista una tarea que ya está hecha
    public function quitarTarea() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $this->taskRepo->deleteCompletada($id, $_SESSION['user_id']);
        }
        $this->volverALista();
    }
}
?>
