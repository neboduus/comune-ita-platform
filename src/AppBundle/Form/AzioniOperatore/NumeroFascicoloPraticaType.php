<?php
namespace AppBundle\Form\AzioniOperatore;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class NumeroFascicoloPraticaType
 */
class NumeroFascicoloPraticaType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('numeroFascicolo', TextType::class, array(
            'required' => true,
        ));
    }

}
