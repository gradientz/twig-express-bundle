<?php

namespace Kaliop\TwigExpressBundle\Twig;

use Kaliop\TwigExpressBundle\Core\Utils;

class TwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'twig_express.twig_extension';
    }

    public function getFunctions()
    {
        return [
            // TwigExpress templating helpers (excluding 'files' and 'folders')
            new \Twig_SimpleFunction('param',    [$this, 'paramFunction']),
            new \Twig_SimpleFunction('lorem',    [$this, 'loremFunction']),
            new \Twig_SimpleFunction('markdown', [$this, 'markdownFunction']),
            // Not required, but helps keeping the same API as standalone TwigExpress
            new \Twig_SimpleFunction('twigexpress_layout', [$this, 'teLayoutFunction'])
        ];
    }

    public function teLayoutFunction()
    {
        return '@KaliopTwigExpress/layout.html.twig';
    }

    /**
     * @param string $name
     * @param mixed [$fallback]
     * @return mixed
     */
    public function paramFunction($name='', $fallback='')
    {
        return Utils::getHttpParameter($name, $fallback);
    }

    /**
     * @param string $command Count and type of content to generate
     * @return array|string
     */
    public function loremFunction($command='1-7w')
    {
        return Utils::makeLoremIpsum($command);
    }

    /**
     * @param string  $text   Markdown text to process
     * @param boolean $inline Do not output paragraph-level tags
     * @return string
     */
    public function markdownFunction($text='', $inline=false)
    {
        return Utils::processMarkdown($text, $inline);
    }
}
