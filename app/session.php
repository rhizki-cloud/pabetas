<?php

class PabetasDatabaseSessionHandler implements SessionHandlerInterface
{
    private int $ttl;

    public function __construct(int $ttl)
    {
        $this->ttl = max($ttl, 1800);
    }

    public function open(string $path, string $name): bool
    {
        $this->ensureTable();
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = db()->prepare('SELECT payload FROM app_sessions WHERE id = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (string) $row['payload'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->ttl);
        $stmt = db()->prepare('REPLACE INTO app_sessions (id, payload, expires_at, updated_at) VALUES (?, ?, ?, NOW())');
        return $stmt->execute([$id, $data, $expiresAt]);
    }

    public function destroy(string $id): bool
    {
        $stmt = db()->prepare('DELETE FROM app_sessions WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = db()->prepare('DELETE FROM app_sessions WHERE expires_at <= NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function ensureTable(): void
    {
        db()->exec("CREATE TABLE IF NOT EXISTS app_sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            payload MEDIUMTEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_app_sessions_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

function pabetas_boot_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    ]);

    if ((APP_ENV === 'production') && DB_HOST && DB_NAME) {
        session_set_save_handler(new PabetasDatabaseSessionHandler(SESSION_TIMEOUT), true);
    }

    session_start();
}
