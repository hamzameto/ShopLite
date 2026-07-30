<?php

// Every request to the app goes through this ONE file.
// This is the "front controller" pattern in action.

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

$router = new Router();

// --- Routes ---
// For now, just a couple of simple test routes to prove everything is wired up.
// Real Product/Cart/Auth routes will replace these in the next stages.

$router->get('/', function () {
    header('Content-Type: application/json');
    echo json_encode(['message' => 'ShopLite API is running']);
});

$router->get('/hello/{name}', function ($params) {
    header('Content-Type: application/json');
    echo json_encode(['message' => "Hello, {$params['name']}!"]);
});

// --- Dispatch ---
// Look at the actual incoming request and run whichever route matches.
$router->dispatch();
