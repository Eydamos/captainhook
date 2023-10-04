<?php

/**
 * This file is part of CaptainHook
 *
 * (c) Sebastian Feldmann <sf@sebastian-feldmann.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CaptainHook\App\Hook\Condition\FileStaged;

use CaptainHook\App\Console\IO;
use CaptainHook\App\Git\Diff\FilterUtil;
use CaptainHook\App\Hook\Condition;
use CaptainHook\App\Hook\Constrained;
use CaptainHook\App\Hook\Restriction;
use CaptainHook\App\Hooks;
use SebastianFeldmann\Git\Repository;

/**
 * Class OfType
 *
 * All FileStaged conditions are only applicable for `pre-commit` hooks.
 * The diff filter argument is optional.
 *
 * Example configuration:
 *
 * "action": "some-action"
 * "conditions": [
 *   {"exec": "\\CaptainHook\\App\\Hook\\Condition\\FileStaged\\OfType",
 *    "args": [
 *      "php",
 *      ["A", "C"]
 *    ]}
 * ]
 *
 * @package CaptainHook
 * @author  Sebastian Feldmann <sf@sebastian-feldmann.info>
 * @link    https://github.com/captainhookphp/captainhook
 * @since   Class available since Release 5.0.0
 */
class OfType implements Condition, Constrained
{
    /**
     * File type to check e.g. 'php' or 'html'
     *
     * @var string
     */
    private string $suffix;

    /**
     * --diff-filter option
     *
     * @var array<int, string>
     */
    private array $diffFilter;

    /**
     * OfType constructor
     *
     * @param mixed                     $type
     * @param array<int, string>|string $filter
     */
    public function __construct($type, $filter = [])
    {
        $this->suffix     = (string) $type;
        $this->diffFilter = FilterUtil::filterFromConfigValue($filter);
    }

    /**
     * Return the hook restriction information
     *
     * @return \CaptainHook\App\Hook\Restriction
     */
    public static function getRestriction(): Restriction
    {
        return Restriction::fromArray([Hooks::PRE_COMMIT]);
    }

    /**
     * Evaluates the condition
     *
     * @param  \CaptainHook\App\Console\IO       $io
     * @param  \SebastianFeldmann\Git\Repository $repository
     * @return bool
     */
    public function isTrue(IO $io, Repository $repository): bool
    {
        $files = $repository->getIndexOperator()->getStagedFilesOfType($this->suffix, $this->diffFilter);
        return count($files) > 0;
    }
}
