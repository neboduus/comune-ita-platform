<?php

namespace App\Validator\Constraints;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class ValidMimeTypeValidator
 */
class ValidMimeTypeValidator extends ConstraintValidator
{

  /**
   * @var TranslatorInterface
   */
  private $translator;

  private $allowedExtensions;

  /**
   * ValidMimeTypeValidator constructor.
   * @param TranslatorInterface $translator
   * @param $allowedExtensions
   */
  public function __construct(TranslatorInterface $translator, $allowedExtensions)
  {
    $this->translator = $translator;
    $this->allowedExtensions = array_merge(...$allowedExtensions);
  }

  /**
   * @param mixed $value
   * @param Constraint $constraint
   */
  public function validate($value, Constraint $constraint)
  {
    // Fixme: controllo anche l'estensione perchè per i p7m il mimetype è application/octet-stream e fallisce
    if ($value->getFile() == null ||
        (!in_array($value->getMimeType(), $this->allowedExtensions) && !in_array($value->getFile()->getExtension(), array_keys($this->allowedExtensions)))) {
      $translatedMessage = $this->translator->trans(ValidMimeType::TRANSLATION_ID);
      $this->context
        ->buildViolation($translatedMessage)
        ->atPath('file')
        ->addViolation();
    }
  }
}
