<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\Templating;

use InvalidArgumentException;
use QL\Panthor\TemplateInterface;
use QL\Panthor\Twig\Context;
use Twig\Template;
use Twig\TemplateWrapper;

/**
 * Twig Template implementation.
 *
 * This wraps twig to conform to our template interface, including using Context. If you would like lazy loading of
 * twig templates, see QL\Panthor\Twig\LazyTwig. Lazy loading should NOT be used for templating during the error
 * handling process.
 */
class TwigTemplate implements TemplateInterface
{
    const INVALID_TEMPLATE = 'Invalid template provided. First argument must be an instance of Twig\Template or Twig\TemplateWrapper.';

    /**
     * @var Template|TemplateWrapper
     */
    private $twig;

    /**
     * @var Context
     */
    private $context;

    /**
     * @param Template|TemplateWrapper $twig
     * @param Context|null $context
     */
    public function __construct($twig, ?Context $context = null)
    {
        if (!$twig instanceof Template && !$twig instanceof TemplateWrapper) {
            throw new InvalidArgumentException(self::INVALID_TEMPLATE);
        }

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
