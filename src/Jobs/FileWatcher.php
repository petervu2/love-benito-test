<?php

namespace App\Jobs;
/**
 * Class BaseFileWatcher
 * run every minute to check the base file has been changed or not
 */

use App\Commands\UpdateTargetFile;
use App\Constants\Constant;
use League\Csv\Reader;
use League\Csv\Statement;

class FileWatcher extends BaseJob {
    const BASE_FILE_PATH = __DIR__ . '/../../storage/worldcities.csv';
    const TARGET_FILE_PATH = __DIR__ . '/../../storage/cities.csv';
    const BASE_FILE_LAST_UPDATED_PATH = __DIR__ . '/../../storage/worldcities_last_updated_time.txt';

    private $baseFileHeader = [];
    private $baseFileRecords = [];
    private $targetFileRecords = [];

    public function run() {
        if (!file_exists(self::BASE_FILE_PATH)) {
            throw new \Exception('Base file not existed');
        }

        if (!$this->isFileUpdatedByTime()) {
            throw new \Exception('File not updated after last run');
        }

        if (!file_exists(self::TARGET_FILE_PATH)) {
            $this->handleUpdateFile();
            return;
        }

        if (!$this->isFileUpdatedByContent()) {
            throw new \Exception("Asean countries's data are not updated");
        }

        $this->handleUpdateFile();
    }

    public function getBaseFileHeader() {
        return $this->baseFileHeader;
    }

    public function getBaseFileRecords() {
        return $this->baseFileRecords;
    }

    public function getTargetFileRecords() {
        return $this->targetFileRecords;
    }

    /**
     * check if file updated by the last modification time
     * @return bool
     */
    public function isFileUpdatedByTime() {
        $lastUpdated = filemtime(self::BASE_FILE_PATH);

        // check if the timestamp file is existed or not, if not then it is the first time running
        // write the last modification time of base file to this file
        if (!file_exists(self::BASE_FILE_LAST_UPDATED_PATH)) {
            file_put_contents(self::BASE_FILE_LAST_UPDATED_PATH, $lastUpdated);
            return true;
        }

        // get the last checked timestamp from timestamp file
        // if the last modification time of base file is greater then this number, the base file was updated after
        $lastChecked = file_get_contents(self::BASE_FILE_LAST_UPDATED_PATH);
        if ($lastUpdated > $lastChecked) {
            file_put_contents(self::BASE_FILE_LAST_UPDATED_PATH, $lastUpdated);
            return true;
        }
        return false;
    }

    /**
     * check if the asean countries was updated or not
     * @return bool
     */
    public function isFileUpdatedByContent() {
        $this->loadBaseFileData();
        $this->loadTargetFileData();

        // compare asean countries's records in base file with target file's records to decide the data was updated or not
        return json_encode($this->getBaseFileRecords()) !== json_encode($this->getTargetFileRecords());
    }

    /**
     * load header and asean countries's records from base file
     */
    public function loadBaseFileData() {
        if (!empty($this->getBaseFileHeader()) && !empty($this->getBaseFileRecords())) {
            return;
        }
        //load the CSV document from a file path
        $baseFileCsv = Reader::createFromPath(self::BASE_FILE_PATH, 'r');
        $baseFileCsv->setHeaderOffset(0);

        $stmt = (new Statement())->where([$this, 'filterAseanCountries']);
        $this->baseFileRecords = iterator_to_array($stmt->process($baseFileCsv)->getRecords(), true);
        $this->baseFileHeader = $baseFileCsv->getHeader();
    }

    /**
     * load records from target file
     */
    public function loadTargetFileData() {
        if (!empty($this->getTargetFileRecords())) {
            return;
        }
        //load the CSV document from a file path
        $csv = Reader::createFromPath(self::TARGET_FILE_PATH, 'r');
        $csv->setHeaderOffset(0);

        $stmt = new Statement();
        $this->targetFileRecords = iterator_to_array($stmt->process($csv)->getRecords(), true);
    }


    /**
     * call handler file to write updated data to target file
     */
    public function handleUpdateFile() {
        $this->loadBaseFileData();
        $command = new UpdateTargetFile(self::TARGET_FILE_PATH, $this->getBaseFileHeader(), $this->getBaseFileRecords());
        $commandBus = $this->getContainer()->get(Constant::COMMAND_BUS_SERVICE);
        $commandBus->handle($command);
    }

    public function filterAseanCountries($row) {
        return in_array($row['country'], Constant::ASEAN_COUNTRIES);
    }
}
