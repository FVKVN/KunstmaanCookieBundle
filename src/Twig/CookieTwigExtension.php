<?php

namespace Kunstmaan\CookieBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Kunstmaan\CookieBundle\Entity\CookieConfig;
use Kunstmaan\CookieBundle\Entity\CookieType;
use Kunstmaan\CookieBundle\Helper\LegalCookieHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CookieTwigExtension
 *
 * @package Kunstmaan\CookieBundle\Twig
 */
class CookieTwigExtension extends \Twig_Extension
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var LegalCookieHelper */
    private $cookieHelper;

    /**
     * CookieTwigExtension constructor.
     *
     * @param EntityManagerInterface $em
     * @param LegalCookieHelper      $cookieHelper
     */
    public function __construct(EntityManagerInterface $em, LegalCookieHelper $cookieHelper)
    {
        $this->em = $em;
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_cookie_types', [$this, 'getCookieTypes']),
            new \Twig_SimpleFunction('get_legal_cookie', [$this, 'getLegalCookie']),
            new \Twig_SimpleFunction('get_visitor_type', [$this, 'getVisitorType']),
            new \Twig_SimpleFunction('legal_cookie_is_enabled', [$this, 'isLegalCookieEnabled']),
            new \Twig_SimpleFunction('is_granted_for_cookie_bundle', [$this, 'isGrantedForCookieBundle']),
        ];
    }

    /**
     * @return array|CookieType[]
     */
    public function getCookieTypes()
    {
        return $this->em->getRepository('KunstmaanCookieBundle:CookieType')->findAll();
    }

    /**
     * @param Request $request
     *
     * @return array|mixed
     */
    public function getLegalCookie(Request $request)
    {
        $legalCookie = $this->cookieHelper->getLegalCookie($request);

        return null !== $legalCookie && isset($legalCookie['cookies']) ? $legalCookie['cookies'] : [];
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getVisitorType(Request $request)
    {
        $cookieConfig = $this->em->getRepository('KunstmaanCookieBundle:CookieConfig')->findLatestConfig();

        if (null === $cookieConfig) {
            return CookieConfig::VISITOR_TYPE_NORMAL;
        }

        $clientIpAddresses = array_map('trim', explode(',', $cookieConfig->getclientIpAddresses()));
        $internalIpAddresses = array_map('trim', explode(',', $cookieConfig->getinternalIpAddresses()));

        foreach ($request->getClientIps() as $clientIp) {
            foreach ($clientIpAddresses as $clientIpAddress) {
                if (fnmatch($clientIpAddress, $clientIp)) {
                    return CookieConfig::VISITOR_TYPE_CLIENT;
                }
            }
            foreach ($internalIpAddresses as $internalIpAddress) {
                if (fnmatch($internalIpAddress, $clientIp)) {
                    return CookieConfig::VISITOR_TYPE_INTERNAL;
                }
            }
        }

        return CookieConfig::VISITOR_TYPE_NORMAL;
    }

    /**
     * @param Request $request
     * @param string  $internalName
     *
     * @return bool
     */
    function isLegalCookieEnabled(Request $request, $internalName)
    {
        $cookie = $this->getLegalCookie($request);

        return isset($cookie[$internalName]) && true === $cookie[$internalName];
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    function isGrantedForCookieBundle(Request $request)
    {
        return $this->cookieHelper->isGrantedForCookieBundle($request);
    }
}
