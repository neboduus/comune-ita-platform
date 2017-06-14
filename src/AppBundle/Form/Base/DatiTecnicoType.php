<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DatiTecnicoType extends DatiRichiedenteType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('disclaimer_tecnico', CheckboxType::class, [
            'disabled' => true,
            'mapped' => false,
            'required'=> true,
            'attr' => [
                'checked' => true,
            ],
            'label' => 'Dichiaro di essere autorizzato a presentare la presente domanda per conto dei soggetti elencati negli "ALLEGATI A" che saranno allegati', //TODO: translate me
            'value' => 'checked',
        ]);
        parent::buildForm($builder, $options);
    }

    public function getBlockPrefix()
    {
        return 'pratica_richiedente';
    }
}
