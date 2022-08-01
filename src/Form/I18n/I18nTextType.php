<?php
/**
 * Created by Asier Marqués <asiermarques@gmail.com>
 * Date: 17/5/16
 * Time: 14:53
 */

namespace App\Form\I18n;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class I18nTextType extends AbstractType implements I18nFieldInterface
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
    return TextType::class;
  }
}
