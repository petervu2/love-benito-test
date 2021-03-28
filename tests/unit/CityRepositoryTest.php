<?php
namespace Test\Unit;

use App\Commands\UpdateTargetFile;
use App\Constants\Constant;
use App\Handlers\UpdateTargetFileHandler;
use App\Repositories\CityRepository;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Plugins\LockingMiddleware;

class CityRepositoryTest extends BaseTest
{

    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        $this->switchFile(self::TARGET_FILE_PATH, self::TARGET_FILE_TMP_PATH);
    }

    protected function _after()
    {
        $this->switchFile(self::TARGET_FILE_TMP_PATH, self::TARGET_FILE_PATH);
    }

    /**
     * @dataProvider validateParamsDataProvider
     */
    public function testValidateParams($params, $exceptionMessage)
    {
        $method = $this->getPublicMethod(CityRepository::class, 'validateParams');
        $repository = $this->make(CityRepository::class);
        if (!empty($exceptionMessage)) {
            $this->expectExceptionMessage($exceptionMessage);
        }
        $method->invokeArgs($repository, [$params]);
    }

    /**
     * @throws \Exception
     * @test
     */
    public function handleUpdateFileShouldFailIfCountryIsNotFound() {
        $records = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]
        ];
        $this->writeToCsvFile(self::TARGET_FILE_PATH, $records);
        $repository = $this->make(CityRepository::class);
        $method = $this->getPublicMethod(CityRepository::class, 'handleUpdateFile');
        $this->expectExceptionMessage('City id is not found');
        $method->invokeArgs($repository, [self::TARGET_FILE_PATH, 2, []]);
    }

    /**
     * @throws \Exception
     * @test
     */
    public function handleUpdateFileShouldSuccessIfValidData() {
        $records = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1]
        ];
        $this->writeToCsvFile(self::TARGET_FILE_PATH, $records);

        $container = new Container();
        $container->defaultToShared(true);
        $container->delegate(new ReflectionContainer());
        $updateFileHandler = $this->make(UpdateTargetFileHandler::class, ['handleUpdateTargetFile' => true]);
        $container->add(Constant::COMMAND_BUS_SERVICE, function () use ($updateFileHandler) {
            $locator = new InMemoryLocator();
            $locator->addHandler($updateFileHandler, UpdateTargetFile::class);

            $handlerMiddleware = new CommandHandlerMiddleware(
                new ClassNameExtractor(),
                $locator,
                new HandleClassNameInflector()
            );
            $lockMiddleware = new LockingMiddleware();
            $commandBus = new CommandBus([$lockMiddleware, $handlerMiddleware]);
            return $commandBus;
        }, true);


        $repository = $this->construct(CityRepository::class, [$container]);
        $method = $this->getPublicMethod(CityRepository::class, 'handleUpdateFile');
        $updateFileHandler->expects($this->once())->method('handleUpdateTargetFile');
        $method->invokeArgs($repository, [self::TARGET_FILE_PATH, 1, ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]]);
    }

    public function validateParamsDataProvider() {
        return [
            'city_is_missing' => [
                [
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "a",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'city is missing'
            ],
            'country_is_missing' => [
                [
                    "city" => "hanoi34",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'country is missing'
            ],
            'city_ascii_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'city_ascii is missing'
            ],
            'lat_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'lat is missing'
            ],
            'lng_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'lng is missing'
            ],
            'iso2_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'iso2 is missing'
            ],
            'iso3_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'iso3 is missing'
            ],
            'admin_name_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'admin_name is missing'
            ],
            'capital_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "population" => "124"
                ],
                'capital is missing'
            ],
            'population_is_missing' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                ],
                'population is missing'
            ],
            'lat_is_not_number' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "a",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'lat should be a number'
            ],
            'lng_is_not_number' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "b",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'lng should be a number'
            ],
            'population_is_not_number' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "abc"
                ],
                'population should be a number'
            ],
            'iso2_is_not_2_letters' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "test",
                    "iso3" => "abc",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'iso2 should be 2 letters'
            ],
            'iso3_is_not_3_letters' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "test",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'iso3 should be 3 letters'
            ],
            'country_is_not_asean' => [
                [
                    "city" => "hanoi34",
                    "country" => "United State",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "ABC",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                'country United State is not an Asean country'
            ],
            'success' => [
                [
                    "city" => "hanoi34",
                    "country" => "Vietnam",
                    "city_ascii" => "123",
                    "lat" => "12",
                    "lng" => "23",
                    "iso2" => "US",
                    "iso3" => "ABC",
                    "admin_name" => "admin",
                    "capital" => "hanoi",
                    "population" => "124"
                ],
                ''
            ],
        ];
    }
}
