<?php


namespace AppBundle\Form\Admin\ServiceGroup;

use AppBundle\Entity\ServiceGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ServiceGroupType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var ServiceGroup $serviceGroup */
    $serviceGroup = $builder->getData();

    $builder
      ->add('name', TextType::class, [
        'label' => 'gruppo_di_servizi.nome'
      ])
      ->add('description', TextareaType::class, [
        'label' => 'gruppo_di_servizi.descrizione',
        'required' => false
      ])
      ->add('who', TextareaType::class, [
        'label' => 'gruppo_di_servizi.a_chi_si_rivolge',
        'required' => false
      ])
      ->add('howto', TextareaType::class, [
        'label' => 'gruppo_di_servizi.accedere',
        'required' => false
      ])
      ->add('special_cases', TextareaType::class, [
        'label' => 'gruppo_di_servizi.casi_particolari',
        'required' => false
      ])
      ->add('more_info', TextareaType::class, [
        'label' => 'gruppo_di_servizi.maggiori_info',
        'required' => false
      ])
      ->add('sticky', CheckboxType::class, [
        'label' => 'gruppo_di_servizi.in_evidenza',
        'required' => false,
      ])
      ->add(
        'topics',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\Categoria',
          'choice_label' => 'name',
          'label' => 'servizio.categoria',
        ]
      )
      ->add(
        'recipients',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\Recipient',
          'choice_label' => 'name',
          'label' => 'servizio.destinatari',
          'attr' => ['style' => 'columns: 2;'],
          'required' => false,
          'multiple' => true,
          'expanded' => true
        ]
      )
      ->add(
        'geographic_areas',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\GeographicArea',
          'choice_label' => 'name',
          'label' => 'servizio.aree_geografiche',
          'attr' => ['style' => 'columns: 2;'],
          'required' => false,
          'multiple' => true,
          'expanded' => true
        ]
      )
      ->add('coverage', TextType::class, [
        'label' => 'gruppo_di_servizi.copertura_helper',
        'data' => is_array($serviceGroup->getCoverage()) ? implode(',', $serviceGroup->getCoverage()) : $serviceGroup->getCoverage(),
        'required' => false
      ])
      ->add('register_in_folder', CheckboxType::class, [
        'label' => 'gruppo_di_servizi.protocolla_in_fascicolo',
        'required' => false
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\ServiceGroup',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_service_group_type';
  }
}
