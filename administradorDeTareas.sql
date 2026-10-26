CREATE DATABASE IF NOT EXISTS administradorDeTareas;
USE administradorDeTareas;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    contraseña VARCHAR(255) NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tareas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    prioridad ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    completada TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Qué tareas se han compartido con qué usuarios
CREATE TABLE IF NOT EXISTS tareas_compartidas (
    tarea_id INT NOT NULL,
    usuario_id INT NOT NULL,
    PRIMARY KEY (tarea_id, usuario_id),
    FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Etiquetas que cada usuario crea dentro de una tarea (una tarea puede tener muchas)
CREATE TABLE IF NOT EXISTS etiquetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarea_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    UNIQUE (tarea_id, nombre),
    FOREIGN KEY (tarea_id) REFERENCES tareas(id) ON DELETE CASCADE
);
