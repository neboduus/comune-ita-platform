<?php


namespace App;


class TestKernel extends InstanceKernel
{
  const DEFAULT_PREFIX = 'comune-di-bugliano';


  /** @return string */
  public function getIdentifier()
  {
    return self::DEFAULT_PREFIX;
  }
}
