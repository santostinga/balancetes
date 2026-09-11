<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class Schema
{
    public static function install(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE company (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    nome TEXT NOT NULL,
    nif TEXT,
    morada TEXT,
    cidade TEXT,
    pais TEXT DEFAULT 'Portugal',
    moeda TEXT DEFAULT 'EUR',
    exercicio INTEGER NOT NULL,
    capital_social REAL DEFAULT 50000,
    iva_taxa REAL DEFAULT 0.23,
    irc_taxa REAL DEFAULT 0.21,
    created_at TEXT NOT NULL
);

CREATE TABLE service_areas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    descricao TEXT,
    custo_fixo_mensal REAL NOT NULL DEFAULT 0,
    custo_pessoal_mensal REAL NOT NULL DEFAULT 0,
    activa INTEGER NOT NULL DEFAULT 1
);

CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    area_id INTEGER NOT NULL,
    codigo TEXT NOT NULL UNIQUE,
    nome TEXT NOT NULL,
    tipo TEXT NOT NULL DEFAULT 'servico',
    preco_min REAL NOT NULL,
    preco_max REAL NOT NULL,
    custo_unitario REAL NOT NULL,
    margem_percent REAL NOT NULL,
    volume_mensal_min INTEGER NOT NULL,
    volume_mensal_max INTEGER NOT NULL,
    sazonalidade TEXT,
    activo INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (area_id) REFERENCES service_areas(id) ON DELETE RESTRICT
);

CREATE TABLE accounts (
    codigo TEXT PRIMARY KEY,
    nome TEXT NOT NULL,
    classe INTEGER NOT NULL,
    natureza TEXT NOT NULL,
    tipo TEXT NOT NULL,
    nivel INTEGER NOT NULL DEFAULT 1,
    parent_codigo TEXT
);

CREATE TABLE journal_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    data TEXT NOT NULL,
    numero TEXT NOT NULL UNIQUE,
    descricao TEXT NOT NULL,
    origem TEXT NOT NULL DEFAULT 'simulacao',
    mes INTEGER NOT NULL,
    ano INTEGER NOT NULL,
    area_id INTEGER,
    product_id INTEGER,
    documento TEXT
);

CREATE TABLE journal_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entry_id INTEGER NOT NULL,
    account_codigo TEXT NOT NULL,
    debito REAL NOT NULL DEFAULT 0,
    credito REAL NOT NULL DEFAULT 0,
    descricao TEXT,
    FOREIGN KEY (entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_codigo) REFERENCES accounts(codigo)
);

CREATE TABLE simulation_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exercicio INTEGER NOT NULL,
    seed INTEGER NOT NULL,
    variabilidade REAL NOT NULL DEFAULT 0.12,
    incluir_iva INTEGER NOT NULL DEFAULT 1,
    taxa_recebimento REAL NOT NULL DEFAULT 0.82,
    taxa_pagamento REAL NOT NULL DEFAULT 0.75,
    created_at TEXT NOT NULL,
    resumo TEXT
);

CREATE INDEX idx_entries_periodo ON journal_entries (ano, mes);
CREATE INDEX idx_lines_conta ON journal_lines (account_codigo);
CREATE INDEX idx_lines_entry ON journal_lines (entry_id);
SQL);

        self::seedAccounts($pdo);
    }

    public static function seedAccounts(PDO $pdo): void
    {
        $accounts = [
            ['11', 'Caixa', 1, 'devedora', 'activo', 2, '1'],
            ['12', 'Depósitos à ordem', 1, 'devedora', 'activo', 2, '1'],
            ['21', 'Clientes', 2, 'devedora', 'activo', 2, '2'],
            ['22', 'Fornecedores', 2, 'credora', 'passivo', 2, '2'],
            ['231', 'Pessoal — remunerações a pagar', 2, 'credora', 'passivo', 3, '23'],
            ['238', 'Pessoal — encargos a pagar', 2, 'credora', 'passivo', 3, '23'],
            ['2411', 'IRC a pagar', 2, 'credora', 'passivo', 4, '241'],
            ['2431', 'IVA a pagar / a recuperar', 2, 'credora', 'passivo', 4, '243'],
            ['2432', 'IVA dedutível', 2, 'devedora', 'activo', 4, '243'],
            ['2433', 'IVA liquidado', 2, 'credora', 'passivo', 4, '243'],
            ['43', 'Equipamento básico', 4, 'devedora', 'activo', 2, '4'],
            ['438', 'Depreciações acumuladas — equipamento', 4, 'credora', 'activo', 3, '43'],
            ['51', 'Capital realizado', 5, 'credora', 'capital', 2, '5'],
            ['56', 'Resultado líquido do período', 5, 'credora', 'capital', 2, '5'],
            ['621', 'Electricidade', 6, 'devedora', 'gasto', 3, '62'],
            ['622', 'Rendas e alugueres', 6, 'devedora', 'gasto', 3, '62'],
            ['623', 'Comunicação', 6, 'devedora', 'gasto', 3, '62'],
            ['624', 'Trabalhos especializados', 6, 'devedora', 'gasto', 3, '62'],
            ['626', 'Deslocações e estadas', 6, 'devedora', 'gasto', 3, '62'],
            ['627', 'Publicidade e propaganda', 6, 'devedora', 'gasto', 3, '62'],
            ['632', 'Remunerações do pessoal', 6, 'devedora', 'gasto', 3, '63'],
            ['635', 'Encargos sobre remunerações', 6, 'devedora', 'gasto', 3, '63'],
            ['64', 'Gastos de depreciação e de amortização', 6, 'devedora', 'gasto', 2, '6'],
            ['68', 'Outros gastos', 6, 'devedora', 'gasto', 2, '6'],
            ['72', 'Prestações de serviços', 7, 'credora', 'rendimento', 2, '7'],
            ['78', 'Outros rendimentos', 7, 'credora', 'rendimento', 2, '7'],
            ['812', 'Imposto sobre o rendimento do período', 8, 'devedora', 'resultado', 3, '81'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO accounts (codigo, nome, classe, natureza, tipo, nivel, parent_codigo)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        foreach ($accounts as $account) {
            $stmt->execute($account);
        }
    }
}
