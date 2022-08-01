<?php


namespace App\Utils;


use WhichBrowser\Parser;

class BrowserParser
{
  /**
   * @var string
   */
  private $browserRestriction = null;

  /**
   * BrowserParser constructor.
   * @param string $browserRestriction
   */
  public function __construct($browserRestriction)
  {
     $restrictions = explode('|', $browserRestriction);
     if (!empty($restrictions)) {
       foreach ($restrictions as $restriction) {
         $this->browserRestriction []= explode(',', $restriction);
       }
     }
  }

  /**
   * @return bool
   */
  public function isBrowserRestricted()
  {
    $browserParser = new Parser(getallheaders());
    if (is_array($this->browserRestriction)) {
      foreach ($this->browserRestriction as $restriction) {
        // Maxthon,<,4.0.5
        if ($browserParser->isBrowser($restriction[0], $restriction[1], $restriction[2])) {
          return true;
        }
      }
    }
    return false;
  }
}
