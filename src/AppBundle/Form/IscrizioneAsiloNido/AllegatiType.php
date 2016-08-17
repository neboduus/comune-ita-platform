<?php
namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Base\AllegatoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class AllegatiType
 */
class AllegatiType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('allegati', CollectionType::class, [
            'entry_type' => AllegatoType::class,
            'allow_add' => true,
            'by_reference' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_allegati';
    }
}
