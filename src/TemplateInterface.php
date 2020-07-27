<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor;

interface TemplateInterface
{
    /**
     * Render the template with context data.
     *
     * @return string
     */
    public function render(array $context = []): string;
}
