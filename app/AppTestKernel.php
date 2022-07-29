<?php

require_once __DIR__ . '/AppKernel.php';

/**
 * Class AppTestKernel
 */
class AppTestKernel extends InstanceKernel
{
  protected $identifier = 'comune-di-test';

  private $kernelModifier;

  public function boot()
  {
    parent::boot();
    if ($kernelModifier = $this->kernelModifier) {
      $kernelModifier($this);
    }
    $this->removeEnvVariables();
  }

  public function setKernelModifier(\Closure $kernelModifier)
  {
    $this->kernelModifier = $kernelModifier;
    $this->shutdown();
  }

  /**
   * @return void
   */
  private function removeEnvVariables()
  {
    putenv('shibb_pat_attribute_codicefiscale');
    putenv('shibb_pat_attribute_cognome');
    putenv('shibb_pat_attribute_nome');
    putenv('shibb_pat_attribute_sesso');
    putenv('shibb_pat_attribute_emailaddress');
    putenv('shibb_pat_attribute_datanascita');
    putenv('shibb_pat_attribute_luogonascita');
    putenv('shibb_pat_attribute_provincianascita');
    putenv('shibb_pat_attribute_telefono');
    putenv('shibb_pat_attribute_cellulare');
    putenv('shibb_pat_attribute_indirizzoresidenza');
    putenv('shibb_pat_attribute_capresidenza');
    putenv('shibb_pat_attribute_cittaresidenza');
    putenv('shibb_pat_attribute_provinciaresidenza');
    putenv('shibb_pat_attribute_statoresidenza');
    putenv('shibb_pat_attribute_spidcode');
    putenv('shibb_pat_attribute_x509certificate_issuerdn');
    putenv('shibb_pat_attribute_x509certificate_subjectdn');
    putenv('shibb_pat_attribute_x509certificate_base64');
    putenv('shibb_Shib-Session-ID');
    putenv('shibb_Shib-Session-Index');
    putenv('shibb_Shib-Authentication-Instant');
  }
}
