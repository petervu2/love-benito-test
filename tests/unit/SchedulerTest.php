<?php

namespace Test\Unit;
class SchedulerTest extends BaseTest
{
    const TEST_FILE = __DIR__ . '/../../storage/test.csv';
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

    public function testShouldCreateTargetFileWhenRunFirstTime()
    {
        $this->assertFileNotExists(self::TARGET_FILE_PATH);
        $records = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii" => "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1]
        ];
        $this->writeToCsvFile(self::BASE_FILE_PATH, $records);

        $this->expectOutputRegex("/Updated file successfully!/");
        include __DIR__ . '/../../src/Jobs/scheduler.php';
        $this->assertFileExists(self::TARGET_FILE_PATH);
        $this->assertFileEquals(self::TARGET_FILE_PATH, self::BASE_FILE_PATH);
    }


    public function testShouldFailWhenBaseFileNotUpdated()
    {
        $this->assertFileNotExists(self::TARGET_FILE_PATH);
        $records = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii" => "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1]
        ];
        $this->writeToCsvFile(self::BASE_FILE_PATH, $records);

        include __DIR__ . '/../../src/Jobs/scheduler.php';
        sleep(2);
        $this->expectOutputRegex("/File not updated after last run/");
        include __DIR__ . '/../../src/Jobs/scheduler.php';
    }

    public function testShouldFailWhenAseanCountriesNotUpdated()
    {
        $this->assertFileNotExists(self::TARGET_FILE_PATH);
        $baseRecords = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii" => "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1],
            ['city' => 'newyork', "city_ascii" => "us", "lat" => "23", "lng" => "45", 'country' => 'United State', "iso2" => "US", "iso3" => "USA", "admin_name" => "admin", "capital" => "newyork", "population" => "1111", "id" => 2]
        ];
        $this->writeToCsvFile(self::BASE_FILE_PATH, $baseRecords);


        include __DIR__ . '/../../src/Jobs/scheduler.php';
        sleep(2);
        $baseRecords = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii" => "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1],
            ['city' => 'newyork 2', "city_ascii" => "us 2", "lat" => "23", "lng" => "45", 'country' => 'United State', "iso2" => "US", "iso3" => "USA", "admin_name" => "admin", "capital" => "newyork", "population" => "1111", "id" => 2],
        ];
        $this->writeToCsvFile(self::BASE_FILE_PATH, $baseRecords);

        $this->expectOutputRegex("/Asean countries\'s data are not updated/");
        include __DIR__ . '/../../src/Jobs/scheduler.php';
    }

    public function testShouldUpdateFileSuccessfullyWhenBaseFileIsUpdatedAndDataUpdatedIsAseanCountry() {
        $this->assertFileNotExists(self::TARGET_FILE_PATH);
        $baseRecords = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii" => "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1],
            ['city' => 'newyork', "city_ascii" => "us", "lat" => "23", "lng" => "45", 'country' => 'United State', "iso2" => "US", "iso3" => "USA", "admin_name" => "admin", "capital" => "newyork", "population" => "1111", "id" => 2]
        ];
        $this->writeToCsvFile(self::BASE_FILE_PATH, $baseRecords);


        include __DIR__ . '/../../src/Jobs/scheduler.php';
        sleep(2);
        $baseRecords = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi 2', "city_ascii" => "1234", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1],
            ['city' => 'newyork', "city_ascii" => "us", "lat" => "23", "lng" => "45", 'country' => 'United State', "iso2" => "US", "iso3" => "USA", "admin_name" => "admin", "capital" => "newyork", "population" => "1111", "id" => 2]
        ];

        $this->writeToCsvFile(self::BASE_FILE_PATH, $baseRecords);
        $this->expectOutputRegex("/Updated file successfully!/");
        include __DIR__ . '/../../src/Jobs/scheduler.php';
        $this->assertFileExists(self::TARGET_FILE_PATH);

        $testRecords = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi 2', "city_ascii" => "1234", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1]
        ];

        $this->writeToCsvFile(self::TEST_FILE, $testRecords);
        $this->assertFileEquals(self::TEST_FILE, self::TARGET_FILE_PATH);
    }
}
