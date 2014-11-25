<?php
namespace Nitronet\Fwk\Assetic;

use Fwk\Core\Action\ProxyFactory as PF;
use Fwk\Core\Application;
use Fwk\Core\Components\UrlRewriter\UrlRewriterLoadedEvent;
use Fwk\Core\Components\ViewHelper\ViewHelperLoadedEvent;
use Fwk\Core\Plugin;
use Fwk\Di\ClassDefinition;
use Fwk\Di\Container;
use Fwk\Core\Components\UrlRewriter\Route;
use Fwk\Core\Components\UrlRewriter\RouteParameter;

class AsseticPlugin implements Plugin
{
    private $config = array();

    private $shortcuts = array();

    public function __construct(array $config = array(), array $shortcuts = array())
    {
        $this->config = array_merge(array(
            'directory'     => null,
            'action'        => 'Asset',
            'controller'    => 'Nitronet\Fwk\Assetic\Controllers\AssetAction:show',
            'debug'         => false,
            'cache'         => false,
            'cacheDir'      => null,
            'cacheStrategy' => 'content',
            'cssrewrite'    => true,
            'helperName'    => 'asset'
        ), $config);
    }

    public function loadServices(Container $container)
    {
        // the ViewHelper
        $defViewHelper = new ClassDefinition('Nitronet\Fwk\Assetic\AssetViewHelper', array(
            $this->cfg('helperName', 'asset'),
            'url',
            $this->cfg('debug', false),
            $this->cfg('action')
        ));
        $container->set('assetic.ViewHelper', $defViewHelper, true);

        // filter manager
        $defFilterManager = new ClassDefinition('Assetic\FilterManager');
        $container->set('assetic.FilterManager', $defFilterManager, true);

        // cssrewrite filter
        if ($this->cfg('cssrewrite', true) === true) {
            $defCssRewriteFilter = new ClassDefinition('Nitronet\Fwk\Assetic\Filters\CssRewriteFilter', array(
                '@assetic.ViewHelper'
            ));

            $container->set('assetic.CssRewriteFilter', $defCssRewriteFilter, true);
            $defFilterManager->addMethodCall('set', array('cssrewrite', '@assetic.CssRewriteFilter'));
        }

        // asset factory
        $defAssetFactory = new ClassDefinition('Assetic\Factory\AssetFactory', array(
            $this->cfg('directory', null),
            $this->cfg('debug', false),
        ));

        $defAssetFactory->addMethodCall('setFilterManager', array('@assetic.FilterManager'));
        $container->set('assetic.AssetFactory', $defAssetFactory, true);

        // service
        $defService = new ClassDefinition('Nitronet\Fwk\Assetic\AssetsService', array(
            '@assetic.AssetFactory'
        ));

        $defService->addMethodCall('addShortcuts', array($this->shortcuts));
        $container->set('assetic', $defService, true);

        // caching
        if ($this->cfg('cache', false) === true) {
            $defFilesystemCache = new ClassDefinition('Assetic\Cache\FilesystemCache', array(
                $this->cfg('cacheDir', null)
            ));
            $container->set('assetic.FilesystemCache', $defFilesystemCache, true);

            $defCacheBustingWorker = new ClassDefinition('Assetic\Factory\Worker\CacheBustingWorker', array(
                $this->cfg('cacheStrategy', 'content')
            ));
            $container->set('assetic.CacheBustingWorker', $defCacheBustingWorker, true);

            $defAssetFactory->addMethodCall('addWorker', array('@assetic.CacheBustingWorker'));
            $defService->addArgument('@assetic.FilesystemCache');
        }
    }

    public function load(Application $app)
    {
        $app->register(
            $this->cfg('action', 'Asset'),
            PF::factory($this->cfg('controller', 'Nitronet\Fwk\Assetic\Controllers\AssetAction:show'))
        );
    }

    public function onUrlRewriterLoaded(UrlRewriterLoadedEvent $event)
    {
        $event->getUrlRewriterService()->addRoute(
            new Route($this->cfg('action', 'Asset'), '/asset/:name', array(
                new RouteParameter('name', null, '.*', true)
            )
        ));
    }

    public function onViewHelperLoaded(ViewHelperLoadedEvent $event)
    {
        $event->getViewHelperService()->add(
            $this->cfg('helperName', 'asset'),
            $event->getApplication()->getServices()->get('assetic.ViewHelper')
        );
    }

    protected function cfg($key, $default = false)
    {
        return (array_key_exists($key, $this->config) ? $this->config[$key] : $default);
    }
}