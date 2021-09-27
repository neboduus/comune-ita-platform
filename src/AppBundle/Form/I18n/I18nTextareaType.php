<?php

namespace AppBundle\Form\I18n;

use AppBundle\Form\I18n\I18nFieldInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class I18nTextareaType extends AbstractType implements I18nFieldInterface
{

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(
      [
        "compound" => true,
      ]
    );
    $resolver->setRequired(["compound"]);
    $resolver->setAllowedValues("compound", true);


  }

  public function getParent()
  {
    return TextareaType::class;
  }


}
