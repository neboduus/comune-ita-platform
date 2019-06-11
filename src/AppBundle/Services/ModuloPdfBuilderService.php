<?php

namespace AppBundle\Services;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\RichiestaIntegrazioneRequestInterface;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RispostaOperatore;
use AppBundle\Entity\RispostaOperatoreDTO;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

class ModuloPdfBuilderService
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var PropertyMappingFactory
     */
    private $propertyMappingFactory;

    /**
     * @var DirectoryNamerInterface
     */
    private $directoryNamer;

    /**
     * @var GeneratorInterface
     */
    private $generator;

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var string
     */
    private $dateTimeFormat;

    public function __construct(
        Filesystem $filesystem,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        PropertyMappingFactory $propertyMappingFactory,
        DirectoryNamerInterface $directoryNamer,
        GeneratorInterface $generator,
        EngineInterface $templating,
        $dateTimeFormat
    ) {
        $this->filesystem = $filesystem;
        $this->em = $em;
        $this->translator = $translator;
        $this->propertyMappingFactory = $propertyMappingFactory;
        $this->directoryNamer = $directoryNamer;
        $this->generator = $generator;
        $this->templating = $templating;
        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * @param Pratica $pratica
     *
     * @return RispostaOperatore
     */
    public function createUnsignedResponseForPratica(Pratica $pratica)
    {
        $unsignedResponse = new RispostaOperatore();
        $this->createAllegatoInstance($pratica, $unsignedResponse);
        $servizioName = $pratica->getServizio()->getName();
        $now = new \DateTime();
        $now->setTimestamp($pratica->getSubmissionTime());
        $unsignedResponse->setOriginalFilename("Servizio {$servizioName} " . $now->format('Ymdhi'));
        $unsignedResponse->setDescription(
            $this->translator->trans(
                'pratica.modulo.descrizioneRisposta',
                [
                    'nomeservizio' => $pratica->getServizio()->getName(),
                    'datacompilazione' => $now->format($this->dateTimeFormat)
                ])
        );
        $this->em->persist($unsignedResponse);
        return $unsignedResponse;
    }


    /**
     * @param Pratica $pratica
     *
     * @return RispostaOperatore
     */
    public function createSignedResponseForPratica(Pratica $pratica)
    {
        $signedResponse = new RispostaOperatore();
        $this->createAllegatoInstance($pratica, $signedResponse);
        $servizioName = $pratica->getServizio()->getName();
        $now = new \DateTime();
        $now->setTimestamp($pratica->getSubmissionTime());
        $signedResponse->setOriginalFilename("Servizio {$servizioName} " . $now->format('Ymdhi'));
        $signedResponse->setDescription(
            $this->translator->trans(
                'pratica.modulo.descrizioneRisposta',
                [
                    'nomeservizio' => $pratica->getServizio()->getName(),
                    'datacompilazione' => $now->format($this->dateTimeFormat)
                ])
        );
        $this->em->persist($signedResponse);
        return $signedResponse;
    }


    /**
 * @param Pratica $pratica
 *
 * @return ModuloCompilato
 */
    public function createForPratica(Pratica $pratica)
    {
        $moduloCompilato = new ModuloCompilato();
        $this->createAllegatoInstance($pratica, $moduloCompilato);
        $servizioName = $pratica->getServizio()->getName();
        $now = new \DateTime();
        $now->setTimestamp($pratica->getSubmissionTime());
        $moduloCompilato->setOriginalFilename("Modulo {$servizioName} " . $now->format('Ymdhi'));
        $moduloCompilato->setDescription(
            $this->translator->trans(
                'pratica.modulo.descrizione',
                [
                    'nomeservizio' => $pratica->getServizio()->getName(),
                    'datacompilazione' => $now->format($this->dateTimeFormat)
                ])
        );
        $this->em->persist($moduloCompilato);

        return $moduloCompilato;
    }

    /**
     * @param Pratica $pratica
     *
     * @return ModuloCompilato
     */
    public function showForPratica(Pratica $pratica)
    {
        $allegato = new ModuloCompilato();
        $content = $this->renderForPratica($pratica);

        $allegato->setOwner($pratica->getUser());
        $destinationDirectory = $this->getDestinationDirectoryFromContext($allegato);
        $fileName = $pratica->getId().'-prot.pdf';
        $filePath = $destinationDirectory.DIRECTORY_SEPARATOR.$fileName;

        $this->filesystem->dumpFile($filePath, $content);
        $allegato->setFile(new File($filePath));
        $allegato->setFilename($fileName);


        $servizioName = $pratica->getServizio()->getName();
        $now = new \DateTime();
        $now->setTimestamp($pratica->getSubmissionTime());
        $allegato->setOriginalFilename("Modulo {$servizioName} " . $now->format('Ymdhi'));

        return $allegato;
    }


    /**
     * @param Pratica $pratica
     * @param RichiestaIntegrazioneDTO $integrationRequest
     *
     * @return RichiestaIntegrazione
     */
    public function creaModuloProtocollabilePerRichiestaIntegrazione(
        Pratica $pratica,
        RichiestaIntegrazioneDTO $integrationRequest
    ) {
        $integration = new RichiestaIntegrazione();
        $payload = $integrationRequest->getPayload();

        if (isset($payload['FileRichiesta'])  && !empty($payload['FileRichiesta'])) {
            $content = base64_decode($payload['FileRichiesta']);
            unset($payload['FileRichiesta']);
            $fileName = uniqid() . '.p7m';
        } else {
            $content = $this->renderForPraticaIntegrationRequest($pratica, $integrationRequest);
            $fileName = uniqid() . '.pdf';
        }

        $integration->setPayload($payload);
        $integration->setOwner($pratica->getUser());
        $integration->setOriginalFilename((new \DateTime())->format('Ymdhi'));
        $integration->setDescription($integrationRequest->getMessage());
        $integration->setPratica($pratica);

        $destinationDirectory = $this->getDestinationDirectoryFromContext($integration);
        $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

        $this->filesystem->dumpFile($filePath, $content);
        $integration->setFile(new File($filePath));
        $integration->setFilename($fileName);

        $this->em->persist($integration);

        return $integration;
    }

    /**
     * @param Pratica $pratica
     * @param RispostaOperatoreDTO $rispostaOperatore
     * @return RispostaOperatore|null
     * @throws \Exception
     */
    public function creaRispostaOperatore(
        Pratica $pratica,
        RispostaOperatoreDTO $rispostaOperatore
    ) {
        $response = new RispostaOperatore();
        $payload = $rispostaOperatore->getPayload();

        if (isset($payload['FileRichiesta'])  && !empty($payload['FileRichiesta'])) {
            $content = base64_decode($payload['FileRichiesta']);
            unset($payload['FileRichiesta']);
            $fileName = uniqid() . '.p7m';
        } else {
            return null;
        }

        $response->setOwner($pratica->getUser());
        $response->setOriginalFilename((new \DateTime())->format('Ymdhi'));
        $response->setDescription($rispostaOperatore->getMessage() ?? '');

        $destinationDirectory = $this->getDestinationDirectoryFromContext($response);
        $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

        $this->filesystem->dumpFile($filePath, $content);
        $response->setFile(new File($filePath));
        $response->setFilename($fileName);

        $this->em->persist($response);

        return $response;
    }

    private function renderForPraticaIntegrationRequest(
        Pratica $pratica,
        RichiestaIntegrazioneDTO $integrationRequest
    ){
        $html = $this->templating->render('AppBundle:Pratiche:pdf/parts/integration.html.twig', [
            'pratica' => $pratica,
            'richiesta_integrazione' => $integrationRequest,
            'user' => $pratica->getUser(),
        ]);

        $header = $this->templating->render('@App/Pratiche/pdf/parts/header.html.twig', [
            'pratica' => $pratica,
            'user' => $pratica->getUser(),
        ]);
        $footer = $this->templating->render('@App/Pratiche/pdf/parts/footer.html.twig', [
            'pratica' => $pratica,
            'user' => $pratica->getUser(),
        ]);

        $content = $this->generator->getOutputFromHtml($html, array(
            'header-html' => $header,
            'footer-html' => $footer,
            'margin-top' => 20,
            'margin-right' => 0,
            'margin-bottom' => 20,
            'header-spacing' => 6,
            'encoding' => 'UTF-8',
            'margin-left' => 0,
            'images' => true,
            'no-background' => false,
            'lowquality' => false
        ));
        return $content;
    }

    /**
     * @param Pratica $pratica
     *
     * @return string
     */
    private function renderForPratica(Pratica $pratica)
    {
        $className = (new \ReflectionClass($pratica))->getShortName();

        return $this->renderForClass($pratica, $className);
    }

    /**
     * @param Pratica $pratica
     *
     * @return string
     */
    private function renderForResponse(Pratica $pratica)
    {
        $className = (new \ReflectionClass(RispostaOperatore::class))->getShortName();

        return $this->renderForClass($pratica, $className);
    }

    /**
     * @param Allegato $moduloCompilato
     *
     * @return string
     */
    private function getDestinationDirectoryFromContext(Allegato $moduloCompilato)
    {
        /** @var PropertyMapping $mapping */
        $mapping = $this->propertyMappingFactory->fromObject($moduloCompilato)[0];
        $path = $this->directoryNamer->directoryName($moduloCompilato, $mapping);
        $destinationDirectory = $mapping->getUploadDestination() . '/' . $path;

        return $destinationDirectory;
    }

    /**
     * @param Pratica $pratica
     * @param $allegato
     */
    private function createAllegatoInstance(Pratica $pratica, Allegato $allegato)
    {
        $content = null;
        if($allegato instanceof RispostaOperatore) {
            $content = $this->renderForResponse($pratica);
        } else {
            $content = $this->renderForPratica($pratica);
        }

        $allegato->setOwner($pratica->getUser());
        $destinationDirectory = $this->getDestinationDirectoryFromContext($allegato);
        $fileName = uniqid().'.pdf';
        $filePath = $destinationDirectory.DIRECTORY_SEPARATOR.$fileName;

        $this->filesystem->dumpFile($filePath, $content);
        $allegato->setFile(new File($filePath));
        $allegato->setFilename($fileName);
    }

    /**
     * @param Pratica $pratica
     * @param $className
     * @return string
     */
    private function renderForClass(Pratica $pratica, $className): string
    {
        $html = $this->templating->render('AppBundle:Pratiche:pdf/'.$className.'.html.twig', [
            'pratica' => $pratica,
            'user' => $pratica->getUser(),
        ]);

        $header = $this->templating->render('@App/Pratiche/pdf/parts/header.html.twig', [
            'pratica' => $pratica,
            'user' => $pratica->getUser(),
        ]);
        $footer = $this->templating->render('@App/Pratiche/pdf/parts/footer.html.twig', [
            'pratica' => $pratica,
            'user' => $pratica->getUser(),
        ]);

        $content = $this->generator->getOutputFromHtml($html, array(
            'header-html' => $header,
            'footer-html' => $footer,
            'margin-top' => 20,
            'margin-right' => 0,
            'margin-bottom' => 20,
            'header-spacing' => 6,
            'encoding' => 'UTF-8',
            'margin-left' => 0,
            'images' => true,
            'no-background' => false,
            'lowquality' => false
        ));




        return $content;
    }
}