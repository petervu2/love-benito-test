<?php
namespace App\Repositories;

use App\Commands\UpdateTargetFile;
use App\Constants\Constant;
use League\Csv\Reader;
use League\Csv\Statement;

class CityRepository extends BaseRepository implements CityRepositoryInterface {
    const TARGET_FILE_PATH = __DIR__ . '/../../storage/cities.csv';

    protected $availableParams = [
        'city',
        'city_ascii',
        'lat',
        'lng',
        'country',
        'iso2',
        'iso3',
        'admin_name',
        'capital',
        'population'
    ];

    protected $numberParams = [
        'lat',
        'lng',
        'population'
    ];

    /**
     * @param $id
     * @param $params
     * @throws \Exception
     */
    public function updateCity($id, $params)
    {
        $this->validateParams($params);
        $this->handleUpdateFile(self::TARGET_FILE_PATH, $id, $params);
    }

    /**
     * validate params before update city
     * @param $params
     * @throws \Exception
     */
    private function validateParams($params) {
        foreach ($this->availableParams as $key) {
            if (!array_key_exists($key, $params)) {
                throw new \Exception("$key is missing");
            }
            if (in_array($key, $this->numberParams) && !is_numeric($params[$key])) {
                throw new \Exception("$key should be a number");
            }
        }
        if (strlen($params['iso2']) != 2) {
            throw new \Exception("iso2 should be 2 letters");
        }
        if (strlen($params['iso3']) != 3) {
            throw new \Exception("iso3 should be 3 letters");
        }

        if (!in_array($params['country'], Constant::ASEAN_COUNTRIES)) {
            throw new \Exception("country {$params['country']} is not an Asean country");
        }
    }

    /**
     * @param $filePath
     * @param $id
     * @param $params
     * @throws \Exception
     */
    private function handleUpdateFile($filePath, $id, $params) {
        // get records from target file
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $fileRecords = $csv->getRecords();
        $foundRecord = false;
        $finalRecords = [];

        //check if the request city id is found in the target file's records
        foreach ($fileRecords as $index => $record) {
            if ($record['id'] == $id) {
                $foundRecord = true;
                foreach ($this->availableParams as $key) {
                    $record[$key] = $params[$key];
                }
            }
            $finalRecords[] = $record;
        }
        if (!$foundRecord) {
            throw new \Exception("City id is not found");
        }
        $header = $csv->getHeader();

        //call handler to update file with request params
        $command = new UpdateTargetFile($filePath, $header, $finalRecords);
        $commandBus = $this->getContainer()->get(Constant::COMMAND_BUS_SERVICE);

        $commandBus->handle($command);
    }
}
