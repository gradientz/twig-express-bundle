<?php

namespace Kaliop\TwigExpressBundle\Controller;

use Kaliop\TwigExpressBundle\Core\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Twig_Error;

class StaticController extends Controller
{
    // The excution flow can go e.g.: findAction -> renderTwig -> showTwigError,
    // and we'd like to keep some information around.
    /** @var bool The kernel.debug parameter */
    protected $debug;
    /** @var string Base URL for a request, e.g. '/static/bundleref/' */
    protected $baseUrl;
    /** @var null|string Valid bundle name, e.g. 'SomeCoolBundle' */
    protected $bundleName;
    /** @var null|string System path to bundle root */
    protected $bundlePath;
    /** @var null|string Document root, e.g. '@SomeCoolBundle/Resources/views/static' */
    protected $docRootName;
    /** @var null|string System path to document root */
    protected $docRootPath;
    /** @var null|string The name of an existing template we're trying to show */
    protected $templateName;

    /**
     * List Assetic bundles
     */
    public function rootAction()
    {
        return $this->render('@KaliopTwigExpress/root.html.twig', [
            'bundles' => $this->container->getParameter('twig_express.bundles')
        ]);
    }

    /**
     * Find a template to render or a folder whose content to list
     * @param  string $slug Slug identifying a bundle
     * @param  string $path Path to a resource (starting with '/')
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function findAction($slug, $path)
    {
        $this->debug = $this->container->getParameter('kernel.debug');
        $cleanPath = Utils::getCleanPath($path);
        $pathExt = pathinfo($cleanPath, PATHINFO_EXTENSION);
        $showSource = $pathExt === 'twig';

        // Base URL for redirects and breadcrumbs (no trailing slash)
        $this->baseUrl = $this->generateUrl('kaliop_twig_express_find', [
            'slug' => $slug,
            'path' => ''
        ]);

        // Redirect if we can clean up the URL
        if ($cleanPath !== $path) {
            return $this->redirect($this->baseUrl . $cleanPath);
        }

        // Redirect if URL asks for a Twig file but we’re not in debug mode
        if (!$this->debug && substr($cleanPath, -5) === '.twig') {
            return $this->redirect($this->baseUrl . substr($cleanPath, 0, -5));
        }

        // Figure out bundle name
        $bundleConfig = $this->container->getParameter('twig_express.bundles');
        if (!array_key_exists($slug, $bundleConfig)) {
            $rootUrl = $this->generateUrl('kaliop_twig_express_root');
            return $this->redirect($rootUrl . '?was=' . $slug);
        }
        $this->bundleName = $bundleConfig[$slug]['name'];

        // Check that it's a valid bundle
        $allBundles = array_keys($this->get('kernel')->getBundles());
        if (!in_array($this->bundleName, $allBundles)) {
            throw new \Exception('Unknown bundle "'.$this->bundleName.'". Make sur this bundle is installed and your "twig_express.bundles" config is correct.');
        }

        // Where is our "document root"?
        $docRoot = $bundleConfig[$slug]['root'];
        $this->bundlePath = rtrim($this->container->get('kernel')->locateResource('@'.$this->bundleName), '/');
        $this->docRootName = '@'.$this->bundleName . '/' . $docRoot;
        $this->docRootPath = $this->bundlePath . '/' . $docRoot;
        $basePath = $this->docRootPath . rtrim($cleanPath, '/');

        // First look for a directory
        if (!$pathExt && is_dir($basePath)) {
            // Redirect folder URL if missing the trailing slash
            if (substr($cleanPath, -1) !== '/') {
                return $this->redirect($this->baseUrl . $cleanPath . '/');
            }
            $template = null;
            if (file_exists($basePath . '/index.html.twig')) {
                return $this->renderTwig($this->docRootName . $cleanPath . 'index.html.twig');
            } elseif (file_exists($basePath . '/index.twig')) {
                return $this->renderTwig($this->docRootName . $cleanPath . 'index.twig');
            } else {
                return $this->renderDir($basePath, $cleanPath);
            }
        }
        // Then look for a file
        elseif (file_exists($basePath . ($showSource ? '' : '.twig'))) {
            // Redirect without slash if needed
            if (substr($path, -1) === '/') {
                return $this->redirect($this->baseUrl . substr($cleanPath, 0, -1));
            } elseif ($showSource) {
                return $this->showSource($basePath, $cleanPath);
            } else {
                return $this->renderTwig($this->docRootName . $cleanPath . '.twig');
            }
        }
        // Finish with 404 if nothing matched
        return $this->render404($cleanPath);
    }

    /**
     * Show a directory listing page
     * @param string $dirPath Directory system path
     * @param string $urlFragment URL without base or bundle key
     * @return Response
     */
    private function renderDir($dirPath, $urlFragment)
    {
        // Prepare breadcrumbs
        $breadcrumbs = Utils::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $urlFragment
        );

        // Prepare content list
        $finder = new Finder();
        $iterator = $finder->depth(0)->in($dirPath)->sortByName();
        $dirList = [];
        $fileList = [];
        foreach ($iterator->directories() as $dir) {
            $dirList[] = $dir->getFilename();
        }
        foreach ($iterator->files()->name('*.twig') as $file) {
            $fileList[] = str_replace('.twig', '', $file->getFilename());
        }

