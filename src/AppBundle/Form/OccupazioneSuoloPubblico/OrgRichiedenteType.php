<?php

namespace AppBundle\Form\OccupazioneSuoloPubblico;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OrgRichiedenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.occupazione_suolo_pubblico.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.occupazione_suolo_pubblico.org_richiedente.title', true);

        $builder->add('ruoloUtenteOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.ruolo_utente'])
            ->add('ragioneSocialeOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.ragione_sociale'])
            ->add('indirizzoOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.indirizzo'])
            ->add('civicoOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.civico'])
            ->add('capOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.cap'])
            ->add('comuneOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.comune'])
            ->add('provinciaOrgRichiedente', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.org_richiedente.provincia']);
    }

    public function getBlockPrefix()
    {
        return 'occupazione_suolo_pubblico_org_richiedente';
    }
}
