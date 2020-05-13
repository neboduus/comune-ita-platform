<?php

namespace AppBundle\FormIO;

class Schema
{
  private $id;

  private $server;

  private $sources = [];

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
    $this->components[$name] = [
      'name' => $name,
      'form_name' => implode(':', explode('.', $name)),
      'form_type' => $type,
      'form_options' => $options,
      'label' => isset($options['label']) ? $options['label'] : null,
    ];
  }

  public function hasComponents()
  {
    return count($this->components);
  }

  public function getComponentsColumns($column)
  {
    return array_column($this->components, $column);
  }

  public function getComponents()
  {
    return array_values($this->components);
  }

  public function hasComponent($name)
  {
    return isset($this->components[$name]);
  }

  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : [];
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
      if (isset($component['form_options']['required']) && $component['form_options']['required']){
        $required[] = $component;
      }
    }

    return $required;
  }
}
