<?php

namespace AppBundle\Form\Operatore\StatoFamiglia;

use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\CambioResidenza;
use AppBundle\Form\Base\ChooseAllegatoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class UploadStatoFamigliaType extends AbstractType
{
    const FILE_DESCRIPTION = "Certificato dello stato di famiglia richiesto";

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();

        $helper->setGuideText('operatori.flow.allega_documentazione_richiesta', true);
        $builder
            ->add('allegati_operatore', ChooseAllegatoType::class, [
                'label' => 'operatori.flow.allega_documentazione_richiesta',
                'fileDescription' => self::FILE_DESCRIPTION,
                'required' => true,
                'pratica' => $builder->getData(),
                'class' => AllegatoOperatore::class,
                'mapped' => false,
            ]);
    }

    public function getBlockPrefix()
    {
        return 'upload_stato_famiglia';
    }
}
