<?php
/**
 * @copyright (c) 2020 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\HTTPProblem\Renderer;

use QL\Panthor\HTTPProblem\HTTPProblem;
use QL\Panthor\HTTPProblem\ProblemRendererInterface;
use QL\Panthor\Utility\JSON;

class JSONRenderer implements ProblemRendererInterface
{
    /**
     * @var JSON
     */
    private $json;

    /**
     * @param JSON $json
     */
    public function __construct(?JSON $json = null)
    {
        $this->json = $json ?: new JSON;
    }

    /**
     * @param HTTPProblem $problem
     *
     * @return int
     */
    public function status(HTTPProblem $problem): int
    {
        return $problem->status();
    }

    /**
     * @param HTTPProblem $problem
     *
     * @return array
     */
    public function headers(HTTPProblem $problem): array
    {
        return [
            'Content-Type' => 'application/problem+json',
        ];
    }

    /**
     * @param HTTPProblem $problem
     *
     * @return string
     */
    public function body(HTTPProblem $problem): string
    {
        $data = [
            'status' => $problem->status(),
        ];

        if ($problem->title()) {
            $data['title'] = $problem->title();
        }

        if (!in_array($problem->type(), [null, 'about:blank'], true)) {
            $data['type'] = $problem->type();
        }

        if ($problem->detail()) {
            $data['detail'] = $problem->detail();
        }

        if ($problem->instance()) {
            $data['instance'] = $problem->instance();
        }

        $data = array_merge($data, $problem->extensions());

        return $this->json->encode($data);
    }
}
