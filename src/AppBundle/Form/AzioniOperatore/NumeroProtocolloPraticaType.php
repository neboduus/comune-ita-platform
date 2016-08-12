<?php
namespace AppBundle\Form\AzioniOperatore;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class NumeroFascicoloPraticaType
 */
class NumeroProtocolloPraticaType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('numeroProtocollo', TextType::class, array(
            'required' => true,
        ));
    }

}
