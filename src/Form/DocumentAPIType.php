<?php

namespace App\Form;

use App\Entity\Document;
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

class DocumentAPIType extends AbstractType
{
    private $rootDir;
    private $allowedExtensions;

    public function __construct($rootDir, $allowedExtensions)
    {
        $this->rootDir = $rootDir;
        $this->allowedExtensions = array_merge(...$allowedExtensions);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('owner', EntityType::class, [
        'class' => 'App\Entity\CPSUser',
        'required' => true,
        'label' => 'Proprietario'
      ])
      ->add('folder', EntityType::class, [
        'class' => 'App\Entity\Folder',
        'required' => true,
        'label' => 'Cartella'
      ])
      ->add('md5', TextType::class, [
        'label' => 'md5',
        'required' => false
      ])
      ->add('mime_type', TextType::class, [
        'label' => 'mime-Type del file',
        'required' => false
      ])
      ->add('original_filename', TextType::class, [
        'label' => 'nome originale del file',
        'required' => false
      ])
      ->add('address', UrlType::class, [
        'label' => 'Url del file',
        'required' => false
      ])
      ->add('file', TextType::class, [
        'label' => 'File',
        'required' => false,
        'mapped' => false
      ])
      ->add('title', TextType::class, [
        'label' => 'Titolo',
        'required' => true
      ])
      ->add('topics', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'label' => 'Topics',
        'multiple' => true
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Descrizione',
        'required' => false
      ])
      ->add('readers_allowed', CollectionType::class, [
        'entry_type' => TextType::class,
        'label' => 'Cf di chi puo vedere questo documento',
        'required' => false,
        'allow_add' => true
      ])
      ->add('validity_begin', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'Data di inizio validità'
      ])
          ->add('validity_end', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'Data di fine validità'
      ])
      ->add('expire_at', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'Data di scadenza'
      ])
      ->add('due_date', DateTimeType::class, [
        'widget' => 'single_text',
        'required' => false,
        'label' => 'Data di scadenza'
      ])
      ->add('correlated_services', EntityType::class, [
        'class' => 'App\Entity\Servizio',
        'label' => 'Servizi correlati',
        'multiple' => true
      ])
      ->add('store', CheckboxType::class, [
        'label' => 'Salvare il file?',
        'required' => false,
        'mapped' => false
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
            return $event->getForm()->addError(new FormError('Original filename non è una stringa ASCII valida'));
        }

        // Check folder
        if (!$document->getFolder()) {
            return $event->getForm()->addError(new FormError('Cartella non valida'));
        }
        if ($document->getOwner() != $document->getFolder()->getOwner()) {
            return $event->getForm()->addError(new FormError('L\'utente fornito non è il proprietario della cartella'));
        }

        if (!in_array($document->getMimeType(), $this->allowedExtensions)) {
            return $event->getForm()->addError(
                new FormError('Mime type non valido')
            );
        }

        $extension = explode('.', $document->getOriginalFilename());
        if (count($extension) < 2) {
            return $event->getForm()->addError(
                new FormError('E\'obbligatorio specificare l\'estensione del file le campo original_filename')
            );
        } elseif (!array_key_exists(end($extension), $this->allowedExtensions)) {
            return $event->getForm()->addError(new FormError('Estensione non valida'));
        }

        $extension = end($extension);

        $directory = $this->rootDir . '/../var/uploads/documents/users/' . $document->getOwnerId() . DIRECTORY_SEPARATOR . $document->getFolderId();
        if (!is_dir($directory)) {
            mkdir($directory, 0770, true);
        }

        $fileName  = $directory . DIRECTORY_SEPARATOR . $document->getId() . '.' . $extension;
        if ($form->get("file")->getData()) {
            // If both file and address are provided, keep file
            $document->setAddress(null);
            $base64 = explode(',', $form->get("file")->getData());
            $base64=end($base64);

            try {
                $this->saveFileToLocalFileSystem($fileName, base64_decode($base64), $document->getMimeType());
            } catch (\Exception $e) {
                return $event->getForm()->addError(
                    new FormError($e->getMessage())
                );
            }
        } else {
            $store = $form->get("store")->getData();
            if ($store) {
                $fileName = $directory . DIRECTORY_SEPARATOR . $document->getId() . '.' . $extension;
                $content = file_get_contents($document->getAddress());
                try {
                    $this->saveFileToLocalFileSystem($fileName, $content, $document->getMimeType());
                } catch (\Exception $e) {
                    return $event->getForm()->addError(
                        new FormError($e->getMessage())
                    );
                }
            } else {
                $fileName = $document->getAddress();
            }
        }

        if ($document->getMd5() && $document->getMd5() != md5_file($fileName)) {
            return $event->getForm()->addError(new FormError('L\'md5 non coincide'));
        } elseif (!$document->getMd5()) {
            $document->setMd5(md5_file($fileName));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
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

    /**
     * @param $filename string file path
     * @param $content string file content
     * @param $mimeType string file mime type (for validity check)
     * @throws \Exception
     */
    private function saveFileToLocalFileSystem($filename, $content, $mimeType)
    {
        $fileSize = file_put_contents($filename, $content);
        if ($fileSize > 16000000) {
            unlink($filename);
            throw new \Exception('Documento troppo grande');
        }
        $fileMimeType = mime_content_type($filename);
        if ($fileMimeType != $mimeType) {
            unlink($filename);
            throw new \Exception('Il Mime type non coincide');
        }
    }
}
