<?php

namespace AppBundle\Services;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\GeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\TranslatorInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Symfony\Component\HttpFoundation\File\File;

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

    public function __construct(
        Filesystem $filesystem,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        PropertyMappingFactory $propertyMappingFactory,
        DirectoryNamerInterface $directoryNamer,
        GeneratorInterface $generator,
        EngineInterface $templating
    ) {
        $this->filesystem = $filesystem;
        $this->em = $em;
        $this->translator = $translator;
        $this->propertyMappingFactory = $propertyMappingFactory;
        $this->directoryNamer = $directoryNamer;
        $this->generator = $generator;
        $this->templating = $templating;
    }

    /**
     * @param Pratica $pratica
     * @param CPSUser $user
     *
     * @return ModuloCompilato
     */
    public function createForPratica(Pratica $pratica, CPSUser $user)
    {
        $content = $this->renderForPratica($pratica, $user);
        $moduloCompilato = new ModuloCompilato();
        $moduloCompilato->setOwner($user);
        $destinationDirectory = $this->getDestinationDirectoryFromContext($moduloCompilato);
        $fileName = uniqid() . '.pdf';
        $filePath = $destinationDirectory . DIRECTORY_SEPARATOR . $fileName;

        $this->filesystem->dumpFile($filePath, $content);
        $moduloCompilato->setFile(new File($filePath));

        $now = new \DateTime();
        $now->setTimestamp($pratica->getSubmissionTime());

        $moduloCompilato->setFilename($fileName);
        $servizioName = $pratica->getServizio()->getName();
        $moduloCompilato->setOriginalFilename("Modulo {$servizioName} " . $now->format('Ymdhi'));
        $moduloCompilato->setDescription(
            $this->translator->trans(
                'pratica.modulo.descrizione',
                [
                    'nomeservizio' => $pratica->getServizio()->getName(),
                    'datacompilazione' => $now->format('d/m/Y h:i')
                ])
        );
        $this->em->persist($moduloCompilato);
        return $moduloCompilato;
    }

    /**
     * @param Pratica $pratica
     * @param CPSUser $user
     *
     * @return string
     */
    private function renderForPratica(Pratica $pratica, CPSUser $user)
    {
        $className = (new \ReflectionClass($pratica))->getShortName();
        $html = $this->templating->render('AppBundle:Pratiche:pdf/' . $className . '.html.twig', [
            'pratica' => $pratica,
            'user' => $user,
        ]);

        $header = $this->templating->render('@App/Pratiche/pdf/parts/header.html.twig');
        $footer = $this->templating->render('@App/Pratiche/pdf/parts/footer.html.twig');

        $content = $this->generator->getOutputFromHtml($html, array(
            'header-html' => $header,
            'footer-html' => $footer,
            'margin-top'    => 40,
            'margin-right'  => 0,
            'margin-bottom' => 15,
            'header-spacing'=> 6,
            'encoding' => 'UTF-8',
            'margin-left'   => 0,
            'images' => true,
            'no-background' => false,
            'lowquality' => false
        ));

        return $content;
    }

    /**
     * @param ModuloCompilato $moduloCompilato
     *
     * @return string
     */
    private function getDestinationDirectoryFromContext(ModuloCompilato $moduloCompilato)
    {
        /** @var PropertyMapping $mapping */
        $mapping = $this->propertyMappingFactory->fromObject($moduloCompilato)[0];
        $path = $this->directoryNamer->directoryName($moduloCompilato, $mapping);
        $destinationDirectory = $mapping->getUploadDestination() . '/' . $path;

        return $destinationDirectory;
    }
}
