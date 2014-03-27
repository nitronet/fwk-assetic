<?php
namespace FwkAssetic\Controllers;

use Fwk\Core\ServicesAware;
use Fwk\Core\ContextAware;
use Fwk\Di\Container;
use Symfony\Component\HttpFoundation\Response;
use Fwk\Core\Context;
use Assetic\Asset\AssetCache;
use Assetic\Cache\FilesystemCache;
use Assetic\Asset\AssetInterface;

class AssetAction implements ServicesAware, ContextAware
{
    public $asset;
    
    protected $services;
    protected $context;
    
    public function show()
    {
        $assetic    = $this->getServices()->get('assetic');
        $response   = new Response();
        $response->setExpires(new \DateTime());
        
        if (empty($this->asset) || strpos($this->asset, '/') === false) {
            $response->setStatusCode(400);
            return $response;
        }
        
        list(,$formulaName,) = explode('/', $this->asset);
        
        // asset not found
        if (empty($formulaName) || !$assetic->getAssetManager()->has($formulaName)) {
            $response->setStatusCode(404);
            return $response;
        }
        
        $formula     = $assetic->getAssetManager()->getFormula($formulaName);
        if ($formula[0] instanceof AssetInterface) {
            $asset   = $formula[0];
        } else {
            $asset   = $assetic->getFactory()->createAsset($formula[0], $formula[1], $formula[2]);
        } 
        
        if (null !== $lastModified = $asset->getLastModified()) {
            $date = new \DateTime();
            $date->setTimestamp($lastModified);
            $response->setLastModified($date);
        }

        $formula['last_modified'] = $lastModified;
        $response->setETag(md5($asset->getContent()));
        
        $this->defineContentType($response);
        
        if ($response->isNotModified($this->getContext()->getRequest())) {
            return $response;
        }
        
        $response->setContent($this->cacheAsset($asset)->dump());
        
        return $response;
    }
    
    /**
     * 
     * @param AssetInterface $asset
     * 
     * @return AssetInterface 
     */
    protected function cacheAsset(AssetInterface $asset)
    {
        $assetic    = $this->getServices()->get('assetic');
        $useCache   = $assetic->hasCache();
        
        if (!$useCache) {
            return $asset;
        }
        
        $dir = $this->getServices()->getProperty('assetic.cache.directory', sys_get_temp_dir());
        return new AssetCache(
            $asset, 
            new FilesystemCache($dir)
        );
    }
    
    /**
     * Defines the content type header on the Response according to the
     * required asset name (@see $this->asset)
     * 
     * @param Response $response To-be-returned HTTP Response
     * 
     * @return void
     */
    protected function defineContentType(Response $response)
    {
        if(strpos($this->asset, '.js', strlen($this->asset)-3)) {
            $response->headers->set('Content-Type', 'text/javascript');
        } elseif(strpos($this->asset, '.css', strlen($this->asset)-4)) {
            $response->headers->set('Content-Type', 'text/css');
        } elseif(strpos($this->asset, '.png', strlen($this->asset)-4)) {
            $response->headers->set('Content-Type', 'image/png');
        } elseif(strpos($this->asset, '.jpg', strlen($this->asset)-4)
                || strpos($this->asset, '.jpeg', strlen($this->asset)-5)) {
            $response->headers->set('Content-Type', 'image/jpeg');
        } elseif(strpos($this->asset, '.gif', strlen($this->asset)-4)) {
            $response->headers->set('Content-Type', 'image/gif');
        } elseif(strpos($this->asset, '.ttf', strlen($this->asset)-4)) {
            $response->headers->set('Content-Type', 'application/font-ttf');
        } elseif(strpos($this->asset, '.woff', strlen($this->asset)-5)) {
            $response->headers->set('Content-Type', 'application/font-woff');
        } elseif(strpos($this->asset, '.svg', strlen($this->asset)-4)) {
            $response->headers->set('Content-Type', 'application/svg+xml');
        }
    }
    
    public function getServices()
    {
        return $this->services;
    }

    public function setServices(Container $services)
    {
        $this->services = $services;
    }
    
    public function getContext()
    {
        return $this->context;
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
    }
}