<?php

namespace AppBundle\Form\Operatore\Base;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Base\ChooseAllegatoType;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UploadAllegatoOperatoreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var Pratica $pratica */
        $pratica = $builder->getData();

        $helper->setGuideText('operatori.flow.allega_documentazione_richiesta', true);
        $builder
            ->add('allegati_operatore', ChooseAllegatoType::class, [
                'label' => 'operatori.flow.allega_documentazione_richiesta',
                'fileDescription' => $pratica->getServizio()->getName() . ' pratica ' . $pratica->getUser()->getFullName() . ' del ' . strftime( '%d/%m/%Y %H:%M',  $pratica->getCreationTime()),
                'required' => true,
                'pratica' => $builder->getData(),
                'class' => AllegatoOperatore::class,
                'mapped' => false,
            ]);

    }

    public function getBlockPrefix()
    {
        return 'upload_allegato_operatore';
    }
}
