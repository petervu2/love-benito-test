<?php
namespace Test\Unit;

use App\Commands\UpdateTargetFile;
use App\Constants\Constant;
use App\Handlers\UpdateTargetFileHandler;
use App\Jobs\FileWatcher;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Tactician\CommandBus;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\CommandNameExtractor\ClassNameExtractor;
use League\Tactician\Handler\Locator\InMemoryLocator;
use League\Tactician\Handler\MethodNameInflector\HandleClassNameInflector;
use League\Tactician\Plugins\LockingMiddleware;
use phpDocumentor\Reflection\File;

class FileWatcherTest extends BaseTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        $this->switchFile(self::BASE_FILE_PATH, self::BASE_FILE_TMP_PATH);
        $this->switchFile(self::BASE_FILE_UPDATED_TIME_PATH, self::BASE_FILE_UPDATED_TIME_TMP_PATH);
        $this->switchFile(self::TARGET_FILE_PATH, self::TARGET_FILE_TMP_PATH);
    }

    protected function _after()
    {
        $this->switchFile(self::BASE_FILE_TMP_PATH, self::BASE_FILE_PATH);
        $this->switchFile(self::BASE_FILE_UPDATED_TIME_TMP_PATH, self::BASE_FILE_UPDATED_TIME_PATH);
        $this->switchFile(self::TARGET_FILE_TMP_PATH, self::TARGET_FILE_PATH);
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function itShouldRunSuccessfullyTheFirstTime() {
        file_put_contents(self::BASE_FILE_PATH, 'update first time');
        $method = $this->getPublicMethod(FileWatcher::class, 'run');
        $fileWatcher = $this->make(FileWatcher::class, ['handleUpdateFile' => true]);
        $fileWatcher->expects($this->once())->method('handleUpdateFile');
        $method->invokeArgs($fileWatcher, []);
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function itShouldNotRunWithoutUpdate() {
        file_put_contents(self::BASE_FILE_PATH, 'update first time');
        $method = $this->getPublicMethod(FileWatcher::class, 'run');
        $fileWatcher = $this->make(FileWatcher::class, ['handleUpdateFile' => true, 'isFileUpdatedByTime' => false]);
        $fileWatcher->expects($this->never())->method('handleUpdateFile');
        $this->expectExceptionMessage('File not updated after last run');
        $method->invokeArgs($fileWatcher, []);
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function itShouldNotRunIfFileUpdatedButNotForAseanCountries() {
        file_put_contents(self::BASE_FILE_PATH, 'update first time');
        file_put_contents(self::TARGET_FILE_PATH, 'update first time');
        $method = $this->getPublicMethod(FileWatcher::class, 'run');
        $fileWatcher = $this->make(FileWatcher::class, ['handleUpdateFile' => true, 'isFileUpdatedByTime' => true, 'isFileUpdatedByContent' => false]);
        $fileWatcher->expects($this->never())->method('handleUpdateFile');
        $this->expectExceptionMessage("Asean countries's data are not updated");
        $method->invokeArgs($fileWatcher, []);
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function itShouldRunIfFileUpdatedForAseanCountries() {
        file_put_contents(self::BASE_FILE_PATH, 'update first time');
        file_put_contents(self::TARGET_FILE_PATH, 'update first time');
        $method = $this->getPublicMethod(FileWatcher::class, 'run');
        $fileWatcher = $this->make(FileWatcher::class, ['handleUpdateFile' => true, 'isFileUpdatedByTime' => true, 'isFileUpdatedByContent' => true]);
        $fileWatcher->expects($this->once())->method('handleUpdateFile');
        $method->invokeArgs($fileWatcher, []);
    }

    /**
     * @throws \Exception
     *
     * @test
     */
    public function handleUpdateFileShouldCallHandlerSuccessfully(){
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

        $fileWatcher = $this->construct(FileWatcher::class, [$container], ['getBaseFileHeader' => [], 'getBaseFileRecords' => [], 'loadBaseFileData' => true]);
        $method = $this->getPublicMethod(FileWatcher::class, 'handleUpdateFile');
        $updateFileHandler->expects($this->once())->method('handleUpdateTargetFile');
        $method->invokeArgs($fileWatcher, []);
    }

    // tests
    public function testIsFileUpdatedByTime()
    {
//        fwrite(STDERR, print_r("\n last checked: " .$lastChecked . "\n", TRUE));
        $method = $this->getPublicMethod(FileWatcher::class, 'isFileUpdatedByTime');

        $fileWatcher = $this->make(FileWatcher::class);

        file_put_contents(self::BASE_FILE_PATH, 'update first time');
        self::assertTrue($method->invokeArgs($fileWatcher, []));
        self::assertFalse($method->invokeArgs($fileWatcher, []));
        sleep(2);
        file_put_contents(self::BASE_FILE_PATH, 'update second time');
        filemtime(self::BASE_FILE_PATH);

        self::assertTrue($method->invokeArgs($fileWatcher, []));
    }

    /**
     * @dataProvider baseCsvDataProvider
     */
    public function testLoadBaseFileData($records, $expectedHeader, $expectedRecords) {
        $method = $this->getPublicMethod(FileWatcher::class, 'loadBaseFileData');
        $fileWatcher = new FileWatcher(new Container());
        $this->writeToCsvFile(self::BASE_FILE_PATH, $records);
        $method->invokeArgs($fileWatcher, []);
        $this->assertEquals($expectedHeader, $fileWatcher->getBaseFileHeader());
        $this->assertEquals($expectedRecords, $fileWatcher->getBaseFileRecords());
    }

    /**
     * @dataProvider targetCsvDataProvider
     */
    public function testLoadTargetFileData($records, $expectedRecords) {
        $method = $this->getPublicMethod(FileWatcher::class, 'loadTargetFileData');
        $fileWatcher = new FileWatcher(new Container());
        $this->writeToCsvFile(self::TARGET_FILE_PATH, $records);
        $method->invokeArgs($fileWatcher, []);
        $this->assertEquals($expectedRecords, $fileWatcher->getTargetFileRecords());
    }

    /**
     * @dataProvider fileUpdatedByContentDataProvider
     */
    public function testIsFileUpdatedByContent($baseFileHeader, $baseFileRecords, $targetFileRecords, $expected){
        $method = $this->getPublicMethod(FileWatcher::class, 'isFileUpdatedByContent');
        $fileWatcher = $this->make(FileWatcher::class, ['getBaseFileHeader' => $baseFileHeader, 'getBaseFileRecords' => $baseFileRecords, 'targetFileRecords' => $targetFileRecords]);
        $this->assertEquals($expected, $method->invokeArgs($fileWatcher, []));
    }

    /**
     * @dataProvider filterAseanCountriesDataProvider
     */
    public function testFilterAseanCountries($row, $expected) {
        $method = $this->getPublicMethod(FileWatcher::class, 'filterAseanCountries');
        $fileWatcher = $this->make(FileWatcher::class);
        $this->assertEquals($expected, $method->invokeArgs($fileWatcher, [$row]));
    }

    public function baseCsvDataProvider() {
        return [
            'only_asean' => [
                [
                    ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                    ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]
                ],
                ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                [1 => ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]]
            ],
            'not_only_asean' => [
                [
                    ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                    ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"],
                    ['city' => 'Net', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Netherland', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "2"],
                ],
                ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                [1 => ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]]
            ],
            'no_asesan' => [
                [
                    ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                    ['city' => 'Net', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Netherland', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "2"],
                ],
                ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                []
            ]
        ];
    }

    public function targetCsvDataProvider() {
        return [
            'have_data' => [
                [
                    ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                    ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]
                ],
                [1 => ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]]
            ],
            'no_data' => [
                [
                    ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                    []
                ],
                []
            ],
        ];
    }

    public function fileUpdatedByContentDataProvider() {
        return [
            'content_not_updated' => [
                ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                [1 => ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]],
                [1 => ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]],
                false
            ],
            'content_updated' => [
                ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
                [1 => ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]],
                [1 =>['city' => 'hanoi2', "city_ascii"=> "456", "lat" => "123", "lng" => "456", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => "1"]],
                true
            ]
        ];
    }

    public function filterAseanCountriesDataProvider() {
        return [
            'Brunei' => [
                ['country' => 'Brunei'],
                true
            ],
            'Cambodia' => [
                ['country' => 'Cambodia'],
                true
            ],
            'Indonesia' => [
                ['country' => 'Indonesia'],
                true
            ],
            'Laos' => [
                ['country' => 'Laos'],
                true
            ],
            'Malaysia' => [
                ['country' => 'Malaysia'],
                true
            ],
            'Myanmar' => [
                ['country' => 'Myanmar'],
                true
            ],
            'Philippines' => [
                ['country' => 'Philippines'],
                true
            ],
            'Singapore' => [
                ['country' => 'Singapore'],
                true
            ],
            'Thailand' => [
                ['country' => 'Thailand'],
                true
            ],
            'Vietnam' => [
                ['country' => 'Vietnam'],
                true
            ],
            'United State' => [
                ['country' => 'United State'],
                false
            ],
            'Netherland' => [
                ['country' => 'Netherland'],
                false
            ]
        ];
    }

}
