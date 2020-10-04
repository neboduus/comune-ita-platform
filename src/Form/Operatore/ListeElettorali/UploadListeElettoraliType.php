<?php

namespace App\Form\Operatore\ListeElettorali;

use App\Entity\AllegatoOperatore;
use App\Entity\CambioResidenza;
use App\Form\Base\ChooseAllegatoType;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UploadListeElettoraliType extends AbstractType
{
    const FILE_DESCRIPTION = "Attestazione di iscrizione alle liste elettorali richiesto";

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
        return 'upload_attestazione_liste_elettorali';
    }
}
