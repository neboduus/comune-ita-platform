<?php

namespace Tests\AppBundle\Constraints;

use AppBundle\Entity\Allegato;
use AppBundle\Validator\Constraints\AtLeastOneAttachmentConstraint;
use AppBundle\Validator\Constraints\AtLeastOneAttachmentConstraintValidator;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Tests\AppBundle\Base\AbstractAppTestCase;

class AtLeastOneAttachmentConstraintValidatorTest extends AbstractAppTestCase
{
    public function testValidatorAddsViolationIfValueIsNull()
    {
        $mockContext = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock2 = $this->getMockForAbstractClass(ConstraintViolationBuilderInterface::class);
        $mock2->method('setParameter')
            ->willReturn($mock2);
        $mockContext->method('buildViolation')
            ->willReturn($mock2);

        $mock2->expects($this->once())
            ->method('addViolation');

        $validator = $this->container->get(AtLeastOneAttachmentConstraintValidator::class);
        $validator->initialize($mockContext);
        $validator->validate([], new AtLeastOneAttachmentConstraint());
    }

    public function testValidatorAddsViolationIfGivenBogusValues()
    {
        $mockContext = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock2 = $this->getMockForAbstractClass(ConstraintViolationBuilderInterface::class);
        $mock2->method('setParameter')
            ->willReturn($mock2);
        $mockContext->method('buildViolation')
            ->willReturn($mock2);

        $mock2->expects($this->exactly(2))
            ->method('addViolation');

        $validator = $this->container->get(AtLeastOneAttachmentConstraintValidator::class);
        $validator->initialize($mockContext);
        $validator->validate([Uuid::uuid4(),Uuid::uuid4()], new AtLeastOneAttachmentConstraint());
    }

    public function testValidatorAddsViolationIfFilesAreNotExisting()
    {
        $allegato = new Allegato();
        $allegato->setOwner($this->createCPSUser());
        $allegato->setDescription('Love, love will tear us apart');
        $allegato->setFilename('again.txt');
        $allegato->setOriginalFilename('again2.txt');
        $this->em->persist($allegato);

        $mockContext = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mock2 = $this->getMockForAbstractClass(ConstraintViolationBuilderInterface::class);
        $mock2->method('setParameter')
            ->willReturn($mock2);
        $mockContext->method('buildViolation')
            ->willReturn($mock2);

        $mock2->expects($this->once())
            ->method('addViolation');

        $validator = $this->container->get(AtLeastOneAttachmentConstraintValidator::class);
        $validator->initialize($mockContext);
        $validator->validate([$allegato->getId()], new AtLeastOneAttachmentConstraint());
    }
}
