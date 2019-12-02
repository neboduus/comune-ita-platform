<?php

namespace AppBundle\Model;


interface FlowStepInterface
{
  /**
   * @return array
   */
  public function getParameters();

  /**
   * @param string $field
   *
   * @return mixed
   */
  public function getParameter( string $parameter );

}
