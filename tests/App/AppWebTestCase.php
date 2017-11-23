<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AppWebTestCase extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        return new \VirtualKernel(
            $options['environment'] ?? 'test',
            $options['debug'] ?? true,
            'app'
        );
    }
}
