<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class ServiceArea
{
    public static function all(): array
    {
        return Database::run('SELECT * FROM service_areas ORDER BY codigo')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::run('SELECT * FROM service_areas WHERE id = ?', [$id])->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO service_areas (codigo, nome, descricao, custo_fixo_mensal, custo_pessoal_mensal, activa)
             VALUES (:codigo, :nome, :descricao, :custo_fixo_mensal, :custo_pessoal_mensal, :activa)',
            self::payload($data)
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload[':id'] = $id;
        Database::run(
            'UPDATE service_areas SET codigo=:codigo, nome=:nome, descricao=:descricao,
             custo_fixo_mensal=:custo_fixo_mensal, custo_pessoal_mensal=:custo_pessoal_mensal, activa=:activa
             WHERE id = :id',
            $payload
        );
    }

    public static function delete(int $id): void
    {
        $used = Database::run('SELECT COUNT(*) FROM products WHERE area_id = ?', [$id])->fetchColumn();
        if ((int) $used > 0) {
            throw new \RuntimeException('Não é possível eliminar uma área com produtos associados.');
        }
        Database::run('DELETE FROM service_areas WHERE id = ?', [$id]);
    }

    private static function payload(array $data): array
    {
        return [
            ':codigo' => strtoupper(trim((string) $data['codigo'])),
            ':nome' => trim((string) $data['nome']),
            ':descricao' => trim((string) ($data['descricao'] ?? '')),
            ':custo_fixo_mensal' => (float) str_replace(',', '.', (string) ($data['custo_fixo_mensal'] ?? 0)),
            ':custo_pessoal_mensal' => (float) str_replace(',', '.', (string) ($data['custo_pessoal_mensal'] ?? 0)),
            ':activa' => isset($data['activa']) ? 1 : 0,
        ];
    }
}
