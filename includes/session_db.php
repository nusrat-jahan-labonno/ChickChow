<?php
// Keeps PHP sessions (login, cart) in the database instead of files.
// Needed on Vercel, because its servers are temporary and cannot keep session files.
class CkDbSessionHandler implements SessionHandlerInterface {
    private PDO $db;
    private string $lastData = '';
    private int $lastTime = 0;
    public function __construct(PDO $db) { $this->db = $db; }

    private function create(): void {
        $this->db->exec("CREATE TABLE IF NOT EXISTS ck_sessions (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            data MEDIUMTEXT NOT NULL,
            updated_at INT NOT NULL)");
    }
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }
    public function read(string $id): string|false {
        try {
            $st = $this->db->prepare("SELECT data FROM ck_sessions WHERE id = ?");
            $st->execute([$id]);
        } catch (PDOException $e) {            // table not created yet
            $this->create();
            return '';
        }
        $d = $st->fetchColumn();
        $this->lastData = $d === false ? '' : (string)$d;
        $this->lastTime = time();
        return $this->lastData;
    }
    public function write(string $id, string $data): bool {
        // nothing changed: skip the database write (faster page loads)
        if ($data === $this->lastData && $data !== '' && time() - $this->lastTime < 600) return true;
        $st = $this->db->prepare("REPLACE INTO ck_sessions (id, data, updated_at) VALUES (?, ?, ?)");
        return $st->execute([$id, $data, time()]);
    }
    public function destroy(string $id): bool {
        return $this->db->prepare("DELETE FROM ck_sessions WHERE id = ?")->execute([$id]);
    }
    public function gc(int $max_lifetime): int|false {
        $st = $this->db->prepare("DELETE FROM ck_sessions WHERE updated_at < ?");
        $st->execute([time() - $max_lifetime]);
        return $st->rowCount();
    }
}
