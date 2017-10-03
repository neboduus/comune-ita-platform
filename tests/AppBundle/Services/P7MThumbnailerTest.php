<?php
namespace Tests\AppBundle\Services;

use AppBundle\Entity\Allegato;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Tests\AppBundle\Base\AbstractAppTestCase;

/**
 * Class P7MSignatureCheckServiceTest
 */
class P7MThumbnailerTest extends AbstractAppTestCase
{

    const INVALID_FILE = __DIR__. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lenovo-yoga-xp1.pdf';
    const VALID_FILE = __DIR__. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'AttoFirmatoDiProva.pdf.p7m';


    /**
     * @test
     */
    public function testItExists()
    {
        $this->assertNotNull($this->container->get('ocsdc.p7m_thumbnailer_service'));
    }


    /**
     * @test
     */
    public function testItThrowsIfFileIsMissing()
    {
        $this->expectException(FileNotFoundException::class);
        $allegato = $this->createAllegatoForUser($this->createCPSUser());
        $service = $this->container->get('ocsdc.p7m_thumbnailer_service');
        $service->createThumbnailForAllegato($allegato);
    }

    public function testItCreatesThumbnailDirInUserDirIfMissing()
    {
        $user = $this->createCPSUser();

        $uploadPath = __DIR__.'/../../../var/uploads/pratiche/allegati/'.$user->getId();
        $userThumbnailPath = $uploadPath.'/thumbnails/';

        mkdir($uploadPath, 0777, true);
        copy(__DIR__.'/../Assets/signed.pdf.p7m', $uploadPath.'/signed.pdf.p7m');

        $this->assertFileNotExists($userThumbnailPath);

        $allegato = $this->createAllegatoForUser($user,'some description', 'signed.pdf.p7m');
        $service = $this->container->get('ocsdc.p7m_thumbnailer_service');
        $service->createThumbnailForAllegato($allegato);
        $this->assertFileExists($userThumbnailPath);
    }

    public function testItCreatesThumbnailInUserDir()
    {
        $user = $this->createCPSUser();

        $uploadPath = __DIR__.'/../../../var/uploads/pratiche/allegati/'.$user->getId();
        $userThumbnailPath = $uploadPath.'/thumbnails/';

        mkdir($uploadPath, 0777, true);
        copy(__DIR__.'/../Assets/signed.pdf.p7m', $uploadPath.'/signed.pdf.p7m');

        $this->assertFileNotExists($userThumbnailPath);

        $allegato = $this->createAllegatoForUser($user,'some description', 'signed.pdf.p7m');
        $service = $this->container->get('ocsdc.p7m_thumbnailer_service');
        $result = $service->createThumbnailForAllegato($allegato);
        $this->assertFileExists($userThumbnailPath.'/signed.pdf.p7m.png');
        $this->assertTrue($result);
    }
}
