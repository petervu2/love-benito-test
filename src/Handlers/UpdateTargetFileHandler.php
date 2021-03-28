<?php
namespace App\Handlers;

use App\Commands\UpdateTargetFile;
use League\Csv\Writer;

class UpdateTargetFileHandler {
    const FILE_TEMP_PATH = __DIR__ . '/../../storage/tmp.csv';
    public function handleUpdateTargetFile(UpdateTargetFile $command)
    {
        // write to the tmp first to avoid conflict
        $writer = Writer::createFromPath(self::FILE_TEMP_PATH, 'w+');

        try {
            //insert header and records
            $writer->insertOne($command->getHeader());
            $writer->insertAll($command->getRecords());

            // copy tmp file to target file
            copy(self::FILE_TEMP_PATH, $command->getFilePath());

            // remove tmp file
            if (file_exists(self::FILE_TEMP_PATH)) {
                unlink(self::FILE_TEMP_PATH);
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to update file');
        }
    }
}
