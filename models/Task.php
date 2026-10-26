<?php

class Task implements ArrayAccess
{
    public const PRIORIDADES = [
        'baja'  => 'Baja',
        'media' => 'Media',
        'alta'  => 'Alta',
    ];

    private int $id;
    private int $usuario_id;
    private string $titulo;
    private ?string $descripcion;
    private string $prioridad;
    private bool $completada;
    private array $etiquetas = []; // objetos Etiqueta, los carga el controlador

    public function __construct(
        int $id,
        int $usuario_id,
        string $titulo,
        ?string $descripcion = null,
        string $prioridad = 'media',
        bool $completada = false
    ) {
        $this->id = $id;
        $this->usuario_id = $usuario_id;
        $this->titulo = $titulo;
        $this->descripcion = $descripcion;
        $this->prioridad = $prioridad;
        $this->completada = $completada;
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['id', 'usuario_id', 'titulo', 'descripcion', 'prioridad', 'completada']);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match($offset) {
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'prioridad' => $this->prioridad,
            'completada' => $this->completada,
            default => null
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        match($offset) {
            'id' => $this->id = (int)$value,
            'usuario_id' => $this->usuario_id = (int)$value,
            'titulo' => $this->titulo = (string)$value,
            'descripcion' => $this->descripcion = $value,
            'prioridad' => $this->prioridad = (string)$value,
            'completada' => $this->completada = (bool)$value,
            default => null
        };
    }

    public function offsetUnset(mixed $offset): void {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    public function getTitulo(): string
    {
        return $this->titulo;
    }

    // Los setters devuelven un mensaje con lo que se ha cambiado
    public function setTitulo(string $titulo): string
    {
        $this->titulo = $titulo;
        return "Título cambiado a: " . $titulo;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): string
    {
        $this->descripcion = $descripcion;
        return $descripcion === null ? "Descripción borrada" : "Descripción cambiada";
    }

    public function getPrioridad(): string
    {
        return $this->prioridad;
    }

    public function setPrioridad(string $prioridad): string
    {
        $this->prioridad = $prioridad;
        return "Prioridad cambiada a: " . (self::PRIORIDADES[$prioridad] ?? $prioridad);
    }

    public function isCompletada(): bool
    {
        return $this->completada;
    }

    public function setCompletada(bool $completada): string
    {
        $this->completada = $completada;
        return $completada ? "Tarea marcada como hecha" : "Tarea marcada como pendiente";
    }

    /** @return Etiqueta[] */
    public function getEtiquetas(): array
    {
        return $this->etiquetas;
    }

    public function setEtiquetas(array $etiquetas): string
    {
        $this->etiquetas = $etiquetas;
        return "La tarea tiene " . count($etiquetas) . " etiqueta(s)";
    }
}
