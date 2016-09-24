<?php

namespace Gradientz\TwigExpressBundle\Core;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Utils
{
    /**
     * Cleans up a local resource path, removing back-slashes, double dots, etc.
     * Should not be necessary for content from a URL but let's be on the safe side.
     * @param  string $path
     * @return string
     */
    static function getCleanPath($path)
    {
        return preg_replace(
            ['/\\\/', '/\/{2,}/', '/\.{2,}/'],
            ['/', '/', '.'],
            $path
        );
    }

    /**
     * Gets the Media type for an extension, using a limited list
     * with common use cases only. Defaults to text/plain.
     * @param  string $ext - lowercase file extension
     * @return string  The corresponding media type
     */
    static function getMediaType($ext)
    {
        $type = 'text/plain';
        $knownTypes = [
            'htm' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'xml' => 'application/xml',
        ];
        if (array_key_exists($ext, $knownTypes)) {
            $type = $knownTypes[$ext];
        }
        return $type;
    }

    /**
     * Make an associative array with URL=>Name values
     * representing the breadcrumbs for a given base URL and path
     * @param  string $baseUrl (no trailing slash)
     * @param  string $bundleName
     * @param  string $path
     * @return array
     */
    static function makeBreadcrumbs($baseUrl, $bundleName, $path)
    {
        $url = $baseUrl . '/';
        $crumbs = [['url' => $url, 'name' => $bundleName]];
        $fragments = array_filter(explode('/', $path));
        $last = array_pop($fragments);

        foreach ($fragments as $fragment) {
            $url .= $fragment . '/';
            $crumbs[] = ['url' => $url, 'name' => $fragment];
        }
        if ($last) {
            $ext = pathinfo($last, PATHINFO_EXTENSION);
            if ($ext === 'twig') {
                $noTwigExt = substr($last, 0, -5);
                $crumbs[] = ['url' => $url . $noTwigExt, 'name' => $noTwigExt];
                $crumbs[] = ['url' => $url . $last, 'name' => '.twig'];
            }
            else {
                $url .= $last . ($ext === '' ? '/' : '');
                $crumbs[] = ['url' => $url, 'name' => $last];
            }
        }

        return $crumbs;
    }

    /**
     * Format a block of code (especially Twig code) for displaying in an HTML page.
     * @param string $code Source code
     * @param bool $numbers Add line numbers
     * @param int $highlight Line number to highlight
     * @param int $extract Number of lines to show before and after an highlighted line
     * @return string
     */
    static function formatCodeBlock($code, $numbers=true, $highlight=0, $extract=4)
    {
        $escaped = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = preg_split("/(\r\n|\n)/", $escaped);
        // Use 1-indexes
        $start = 1;
        $end = count($lines);
        if ($highlight > 0) {
            $highlight = min($end, $highlight);
            $start = max(1, $highlight - $extract);
            $end = min($end, $highlight + $extract);
        }
        $excerpt = [];
        // Add line numbers and mark the selected line
        for ($i = $start - 1; $i < $end; $i++) {
            $text = $lines[$i];
            $num = '';
            // Don't show number on a last empty line
            if ($numbers && ($i < $end - 1 || $text !== '')) {
                $num = '<span data-num="'.($i+1).'"></span>';
            }
            if ($i === $highlight - 1) {
                $excerpt[] = "$num<mark>$text</mark>";
            } else {
                $excerpt[] = $num . $text;
            }
        }
        return implode("\n", $excerpt);
    }

    /**
     * Map a Twig template’s filename with a syntax highlighting name
     * used by Highlight.js.
     * @param string $filename
     * @return string
     */
    static function getHighlightLanguage($filename)
    {
        // Try to figure out the subLanguage
        $subLang   = 'xml';
        $subLangs  = [
            'xml'  => 'xml',
            'html' => 'xml',
            'htm'  => 'xml',
            'json' => 'json',
            'js'   => 'javascript',
            'css'  => 'css',
            'md'   => 'markdown',
            'mdown' => 'markdown',
            'markdown' => 'markdown'
        ];
        $ext = pathinfo(preg_replace('/\.twig$/', '', strtolower($filename)), PATHINFO_EXTENSION);
        if (array_key_exists($ext, $subLangs)) {
            $subLang = $subLangs[$ext];
        }
        return $subLang;
    }
}
