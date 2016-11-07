<?php


namespace AppBundle\Form\AllacciamentoAcquedotto;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DatiImmobileType extends AbstractType
{

    const TIPI_QUALIFICA = [
        'proprietario',
        'locatario',
        'erede/familiare/convivente',
        'assegnatario dell\'immobile',
        'altro',
    ];

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('allacciamento_acquedotto.guida_alla_compilazione.dati_immobile', true);

        $builder
            ->add('allacciamentoAcquedottoImmobileQualifica', ChoiceType::class, [
                'required' => true,
                'choices' => array_combine(self::TIPI_QUALIFICA, self::TIPI_QUALIFICA),
                'label' => 'allacciamento_acquedotto.datiImmobile.qualifica',
            ])
            ->add('allacciamentoAcquedottoImmobileProvincia', TextType::class, [
                'required' => true,
                'label' => 'allacciamento_acquedotto.datiImmobile.provincia',
            ])
            ->add('allacciamentoAcquedottoImmobileComune', TextType::class, [
                'required' => true,
                'label' => 'allacciamento_acquedotto.datiImmobile.comune',
            ])
            ->add('allacciamentoAcquedottoImmobileIndirizzo', TextType::class, [
                'required' => true,
                'label' => 'allacciamento_acquedotto.datiImmobile.indirizzo',
            ])
            ->add('allacciamentoAcquedottoImmobileNumeroCivico', TextType::class, [
                'required' => true,
                'label' => 'allacciamento_acquedotto.datiImmobile.numero_civico',
            ])
            ->add('allacciamentoAcquedottoImmobileCap', IntegerType::class, [
                'required' => true,
                'label' => 'allacciamento_acquedotto.datiImmobile.cap',
            ])
            ->add('allacciamentoAcquedottoImmobileScala', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.scala',
            ])
            ->add('allacciamentoAcquedottoImmobilePiano', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.piano',
            ])
            ->add('allacciamentoAcquedottoImmobileInterno', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.interno',
            ])
            ->add('allacciamentoAcquedottoImmobileCatastoCategoria', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.catasto_categoria',
            ])
            ->add('allacciamentoAcquedottoImmobileCatastoCodiceComune', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.catasto_codice_comune',
            ])
            ->add('allacciamentoAcquedottoImmobileCatastoFoglio', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.catasto_foglio',
            ])
            ->add('allacciamentoAcquedottoImmobileCatastoSezione', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.catasto_sezione',
            ])
            ->add('allacciamentoAcquedottoImmobileCatastoMappale', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiImmobile.catasto_mappale',
            ]);
    }

    public function getBlockPrefix()
    {
        return 'allacciamento_acquedotto_dati_immobile';
    }
}
