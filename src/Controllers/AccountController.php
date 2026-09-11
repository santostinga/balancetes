<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Account;
use App\Support\View;

final class AccountController
{
    public function index(): void
    {
        View::render('contas/index', [
            'title' => 'Plano de contas',
            'accounts' => Account::all(),
        ]);
    }
}
