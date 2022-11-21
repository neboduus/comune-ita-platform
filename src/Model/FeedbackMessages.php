<?php


namespace App\Model;


use App\Entity\Pratica;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\Constraints as Assert;


class FeedbackMessages implements \JsonSerializable
{
  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status draft message is required")
   * @OA\Property(description="Status draft feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusDraft;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status pre submit message is required")
   * @OA\Property(description="Status pre submit feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusPreSubmit;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status submitted message is required")
   * @OA\Property(description="Status submitted feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusSubmitted;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status registered message is required")
   * @OA\Property(description="Status registered feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusRegistered;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status pending message is required")
   * @OA\Property(description="Status pending feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusPending;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status complete message is required")
   * @OA\Property(description="Status complete feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusComplete;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status cancelled message is required")
   * @OA\Property(description="Status cancelled feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusCancelled;

  /**
   * @var FeedbackMessage
   * @Assert\NotNull(message="status withdraw message is required")
   * @OA\Property(description="Status withdraw feedback message ", type="object", ref=@Model(type=FeedbackMessage::class, groups={"read", "write"}))
   */
  private $statusWithdraw;

  /**
   * @return FeedbackMessage
   */
  public function getStatusDraft(): FeedbackMessage
  {
    return $this->statusDraft;
  }


  /**
   * @param FeedbackMessage $statusDraft
   */
  public function setStatusDraft(FeedbackMessage $statusDraft)
  {
    $this->statusDraft = $statusDraft;
  }


  /**
   * @return FeedbackMessage
   */
  public function getStatusPreSubmit(): FeedbackMessage
  {
    return $this->statusPreSubmit;
  }


  /**
   * @param FeedbackMessage $statusPreSubmit
   */
  public function setStatusPreSubmit(FeedbackMessage $statusPreSubmit)
  {
    $this->statusPreSubmit = $statusPreSubmit;
  }

  /**
   * @return FeedbackMessage
   */
  public function getStatusSubmitted(): FeedbackMessage
  {
    return $this->statusSubmitted;
  }


  /**
   * @param FeedbackMessage $statusSubmitted
   */
  public function setStatusSubmitted(FeedbackMessage $statusSubmitted)
  {
    $this->statusSubmitted = $statusSubmitted;
  }

  /**
   * @return FeedbackMessage
   */
  public function getStatusRegistered(): FeedbackMessage
  {
    return $this->statusRegistered;
  }


  /**
   * @param FeedbackMessage $statusRegistered
   */
  public function setStatusRegistered(FeedbackMessage $statusRegistered)
  {
    $this->statusRegistered = $statusRegistered;
  }

  /**
   * @return FeedbackMessage
   */
  public function getStatusPending(): FeedbackMessage
  {
    return $this->statusPending;
  }


  /**
   * @param FeedbackMessage $statusPending
   */
  public function setStatusPending(FeedbackMessage $statusPending)
  {
    $this->statusPending = $statusPending;
  }

  /**
   * @return FeedbackMessage
   */
  public function getStatusComplete(): FeedbackMessage
  {
    return $this->statusComplete;
  }


  /**
   * @param FeedbackMessage $statusComplete
   */
  public function setStatusComplete(FeedbackMessage $statusComplete)
  {
    $this->statusComplete = $statusComplete;
  }

  /**
   * @return FeedbackMessage
   */
  public function getStatusCancelled(): FeedbackMessage
  {
    return $this->statusCancelled;
  }


  /**
   * @param FeedbackMessage $statusCancelled
   */
  public function setStatusCancelled(FeedbackMessage $statusCancelled)
  {
    $this->statusCancelled = $statusCancelled;
  }

  /**
   * @return FeedbackMessage
   */
  public function getStatusWithdraw(): FeedbackMessage
  {
    return $this->statusWithdraw;
  }


  /**
   * @param FeedbackMessage $statusWithdraw
   */
  public function setStatusWithdraw(FeedbackMessage $statusWithdraw)
  {
    $this->statusWithdraw = $statusWithdraw;
  }

  /**
   * @param $statusCode
   * @return FeedbackMessage|null
   */
  public function getMessageByStatusCode($statusCode): ?FeedbackMessage
  {
    switch ($statusCode) {
      case Pratica::STATUS_DRAFT:
        return $this->getStatusDraft();
      case Pratica::STATUS_PRE_SUBMIT:
        return $this->getStatusPreSubmit();
      case Pratica::STATUS_SUBMITTED:
        return $this->getStatusSubmitted();
      case Pratica::STATUS_REGISTERED:
        return $this->getStatusRegistered();
      case Pratica::STATUS_PENDING:
        return $this->getStatusPending();
      case Pratica::STATUS_COMPLETE:
        return $this->getStatusComplete();
      case Pratica::STATUS_CANCELLED:
        return $this->getStatusCancelled();
      case Pratica::STATUS_WITHDRAW:
        return $this->getStatusWithdraw();
      default:
        return null;
    }
  }

  /**
   * @param $statusCode
   * @param FeedbackMessage $feedbackMessage
   * @return $this
   */
  public function setMessageByStatusCode($statusCode, FeedbackMessage $feedbackMessage): FeedbackMessages
  {
    switch ($statusCode) {
      case Pratica::STATUS_DRAFT:
        $this->setStatusDraft($feedbackMessage);
        break;
      case Pratica::STATUS_PRE_SUBMIT:
        $this->setStatusPreSubmit($feedbackMessage);
        break;
      case Pratica::STATUS_SUBMITTED:
        $this->setStatusSubmitted($feedbackMessage);
        break;
      case Pratica::STATUS_REGISTERED:
        $this->setStatusRegistered($feedbackMessage);
        break;
      case Pratica::STATUS_PENDING:
        $this->setStatusPending($feedbackMessage);
        break;
      case Pratica::STATUS_COMPLETE:
        $this->setStatusComplete($feedbackMessage);
        break;
      case Pratica::STATUS_CANCELLED:
        $this->setStatusCancelled($feedbackMessage);
        break;
      case Pratica::STATUS_WITHDRAW:
        $this->setStatusWithdraw($feedbackMessage);
        break;
      default:
        break;
    }
    return $this;
  }

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }

}
