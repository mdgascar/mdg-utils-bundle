<?php

declare(strict_types=1);

use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__) . '/vendor/autoload.php';

// Test kernels cache their compiled container; start each run from scratch so the bundle's
// configuration and service registration run (and are covered) every time.
(new Filesystem())->remove(sys_get_temp_dir() . '/mdg-utils-bundle');
