<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Company;
use App\Support\View;

final class CompanyController
{
    public function edit(): void
    {
        View::render('empresa/form', [
            'title' => 'Empresa',
            'company' => Company::get(),
        ]);
    }

    public function save(): void
    {
        verify_csrf();
        try {
            Company::save($_POST);
            flash('ok', 'Dados da empresa gravados.');
        } catch (\Throwable $e) {
            flash('erro', $e->getMessage());
        }
        redirect('/empresa');
    }
}
