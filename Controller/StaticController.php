<?php

namespace Gradientz\TwigExpressBundle\Controller;

use Gradientz\TwigExpressBundle\Core\StaticManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Twig_Error;


class StaticController extends Controller {

    // The excution flow can go e.g.: findAction -> renderTwig -> showTwigError,
    // and we'd like to keep some information around.
    /** @var string Base URL for a request, e.g. '/static/bundleref/' */
    protected $baseUrl;
    /** @var null|string Valid bundle name, e.g. 'SomeCoolBundle' */
    protected $bundleName;
    /** @var null|string Resource root, e.g. '@SomeCoolBundle/Resources/views/static' */
    protected $resourceRoot;
    /** @var null|string System path to resource root */
    protected $resourceRootPath;
    /** @var null|string The name of an existing template we're trying to show */
    protected $templateName;

    /**
     * List Assetic bundles
     */
    public function rootAction() {
        return $this->render('TwigExpressBundle::list.html.twig', [
            'bundles' => StaticManager::getStaticBundles($this->container)
        ]);
    }

	/**
	 * Find a template to render or a folder whose content to list
	 * @param  string  $bundle
	 * @param  string  $path
	 * @return RedirectResponse|Response
	 */
    public function findAction($bundle, $path) {
		$cleanPath = StaticManager::getCleanPath($path);
		$cleanRef = preg_replace('/(_|bundle$)/', '', strtolower($bundle));
        $ext = pathinfo($cleanPath, PATHINFO_EXTENSION);

        // Base URL for redirects and breadcrumbs
        $baseUrl = $this->generateUrl('twig_express_find', [
            'bundle' => $cleanRef,
            'path' => '/'
        ]);

        // Redirect if we can clean up the URL
		if ($cleanRef !== $bundle || $cleanPath !== trim($path, '/')) {
            $trailing = !$ext && substr($path, -1) === '/';
            $url = $baseUrl . $cleanPath . ($trailing ? '/' : '');
            return $this->redirect($url);
		}

        $bundleName = StaticManager::findStaticBundle($this->container, $cleanRef);
        // No bundle found -> redirect to list of bundles
        if (!$bundleName) {
			$url = $this->generateUrl('twig_express_root') . '?was=' . $cleanRef;
			return $this->redirect($url);
		}

		// Identify target file or directory
		$resourceRoot = '@'.$bundleName.'/'.StaticManager::VIEWS_ROOT;
		$rootPath = $this->container->get('kernel')->locateResource($resourceRoot);

        $isDir = !$ext && is_dir("$rootPath/$cleanPath");
        // Redirect folder URL if missing the trailing slash
        if ($isDir && substr($path, -1) !== '/') {
            return $this->redirect($baseUrl . $cleanPath . '/');
        }

		// Is there a file we can render?
        $lookupPaths = [];
        $templateName = null;
        $templatePath = null;
        if ($ext === 'twig') {
            if (file_exists("$rootPath/$cleanPath")) {
                $templateName = "$resourceRoot/$cleanPath";
                $templatePath = "$rootPath/$path";
            }
        } else {
            if ($isDir) {
                $lookupPaths[] = "$cleanPath/index.twig";
                $lookupPaths[] = "$cleanPath/index.html.twig";
            } else {
                $lookupPaths[] = "$cleanPath.twig";
                if ($ext === '') $lookupPaths[] = "$cleanPath.html.twig";
            }
            foreach ($lookupPaths as $path) {
                if (file_exists("$rootPath/$path")) {
                    $templateName = "$resourceRoot/$path";
                    $templatePath = "$rootPath/$path";
                    break;
                }
            }
        }

        // Update class vars
        $this->baseUrl = $baseUrl;
        $this->bundleName = $bundleName;
        $this->resourceRoot = $resourceRoot;
        $this->resourceRootPath = $rootPath;
        $this->templateName = $templateName;

        if ($templatePath && $ext === 'twig') {
            return $this->showTwigSource($templatePath, $cleanPath);
        }
        // Also render index templates if they exist
		if ($templateName) {
		    return $this->renderTwig($templateName);
		}
		if ($isDir) {
		    return $this->renderDir($rootPath, $cleanPath);
		}
        return $this->render404($cleanPath, $lookupPaths);
	}

