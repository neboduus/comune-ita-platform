<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TagsType extends AbstractType {

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->addViewTransformer(new CallbackTransformer(
      function ($original) {
        return $original ? implode(', ', $original) : '';
      },
      function ($submitted) {
        if (!$submitted) {
          return [];
        }

        $submitted = array_map(function($tag) {
          return trim($tag);
        }, explode(',', $submitted));

        return $submitted;
      }
    ));
  }

  public function getName()
  {
    return 'tags';
  }

  public function getParent()
  {
    return TextType::class;
  }


}
