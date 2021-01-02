<?php
namespace App\Tests\Services;

use App\Tests\Base\AbstractAppTestCase;

/**
 * Class P7MSignatureCheckServiceTest
 */
class P7MSignatureCheckServiceTest extends AbstractAppTestCase
{

    const INVALID_FILE = __DIR__. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lenovo-yoga-xp1.pdf';
    const VALID_FILE = __DIR__. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'AttoFirmatoDiProva.pdf.p7m';


    /**
     * @test
     */
    public function testItExists()
    {
        $this->assertNotNull(static::$container->get('ocsdc.p7m_signature_check'));
    }

    public function testItReturnsTrueIfCheckingAValidFile() {
        $service = static::$container->get('ocsdc.p7m_signature_check');
        $this->assertFalse($service->check(self::VALID_FILE));
    }

    public function testItReturnsFalseIfCheckingAnInvalidFile() {
        $service = static::$container->get('ocsdc.p7m_signature_check');
        $this->assertFalse($service->check(self::INVALID_FILE));
    }

}
