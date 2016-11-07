<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\User;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class MailerServiceTest
 */
class MailerServiceTest extends AbstractAppTestCase
{
    /**
     * @test
     */
    public function setUp()
    {
        parent::setUp();
        $this->cleanDb(Allegato::class);
        $this->cleanDb(Pratica::class);
        $this->cleanDb(User::class);
    }

    /**
     * @test
     */
    public function testMailerServiceDispatchesMailToCPSUserForPraticaSubmitted()
    {
        $cpsUser = $this->createCPSUser();
        $swiftMailer = $this->setupSwiftmailerMock([$cpsUser]);
        $pratica = $this->createPratica($cpsUser);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);
        $this->container->set('swiftmailer.mailer.default', $swiftMailer);
        $mailerService = $this->container->get('ocsdc.mailer');
        $mailerService->dispatchMailForPratica($pratica, $this->container->getParameter('default_from_email_address'));
    }

    /**
     * @test
     */
    public function testMailerServiceDispatchesMailToCPSUserOnlyOncePerStatusChange()
    {
        $cpsUser = $this->createCPSUser();
        $swiftMailer = $this->setupSwiftmailerMock([]);
        $pratica = $this->createPratica($cpsUser);
        $pratica->setLatestCPSCommunicationTimestamp(time() + 1000);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);
        $this->container->set('swiftmailer.mailer.default', $swiftMailer);
        $mailerService = $this->container->get('ocsdc.mailer');
        $mailerService->dispatchMailForPratica($pratica, $this->container->getParameter('default_from_email_address'));
    }

    /**
     * @test
     */
    public function testMailerServiceCanBeForcedToDispatchMail()
    {
        $cpsUser = $this->createCPSUser();
        $swiftMailer = $this->setupSwiftmailerMock([$cpsUser]);
        $pratica = $this->createPratica($cpsUser);
        $pratica->setLatestCPSCommunicationTimestamp(time() + 1000);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);
        $this->container->set('swiftmailer.mailer.default', $swiftMailer);
        $mailerService = $this->container->get('ocsdc.mailer');
        $mailerService->dispatchMailForPratica($pratica, $this->container->getParameter('default_from_email_address'), true);
    }

    /**
     * @test
     */
    public function testMailerServiceDoesNotDispatchMailToCPSUserWithNoContactEmail()
    {
        $cpsUser = $this->createCPSUser();
        $cpsUser->setEmailContatto(null);
        $swiftMailer = $this->setupSwiftmailerMock([]);
        $pratica = $this->createPratica($cpsUser);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);
        $this->container->set('swiftmailer.mailer.default', $swiftMailer);
        $mailerService = $this->container->get('ocsdc.mailer');
        $mailerService->dispatchMailForPratica($pratica, $this->container->getParameter('default_from_email_address'));
    }

    /**
     * @test
     */
    public function testMailerServiceDispatchesMailToOperatoreWhenPresent()
    {
        $cpsUser = $this->createCPSUser();
        $operatore = $this->createOperatoreUser('pippo', 'pippo');
        $swiftMailer = $this->setupSwiftmailerMock([$cpsUser, $operatore]);
        $pratica = $this->createPratica($cpsUser);
        $pratica->setOperatore($operatore);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);
        $this->container->set('swiftmailer.mailer.default', $swiftMailer);
        $mailerService = $this->container->get('ocsdc.mailer');
        $mailerService->dispatchMailForPratica($pratica, $this->container->getParameter('default_from_email_address'));
    }

    /**
     * @test
     */
    public function testMailerServiceUpdatesPraticaTimestampsWhenSendingMail()
    {
        $cpsUser = $this->createCPSUser();
        $operatore = $this->createOperatoreUser('pippo', 'pippo');
        $swiftMailer = $this->setupSwiftmailerMock([$cpsUser, $operatore]);
        $pratica = $this->createPratica($cpsUser);
        $pratica->setOperatore($operatore);
        $pratica->setStatus(Pratica::STATUS_SUBMITTED);
        $this->container->set('swiftmailer.mailer.default', $swiftMailer);
        $mailerService = $this->container->get('ocsdc.mailer');
        $mailerService->dispatchMailForPratica($pratica, $this->container->getParameter('default_from_email_address'));
        $this->assertGreaterThanOrEqual($pratica->getLatestStatusChangeTimestamp(), $pratica->getLatestCPSCommunicationTimestamp());
        $this->assertGreaterThanOrEqual($pratica->getLatestStatusChangeTimestamp(), $pratica->getLatestOperatoreCommunicationTimestamp());
    }
}
