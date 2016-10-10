<?php

namespace AppBundle\Form\AutoletturaAcqua;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DatiContatoreType extends AbstractType
{
    const CAMPI_CONTATORE = [
        'contatore_numero',
        'contatore_uso',
        'contatore_unita_immobiliari',
    ];

    const TIPI_USO = [
        "DOMESTICO",
        "NON DOMESTICO (uffici, negozi etc.)",
        "IRRIGUO (giardino, orto)",
        "ALLEVAMENTO ANIMALI (stalle per abbevera mento animali)",
    ];

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('autolettura_acqua.guida_alla_compilazione.dati_contatore', true);

        foreach (self::CAMPI_CONTATORE as $identifier) {
            $type = TextType::class;
            $opts = [
                "label" => 'autolettura_acqua.datiContatore.'.$identifier
            ];
            switch ($identifier) {
                case 'contatore_uso':
                    $type = ChoiceType::class;
                    $opts['choices'] = array_combine( self::TIPI_USO, self::TIPI_USO);
                    break;
                case 'contatore_unita_immobiliari':
                    $type = IntegerType::class;
                    break;
                default:
                    break;
            }
            $builder->add($identifier, $type, $opts);
        }
    }

    public function getBlockPrefix()
    {
        return 'autolettura_acqua_contatore';
    }
}
