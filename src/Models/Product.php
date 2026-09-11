<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

final class Product
{
    public static function all(): array
    {
        return Database::run(
            'SELECT p.*, a.codigo AS area_codigo, a.nome AS area_nome
             FROM products p
             JOIN service_areas a ON a.id = p.area_id
             ORDER BY a.codigo, p.codigo'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        return Database::run('SELECT * FROM products WHERE id = ?', [$id])->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO products (area_id, codigo, nome, tipo, preco_min, preco_max, custo_unitario,
             margem_percent, volume_mensal_min, volume_mensal_max, sazonalidade, activo)
             VALUES (:area_id, :codigo, :nome, :tipo, :preco_min, :preco_max, :custo_unitario,
             :margem_percent, :volume_mensal_min, :volume_mensal_max, :sazonalidade, :activo)',
            self::payload($data)
        );

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload[':id'] = $id;
        Database::run(
            'UPDATE products SET area_id=:area_id, codigo=:codigo, nome=:nome, tipo=:tipo,
             preco_min=:preco_min, preco_max=:preco_max, custo_unitario=:custo_unitario,
             margem_percent=:margem_percent, volume_mensal_min=:volume_mensal_min,
             volume_mensal_max=:volume_mensal_max, sazonalidade=:sazonalidade, activo=:activo
             WHERE id = :id',
            $payload
        );
    }

    public static function delete(int $id): void
    {
        Database::run('DELETE FROM products WHERE id = ?', [$id]);
    }

    public static function defaultSeasonality(): array
    {
        return [0.92, 0.95, 1.05, 1.02, 1.04, 0.98, 0.88, 0.62, 1.08, 1.18, 1.22, 1.06];
    }

    private static function payload(array $data): array
    {
        $precoMin = (float) str_replace(',', '.', (string) $data['preco_min']);
        $precoMax = (float) str_replace(',', '.', (string) $data['preco_max']);
        $custo = (float) str_replace(',', '.', (string) $data['custo_unitario']);
        $precoMedio = ($precoMin + $precoMax) / 2;
        $margem = $precoMedio > 0 ? round((($precoMedio - $custo) / $precoMedio) * 100, 2) : 0;

        if (isset($data['margem_percent']) && $data['margem_percent'] !== '') {
            $margem = (float) str_replace(',', '.', (string) $data['margem_percent']);
        }

        $season = $data['sazonalidade'] ?? self::defaultSeasonality();
        if (is_string($season)) {
            $decoded = json_decode($season, true);
            $season = is_array($decoded) ? $decoded : self::defaultSeasonality();
        }

        return [
            ':area_id' => (int) $data['area_id'],
            ':codigo' => strtoupper(trim((string) $data['codigo'])),
            ':nome' => trim((string) $data['nome']),
            ':tipo' => ($data['tipo'] ?? 'servico') === 'produto' ? 'produto' : 'servico',
            ':preco_min' => $precoMin,
            ':preco_max' => $precoMax,
            ':custo_unitario' => $custo,
            ':margem_percent' => $margem,
            ':volume_mensal_min' => (int) $data['volume_mensal_min'],
            ':volume_mensal_max' => (int) $data['volume_mensal_max'],
            ':sazonalidade' => json_encode(array_values($season)),
            ':activo' => isset($data['activo']) ? 1 : 0,
        ];
    }
}
