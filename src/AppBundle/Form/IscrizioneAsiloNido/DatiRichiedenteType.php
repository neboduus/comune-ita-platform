<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Entity\CPSUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DatiRichiedenteType extends AbstractType
{
    const CAMPI_RICHIEDENTE = array(
        'richiedente_nome' => true,
        'richiedente_cognome' => true,
        'richiedente_luogo_nascita' => true,
        'richiedente_data_nascita' => true,
        'richiedente_indirizzo_residenza' => true,
        'richiedente_cap_residenza' => true,
        'richiedente_citta_residenza' => true,
        'richiedente_telefono' => false,
        'richiedente_email' => false,
    );

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('iscrizione_asilo_nido.guida_alla_compilazione.dati_richiedente', true);

        /** @var CPSUser $user */
        $user = $builder->getData()->getUser();
        foreach (self::CAMPI_RICHIEDENTE as $identifier => $disabledBecauseProvidedByCPS) {
            $type = TextType::class;
            $opts = [
                "label" => 'iscrizione_asilo_nido.datiRichiedente.'.$identifier,
                'disabled' => $disabledBecauseProvidedByCPS,
            ];
            switch ($identifier) {
                case 'richiedente_telefono':
                    $type = TextType::class;
                    $opts['disabled'] = $user->getTelefono() == null ? false : true;
                    break;
                case 'richiedente_email':
                    $type = EmailType::class;
                    $opts['disabled'] = $user->getEmail() == null ? false : true;
                    break;
                case 'richiedente_data_nascita':
                    $type = DateType::class;
                    $opts['widget'] = 'single_text';
                    break;
                default:
                    break;
            }
            $builder->add($identifier, $type, $opts);
        }
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_richiedente';
    }
}
