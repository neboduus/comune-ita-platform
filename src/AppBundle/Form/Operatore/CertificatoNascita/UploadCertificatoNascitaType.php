<?php

namespace AppBundle\Form\Operatore\CertificatoNascita;

use AppBundle\Entity\AllegatoOperatore;
use AppBundle\Entity\CambioResidenza;
use AppBundle\Form\Base\ChooseAllegatoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class UploadCertificatoNascitaType extends AbstractType
{
    const FILE_DESCRIPTION = "Certificato di nascita richiesto";

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();

        $helper->setGuideText('operatori.allega_certificato_richiesto', true);
        $builder
            ->add('allegati_operatore', ChooseAllegatoType::class, [
                'label' => 'operatori.certificato',
                'fileDescription' => self::FILE_DESCRIPTION,
                'required' => true,
                'pratica' => $builder->getData(),
                'class' => AllegatoOperatore::class,
                'mapped' => false,
            ]);

    }

    public function getBlockPrefix()
    {
        return 'upload_certificato_nascita';
    }
}
