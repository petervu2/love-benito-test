<?php

class UpdateCityCest
{
    const TARGET_FILE_PATH = __DIR__ . '/../../storage/cities.csv';
    const TARGET_FILE_TMP_PATH = __DIR__ . '/../../storage/cities_tmp.csv';

    public function _before(ApiTester $I)
    {
        if (file_exists(self::TARGET_FILE_PATH)) {
            copy(self::TARGET_FILE_PATH, self::TARGET_FILE_TMP_PATH);
            unlink(self::TARGET_FILE_PATH);
        }
    }

    public function _after() {
        if (file_exists(self::TARGET_FILE_TMP_PATH)) {
            copy(self::TARGET_FILE_TMP_PATH, self::TARGET_FILE_PATH);
            unlink(self::TARGET_FILE_TMP_PATH);
        }
    }

    /**
     * @param ApiTester $I
     *
     */
    public function testFailValidateNumber(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/1360771077', ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "abc", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => false, 'message' => 'lat should be a number']);
    }

    /**
     * @param ApiTester $I
     *
     */
    public function testFailMissingParams(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/1360771077', ["city_ascii"=> "1234", "lat" => "123", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => false, 'message' => 'city is missing']);
    }

    /**
     * @param ApiTester $I
     *
     */
    public function testFailNotAseanCountry(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/1360771077', ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "123", "lng" => "23", 'country' => 'United State', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => false, 'message' => 'country United State is not an Asean country']);
    }

    /**
     * @param ApiTester $I
     *
     */
    public function testFailNotValidIso2(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/1360771077', ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "123", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VNA", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => false, 'message' => 'iso2 should be 2 letters']);
    }

    /**
     * @param ApiTester $I
     *
     */
    public function testFailNotValidIso3(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/1360771077', ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "123", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA45", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => false, 'message' => 'iso3 should be 3 letters']);
    }

    /**
     * @param ApiTester $I
     *
     */
    public function testFailCityIdNotFound(ApiTester $I)
    {
        $records = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1]
        ];
        $fp = fopen(self::TARGET_FILE_PATH, 'w');

        foreach ($records as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/2', ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "123", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => false, 'message' => 'City id is not found']);
    }

    public function testUpdateCitySuccessfully(ApiTester $I)
    {
        $records = [
            ['city', 'city_ascii', 'lat', 'lng', 'country', 'iso2', 'iso3', 'admin_name', 'capital', 'population', 'id'],
            ['city' => 'hanoi', "city_ascii"=> "123", "lat" => "12", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124", "id" => 1]
        ];
        $fp = fopen(self::TARGET_FILE_PATH, 'w');

        foreach ($records as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPut('/cities/1', ['city' => 'hanoi2', "city_ascii"=> "1234", "lat" => "123", "lng" => "23", 'country' => 'Vietnam', "iso2" => "VN", "iso3" => "VNA", "admin_name" => "admin", "capital" => "hanoi", "population" => "124"]);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson(['success' => true]);
    }
}
