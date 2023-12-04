<?php
/*
 * SPDX-FileCopyrightText: 2023 Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace Ruga\Skeleton\Test\PHPUnit;

use Laminas\ServiceManager\ServiceManager;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;
use PHPUnit\Framework\TestCase;

/**
 * Common setup for all PHPUnit tests that use the common configuration and a container.
 * Loads configuration and creates a service manager.
 *
 * @author   Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 */
abstract class AbstractTestSetUp extends \Ruga\Db\PHPUnit\AbstractTestSetUp
{
    
    protected function setUp(): void
    {
        parent::setUp();
    }
    
    
    
    /**
     * Return the test specific merged config.
     *
     * @return array
     */
    public function configProvider()
    {
        $config = new ConfigAggregator(
            [
                new \Ruga\Db\ConfigProvider(),
                new PhpFileProvider(__DIR__ . "/../../config/config.php"),
                new PhpFileProvider(__DIR__ . "/../../config/config.local.php"),
            ], null, []
        );
        return $config->getMergedConfig();
    }
    
    
}