	/**
	 * Show a directory listing page
	 * @param string $rootPath
     * @param string $localPath
	 * @return Response
	 */
	private function renderDir($rootPath, $localPath) {
        // Prepare breadcrumbs
        $breadcrumbs = StaticManager::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $localPath
        );

		// Prepare content list
		$finder = new Finder();
		$iterator = $finder->depth(0)->in("$rootPath/$localPath")->sortByName();
		$dirList = [];
		$fileList = [];
		foreach ($iterator->directories() as $dir) {
            $dirList[] = $dir->getFilename();
		}
		foreach ($iterator->files()->name('*.twig') as $file) {
            $fileList[] = str_replace('.twig', '', $file->getFilename());
		}

		return $this->render('TwigExpressBundle::index.html.twig', [
			'crumbs' => $breadcrumbs,
			'dirList' => $dirList,
			'fileList' => $fileList
		]);
	}

	/**
	 * Show a File Not Found page
     * @param string $path
     * @param array  $lookupPaths
	 * @return Response
	 */
	private function render404($path, $lookupPaths) {
	    // Prepare breadcrumbs
        $breadcrumbs = StaticManager::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $path
        );

		// Prepare message
        $root = $this->resourceRoot;
		$message = '<p>Could not find : <code class="error">'.$path.'</code><br>';
		$message .= "\nIn : <code>$root</code></p>\n";
        $message .= "<p>We looked for:";
        array_unshift($lookupPaths, $path . '/');
        foreach ($lookupPaths as $path) {
            $message .= "<br>\n<code>$root/$path</code>";
        }
        $message .= "\n</p>";

		$response = $this->render('TwigExpressBundle::notfound.html.twig', [
			'crumbs' => $breadcrumbs,
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
	 */
	private function renderTwig($templateName) {
	    // Do we have an extension, like .html or .json?
		$ext = pathinfo(substr($templateName, 0, -5), PATHINFO_EXTENSION);
        $cType = $ext ? StaticManager::getMediaType($ext) . ';charset=utf-8' : null;
		try {
			$response = $this->render($templateName);
			if ($cType) $response->headers->set('Content-Type', $cType);
			return $response;
		}
		catch (Twig_Error $error) {
			return $this->showTwigError($error);
		}
	}

	/**
	 * Show an error page for a Twig_Error, with the faulty Twig code if we can.
	 * @param Twig_Error $error
	 * @return Response
	 */
	private function showTwigError(Twig_Error $error) {
	    // Might be different from $this->templateName, if the error
        // occurred in an included file.
		$template = $error->getTemplateFile();
        $line = $error->getTemplateLine();
        $message = $error->getRawMessage();
        $localPath = str_replace($this->resourceRoot, '', $template);
        $systemPath = $this->resourceRootPath . '/' . $localPath;

        // Prepare breadcrumbs
        $breadcrumbs = StaticManager::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $localPath
        );

		$data = [
			'crumbs' => $breadcrumbs,
            'activeCrumb' => count($breadcrumbs) - 1,
			'title' => get_class($error),
			'message' => "$message<br>\nOn line $line of $template"
		];

        // Get a few lines of code from the buggy template
		if (file_exists($systemPath)) {
            $code = file_get_contents($systemPath);
            $data['code'] = StaticManager::formatCodeBlock($code, true, $line, 5);
            $data['codeLang'] = StaticManager::getHighlightLanguage($template);
		}

        return $this->render('TwigExpressBundle::twigerror.html.twig', $data);
    }

	/**
	 * Show a Twig file with syntax highlighting
	 * @param string $systemPath Full path to file
     * @param string $localPath Full path to file
     * @return Response
	 */
	private function showTwigSource($systemPath, $localPath) {
        // Prepare breadcrumbs
        $breadcrumbs = StaticManager::makeBreadcrumbs(
            $this->baseUrl,
            $this->bundleName,
            $localPath
        );
        $code = file_get_contents($systemPath);
		$data = [
		    'crumbs' => $breadcrumbs,
			'code' => StaticManager::formatCodeBlock($code, true),
            'codeLang' => StaticManager::getHighlightLanguage($localPath)
		];
        return $this->render('TwigExpressBundle::twigsource.html.twig', $data);
	}

}
