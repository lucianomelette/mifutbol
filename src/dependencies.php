<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// Service factory for the ORM
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['settings']['db']);

$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function ($container) {
    return $capsule;
};

// login controller
$container['LoginController'] = function ($container) {
	return new \App\Controllers\LoginController($container);
};

// projects controller
$container['ProjectsController'] = function ($container) {
	return new \App\Controllers\ProjectsController($container);
};

// currencies controller
$container['CurrenciesController'] = function ($container) {
	return new \App\Controllers\CurrenciesController($container);
};

// exchange controller
$container['ExchangeController'] = function ($container) {
	return new \App\Controllers\ExchangeController($container);
};

// customers controller
$container['CustomersController'] = function ($container) {
	return new \App\Controllers\CustomersController($container);
};

// sales controller
$container['SalesController'] = function ($container) {
	return new \App\Controllers\SalesController($container);
};

// collections controller
$container['CollectionsController'] = function ($container) {
	return new \App\Controllers\CollectionsController($container);
};

// sales reports controller
$container['SalesReportsController'] = function ($container) {
	return new \App\Controllers\SalesReportsController($container);
};

// suppliers controller
$container['SuppliersController'] = function ($container) {
	return new \App\Controllers\SuppliersController($container);
};

// purchases controller
$container['PurchasesController'] = function ($container) {
	return new \App\Controllers\PurchasesController($container);
};

// payments controller
$container['PaymentsController'] = function ($container) {
	return new \App\Controllers\PaymentsController($container);
};

// purchases reports controller
$container['PurchasesReportsController'] = function ($container) {
	return new \App\Controllers\PurchasesReportsController($container);
};

// cash controller
$container['CashController'] = function ($container) {
	return new \App\Controllers\CashController($container);
};

// cash report controller
$container['CashReportsController'] = function ($container) {
	return new \App\Controllers\CashReportsController($container);
};

// banks controller
$container['BanksController'] = function ($container) {
	return new \App\Controllers\BanksController($container);
};

// banks accounts controller
$container['BanksAccountsController'] = function ($container) {
	return new \App\Controllers\BanksAccountsController($container);
};

// products controller
$container['ProductsController'] = function ($container) {
	return new \App\Controllers\ProductsController($container);
};

// tournaments controller
$container['TournamentsController'] = function ($container) {
	return new \App\Controllers\TournamentsController($container);
};

// tournaments categories controller
$container['TournamentsCategoriesController'] = function ($container) {
	return new \App\Controllers\TournamentsCategoriesController($container);
};

// enrollments controller
$container['EnrollmentsController'] = function ($container) {
	return new \App\Controllers\EnrollmentsController($container);
};

// matches controller
$container['MatchesController'] = function ($container) {
	return new \App\Controllers\MatchesController($container);
};

// gallery controller
$container['GalleryController'] = function ($container) {
	return new \App\Controllers\GalleryController($container);
};

// rankings controller
$container['RankingsController'] = function ($container) {
	return new \App\Controllers\RankingsController($container);
};

// scheduler controller
$container['SchedulerController'] = function ($container) {
	return new \App\Controllers\SchedulerController($container);
};

// courts controller
$container['CourtsController'] = function ($container) {
	return new \App\Controllers\CourtsController($container);
};

// api controller
$container['ApiController'] = function ($container) {
	return new \App\Controllers\ApiController($container);
};