<?php

use App\Actions\ApplicationHealthCheckAction;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Instantiate App
 *
 * In order for the factory to work you need to ensure you have installed
 * a supported PSR-7 implementation of your choice e.g.: Slim PSR-7 and a supported
 * ServerRequest creator (included with Slim PSR-7)
 */
$app = AppFactory::create();

// Add Routing Middleware
$app->addRoutingMiddleware();

/**
 * Container
 */
$container = new League\Container\Container();

$container->defaultToShared(true);
$container->delegate(new \League\Container\ReflectionContainer());

$container->add(\App\Constants\Constant::COMMAND_BUS_SERVICE, function () {
    $locator = new \League\Tactician\Handler\Locator\InMemoryLocator();
    $locator->addHandler(new \App\Handlers\UpdateTargetFileHandler(), \App\Commands\UpdateTargetFile::class);

    $handlerMiddleware = new \League\Tactician\Handler\CommandHandlerMiddleware(
        new \League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor(),
        $locator,
        new \League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector()
    );
    $lockMiddleware = new \League\Tactician\Plugins\LockingMiddleware();
    $commandBus = new \League\Tactician\CommandBus([$lockMiddleware, $handlerMiddleware]);
    return $commandBus;
}, true);

$container->add(\App\Repositories\CityRepositoryInterface::class, \App\Repositories\CityRepository::class)->addArgument($container);

AppFactory::setContainer($container);
/**
 * Add Error Handling Middleware
 *
 * @param bool $displayErrorDetails -> Should be set to false in production
 * @param bool $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool $logErrorDetails -> Display error details in error log
 * which can be replaced by a callable of your choice.
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$app->add(new \App\Middlewares\JsonBodyParserMiddleware());


$app->put('/cities/{id}', function (Request $request, Response $response, $args) use ($container) {
    $cityRepository = $container->get(\App\Repositories\CityRepositoryInterface::class);

    try {
        $cityRepository->updateCity($args['id'], $request->getParsedBody());
        $response->getBody()->write(json_encode(['success' => true]));
    } catch (Exception $e) {
        $payload = json_encode(['success' => false, 'message' => $e->getMessage()]);
        $response->getBody()->write($payload);
    }

    return $response->withHeader('Content-Type', 'application/json');;
});

// Define app routes
$app->get('/',
    function (Request $request, Response $response, $args) use ($container) {
        $response->getBody()->write("Love, Bonito");
        return $response;

    });



// Run app
$app->run();
