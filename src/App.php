<?php

declare(strict_types=1);

namespace App;

use App\Controllers\AccountController;
use App\Controllers\AreaController;
use App\Controllers\CompanyController;
use App\Controllers\DashboardController;
use App\Controllers\JournalController;
use App\Controllers\LedgerController;
use App\Controllers\ProductController;
use App\Controllers\ReportController;
use App\Controllers\SimulationController;
use App\Controllers\TrialBalanceController;
use App\Support\Database;
use App\Support\Router;

final class App
{
    public static function boot(): void
    {
        session_start();
        Database::pdo();

        $router = new Router();
        $router->get('/', [new DashboardController(), 'index']);
        $router->get('/empresa', [new CompanyController(), 'edit']);
        $router->post('/empresa', [new CompanyController(), 'save']);
        $router->get('/areas', [new AreaController(), 'index']);
        $router->post('/areas', [new AreaController(), 'save']);
        $router->post('/areas/{id}/delete', [new AreaController(), 'delete']);
        $router->get('/produtos', [new ProductController(), 'index']);
        $router->post('/produtos', [new ProductController(), 'save']);
        $router->post('/produtos/{id}/delete', [new ProductController(), 'delete']);
        $router->get('/plano-contas', [new AccountController(), 'index']);
        $router->get('/simulacao', [new SimulationController(), 'index']);
        $router->post('/simulacao/gerar', [new SimulationController(), 'generate']);
        $router->post('/simulacao/demo', [new SimulationController(), 'demo']);
        $router->get('/diario', [new JournalController(), 'index']);
        $router->get('/balancete', [new TrialBalanceController(), 'index']);
        $router->get('/razao', [new LedgerController(), 'index']);
        $router->get('/relatorio-anual', [new ReportController(), 'index']);

        $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
    }
}
