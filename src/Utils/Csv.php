<?php


namespace App\Utils;


class Csv
{

  public static function csvToArray($filename = '', $delimiter= ',', $enclosure = '"')
  {
    if(!file_exists($filename) || !is_readable($filename))
      return false;

    $header = null;

    $data = array();
    if (($handle = fopen($filename, 'r')) !== false)    {
      while (($row = fgetcsv($handle, 0, $delimiter)) !== false){

        //print_r($row);
        //$row = array_map('w1250_to_utf8', $row);
        if(!$header) {
          $temp = array();
          foreach ($row as $r) {
            //$temp []= strtolower(str_replace(' ', '_', $r));
            $temp []= $r;
          }
          $header = $temp;
        }
        else {
          $data[] =  array_combine($header, $row);
        }
      }
      fclose($handle);
    }
    //print_r($data);
    return $data;
  }

}
