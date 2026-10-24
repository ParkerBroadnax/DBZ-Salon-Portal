-- MySQL / MariaDB schema for the salon portal.
-- Replaces the empty beauty.sql from the original upload.

CREATE TABLE IF NOT EXISTS services (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120)  NOT NULL UNIQUE,
    cost       DECIMAL(8, 2) NOT NULL CHECK (cost >= 0),
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS appointments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apt_number CHAR(9)       NOT NULL UNIQUE,
    name       VARCHAR(120)  NOT NULL,
    email      VARCHAR(190)  NOT NULL,
    phone      VARCHAR(30)   NOT NULL,
    service_id INT UNSIGNED  NOT NULL,
    apt_date   DATE          NOT NULL,
    apt_time   TIME          NOT NULL,
    status     VARCHAR(20)   NOT NULL DEFAULT 'pending'
               CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled')),
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services (id),
    INDEX idx_appointments_date (apt_date),
    INDEX idx_appointments_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
