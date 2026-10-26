<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/Etiqueta.php';

class EtiquetaRepository
{
    private static function mapRow(array $row): Etiqueta
    {
        return new Etiqueta(
            (int)$row['id'],
            (int)$row['tarea_id'],
            $row['nombre']
        );
    }

    /**
     * Etiquetas de una tarea ordenadas por nombre.
     * @return Etiqueta[]
     */
    public function getByTarea(int $tarea_id): array
    {
        $connect = db::connect();
        $stmt = $connect->prepare('SELECT * FROM etiquetas WHERE tarea_id = ? ORDER BY nombre');
        $stmt->bind_param('i', $tarea_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $etiquetas = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $etiquetas[] = self::mapRow($row);
            }
        }
        $stmt->close();
        $connect->close();
        return $etiquetas;
    }

    // Añade una etiqueta a una tarea.
    // En la BD (tarea_id, nombre) es UNIQUE, así que una tarea no puede tener dos etiquetas iguales:
    // IGNORE hace que si ya la tiene no salte un error, simplemente no la añade otra vez
    public function create(Etiqueta $etiqueta): bool
    {
        $connect = db::connect();
        $stmt = $connect->prepare('INSERT IGNORE INTO etiquetas (tarea_id, nombre) VALUES (?, ?)');
        $tarea_id = $etiqueta->getTareaId();
        $nombre = $etiqueta->getNombre();
        $stmt->bind_param('is', $tarea_id, $nombre);
        $ok = $stmt->execute();
        $stmt->close();
        $connect->close();
        return $ok;
    }

    // Borra todas las etiquetas de una tarea
    public function deleteByTarea(int $tarea_id): bool
    {
        $connect = db::connect();
        $stmt = $connect->prepare('DELETE FROM etiquetas WHERE tarea_id = ?');
        $stmt->bind_param('i', $tarea_id);
        $ok = $stmt->execute();
        $stmt->close();
        $connect->close();
        return $ok;
    }
}
?>
