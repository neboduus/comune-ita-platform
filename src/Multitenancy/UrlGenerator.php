<?php

namespace App\Multitenancy;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator;
use Symfony\Component\Routing\RequestContext;

class UrlGenerator extends CompiledUrlGenerator implements TenantAwareInterface
{
    use TenantAwareTrait;

    private $compiledRoutes = [];
    private $defaultLocale;

    public function __construct(array $compiledRoutes, RequestContext $context, LoggerInterface $logger = null, string $defaultLocale = null)
    {
        $this->compiledRoutes = $compiledRoutes;
        $this->context = $context;
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $locale = $parameters['_locale']
            ?? $this->context->getParameter('_locale')
                ?: $this->defaultLocale;

        if (null !== $locale) {
            do {
                if (($this->compiledRoutes[$name . '.' . $locale][1]['_canonical_route'] ?? null) === $name) {
                    $name .= '.' . $locale;
                    break;
                }
            } while (false !== $locale = strstr($locale, '_', true));
        }

        if (!isset($this->compiledRoutes[$name])) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        list($variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes) = $this->compiledRoutes[$name];

        if ($this->hasTenant() && $this->getTenant()->hasPathInfoPrefix()) {
            $tokens[] = ['text', '/' . $this->getTenant()->getPathInfoPrefix()];
        }

        if (isset($defaults['_canonical_route']) && isset($defaults['_locale'])) {
            if (!\in_array('_locale', $variables, true)) {
                unset($parameters['_locale']);
            } elseif (!isset($parameters['_locale'])) {
                $parameters['_locale'] = $defaults['_locale'];
            }
        }

        return $this->doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
}
