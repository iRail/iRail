<?php

use Irail\Repositories\Irail\StationsRepository;
use Irail\Repositories\JourneyPlanningRepository;
use Irail\Repositories\LiveboardRepository;
use Irail\Repositories\Nmbs\NmbsRivJourneyPlanningRepository;
use Irail\Repositories\Nmbs\NmbsRivLiveboardRepository;
use Irail\Repositories\Nmbs\NmbsRivVehicleRepository;
use Irail\Repositories\Nmbs\NmbsRssDisturbancesRepository;
use Irail\Repositories\Riv\NmbsRivRawDataRepository;
use Irail\Repositories\ServiceAlertsRepository;
use Irail\Repositories\VehicleJourneyRepository;

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

// $app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Irail\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Irail\Console\Kernel::class
);


$app->singleton(
    NmbsRivRawDataRepository::class,
    NmbsRivRawDataRepository::class
);


$app->singleton(
    StationsRepository::class,
    StationsRepository::class
);

$app->singleton(
    LiveboardRepository::class,
    NmbsRivLiveboardRepository::class
);

$app->singleton(
    JourneyPlanningRepository::class,
    NmbsRivJourneyPlanningRepository::class
);

$app->singleton(
    VehicleJourneyRepository::class,
    NmbsRivVehicleRepository::class
);

$app->singleton(
    ServiceAlertsRepository::class,
    NmbsRssDisturbancesRepository::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//     Irail\Http\Middleware\ExampleMiddleware::class
// ]);

// $app->routeMiddleware([
//     'auth' => Irail\Http\Middleware\Authenticate::class,
// ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(Irail\Providers\AppServiceProvider::class);
// $app->register(Irail\Providers\AuthServiceProvider::class);
// $app->register(Irail\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'Irail\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
    require __DIR__ . '/../routes/api.php';
});

return $app;
