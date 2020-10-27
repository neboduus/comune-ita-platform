<?php

namespace App\DataFixtures;

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;
use Google\Spreadsheet\SpreadsheetService;

trait GoogleSpreadsheetTrait
{
    protected static $cacheData = array();

    //protected static $PUBLIC_SPREADSHEETS_URL = 'https://docs.google.com/spreadsheets/d/1mbGZN9OIjfsrrjVbs2QB1DjzzMoCPT6MD5cPTJS4308/edit#gid=0';
    //protected static $PUBLIC_SPREADSHEETS_ID = '1mbGZN9OIjfsrrjVbs2QB1DjzzMoCPT6MD5cPTJS4308';

    private function getGoogleSpreadsheetData($worksheetTitle, $spreadsheetId = '1mbGZN9OIjfsrrjVbs2QB1DjzzMoCPT6MD5cPTJS4308')
    {
        if (getenv('FIXTURES_PUBLIC_SPREADSHEETS_ID')){
            $spreadsheetId = getenv('FIXTURES_PUBLIC_SPREADSHEETS_ID');
        }
        if (!isset(self::$cacheData[$spreadsheetId.$worksheetTitle])) {
            $serviceRequest = new DefaultServiceRequest("");
            ServiceRequestFactory::setInstance($serviceRequest);

            $spreadsheetService = new SpreadsheetService();
            $worksheetFeed = $spreadsheetService->getPublicSpreadsheet($spreadsheetId);
            $worksheet = $worksheetFeed->getByTitle($worksheetTitle);

            $data = $worksheet->getCsv();

            $dataArray = str_getcsv($data, "\r\n");
            foreach ($dataArray as &$row) {
                $row = str_getcsv($row, ",");
            }

            array_walk($dataArray, function (&$a) use ($dataArray) {
                $a = array_map('trim', $a);
                $a = array_combine($dataArray[0], $a);
            });
            array_shift($dataArray); # remove column header

            self::$cacheData[$spreadsheetId.$worksheetTitle] = $dataArray;
        }

        return self::$cacheData[$spreadsheetId.$worksheetTitle];
    }
}
