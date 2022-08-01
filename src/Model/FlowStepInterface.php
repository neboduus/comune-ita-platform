<?php

namespace App\Model;


interface FlowStepInterface
{
  /**
   * @return array
   */
  public function getParameters();


  /**
   * @param string|null $parameter
   * @return mixed
   */
  public function getParameter( ?string $parameter );

}
