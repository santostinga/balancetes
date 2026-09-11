<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/database.sqlite';
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dir = dirname(self::path());
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $fresh = !file_exists(self::path());
        $pdo = new PDO('sqlite:' . self::path(), options: [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        self::$pdo = $pdo;

        if ($fresh) {
            Schema::install($pdo);
        }

        return $pdo;
    }

    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }
}
