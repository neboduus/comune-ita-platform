<?php
namespace App\Validator\Constraints;

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

    /**
     * ValidMimeTypeValidator constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param mixed      $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $mimeType = $value->getFile()->getMimeType();
        if ($value->getFile() == null || !in_array(
            $value->getFile()->getMimeType(),
            array(
                'image/jpeg',
                'image/gif',
                'image/png',
                'application/postscript',
                'application/pdf',
                'application/octet-stream',
            )
        )) {
            $translatedMessage = $this->translator->trans(ValidMimeType::TRANSLATION_ID);
            $this->context
                ->buildViolation($translatedMessage)
                ->atPath('file')
                ->addViolation();
        }
    }
}
