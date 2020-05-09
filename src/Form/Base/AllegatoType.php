<?php
namespace App\Form\Base;

use App\Entity\Allegato;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

/**
 * Class AllegatoType
 */
class AllegatoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //TODO: add thumbnail if file has been submitted already
        //otherwise add file input
        $builder
            ->add('file', VichFileType::class, [
                'allow_delete' => false,
                'required' => true,
                'attr' => [
                    'class' => 'btn btn-default',
                ]
            ])
            ->add('description', TextType::class, [
                'required' => false,
            ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'ocsdc_allegato';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Allegato::class,
        ));
    }
}
