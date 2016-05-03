<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Templating;

use QL\Panthor\TemplateInterface;
use QL\Panthor\Twig\Context;
use Twig_Template;

/**
 * Twig Template implementation.
 *
 * This wraps twig to conform to our template interface, including using Context. If you would like lazy loading of
 * twig templates, see QL\Panthor\Twig\LazyTwig. Lazy loading should NOT be used for templating during the error
 * handling process.
 */
class TwigTemplate implements TemplateInterface
{
    /**
     * @var Twig_Template
     */
    private $twig;

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Twig_Template $twig
     * @param Context|null $context
     */
    public function __construct(Twig_Template $twig, Context $context = null)
    {
        $this->twig = $twig;
        $this->context = $context ?: new Context;
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
        $context = $this->context()->get();

        return $this->twig->render($context);
    }
}
