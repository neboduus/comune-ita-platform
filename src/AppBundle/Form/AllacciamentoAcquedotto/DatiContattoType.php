<?php

namespace AppBundle\Form\AllacciamentoAcquedotto;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\VarDumper\VarDumper;

class DatiContattoType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('allacciamento_acquedotto.guida_alla_compilazione.dati_contatto', true);

        $builder
            ->add('allacciamentoAcquedottoUseAlternateContact', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    "Usa l'indirizzo di residenza" => 0,
                    "Usa i dati sotto riportati" => 1,
                ],
                'label' => 'allacciamento_acquedotto.datiContatto.use_alternate',
            ])
            ->add('allacciamentoAcquedottoAlternateContactVia', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiContatto.indirizzo',
            ])
            ->add('allacciamentoAcquedottoAlternateContactCivico', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiContatto.numero_civico',
            ])
            ->add('allacciamentoAcquedottoAlternateContactCAP', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiContatto.cap',
            ])
            ->add('allacciamentoAcquedottoAlternateContactComune', TextType::class, [
                'required' => false,
                'label' => 'allacciamento_acquedotto.datiContatto.comune',
            ]);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));

    }

    public function getBlockPrefix()
    {
        return 'allacciamento_acquedotto_dati_contatto';
    }

    /**
     * FormEvents::PRE_SUBMIT $listener
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if ((int)$data['allacciamentoAcquedottoUseAlternateContact'] == 1){
            unset($data['allacciamentoAcquedottoUseAlternateContact']);
            foreach($data as $key => $value){
                if (empty($value)){
                    $event->getForm()->addError(new FormError('Completa tutti i campi'));
                    break;
                }
            }
        }
    }

}
