<?php

namespace AppBundle\FormIO;

class Schema
{
  private $id;

  private $server;

  private $sources = [];

  /**
   * @return SchemaComponent[]
   */
  private $components = [];

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id): void
  {
    $this->id = $id;
  }

  /**
   * @return mixed
   */
  public function getServer()
  {
    return $this->server;
  }

  /**
   * @param mixed $server
   */
  public function setServer($server): void
  {
    $this->server = $server;
  }

  public function addComponent($name, $type, $options)
  {
    $this->components[$name] = new SchemaComponent($name, $type, $options);
  }

  public function hasComponents()
  {
    return count($this->components);
  }

  public function getComponentsColumns($column)
  {
    return array_column($this->components, $column);
  }

  /**
   * @return SchemaComponent[]
   */
  public function getComponents()
  {
    return array_values($this->components);
  }

  public function hasComponent($name)
  {
    return isset($this->components[$name]);
  }

  public function countComponents()
  {
    return count($this->components);
  }

  /**
   * @param $name
   * @return array|SchemaComponent
   */
  public function getComponent($name)
  {
    return $this->components[$name];
  }

  public function addSource($id, $data)
  {
    $this->sources[$id] = $data;
  }

  public function getDataBuilder()
  {
    return new DataBuilder($this);
  }

  public function getRequiredComponents()
  {
    $required = [];
    foreach ($this->getComponents() as $component){
      if ($component->isRequired()){
        $required[] = $component;
      }
    }

    return $required;
  }
}
