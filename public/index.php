<?php

use DI\Container;
use Dotenv\Dotenv;
use Osana\Challenge\Http\Controllers\FindUsersController;
use Osana\Challenge\Http\Controllers\ShowUserController;
use Osana\Challenge\Http\Controllers\StoreUserController;
use Osana\Challenge\Http\Controllers\VersionController;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Slim\Factory\AppFactory;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// env vars
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// service container
$container = new Container();
$container->set(LocalUsersRepository::class, function () {
    return new LocalUsersRepository();
});
$container->set(GitHubUsersRepository::class, function () {
    return new GitHubUsersRepository();
});

// application
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(new WhoopsMiddleware(['enable' => env('API_ENV') === 'local']));

$app->setBasePath("/backend-applicants/public/index.php");
// routes
$app->get('/', VersionController::class);
$app->get('/users', FindUsersController::class);
$app->get('/users/{type}/{login}', ShowUserController::class);
$app->post('/users', StoreUserController::class);

$app->run();
