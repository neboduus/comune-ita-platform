<?php

namespace App\Services;

use App\Entity\Allegato;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;

class P7MThumbnailerService
{

    /**
     * @var DirectoryNamerService
     */
    private $directoryNamer;

    /**
     * @var PropertyMappingFactory
     */
    private $mappingFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $gsBinaryPath;

    /**
     * P7MThumbnailerService constructor.
     *
     * @param DirectoryNamerService $directoryNamer
     * @param PropertyMappingFactory $mappingFactory
     * @param LoggerInterface $logger
     * @param string $gsBinaryPath
     */
    public function __construct(
        DirectoryNamerService $directoryNamer,
        PropertyMappingFactory $mappingFactory,
        LoggerInterface $logger,
        $gsBinaryPath
    ) {
        $this->directoryNamer = $directoryNamer;
        $this->mappingFactory = $mappingFactory;
        $this->logger = $logger;
        $this->gsBinaryPath = $gsBinaryPath;
    }

    /**
     * @param Allegato $allegato
     *
     * @return bool
     */
    public function createThumbnailForAllegato(Allegato $allegato)
    {
        $filename = $allegato->getFilename();
        /** @var PropertyMapping $mapping */
        $mapping = $this->mappingFactory->fromObject($allegato)[0];
        $destDir = $mapping->getUploadDestination() . '/' . $this->directoryNamer->directoryName($allegato, $mapping);
        $thumbnailDestDir = $destDir . '/thumbnails';
        $filePath = $destDir . DIRECTORY_SEPARATOR . $filename;
        $thumbnailFilename = $thumbnailDestDir . '/' . $filename . '.png';

        if (!file_exists($thumbnailDestDir)) {
            mkdir($thumbnailDestDir, 0777, true);
        }

        if (!file_exists($filePath)) {
            throw new FileNotFoundException('missing p7m file');
        }

        $cmd = "{$this->gsBinaryPath} -q -dNOPAUSE -dBATCH -sDEVICE=png256 -r300 -dEPSCrop -sOutputFile=$thumbnailFilename $filePath 2> /dev/null";
        shell_exec($cmd);

        $this->logger->debug("Create thumbnail from pdf with command: $cmd");

        return file_exists($thumbnailFilename);
    }
}
