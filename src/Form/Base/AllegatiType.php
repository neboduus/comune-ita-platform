<?php
namespace App\Form\Base;

use App\Entity\Allegato;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.common.carica_allegati.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.common.carica_allegati.title', true);

        $user = $builder->getData()->getUser();

        $builder->add('allegati', EntityType::class, [
            'class' => Allegato::class,
            'choice_label' => 'choiceLabel',
            'query_builder' => function (EntityRepository $er) use ($user) {
                $builder = $er->createQueryBuilder('a');
                return $builder->where('a.owner = :user')
                    ->andWhere($builder->expr()->isInstanceOf('a', Allegato::class))
                    ->setParameter('user', $user)
                    ->orderBy('a.originalFilename', 'ASC');
            },
            'expanded' => true,
            'multiple' => true,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'pratica_allegati';
    }
}
