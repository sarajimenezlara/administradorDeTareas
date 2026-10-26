<?php

class User implements ArrayAccess
{
    private int $id;
    private string $nombre;
    private string $contraseña;
    private array $tareas = [];

    public function __construct(
        int $id,
        string $nombre,
        string $contraseña
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->contraseña = $contraseña;
    }

    // Métodos de ArrayAccess: permiten usar $user['campo'] como si fuera un array.
    // Los nombres offsetXxx los exige la interfaz y no se pueden cambiar.

    // isset($user['campo']) -> dice si el campo existe
    public function offsetExists(mixed $campo): bool
    {
        return in_array($campo, ['id', 'nombre', 'contraseña']);
    }

    // $user['campo'] -> devuelve el valor del campo
    public function offsetGet(mixed $campo): mixed
    {
        return match($campo) {
            'id' => $this->id,
            'nombre' => $this->nombre,
            'contraseña' => $this->contraseña,
            default => null
        };
    }

    // $user['campo'] = $valor -> guarda el valor en el campo
    public function offsetSet(mixed $campo, mixed $valor): void
    {
        match($campo) {
            'id' => $this->id = (int)$valor,
            'nombre' => $this->nombre = (string)$valor,
            'contraseña' => $this->contraseña = (string)$valor,
            default => null
        };
    }

    // unset($user['campo']) -> no hace nada, un usuario no puede quedarse sin campos
    public function offsetUnset(mixed $campo): void {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): void 
    {
        $this->nombre = $nombre;
    }

    public function getContraseña(): string
    {
        return $this->contraseña;
    }

    public function setContraseña(string $contraseña): void
    {
        $this->contraseña = $contraseña;
    }

    // Las tareas las carga el controlador con el TaskRepository
    public function setTareas(array $tareas): void
    {
        $this->tareas = $tareas;
    }

    public function getTareas(): array
    {
        return $this->tareas;
    }

    // Cuenta las tareas que todavía no están hechas
    public function getNumPendientes(): int
    {
        // array_filter se queda solo con las tareas para las que la función devuelve true
        // (las no completadas) y count cuenta cuántas quedan
        return count(array_filter($this->tareas, fn($t) => !$t->isCompletada()));
    }
}
?>