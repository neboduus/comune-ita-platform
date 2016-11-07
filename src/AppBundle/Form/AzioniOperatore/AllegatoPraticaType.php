<?php
namespace AppBundle\Form\AzioniOperatore;

use AppBundle\Entity\AllegatoOperatore;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Vich\UploaderBundle\VichUploaderBundle;

/**
 * Class AllegatoType
 */
class AllegatoPraticaType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $builder->getData()->getUser();

        $builder->add('allegati', EntityType::class, [
            'class' => AllegatoOperatore::class,
            'choice_label' => 'choiceLabel',
            'query_builder' => function (EntityRepository $er) use ($user) {
                $builder = $er->createQueryBuilder('a');
                return $builder->where('a.owner = :user')
                    ->andWhere($builder->expr()->isInstanceOf('a', AllegatoOperatore::class))
                    ->setParameter('user', $user)
                    ->orderBy('a.originalFilename', 'ASC');
            },
            'expanded' => true,
            'multiple' => true,
        ]);
    }
}
