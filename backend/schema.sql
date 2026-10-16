CREATE TABLE entradas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo_qr VARCHAR(255) NOT NULL,
    entrada_consumida_at DATETIME NULL,
    consumicion_consumida_at DATETIME NULL,
    creada_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_entradas_codigo_qr (codigo_qr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;