<?php

namespace App\Form\IscrizioneRegistroAssociazioni;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class OrgRichiedenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
        ->add('ruoloUtenteOrgRichiedente', TextType::class, ["label" => 'steps.common.org_richiedente.ruolo_utente'])
        ->add('nome_associazione', TextType::class, ["label" => 'steps.common.org_richiedente.nome_associazione'])
        ->add('natura_giuridica', TextType::class, ["label" => 'steps.common.org_richiedente.natura_giuridica'])
        ->add('sito', UrlType::class, ["label" => 'steps.common.org_richiedente.sito'])
        ->add('pagina_social', UrlType::class, ["label" => 'steps.common.org_richiedente.pagina_social'])
        ->add('e_mail', EmailType::class, ["label" => 'steps.common.org_richiedente.e_mail'])
        ->add('numero_iscritti', NumberType::class, ["label" => 'steps.common.org_richiedente.numero_iscritti'])
        ->add('modalita_di_adesione', TextareaType::class, ["label" => 'steps.common.org_richiedente.modalita_di_adesione'])
        ->add('attivita', TextareaType::class, ["label" => 'steps.common.org_richiedente.attivita'])
        ->add('obiettivi', TextareaType::class, ["label" => 'steps.common.org_richiedente.obiettivi'])
        ->add('sede_legale', TextType::class, ["label" => 'steps.common.org_richiedente.sede_legale'])
        ->add('sede_operativa', TextType::class, ["label" => 'steps.common.org_richiedente.sede_operativa'])
        ->add('indirizzo', TextType::class, ["label" => 'steps.common.org_richiedente.indirizzo'])
        ->add('contatti', TextType::class, ["label" => 'steps.common.org_richiedente.contatti']);
    }

    public function getBlockPrefix()
    {
        return 'contributo_associazioni_org_richiedente';
    }
}
