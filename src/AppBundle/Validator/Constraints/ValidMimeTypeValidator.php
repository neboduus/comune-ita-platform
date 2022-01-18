<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Translation\TranslatorInterface;
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
    if ($value->getFile() == null || !in_array(
        $value->getMimeType(),
        $this->allowedExtensions
      )) {
      $translatedMessage = $this->translator->trans(ValidMimeType::TRANSLATION_ID);
      $this->context
        ->buildViolation($translatedMessage)
        ->atPath('file')
        ->addViolation();
    }
  }
}
