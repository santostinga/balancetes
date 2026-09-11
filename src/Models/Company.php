<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Company
{
    public static function get(): ?array
    {
        return Database::run('SELECT * FROM company WHERE id = 1')->fetch() ?: null;
    }

    public static function save(array $data): void
    {
        $existing = self::get();
        $payload = [
            ':nome' => trim((string) $data['nome']),
            ':nif' => trim((string) ($data['nif'] ?? '')),
            ':morada' => trim((string) ($data['morada'] ?? '')),
            ':cidade' => trim((string) ($data['cidade'] ?? '')),
            ':pais' => trim((string) ($data['pais'] ?? 'Portugal')),
            ':moeda' => trim((string) ($data['moeda'] ?? 'EUR')),
            ':exercicio' => (int) $data['exercicio'],
            ':capital_social' => (float) str_replace(',', '.', (string) $data['capital_social']),
            ':iva_taxa' => (float) str_replace(',', '.', (string) ($data['iva_taxa'] ?? 0.23)),
            ':irc_taxa' => (float) str_replace(',', '.', (string) ($data['irc_taxa'] ?? 0.21)),
        ];

        if ($existing) {
            Database::run(
                'UPDATE company SET nome=:nome, nif=:nif, morada=:morada, cidade=:cidade, pais=:pais,
                 moeda=:moeda, exercicio=:exercicio, capital_social=:capital_social, iva_taxa=:iva_taxa, irc_taxa=:irc_taxa
                 WHERE id = 1',
                $payload
            );
            return;
        }

        $payload[':created_at'] = date('c');
        Database::run(
            'INSERT INTO company (id, nome, nif, morada, cidade, pais, moeda, exercicio, capital_social, iva_taxa, irc_taxa, created_at)
             VALUES (1, :nome, :nif, :morada, :cidade, :pais, :moeda, :exercicio, :capital_social, :iva_taxa, :irc_taxa, :created_at)',
            $payload
        );
    }
}
