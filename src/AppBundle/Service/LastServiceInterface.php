<?php

namespace AppBundle\Service;

/**
 * Interface LastServiceInterface
 * @package AppBundle\Service
 */
interface LastServiceInterface
{
    /**
     * @param string $lastUsername
     * @param string $auth
     *
     * @return array
     */
    public function grab($lastUsername, $auth, $type);
}
