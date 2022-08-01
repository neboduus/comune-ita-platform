<?php

namespace App\Form\Operatore\Base;

use App\Entity\Pratica;
use App\Entity\RispostaOperatore;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UploadRispostaOperatoreType extends AbstractType
{
    const FILE_DESCRIPTION = "File Risposta firmato (formato p7m)";


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Pratica $pratica */
        $pratica = $builder->getData();
        $slugEnte = $pratica->getEnte()->getSlug();

        $helper = $options["helper"];
        $helper->setGuideText('operatori.flow.upload_risposta_firmata.guida_alla_compilazione', true);
        $helper->setDescriptionText('operatori.flow.upload_risposta_firmata.testo_descrittivo', true, [
            '%link_download_risposta%' => '/' . $slugEnte . '/operatori/'.$pratica->getId().'/risposta_non_firmata'
        ]);

        $builder
            ->add('allegati_operatore', SignedAllegatoType::class, [
                'label' => 'operatori.flow.upload_risposta_firmata.allega_risposta_firmata',
                'fileDescription' => self::FILE_DESCRIPTION,
                'required' => true,
                'pratica' => $pratica,
                'class' => RispostaOperatore::class,
                'mapped' => false
            ]);
    }

    public function getBlockPrefix()
    {
        return 'upload_risposta_firmata';
    }
}
