<?php
namespace Test\Unit;

class BaseTest extends \Codeception\Test\Unit {
    const BASE_FILE_PATH = __DIR__ . '/../../storage/worldcities.csv';
    const BASE_FILE_TMP_PATH = __DIR__ . '/../../storage/worldcities_tmp.csv';
    const BASE_FILE_UPDATED_TIME_PATH = __DIR__ . '/../../storage/worldcities_last_updated_time.txt';
    const BASE_FILE_UPDATED_TIME_TMP_PATH = __DIR__ . '/../../storage/worldcities_last_updated_time_tmp.txt';

    const TARGET_FILE_PATH = __DIR__ . '/../../storage/cities.csv';
    const TARGET_FILE_TMP_PATH = __DIR__ . '/../../storage/cities_tmp.csv';

    protected function switchFile($sourceFile, $targetFile) {
        if (file_exists($sourceFile)) {
            copy($sourceFile, $targetFile);
            unlink($sourceFile);
        }
    }

    protected function getPublicMethod($className, $methodName) {
        $class = new \ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    protected function writeToCsvFile($fileName, $records) {
        $fp = fopen($fileName, 'w');

        foreach ($records as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
    }
}
