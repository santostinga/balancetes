<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Account
{
    public static function all(): array
    {
        return Database::run('SELECT * FROM accounts ORDER BY codigo')->fetchAll();
    }

    public static function map(): array
    {
        $map = [];
        foreach (self::all() as $account) {
            $map[$account['codigo']] = $account;
        }

        return $map;
    }
}
