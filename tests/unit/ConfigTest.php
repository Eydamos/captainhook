<?php

/**
 * This file is part of CaptainHook
 *
 * (c) Sebastian Feldmann <sf@sebastian-feldmann.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CaptainHook\App;

use CaptainHook\App\Config\Action;
use CaptainHook\App\Config\Plugin;
use CaptainHook\App\Plugin\CaptainHook as CaptainHookPlugin;
use Exception;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * Tests Config::__construct
     */
    public function testConstructor(): void
    {
        $config = new Config('./no-config.json');
        $this->assertFalse($config->isLoadedFromFile());
    }

    /**
     * Tests Config::isLoadedFromFile
     */
    public function testIsLoadedFromFile(): void
    {
        $config = new Config('valid.json', true);
        $this->assertTrue($config->isLoadedFromFile());
    }

    /**
     * Tests Config::getHookConfig
     */
    public function testGetInvalidHook(): void
    {
        $this->expectException(Exception::class);
        $config = new Config('./no-config.json');
        $config->getHookConfig('foo');
    }

    /**
     * Tests Config::getHookConfigToExecute
     */
    public function testGetHookConfigWithVirtualHooks(): void
    {
        $config = new Config('./no-config.json');
        $config->getHookConfig('post-rewrite')->setEnabled(true);
        $config->getHookConfig('post-rewrite')->addAction(new Action('echo foo'));
        $config->getHookConfig('post-change')->setEnabled(true);
        $config->getHookConfig('post-change')->addAction(new Action('echo bar'));

        $hookConfig = $config->getHookConfigToExecute('post-rewrite');

        $this->assertCount(2, $hookConfig->getActions());
    }

    /**
     * Tests Config::getGitDirectory
     */
    public function testAssumeCwdAsGitDir(): void
    {
        $config = new Config('./no-config.json');
        $this->assertEquals(getcwd() . '/.git', $config->getGitDirectory());
    }

    /**
     * Tests Config::getPath
     */
    public function testGetPath(): void
    {
        $path   = realpath(__DIR__ . '/../files/config/valid.json');
        $config = new Config($path);

        $this->assertEquals($path, $config->getPath());
    }

    /**
     * Tests Config::getBootstrap
     */
    public function testGetBootstrapDefault(): void
    {
        $path   = realpath(__DIR__ . '/../files/config/valid.json');
        $config = new Config($path);

        $this->assertEquals('vendor/autoload.php', $config->getBootstrap());
    }

    /**
     * Tests Config::getBootstrap
     */
    public function testGetBootstrapSetting(): void
    {
        $path   = realpath(__DIR__ . '/../files/config/valid.json');
        $config = new Config($path, true, ['bootstrap' => 'libs/autoload.php']);

        $this->assertEquals('libs/autoload.php', $config->getBootstrap());
    }

    /**
     * Tests Config::isFailureAllowed
     */
    public function testIsFailureAllowedDefault(): void
    {
        $path   = realpath(__DIR__ . '/../files/config/valid.json');
        $config = new Config($path, true);

        $this->assertFalse($config->isFailureAllowed());
    }

    /**
     * Tests Config::isFailureAllowed
     */
    public function testIsFailureAllowedSet(): void
    {
        $path   = realpath(__DIR__ . '/../files/config/valid.json');
        $config = new Config($path, true, ['allow-failure' => true]);

        $this->assertTrue($config->isFailureAllowed());
    }
    /**
     * Tests Config::useAnsiColors
     */
    public function testAnsiColorsEnabledByDefault(): void
    {
        $config = new Config('foo.json', true);
        $this->assertTrue($config->useAnsiColors());
    }

    /**
     * Tests Config::useAnsiColors
     */
    public function testDisableAnsiColors(): void
    {
        $config = new Config('foo.json', true, ['ansi-colors' => false]);
        $this->assertFalse($config->useAnsiColors());
    }

    /**
     * Tests Config::getRunMode
     */
    public function testGetRunMode(): void
    {
        $config = new Config('foo.json', true, ['run-mode' => 'docker', 'run-exec' => 'foo']);
        $this->assertEquals('docker', $config->getRunConfig()->getMode());
    }

    /**
     * Tests Config::getRunExec
     */
    public function testGetRunExec(): void
    {
        $config = new Config('foo.json', true, ['run-mode' => 'docker', 'run-exec' => 'foo']);
        $this->assertEquals('foo', $config->getRunConfig()->getDockerCommand());
    }

    /**
     * Tests Config::getRunPath
     */
    public function testGetRunPathEmptyByDefault(): void
    {
        $config = new Config('foo.json', true, ['run-mode' => 'docker', 'run-exec' => 'foo']);
        $this->assertEquals('', $config->getRunConfig()->getCaptainsPath());
    }

    /**
     * Tests Config::getCustomSettings
     */
    public function testGetCustomSettings(): void
    {
        $config = new Config('foo.json', true, ['custom' => ['foo' => 'foo']]);
        $this->assertEquals(['foo' => 'foo'], $config->getCustomSettings());
    }

    /**
     * Tests Config::getRunPath
     */
    public function testGetRunPath(): void
    {
        $config = new Config('foo.json', true, ['run-mode' => 'docker', 'run-exec' => 'foo', 'run-path' => '/foo']);
        $this->assertEquals('/foo', $config->getRunConfig()->getCaptainsPath());
    }

    /**
     * Tests Config::failOnFirstError default
     */
    public function testFailOnFirstErrorDefault(): void
    {
        $config = new Config('foo.json', true, []);
        $this->assertTrue($config->failOnFirstError());
    }

    /**
     * Tests Config::failOnFirstError
     */
    public function testFailOnFirstError(): void
    {
        $config = new Config('foo.json', true, ['fail-on-first-error' => false]);
        $this->assertFalse($config->failOnFirstError());
    }

    /**
     * Tests Config::getJsonData
     */
    public function testGetJsonData(): void
    {
        $config = new Config('./no-config.json');
        $json   = $config->getJsonData();

        $this->assertIsArray($json);
        $this->assertIsArray($json['pre-commit']);
        $this->assertIsArray($json['commit-msg']);
        $this->assertIsArray($json['pre-push']);
    }

    /**
     * Tests Config::getJsonData
     */
    public function testGetJsonDataWithSettings(): void
    {
        $config = new Config(
            './no-config.json',
            false,
            ['run-path' => '/usr/local/bin/captainhook', 'verbosity' => 'debug']
        );
        $json   = $config->getJsonData();

        $this->assertIsArray($json);
        $this->assertIsArray($json['config']);
        $this->assertIsArray($json['config']['run']);
        $this->assertIsArray($json['pre-commit']);
        $this->assertIsArray($json['commit-msg']);
        $this->assertIsArray($json['pre-push']);
    }

    /**
     * Tests Config::getJsonData
     */
    public function testGetJsonDataWithoutEmptyConfig(): void
    {
        $config = new Config('foo.json', true, []);
        $json   = $config->getJsonData();

        $this->assertArrayNotHasKey('config', $json);
    }

    /**
     * Tests Config::getJsonData
     */
    public function testGetJsonDataWithConfigSection(): void
    {
        $config = new Config('foo.json', true, ['run-mode' => 'docker', 'run-exec' => 'foo']);
        $json   = $config->getJsonData();

        $this->assertIsArray($json);
        $this->assertIsArray($json['config']);
        $this->assertEquals('foo', $json['config']['run']['exec']);
        $this->assertArrayNotHasKey('plugins', $json);
    }

    public function testGetPluginsReturnsEmptyArray(): void
    {
        $config = new Config('foo.json');

        $this->assertSame([], $config->getPlugins());
    }

    public function testGetPluginsReturnsArrayOfPlugins(): void
    {
        $plugin1 = new class implements CaptainHookPlugin {
        };
        $plugin1Name = get_class($plugin1);

        $plugin2 = new class implements CaptainHookPlugin {
        };
        $plugin2Name = get_class($plugin2);

        $config = new Config('foo.json', true, [
            'plugins' => [
                [
                    'plugin' => $plugin1Name,
                    'options' => [
                        'foo' => 'bar',
                    ],
                ],
                [
                    'plugin' => $plugin2Name,
                ],
            ],
        ]);

        $json = $config->getJsonData();

        $this->assertIsArray($json);
        $this->assertIsArray($json['config']);
        $this->assertIsArray($json['config']['plugins']);
        $this->assertCount(2, $config->getPlugins());
        $this->assertContainsOnlyInstancesOf(Plugin::class, $config->getPlugins());
        $this->assertSame(
            [
                [
                    'plugin' => $plugin1Name,
                    'options' => ['foo' => 'bar'],
                ],
                [
                    'plugin' => $plugin2Name,
                    'options' => [],
                ],
            ],
            $json['config']['plugins']
        );
    }
}
