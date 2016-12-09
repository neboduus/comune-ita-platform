<?php

namespace AppBundle\Protocollo;

use Symfony\Component\HttpFoundation\ParameterBag;

class PiTreProtocolloParameters extends ParameterBag
{
    public function __construct(array $parameters = array())
    {
        parent::__construct($parameters);
    }

    public static function getEnteParametersKeys()
    {
        return array(
            'recipientID',
            'recipientIDType',
            'codeNodeClassification',
            'codeAdm'
        );
    }

    /**
     * @return string
     */
    public function getCodeAdm()
    {
        return $this->get('codeAdm');
    }

    /**
     * @param string $codeAdm
     */
    public function setCodeAdm($codeAdm)
    {
        $this->set('codeAdm', $codeAdm );
    }

    /**
     * @return string
     */
    public function getRecipientId()
    {
        return $this->get('recipientID');
    }

    /**
     * @param string $recipientId
     */
    public function setRecipientId($recipientId)
    {
        $this->set('recipientID', $recipientId );
    }

    /**
     * @return string
     */
    public function getRecipientIdType()
    {
        return $this->get('recipientIDType');
    }

    /**
     * @param string $recipientIdType
     */
    public function setRecipientIdType($recipientIdType)
    {
        $this->set('recipientIDType', $recipientIdType);
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
