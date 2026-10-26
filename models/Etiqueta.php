<?php

class Etiqueta
{
    private int $id;
    private int $tarea_id;
    private string $nombre;

    public function __construct(int $id, int $tarea_id, string $nombre)
    {
        $this->id = $id;
        $this->tarea_id = $tarea_id;
        $this->nombre = $nombre;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTareaId(): int
    {
        return $this->tarea_id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }
}
?>
