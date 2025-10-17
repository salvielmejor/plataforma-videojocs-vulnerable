-- SCRIPT PER AUTOMATITZAR LA CREACIÓ DE LA BASE DE DADES DEL PROJECTE

-- Crear la base de dades
CREATE DATABASE plataforma_videojocs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear un usuari insegur per l'aplicació
CREATE USER 'plataforma_user'@'%' IDENTIFIED BY '123456789a';

-- Concedir tots els privilegis a la base de dades i de manera global (vulnerable)
GRANT ALL PRIVILEGES ON *.* TO 'plataforma_user'@'%' WITH GRANT OPTION;

-- Aplicar els canvis
FLUSH PRIVILEGES;

-- Seleccionar la base de dades
USE plataforma_videojocs;

-- Taula d'usuaris
CREATE TABLE IF NOT EXISTS usuaris (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_usuari VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(100),
    data_registre DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de jocs
CREATE TABLE IF NOT EXISTS jocs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_joc VARCHAR(50) NOT NULL,
    descripcio TEXT,
    puntuacio_maxima INT DEFAULT 0,
    nivells_totals INT DEFAULT 1,
    actiu BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de nivells dels jocs
CREATE TABLE IF NOT EXISTS nivells_joc (
    id INT PRIMARY KEY AUTO_INCREMENT,
    joc_id INT NOT NULL,
    nivell INT NOT NULL,
    nom_nivell VARCHAR(50),
    configuracio_json JSON NOT NULL,
    puntuacio_minima INT DEFAULT 0,
    FOREIGN KEY (joc_id) REFERENCES jocs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de progrés d'usuari
CREATE TABLE IF NOT EXISTS progres_usuari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuari_id INT NOT NULL,
    joc_id INT NOT NULL,
    nivell_actual INT DEFAULT 1,
    puntuacio_maxima INT DEFAULT 0,
    partides_jugades INT DEFAULT 0,
    ultima_partida DATETIME,
    FOREIGN KEY (usuari_id) REFERENCES usuaris(id) ON DELETE CASCADE,
    FOREIGN KEY (joc_id) REFERENCES jocs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de partides
CREATE TABLE IF NOT EXISTS partides (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuari_id INT NOT NULL,
    joc_id INT NOT NULL,
    nivell_jugat INT NOT NULL,
    puntuacio_obtinguda INT NOT NULL,
    data_partida DATETIME DEFAULT CURRENT_TIMESTAMP,
    durada_segons INT DEFAULT 0,
    FOREIGN KEY (usuari_id) REFERENCES usuaris(id) ON DELETE CASCADE,
    FOREIGN KEY (joc_id) REFERENCES jocs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
