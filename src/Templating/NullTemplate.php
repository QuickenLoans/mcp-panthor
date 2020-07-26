<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
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
     * Render the template with context data.
     *
     * @return string
     */
    public function render(array $context = []): string
    {
        return '';
    }
}
