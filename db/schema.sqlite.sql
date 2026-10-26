-- SQLite version of db/schema.sql, used by the test suite and for quick local runs.

CREATE TABLE IF NOT EXISTS services (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL UNIQUE,
    cost       NUMERIC NOT NULL CHECK (cost >= 0),
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    apt_number TEXT    NOT NULL UNIQUE,
    name       TEXT    NOT NULL,
    email      TEXT    NOT NULL,
    phone      TEXT    NOT NULL,
    service_id INTEGER NOT NULL REFERENCES services (id),
    apt_date   TEXT    NOT NULL,
    apt_time   TEXT    NOT NULL,
    status     TEXT    NOT NULL DEFAULT 'pending'
               CHECK (status IN ('pending', 'confirmed', 'completed', 'cancelled')),
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_appointments_date ON appointments (apt_date);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON appointments (status);

CREATE TABLE IF NOT EXISTS messages (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL,
    email      TEXT    NOT NULL,
    subject    TEXT    NOT NULL,
    body       TEXT    NOT NULL,
    is_read    INTEGER NOT NULL DEFAULT 0 CHECK (is_read IN (0, 1)),
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_messages_created ON messages (created_at);
