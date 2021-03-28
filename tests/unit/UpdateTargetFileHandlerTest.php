<?php
namespace Test\Unit;

class UpdateTargetFileHandlerTest extends BaseTest
{
    /**
     * @var \UnitTester
     */

    const TEST_FILE = __DIR__ . '/../../storage/test.csv';

    protected function _before()
    {
        $this->switchFile(self::TARGET_FILE_PATH, self::TARGET_FILE_TMP_PATH);
        if (file_exists(self::TEST_FILE)) {
            unlink(self::TEST_FILE);
        }
    }

    protected function _after()
    {
        $this->switchFile(self::TARGET_FILE_TMP_PATH, self::TARGET_FILE_PATH);

        if (file_exists(self::TEST_FILE)) {
            unlink(self::TEST_FILE);
        }
    }

    // tests
    public function testUpdateTargetFileCaseNotExisted()
    {
        $header = [
            'city',
            'city_ascii',
            'lat',
            'lng',
            'country',
            'iso2',
            'iso3',
            'admin_name',
            'capital',
            'population',
            'id'
        ];
        $records = [
            [
                'city' => 'hanoi',
                "city_ascii"=> "123",
                "lat" => "12",
                "lng" => "23",
                'country' => 'Vietnam',
                "iso2" => "VN",
                "iso3" => "VNA",
                "admin_name" => "admin",
                "capital" => "hanoi",
                "population" => "124",
                "id" => "123"
            ]
        ];
        $command = $this->construct(\App\Commands\UpdateTargetFile::class, [
            'filePath' => self::TARGET_FILE_PATH,
            'header' => $header,
            'records' => $records
        ]);
        $handler = new \App\Handlers\UpdateTargetFileHandler();
        $handler->handleUpdateTargetFile($command);
        $this->assertFileExists(self::TARGET_FILE_PATH);

        $csvRecords = array_merge([$header], $records);
        $this->writeToCsvFile(self::TEST_FILE, $csvRecords);
        $this->assertFileEquals(self::TEST_FILE, self::TARGET_FILE_PATH);
    }

    /**
     * @depends testUpdateTargetFileCaseNotExisted
     */
    public function testUpdateTargetFileCaseExisted()
    {
        file_put_contents(self::TARGET_FILE_PATH, 'test');
        $header = [
            'city',
            'city_ascii',
            'lat',
            'lng',
            'country',
            'iso2',
            'iso3',
            'admin_name',
            'capital',
            'population',
            'id'
        ];
        $records = [
            [
                'city' => 'ho chi minh',
                "city_ascii"=> "hcm",
                "lat" => "0000",
                "lng" => "1111",
                'country' => 'Vietnam',
                "iso2" => "VN",
                "iso3" => "VNA",
                "admin_name" => "admin",
                "capital" => "hanoi",
                "population" => "9999",
                "id" => "124"
            ],
            [
                'city' => 'bankok',
                "city_ascii"=> "bk",
                "lat" => "9999",
                "lng" => "22222",
                'country' => 'Thailand',
                "iso2" => "TL",
                "iso3" => "THA",
                "admin_name" => "capital",
                "capital" => "bankok",
                "population" => "111111",
                "id" => "125"
            ]
        ];

        $command = $this->construct(\App\Commands\UpdateTargetFile::class, [
            'filePath' => self::TARGET_FILE_PATH,
            'header' => $header,
            'records' => $records
        ]);
        $handler = new \App\Handlers\UpdateTargetFileHandler();
        $handler->handleUpdateTargetFile($command);
        $this->assertFileExists(self::TARGET_FILE_PATH);

        $csvRecords = array_merge([$header], $records);
        $this->writeToCsvFile(self::TEST_FILE, $csvRecords);

        $this->assertFileEquals(self::TEST_FILE, self::TARGET_FILE_PATH);
    }
}
