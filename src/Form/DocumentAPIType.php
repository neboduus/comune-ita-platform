<?php

namespace App\Form;

use App\Entity\Document;
use App\Services\Manager\DocumentManager;
use League\Flysystem\FileExistsException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DocumentAPIType extends AbstractType
{
  private $allowedExtensions;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var DocumentManager
   */
  private $documentManager;

  public function __construct($allowedExtensions, TranslatorInterface $translator, DocumentManager $documentManager)
  {
    $this->translator = $translator;
    $this->documentManager = $documentManager;
    $this->allowedExtensions = array_merge(...$allowedExtensions);
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('owner', EntityType::class, [
        'class' => 'App\Entity\CPSUser',
        'required' => true,
        'label' => 'documenti.documento.owner'
      ])
      ->add('folder', EntityType::class, [
        'class' => 'App\Entity\Folder',
        'required' => true,
        'label' => 'documenti.documento.folder'
      ])
      ->add('md5', TextType::class, [
        'label' => 'documenti.documento.md5',
        'required' => false
      ])
      ->add('mime_type', TextType::class, [
        'label' => 'documenti.documento.mime_type',
        'required' => false
      ])
      ->add('original_filename', TextType::class, [
        'label' => 'documenti.documento.original_filename',
        'required' => false
      ])
      ->add('address', UrlType::class, [
        'label' => 'documenti.documento.address',
        'required' => false
      ])
      ->add('file', TextType::class, [
        'label' => 'documenti.documento.file',
        'required' => false,
        'mapped' => false
      ])
      ->add('title', TextType::class, [
        'label' => 'documenti.documento.title',
        'required' => true
      ])
      ->add('topics', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'documenti.documento.Topics',
        'multiple' => true
      ])
      ->add('description', TextareaType::class, [
        'label' => 'documenti.documento.description',
        'required' => false
      ])
      ->add('readers_allowed', CollectionType::class, [
        'entry_type' => TextType::class,
        'label' => 'documenti.documento.readers_allowed',
        'required' => false,
        'allow_add' => true
      ])
      ->add('validity_begin', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'documenti.documento.validity_begin'
      ])
      ->add('validity_end', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'documenti.documento.validity_end'
      ])
      ->add('expire_at', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'documenti.documento.expire_at'
      ])
      ->add('due_date', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'documenti.documento.data_scadenza'
      ])
      ->add('correlated_services', EntityType::class, [
        'class' => 'App\Entity\Servizio',
        'label' => 'documenti.cartella.servizi_correlati',
        'multiple' => true
      ])
      ->add('store', CheckboxType::class, [
        'label' => 'documenti.documento.store',
        'required' => false
      ])
      ->addEventListener(FormEvents::SUBMIT, array($this, 'onSubmit'));
  }

  public function onSubmit(FormEvent $event)
  {
    // get the form
    $form = $event->getForm();
    /** @var Document $document */
    $document = $event->getForm()->getData();

    $ASCII_fileName = mb_convert_encoding($document->getOriginalFilename(), "ASCII", "auto");
    if ($ASCII_fileName != $document->getOriginalFilename()) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.invalid_original_name')));
    }

    // Check folder
    if (!$document->getFolder()) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.invalid_folder')));
    }

    if ($document->getOwner() !== $document->getFolder()->getOwner()) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.invalid_owner')));
    }

    if (!in_array($document->getMimeType(), $this->allowedExtensions)) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.invalid_mime_type')));
    }

    $extension = explode('.', $document->getOriginalFilename());
    if (count($extension) < 2) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.missing_extension')));
    } else if (!array_key_exists(end($extension), $this->allowedExtensions)) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.invalid_extension')));
    }

    $extension = end($extension);

    if ($form->get("file")->getData()) {
      // If both file and address are provided, keep file
      $document->setAddress(null);
      $document->setStore(true);
      $base64 = explode(',', $form->get("file")->getData());
      $base64 = end($base64);
      $content = base64_decode($base64);
    } else {
      $content = file_get_contents($document->getAddress());
    }

    $md5 = md5($content);
    if ($document->getMd5() && $document->getMd5() !== $md5) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.non_matching_md5')));
    } else if (!$document->getMd5()) {
      $document->setMd5($md5);
    }

    $f = finfo_open();
    $mime_type = finfo_buffer($f, $content, FILEINFO_MIME_TYPE);
    if ($document->getMimeType() !== $mime_type) {
      return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.non_matching_mime_type')));
    }

    if ($document->isStore()) {
      try {
        $this->documentManager->save($document, $content);
      } catch (FileExistsException $e) {
        return $event->getForm()->addError(new FormError($this->translator->trans('documenti.documento.duplicated_file')));
      }
    }

    return $event;
  }

  public
  function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\Document',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_document_api';
  }
}
