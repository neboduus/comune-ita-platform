<?php

namespace AppBundle\Form\AutoletturaAcqua;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DatiLetturaType extends AbstractType
{
    const CAMPI_LETTURA = [
        'lettura_metri_cubi',
        'lettura_data',
    ];

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('autolettura_acqua.guida_alla_compilazione.dati_lettura', true);

        foreach (self::CAMPI_LETTURA as $identifier) {
            $type = TextType::class;
            $opts = [
                "label" => 'autolettura_acqua.datiLettura.'.$identifier
            ];
            switch ($identifier) {
                case 'lettura_data':
                    $type = DateType::class;
                    $opts += [
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                        'attr' => [
                            'class' => 'form-control input-inline datepicker',
                            'data-provide' => 'datepicker',
                            'data-date-format' => 'dd-mm-yyyy'
                        ]
                    ];
                    break;
                default:
                    break;
            }
            $builder->add($identifier, $type, $opts);
        }
    }

    public function getBlockPrefix()
    {
        return 'autolettura_acqua_lettura';
    }
}
