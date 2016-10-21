<?php

namespace AppBundle\Form\AutoletturaAcqua;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Entity\CPSUser;

class DatiIntestatarioType extends AbstractType
{
    const CAMPI_INTESTATARIO = array(
        'intestatario_codice_utente',
        'intestatario_nome',
        'intestatario_cognome',
        'intestatario_indirizzo',
        'intestatario_cap',
        'intestatario_citta',
        'intestatario_telefono',
        'intestatario_email',
    );

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('autolettura_acqua.guida_alla_compilazione.dati_intestatario', true);

        /** @var CPSUser $user */
        $user = $builder->getData()->getUser();

        foreach (self::CAMPI_INTESTATARIO as $identifier) {
            $type = TextType::class;
            $opts = [
                "label" => 'autolettura_acqua.datiIntestatario.'.$identifier
            ];
            switch ($identifier) {
                case 'intestatario_telefono':
                    $type = TextType::class;
                    break;
                case 'intestatario_cap':
                    $type = IntegerType::class;
                    break;
                case 'intestatario_email':
                    $type = EmailType::class;
                    break;
                default:
                    break;
            }
            $builder->add($identifier, $type, $opts);
        }
    }

    public function getBlockPrefix()
    {
        return 'autolettura_acqua_intestatario';
    }
}
