<?php

namespace App\Jobs;


use App\Commands\UpdateTargetFile;
use App\Constants\Constant;
use App\Handlers\UpdateTargetFileHandler;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Plugins\LockingMiddleware;

require_once __DIR__ . '/../../vendor/autoload.php';

$container = new Container();
$container->defaultToShared(true);
$container->delegate(new ReflectionContainer());

// register command and handler class to container
$container->add(Constant::COMMAND_BUS_SERVICE, function () {
    $locator = new InMemoryLocator();
    $locator->addHandler(new UpdateTargetFileHandler(), UpdateTargetFile::class);

    $handlerMiddleware = new CommandHandlerMiddleware(
        new ClassNameExtractor(),
        $locator,
        new HandleClassNameInflector()
    );
    $lockMiddleware = new LockingMiddleware();
    $commandBus = new CommandBus([$lockMiddleware, $handlerMiddleware]);
    return $commandBus;
}, true);

try {
    $baseFileWatcher = new FileWatcher($container);
    $baseFileWatcher->run();
    echo "Updated file successfully!\n";
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}

