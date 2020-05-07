<?php

namespace App\Protocollo;

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
            'recipientIDArray',
            'recipientTypeIDArray',
            'codeNodeClassification',
            'codeAdm',
            'trasmissionIDArray',
            'instance'
        );
    }

    public function getInstance()
    {
        return $this->get('instance');
    }

    public function setInstance($instance)
    {
        $this->set('instance', $instance);
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
        $this->set('codeAdm', $codeAdm);
    }

    /**
     * @param string $recipientIDArray
     */
    public function setRecipientIdArray($recipientIDArray)
    {
        $this->set('recipientIDArray', $recipientIDArray);
    }


    public function addRecipientId($recipientID)
    {
        $recipientIDArray = array();
        if ($this->has('recipientIDArray')) {
            $recipientIDArray = $this->getRecipientIDArray();
        }
        $recipientIDArray [] = $recipientID;
        $this->set('recipientIDArray', $recipientIDArray);
    }

    /**
     * @param string $recipientID
     */
    // FIXME: il wrapper da errore se passo un array, verificare con Francesco
    /**
     * @return string
     */
    public function getRecipientIDArray()
    {
        return $this->get('recipientIDArray');
    }

    /**
     * @param string $recipientTypeIDArray
     */
    public function setRecipientTypeIDArray($recipientTypeIDArray)
    {
        $this->set('recipientTypeIDArray', $recipientTypeIDArray);
    }


    public function addRecipientTypeID($recipientTypeID)
    {
        $recipientTypeIDArray = array();
        if ($this->has('recipientTypeIDArray')) {
            $recipientTypeIDArray = $this->getrecipientTypeIDArray();
        }
        $recipientTypeIDArray [] = $recipientTypeID;
        $this->set('recipientTypeIDArray', $recipientTypeIDArray);
    }

    /**
     * @param string $recipientID
     */
    // FIXME: il wrapper da errore se passo un array, verificare con Francesco
    /**
     * @return string
     */
    public function getRecipientTypeIDArray()
    {
        return $this->get('recipientTypeIDArray');
    }

    /**
     * @return string
     */
    public function getTrasmissionIDArray()
    {
        return $this->get('trasmissionIDArray');
    }

    /**
     * @param string $trasmissionIDArray
     */
    public function setTrasmissionIDArray($trasmissionIDArray)
    {
        $this->set('trasmissionIDArray', $trasmissionIDArray);
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

    public function setFileName($fileName)
    {
        $this->set('fileName', $fileName);
    }

    /**
     * fileContent is file in base64
     * @param $fileContent
     */
    public function setFile($fileContent)
    {
        $this->set('file', $fileContent);
    }

    public function setChecksum($checksum)
    {
        $this->set('checksum', strtoupper($checksum));
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

    /**
     * @param $idProject
     * idProject valorizzato fa in modo che il documento venga inserito in quel preciso fascicolo
     */
    public function setIdProject($idProject)
    {
        $this->set('idProject', $idProject);
    }

    /**
     * @param $createProject
     */
    public function setCreateProject($createProject)
    {
        $this->set('createProject', $createProject);
    }

    public function setDocumentType($documentType)
    {
        $this->set('documentType', $documentType);
    }


    public function getDocumentType()
    {
        return $this->get('documentType');
    }

    // Sender name
    public function setSenderName($senderName)
    {
        $this->set('senderName', $senderName);
    }


    public function getSenderName()
    {
        return $this->get('senderName');
    }

    // Sender surname
    public function setSenderSurname($senderSurname)
    {
        $this->set('senderSurname', $senderSurname);
    }


    public function getSenderSurname()
    {
        return $this->get('senderSurname');
    }

    // Sender cf
    public function setSenderCf($senderCf)
    {
        $this->set('senderCf', $senderCf);
    }


    public function getSenderCf()
    {
        return $this->get('senderCf');
    }

    // Sender Email
    public function setSenderEmail($senderEmail)
    {
        $this->set('senderEmail', $senderEmail);
    }


    public function getSenderEmail()
    {
        return $this->get('senderEmail');
    }
}
