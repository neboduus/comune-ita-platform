<?php

require_once __DIR__.'/AppKernel.php';

/**
 * Class AppTestKernel
 */
class AppTestKernel extends AppKernel
{
    private $kernelModifier;

    public function boot()
    {
        parent::boot();
        if ($kernelModifier = $this->kernelModifier) {
            $kernelModifier($this);
//            $this->kernelModifier = null;
        };
    }

    public function setKernelModifier(\Closure $kernelModifier)
    {
        $this->kernelModifier = $kernelModifier;
        $this->shutdown();
    }
}
