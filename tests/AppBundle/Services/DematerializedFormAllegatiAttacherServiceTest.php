<?php

namespace Tests\AppBundle\Services;

use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Services\DematerializedFormAllegatiAttacherService;
use Doctrine\Common\Collections\ArrayCollection;
use Tests\AppBundle\Base\AbstractAppTestCase;
use TypeError;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia\ElencoSoggettiAventiTitolo;
use AppBundle\Mapper\Giscom\File as GiscomFile;
use AppBundle\Mapper\Giscom\FileCollection as GiscomFileCollection;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as PraticaEdilizia;


/**
 * Class DematerializedFormAllegatiAttacherServiceTest
 */
class DematerializedFormAllegatiAttacherServiceTest extends AbstractAppTestCase
{
    public function testServiceExists()
    {
        $service = $this->container->get('ocsdc.allegati.dematerialized_attacher');
        $this->assertNotNull($service);
    }

    /**
     * @expectedException TypeError
     */
    public function testServiceThrowsIfPassedUnmanagedInstance()
    {
        $service = new DematerializedFormAllegatiAttacherService($this->em);

        $pratica = $this->createPratica($this->createCPSUser());

        $service->attachAllegati($pratica);
    }

    public function testServiceAttachesAllegatiToPratica()
    {
        $service = new DematerializedFormAllegatiAttacherService($this->em);

        $pratica = $this->setupPratica();

        $this->assertEquals(0, $pratica->getAllegati()->count());

        $service->attachAllegati($pratica);

        $this->assertEquals(3, $pratica->getAllegati()->count());
    }

    /**
     * @return \AppBundle\Entity\SciaPraticaEdilizia
     */
    private function setupPratica()
    {
        $ente = $this->createEnti()[0];
        $erogatore = $this->createErogatoreWithEnti([$ente]);
        $fqcn = SciaPraticaEdilizia::class;
        $flow = 'ocsdc.form.flow.scia_pratica_edilizia';
        $servizio = $this->createServizioWithErogatore($erogatore, 'Scia', $fqcn, $flow, 'ROLE_SCIA_TECNICO_ACCREDITATO');

        $geometra = $this->createCPSUser(true, true);

        /** @var SciaPraticaEdilizia $pratica */
        $pratica = $this->createPratica($geometra, null, null, $erogatore, $servizio);
        $allegati = new ArrayCollection();
        for ($i = 0; $i < 3; $i++) {
            $allegati->add($this->createAllegatoForUser($geometra, 'some description', 'signed.pdf.p7m'));
        }

        $praticaScia = (new PraticaEdilizia($pratica->getDematerializedForms()))
            ->setModuloDomanda(new GiscomFile([
                'name' => $allegati[0]->getName(),
                'id' => $allegati[0]->getId(),
                'type' => 'scia_ediliza_modulo_scia'
            ]))
            ->setElencoSoggettiAventiTitolo(new ElencoSoggettiAventiTitolo([
                [
                    'name' => $allegati[1]->getName(),
                    'id' => $allegati[1]->getId(),
                ],
                [
                    'name' => $allegati[2]->getName(),
                    'id' => $allegati[2]->getId(),
                ],
            ]))
            ->setElencoAllegatoTecnici('TEC_PIANTEFAT', new GiscomFileCollection([
                [
                    'name' => $allegati[0]->getName(),
                    'id' => $allegati[0]->getId(),
                ]
            ]));

        $dematerialized = $praticaScia->toHash();

        $pratica->setDematerializedForms($dematerialized);
        $this->em->persist($pratica);
        $this->em->flush();
        return $pratica;
    }
}
