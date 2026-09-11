<?php

declare(strict_types=1);

namespace App;

use App\Controllers\CfGrupoController;
use App\Support\Router;

final class App
{
    public static function boot(): void
    {
        session_start();

        $router = new Router();
        $router->get('/', [new CfGrupoController(), 'index']);
        $router->get('/cf-grupo', [new CfGrupoController(), 'index']);
        $router->get('/cf-grupo/ver', [new CfGrupoController(), 'ver']);
        $router->get('/cf-grupo/pdf', [new CfGrupoController(), 'pdf']);

        $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
    }
}
