<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTPProblem;

/**
 * Convert a problem into status, headers and body to be rendered to the client.
 */
interface ProblemRendererInterface
{
    /**
     * @param HTTPProblem $problem
     *
     * @return int
     */
    public function status(HTTPProblem $problem): int;

    /**
     * @param HTTPProblem $problem
     *
     * @return string[]
     */
    public function headers(HTTPProblem $problem): array;

    /**
     * @param HTTPProblem $problem
     *
     * @return string
     */
    public function body(HTTPProblem $problem): string;
}
