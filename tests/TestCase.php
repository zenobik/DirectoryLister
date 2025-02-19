<?php

namespace Tests;

use DI\Container;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /** @var Container The test container */
    protected $container;

    /** @var string Path to test files directory */
    protected $testFilesPath = __DIR__ . '/_files';

    /**
     * This method is called before each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        Dotenv::createImmutable(__DIR__)->safeLoad();

        $this->container = (new ContainerBuilder)->addDefinitions(
            dirname(__DIR__) . '/app/config/app.php',
            dirname(__DIR__) . '/app/config/container.php'
        )->build();

        $this->container->set('base_path', $this->testFilesPath);
        $this->container->set('asset_path', $this->filePath('app/assets'));
        $this->container->set('cache_path', $this->filePath('app/cache'));
    }

    /**
     * Get the file path to a test file.
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function filePath(string $filePath): string
    {
        return realpath($this->testFilesPath . '/' . $filePath);
    }
}
