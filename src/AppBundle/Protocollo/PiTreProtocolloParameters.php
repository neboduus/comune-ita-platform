<?php

namespace AppBundle\Protocollo;

use Symfony\Component\HttpFoundation\ParameterBag;

class PiTreProtocolloParameters extends ParameterBag
{
    /**
     * @return string
     */
    public function getRecipientId()
    {
        return $this->get('recipientId');
    }

    /**
     * @param string $recipientId
     */
    public function setRecipientId($recipientId)
    {
        $this->set('recipientId', $recipientId );
    }

    /**
     * @return string
     */
    public function getRecipientIdType()
    {
        return $this->get('recipientIdType');
    }

    /**
     * @param string $recipientIdType
     */
    public function setRecipientIdType($recipientIdType)
    {
        $this->set('recipientIdType', $recipientIdType);
    }

    /**
     * @return string
     */
    public function getCodeNodeClassification()
    {
        return $this->get('codeNodeClassification');
    }

    /**
     * @param string $codeNodeClassification
     */
    public function setCodeNodeClassification($codeNodeClassification)
    {
        $this->set('codeNodeClassification', $codeNodeClassification);
    }

    public function setFilePath($filePath)
    {
        $this->set('filePath', $filePath);
    }

    public function setProjectDescription($projectDescription)
    {
        $this->set('projectDescription', $projectDescription);
    }

    public function setDocumentDescription($documentDescription)
    {
        $this->set('documentDescription', $documentDescription);
    }

    public function setDocumentObj($documentObj)
    {
        $this->set('documentObj', $documentObj);
    }

    public function setDocumentId($documentId)
    {
        $this->set('documentId', $documentId);
    }

    public function setAttachmentDescription($attachmentDescription)
    {
        $this->set('attachmentDescription', $attachmentDescription);
    }

}
