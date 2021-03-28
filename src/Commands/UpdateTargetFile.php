<?php
namespace App\Commands;

class UpdateTargetFile {
    protected $header;
    protected $records;
    protected $filePath;

    /**
     * UpdateTargetFile constructor.
     * @param $filePath: the file path to update
     * @param $header: header of csv
     * @param $records: records of csv
     */
    public function __construct($filePath, $header, $records)
    {
        $this->filePath = $filePath;
        $this->header = $header;
        $this->records = $records;
    }

    public function getHeader() {
        return $this->header;
    }

    public function getRecords() {
        return $this->records;
    }

    public function getFilePath() {
        return $this->filePath;
    }
}
