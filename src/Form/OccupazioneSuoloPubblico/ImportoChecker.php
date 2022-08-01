<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 07/12/18
 * Time: 11.25
 */

namespace App\Form\OccupazioneSuoloPubblico;


use App\Services\MyPayService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

class ImportoChecker implements EventSubscriberInterface
{

    /**
     * @var MyPayService
     */
    private $myPayService;

    public function __construct(MyPayService $myPayService) {
        $this->myPayService = $myPayService;
    }

    public static function getSubscribedEvents()
    {
        // TODO: Implement getSubscribedEvents() method.
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit'
        );
    }

}
