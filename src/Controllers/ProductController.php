<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\ServiceArea;
use App\Support\View;

final class ProductController
{
    public function index(): void
    {
        View::render('produtos/index', [
            'title' => 'Produtos e margens',
            'products' => Product::all(),
            'areas' => ServiceArea::all(),
            'edit' => isset($_GET['id']) ? Product::find((int) $_GET['id']) : null,
        ]);
    }

    public function save(): void
    {
        verify_csrf();
        try {
            if (ServiceArea::all() === []) {
                throw new \RuntimeException('Crie primeiro uma área de serviço.');
            }
            $id = (int) ($_POST['id'] ?? 0);
            if (!isset($_POST['activo']) && $id) {
                $_POST['activo'] = null;
            }
            if ($id) {
                Product::update($id, $_POST);
                flash('ok', 'Produto actualizado.');
            } else {
                Product::create($_POST);
                flash('ok', 'Produto criado.');
            }
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
        }
        redirect('/produtos');
    }

    public function delete(string $id): void
    {
        verify_csrf();
        try {
            Product::delete((int) $id);
            flash('ok', 'Produto eliminado.');
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
        }
        redirect('/produtos');
    }
}
