<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Twig;

use InvalidArgumentException;
use QL\Panthor\TemplateInterface;
use Twig\Environment;
use Twig\Template;

/**
 * A simple proxy for twig to lazy load templates and allow incremental context loading.
 *
 * This helps slightly decrease the amount of configuration required, as all templates can extend the same base
 * DI service and set their custom template after instantiation.
 *
 * Example:
 * ```yaml
 * twig.template:
 *     class: 'QL\Panthor\Twig\LazyTwig'
 *     arguments: ['@twig.environment', '@twig.context']
 *
 * my.custom_template:
 *     parent: 'twig.template'
 *     calls: [['setTemplate', ['section/page.twig']]]
 * ```
 */
class LazyTwig implements TemplateInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Template|null
     */
    private $twig;

    /**
     * Relative path to the template
     *
     * @var string|null
     */
    private $template;

    /**
     * The relative path the template itself is optional and may be specified later.
     *
     * @param Environment $environment
     * @param Context|null $context
     * @param string|null $template
     */
    public function __construct(Environment $environment, Context $context = null, $template = null)
    {
        $this->environment = $environment;
        $this->context = $context ?: new Context;
        $this->template = $template;
    }

    /**
     * Get the template context.
     *
     * @return Context
     */
    public function context()
    {
        return $this->context;
    }

    /**
     * Render the template with context data.
     *
     * @param array $context
     *
     * @return string
     */
    public function render(array $context = [])
    {
        $this->context()->addContext($context);

        return $this->lazy()->render($this->context()->get());
    }

    /**
     * Set the template path.
     *
     * @param string $template
     *
     * @return void
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Convenience method if you need access to the Template directly.
     *
     * You should not need to use this.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->lazy(), $name], $arguments);
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return Template
     */
    private function lazy()
    {
        if ($this->twig === null) {
            if (!$this->template) {
                throw new InvalidArgumentException('The template file must be specified.');
            }

            $this->twig = $this->environment->loadTemplate($this->template);
        }

        return $this->twig;
    }
}