        return $this->render('@KaliopTwigExpress/dir.html.twig', [
            'crumbs' => $breadcrumbs,
            'dirList' => $dirList,
            'fileList' => $fileList
        ]);
    }

    /**
     * Show a File Not Found page
     * @param string $urlFragment URL without base or bundle key
     * @return Response
     */
    private function render404($urlFragment)
    {
        // Prepare breadcrumbs
        $breadcrumbs = Utils::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $urlFragment
        );

        // Prepare message
        $root = $this->docRootName;
        if (substr($urlFragment, -5) === '.twig' || substr($urlFragment, -1) === '/') {
            $miss = $urlFragment;
        } else {
            $miss = $urlFragment . '.twig';
        }
        $message = '<p>Could not find : <code class="error">'.$miss.'</code><br>';
        $message .= "\nIn : <code>$root</code></p>";

        $response = $this->render('@KaliopTwigExpress/page.html.twig', [
            'crumbs' => $breadcrumbs,
            'metaTitle' => 'Not found: ' . $urlFragment,
            'title' => 'File does not exist',
            'message' => $message
        ]);
        $response->setStatusCode(404);
        return $response;
    }

    /**
     * Render a Twig template
     * @param string $templateName
     * @return Response
     * @throws \Exception
     */
    private function renderTwig($templateName)
    {
        // Do we have an extension, like .html or .json?
        $ext = pathinfo(substr($templateName, 0, -5), PATHINFO_EXTENSION);
        $cType = $ext ? Utils::getMediaType($ext) . ';charset=utf-8' : null;
        try {
            $response = $this->render($templateName);
            if ($cType) $response->headers->set('Content-Type', $cType);
            return $response;
        }
        catch (Twig_Error $error) {
            if ($this->debug) {
                return $this->showTwigError($error, $templateName);
            } else {
                throw $error;
            }
        }
    }

    /**
     * Show an error page for a Twig_Error, with the faulty Twig code if we can.
     * @param Twig_Error $error
     * @param string $templateName Path of rendered template (error may be in a different template)
     * @return Response
     */
    private function showTwigError(Twig_Error $error, $templateName)
    {
        $line = $error->getTemplateLine();
        $message = $error->getRawMessage();

        // Might be different from $templateName, if the error occurred in an
        // included file. Also we can get one of three types of result:
        // - A full system path
        // - @SomeBundleNameBundle/some/path/xyz
        // - @SomeBundleName/xyz (= @SomeBundleNameBundle/Resources/views/xyz)
        // We're going to have to do a bit of guessing.
        $fileRef  = $error->getTemplateFile();
        $filePath = $fileRef; // ultimately, we want a full system path
        $fileName = $fileRef; // we want a @SomeBundleNameBundle/xyz type of name

        // Key used by Symfony/Twig to reference views inside Resources/views,
        // Format is '@MyBunleName' without the final 'Bundle'.
        $bundlePrefix = '@' . $this->bundleName;
        $bundleViewsPrefix = '@' . substr($this->bundleName, 0, -6);

        // @SomeBundleNameBundle (bundle root)
        if (strpos($fileRef, $bundlePrefix.'/') === 0) {
            $filePath = str_replace($bundlePrefix, $this->bundlePath, $fileRef);
        }
        // @SomeBundleName (bundle root + Resources/views)
        elseif (strpos($fileRef, $bundleViewsPrefix.'/') === 0) {
            $filePath = str_replace($bundleViewsPrefix, $this->bundlePath.'/Resources/views', $fileRef);
            $fileName = str_replace($bundleViewsPrefix, $bundlePrefix.'/Resources/views', $fileRef);
        }
        // Full system path
        elseif (strpos($fileRef, $this->bundlePath) === 0) {
            $fileName = '@'.$this->bundleName . str_replace($this->bundlePath, '', $fileRef);
        }

        // Prepare breadcrumbs
        $breadcrumbs = Utils::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            str_replace($this->docRootName, '', $templateName)
        );

        $data = [
            'metaTitle' => 'Error: ' . basename($fileName),
            'title' => get_class($error),
            'crumbs' => $breadcrumbs,
            'activeCrumb' => count($breadcrumbs) - 1,
            'message' => "$message<br>\nLine $line of <code>$fileName</code>"
        ];

        // Get a few lines of code from the buggy template
        if (file_exists($filePath)) {
            $code = file_get_contents($filePath);
            $data['code'] = Utils::formatCodeBlock($code, true, $line, 5);
            $data['codeLang'] = Utils::getHighlightLanguage($fileRef);
        }

        return $this->render('@KaliopTwigExpress/page.html.twig', $data);
    }

    /**
     * Show a Twig file with syntax highlighting
     * @param string $systemPath Full path to file
     * @param string $urlFragment URL without base or bundle key
     * @return Response
     */
    private function showSource($systemPath, $urlFragment)
    {
        // Prepare breadcrumbs
        $breadcrumbs = Utils::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $urlFragment
        );
        $code = file_get_contents($systemPath);
        $data = [
            'metaTitle' => 'Source: ' . basename($systemPath),
            'crumbs' => $breadcrumbs,
            'code' => Utils::formatCodeBlock($code, true),
            'codeLang' => Utils::getHighlightLanguage($systemPath)
        ];
        return $this->render('@KaliopTwigExpress/page.html.twig', $data);
    }
}
