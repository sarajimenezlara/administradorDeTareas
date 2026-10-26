<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/Task.php';

class TaskRepository
{
    // Un usuario puede usar una tarea si es suya o si se la han compartido.
    // Lleva dos ? y los dos son el id del usuario.
    // La parte de "id IN (SELECT ...)" es una subconsulta: primero saca la lista de ids
    // de tareas compartidas con el usuario y luego mira si el id de la tarea está en esa lista.
    // Se guarda en una constante para no repetir lo mismo en todas las consultas.
    private const PUEDE_USAR = '(usuario_id = ? OR id IN (SELECT tarea_id FROM tareas_compartidas WHERE usuario_id = ?))';

    // Convierte una fila de la BD en un objeto Task.
    // MySQL devuelve todo como texto, por eso se hacen los (int) y (bool)
    private static function mapRow(array $row): Task
    {
        return new Task(
            (int)$row['id'],
            (int)$row['usuario_id'],
            $row['titulo'],
            $row['descripcion'] ?? null,
            $row['prioridad'],
            (bool)$row['completada']
        );
    }

    /**
     * Tareas de un usuario (las suyas y las compartidas con él): primero las pendientes y luego las hechas,
     * y dentro de cada grupo de más a menos prioridad.
     * Si se pasa una etiqueta, solo devuelve las tareas que la tengan.
     * @return Task[]
     */
    public function getByUsuario(int $usuario_id, ?string $etiqueta = null): array
    {
        $conn = db::connect();
        // La consulta se va montando por trozos: el filtro de etiqueta solo se añade si hace falta.
        // La subconsulta saca los ids de las tareas que tienen una etiqueta con ese nombre
        $sql = "SELECT * FROM tareas WHERE " . self::PUEDE_USAR;
        if ($etiqueta !== null) {
            $sql .= " AND id IN (SELECT tarea_id FROM etiquetas WHERE nombre = ?)";
        }
        // FIELD() ordena la prioridad en el orden que le digas (alta, media, baja)
        // y no por orden alfabético, que pondría "alta, baja, media"
        $sql .= " ORDER BY completada ASC, FIELD(prioridad, 'alta', 'media', 'baja'), id DESC";
        $stmt = $conn->prepare($sql);
        // Tiene que haber una letra por cada ? y en el mismo orden: i = número, s = texto.
        // Con etiqueta hay un ? más, por eso hay dos bind_param distintos
        if ($etiqueta !== null) {
            $stmt->bind_param('iis', $usuario_id, $usuario_id, $etiqueta);
        } else {
            $stmt->bind_param('ii', $usuario_id, $usuario_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tasks[] = self::mapRow($row);
            }
        }
        $stmt->close();
        $conn->close();
        return $tasks;
    }

    // Devuelve una tarea solo si el usuario puede usarla
    public function getById(int $id, int $usuario_id): ?Task
    {
        $conn = db::connect();
        $stmt = $conn->prepare('SELECT * FROM tareas WHERE id = ? AND ' . self::PUEDE_USAR);
        $stmt->bind_param('iii', $id, $usuario_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();
        return $row ? self::mapRow($row) : null;
    }

    /** @return int id insertado */
    public function create(Task $task): int
    {
        $conn = db::connect();
        $stmt = $conn->prepare(
            'INSERT INTO tareas (usuario_id, titulo, descripcion, prioridad) VALUES (?, ?, ?, ?)'
        );
        $usuario_id = $task->getUsuarioId();
        $titulo = $task->getTitulo();
        $descripcion = $task->getDescripcion();
        $prioridad = $task->getPrioridad();
        $stmt->bind_param('isss', $usuario_id, $titulo, $descripcion, $prioridad);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return $id;
    }

    // Cambia el título, la descripción y la prioridad (solo si el usuario puede usarla)
    public function update(Task $task, int $usuario_id): bool
    {
        $conn = db::connect();
        $stmt = $conn->prepare(
            'UPDATE tareas SET titulo = ?, descripcion = ?, prioridad = ? WHERE id = ? AND ' . self::PUEDE_USAR
        );
        $titulo = $task->getTitulo();
        $descripcion = $task->getDescripcion();
        $prioridad = $task->getPrioridad();
        $id = $task->getId();
        // 3 textos (titulo, descripcion, prioridad) y 3 números (id y el usuario dos veces por PUEDE_USAR)
        $stmt->bind_param('sssiii', $titulo, $descripcion, $prioridad, $id, $usuario_id, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

    // Marca o desmarca una tarea como hecha (solo si el usuario puede usarla)
    public function setCompletada(int $id, int $usuario_id, bool $completada): bool
    {
        $conn = db::connect();
        $stmt = $conn->prepare('UPDATE tareas SET completada = ? WHERE id = ? AND ' . self::PUEDE_USAR);
        $valor = $completada ? 1 : 0;
        $stmt->bind_param('iiii', $valor, $id, $usuario_id, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

    // Borra una tarea ya hecha (solo si el usuario puede usarla).
    // Se borra para todos, y ON DELETE CASCADE quita también sus etiquetas y lo de compartirla
    public function deleteCompletada(int $id, int $usuario_id): bool
    {
        $conn = db::connect();
        $stmt = $conn->prepare('DELETE FROM tareas WHERE id = ? AND completada = 1 AND ' . self::PUEDE_USAR);
        $stmt->bind_param('iii', $id, $usuario_id, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

    // Comparte una tarea con otro usuario (si ya estaba compartida no hace nada)
    public function compartir(int $tarea_id, int $usuario_id): bool
    {
        $conn = db::connect();
        // La clave primaria es (tarea_id, usuario_id), así que no se puede repetir la misma pareja.
        // IGNORE hace que si ya existe no salte un error, simplemente no inserta nada
        $stmt = $conn->prepare('INSERT IGNORE INTO tareas_compartidas (tarea_id, usuario_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $tarea_id, $usuario_id);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }

    /**
     * Nombres de los usuarios con los que está compartida una tarea.
     * @return string[]
     */
    public function getCompartidaCon(int $tarea_id): array
    {
        $conn = db::connect();
        // JOIN une las dos tablas para sacar el nombre del usuario a partir de su id.
        // tc y u son apodos cortos de las tablas para no escribir el nombre entero
        $stmt = $conn->prepare(
            'SELECT u.nombre FROM tareas_compartidas tc
             JOIN usuarios u ON u.id = tc.usuario_id
             WHERE tc.tarea_id = ? ORDER BY u.nombre'
        );
        $stmt->bind_param('i', $tarea_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $nombres = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $nombres[] = $row['nombre'];
            }
        }
        $stmt->close();
        $conn->close();
        return $nombres;
    }
}
