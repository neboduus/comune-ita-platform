<?php
namespace AppBundle\Form\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DatiContoCorrenteType
 */
class DatiContoCorrenteType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('iban', TextType::class, ['required' => true])
            ->add('intestatarioConto', TextType::class, ['required' => true]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'dati_conto_corrente';
    }
}
