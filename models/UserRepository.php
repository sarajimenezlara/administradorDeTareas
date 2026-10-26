<?php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/User.php';

class UserRepository
{
    private static function mapRow(array $row): User
    {
        return new User(
            (int)$row['id'],
            $row['nombre'],
            $row['contraseña']
        );
    }

    public function getById(int $id): ?User
    {
        $conn = db::connect();
        $stmt = $conn->prepare('SELECT * FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();
        return $row ? self::mapRow($row) : null;
    }

    // Usado en login y registro: busca por nombre de usuario
    public function getByNombre(string $nombre): ?User
    {
        $conn = db::connect();
        $stmt = $conn->prepare('SELECT * FROM usuarios WHERE nombre = ? LIMIT 1');
        $stmt->bind_param('s', $nombre);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        $conn->close();
        return $row ? self::mapRow($row) : null;
    }

    /**
     * Crea un usuario con la contraseña cifrada.
     * @return int id insertado
     */
    public function create(User $user): int
    {
        $conn = db::connect();
        $stmt = $conn->prepare('INSERT INTO usuarios (nombre, contraseña) VALUES (?, ?)');
        $nombre = $user->getNombre();
        $hash = password_hash($user->getContraseña(), PASSWORD_DEFAULT);
        $stmt->bind_param('ss', $nombre, $hash);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return $id;
    }
}
