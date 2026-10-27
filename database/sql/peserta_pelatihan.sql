CREATE DATABASE IF NOT EXISTS peserta_pelatihan_2401020055
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE peserta_pelatihan_2401020055;

DROP TABLE IF EXISTS peserta;
DROP TABLE IF EXISTS program_pelatihan;

-- Tabel master
CREATE TABLE program_pelatihan (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_program VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Tabel utama
CREATE TABLE peserta (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_peserta CHAR(10) NOT NULL UNIQUE,
    nama_peserta VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    usia TINYINT UNSIGNED NOT NULL,
    program_pelatihan_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT chk_peserta_usia CHECK (usia BETWEEN 17 AND 60),
    CONSTRAINT fk_peserta_program
        FOREIGN KEY (program_pelatihan_id)
        REFERENCES program_pelatihan (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Data awal
INSERT INTO program_pelatihan (id, nama_program) VALUES
    (1, 'Laravel Dasar'),
    (2, 'Basis Data');

INSERT INTO peserta
    (kode_peserta, nama_peserta, email, usia, program_pelatihan_id, created_at, updated_at)
VALUES
    ('PS00000001', 'Ayu Lestari',  'ayu@example.com',  20, 1, NOW(), NOW()),
    ('PS00000002', 'Dedi Pranata', 'dedi@example.com', 24, 1, NOW(), NOW()),
    ('PS00000003', 'Mira Ananda',  'mira@example.com', 22, 2, NOW(), NOW());