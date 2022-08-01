<?php

namespace App\Form\Operatore\Base;

use App\Entity\Allegato;
use App\Entity\AllegatoOperatore;
use App\Entity\Pratica;
use App\Form\Base\ChooseAllegatoType;
use App\Form\Extension\TestiAccompagnatoriProcedura;
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
