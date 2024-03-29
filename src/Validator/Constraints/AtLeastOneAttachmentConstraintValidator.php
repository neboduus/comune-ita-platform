<?php

namespace App\Validator\Constraints;

use App\Entity\Allegato;
use App\Entity\SciaPraticaEdilizia;
use App\Services\DirectoryNamerService;
use App\Services\FileSystemService;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class AtLeastOneAttachmentConstraintValidator extends ConstraintValidator
{

  /**
   * @var DirectoryNamerService
   */
  private $directoryNamer;

  /**
   * @var PropertyMappingFactory
   */
  private $pmf;

  /**
   * @var FileSystemService
   */
  private $fs;

  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * AtLeastOneAttachmentConstraintValidator constructor.
   *
   * @param DirectoryNamerService $directoryNamer
   * @param PropertyMappingFactory $pmf
   * @param FileSystemService $fs
   * @param EntityManagerInterface $em
   */
  public function __construct(
    DirectoryNamerService $directoryNamer,
    PropertyMappingFactory $pmf,
    FileSystemService $fs,
    EntityManagerInterface $em
  ) {
    $this->directoryNamer = $directoryNamer;
    $this->pmf = $pmf;
    $this->fs = $fs;
    $this->em = $em;
  }

  /**
   * @param string[] $value
   * @param AtLeastOneAttachmentConstraint|Constraint $constraint
   */
  public function validate($value, Constraint $constraint)
  {
    if (empty($value)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('{{ string }}', "You must choose at least one file to attach to this form")
        ->addViolation();
    } else {
      $allegatiRepo = $this->em->getRepository('App\Entity\Allegato');
      foreach ($value as $id) {
        $allegato = $allegatiRepo->find($id);
        if ($allegato instanceof Allegato) {
          $filename = $allegato->getFilename();

          /** @var PropertyMapping $mapping */
          $mapping = $this->pmf->fromObject($allegato)[0];
          $destDir = $this->directoryNamer->directoryName(
              $allegato,
              $mapping
            );
          $filePath = $destDir . DIRECTORY_SEPARATOR . $filename;

          if (!$this->fs->getFilesystem()->has($filePath)) {
            $this->context->buildViolation($constraint->message)
              ->setParameter('{{ string }}', $filePath)
              ->addViolation();
          }
        } else {
          $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}', "You must choose at least one file to attach to this form")
            ->addViolation();
        }
      }
    }
  }
}
