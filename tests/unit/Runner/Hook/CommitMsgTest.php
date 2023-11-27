<?php

/**
 * This file is part of CaptainHook
 *
 * (c) Sebastian Feldmann <sf@sebastian-feldmann.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CaptainHook\App\Runner\Hook;

use CaptainHook\App\Config\Mockery as ConfigMockery;
use CaptainHook\App\Console\IO\Mockery as IOMockery;
use CaptainHook\App\Mockery as CHMockery;
use Exception;
use PHPUnit\Framework\TestCase;
use SebastianFeldmann\Git\CommitMessage as GitCommitMessage;
use SebastianFeldmann\Git\Operator\Config as ConfigOperator;

class CommitMsgTest extends TestCase
{
    use ConfigMockery;
    use IOMockery;
    use CHMockery;

    /**
     * Tests CommitMsg::run
     */
    public function testRunHookEnabled(): void
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('not tested on windows');
        }

        $io       = $this->createIOMock();
        $config   = $this->createConfigMock();
        $configOp = $this->createMock(ConfigOperator::class);
        $configOp->expects($this->once())->method('getSafely')->willReturn('#');

        $repo = $this->createRepositoryMock();
        $repo->expects($this->once())->method('getConfigOperator')->willReturn($configOp);

        $hookConfig   = $this->createHookConfigMock();
        $actionConfig = $this->createActionConfigMock();
        $actionConfig->method('getAction')->willReturn(CH_PATH_FILES . '/bin/success');
        $hookConfig->expects($this->once())->method('getActions')->willReturn([$actionConfig]);
        $config->expects($this->once())->method('getHookConfigToExecute')->willReturn($hookConfig);
        $config->expects($this->atLeastOnce())->method('isHookEnabled')->willReturn(true);
        $io->expects($this->atLeast(1))->method('write');
        $io->expects($this->once())->method('getArgument')->willReturn(CH_PATH_FILES . '/git/message/valid.txt');

        $runner = new CommitMsg($io, $config, $repo);
        $runner->run();
    }

    /**
     * Tests CommitMsg::run
     */
    public function testRunHookSkippedBecauseOfFixup(): void
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $this->markTestSkipped('not tested on windows');
        }

        $io       = $this->createIOMock();
        $config   = $this->createConfigMock();
        $configOp = $this->createMock(ConfigOperator::class);
        $configOp->expects($this->once())->method('getSafely')->willReturn('#');

        $repo = $this->createRepositoryMock();
        $repo->expects($this->once())->method('getConfigOperator')->willReturn($configOp);
        $repo->expects($this->once())->method('getCommitMsg')->willReturn(new GitCommitMessage('fixup! foo', '#'));

        $hookConfig   = $this->createHookConfigMock();
        $actionConfig = $this->createActionConfigMock();
        $actionConfig->method('getAction')->willReturn(CH_PATH_FILES . '/bin/success');
        $hookConfig->method('isEnabled')->willReturn(true);
        $hookConfig->method('getActions')->willReturn([$actionConfig]);
        $config->method('getHookConfigToExecute')->willReturn($hookConfig);
        $config->expects($this->atLeastOnce())->method('isHookEnabled')->willReturn(true);
        $io->expects($this->atLeast(1))->method('write');
        $io->expects($this->once())->method('getArgument')->willReturn(CH_PATH_FILES . '/git/message/valid.txt');

        $runner = new CommitMsg($io, $config, $repo);
        $runner->run();
    }


    /**
     * Tests CommitMsg::run
     *
     * @throws \Exception
     */
    public function testRunWithoutCommitMsgFile(): void
    {
        $this->expectException(Exception::class);

        $io           = $this->createIOMock();
        $config       = $this->createConfigMock();
        $hookConfig   = $this->createHookConfigMock();
        $repo         = $this->createRepositoryMock();
        $actionConfig = $this->createActionConfigMock();
        $actionConfig->method('getAction')->willReturn(CH_PATH_FILES . '/bin/success');
        $hookConfig->method('isEnabled')->willReturn(true);
        $hookConfig->method('getActions')->willReturn([$actionConfig]);
        $config->method('getHookConfigToExecute')->willReturn($hookConfig);
        $config->expects($this->atLeastOnce())->method('isHookEnabled')->willReturn(true);
        $io->expects($this->once())->method('getArgument')->willReturn('');

        $runner = new CommitMsg($io, $config, $repo);
        $runner->run();
    }
}
