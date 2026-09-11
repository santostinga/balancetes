<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ServiceArea;
use App\Support\View;

final class AreaController
{
    public function index(): void
    {
        View::render('areas/index', [
            'title' => 'Áreas de serviço',
            'areas' => ServiceArea::all(),
            'edit' => isset($_GET['id']) ? ServiceArea::find((int) $_GET['id']) : null,
        ]);
    }

    public function save(): void
    {
        verify_csrf();
        try {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id) {
                ServiceArea::update($id, $_POST);
                flash('ok', 'Área actualizada.');
            } else {
                ServiceArea::create($_POST);
                flash('ok', 'Área criada.');
            }
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
        }
        redirect('/areas');
    }

    public function delete(string $id): void
    {
        verify_csrf();
        try {
            ServiceArea::delete((int) $id);
            flash('ok', 'Área eliminada.');
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
        }
        redirect('/areas');
    }
}
