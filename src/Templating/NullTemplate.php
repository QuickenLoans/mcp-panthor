<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Templating;

use QL\Panthor\TemplateInterface;

/**
 * Null Template implementation.
 */
class NullTemplate implements TemplateInterface
{
    /**
     * @inheritDoc
     */
    public function render(array $context = [])
    {
        return '';
    }
}
