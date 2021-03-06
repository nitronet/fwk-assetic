<?php
namespace Nitronet\Fwk\Assetic;

use Assetic\Factory\AssetFactory;
use Assetic\Cache\CacheInterface;
use Assetic\AssetManager;
use Nitronet\Fwk\Assetic\Cache\CacheFormulaLoader;
use Nitronet\Fwk\Assetic\Cache\LazyAssetManager;

class AssetsService
{
    /**
     * Assetic's Assets Factory
     * @var AssetFactory
     */
    protected $factory;
    
    /**
     * Asset Manager
     * @var AssetManager
     */
    protected $manager;
    
    /**
     * Cache Manager
     * @var CacheInterface
     */
    protected $cache;
    
    /**
     * Shortcuts to Assets paths
     * @var array
     */
    protected $shortcuts = array();

    protected $cacheDirectory = null;
    
    /**
     * Constructor
     * 
     * @param AssetFactory   $factory The AssetFactory 
     * @param CacheInterface $cache   The Cache Manager
     * @param AssetManager   $manager The Asset Manager
     * 
     * @return void 
     */
    public function __construct(AssetFactory $factory = null, 
       CacheInterface $cache = null, $cacheDir = null, AssetManager $manager = null
    ) {
        $this->factory  = $factory;
        $this->manager  = $manager;
        $this->cache    = $cache;
        $this->cacheDirectory = $cacheDir;
        $this->factory->setAssetManager($this->getAssetManager());
    }
    
    /**
     * Returns the AssetFactory.
     * 
     * @return AssetFactory 
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * Defines the AssetFactory 
     * 
     * @param AssetFactory $factory The AssetFactory
     * 
     * @return AssetsService 
     */
    public function setFactory(AssetFactory $factory)
    {
        $this->factory = $factory;
        
        return $this;
    }
    
    /**
     * Returns the Asset Manager
     * 
     * @return AssetManager 
     */
    public function getAssetManager()
    {
        if (!isset($this->manager)) {
            $this->manager = $this->assetManagerFactory();
        }
        
        return $this->manager;
    }

    /**
     * Builds the AssetManager. 
     * 
     * If the Cache Manager is defined, we use a Cache\LazyAssetManager, if not
     * we use the regular AssetManager
     * 
     * @return AssetManager
     */
    protected function assetManagerFactory()
    {
        // cache is configured, let's use it
        if ($this->cache !== null) {
            return new LazyAssetManager(
                $this->getFactory(),
                array(
                    'cache' => new CacheFormulaLoader(
                        $this->cache,
                        $this->getFactory()->getFilterManager()
                    )
                ),
                $this->cache
            );
        } else {
            return new AssetManager();
        }
    }
    
    /**
     * Defines the Assets Manager
     * 
     * @param AssetManager $manager The AssetManager
     * 
     * @return AssetsService
     */
    public function setAssetManager(AssetManager $manager)
    {
        $this->manager = $manager;
        
        return $this;
    }
    
    /**
     * Returns the Cache Manager
     * 
     * @return CacheInterface 
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Defines the Cache Manager
     * 
     * @param CacheInterface $cache The Cache Manager
     * 
     * @return AssetsService 
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getCacheDirectory()
    {
        return $this->cacheDirectory;
    }

    /**
     * @param string $cacheDirectory
     */
    public function setCacheDirectory($cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
    }


    /**
     * Tells if the cache is enabled
     * 
     * @return boolean
     */
    public function hasCache()
    {
        return ($this->cache instanceof CacheInterface);
    }
    
    public function addShortcut($name, $target)
    {
        $this->shortcuts[$name] = $target;
        
        return $this;
    }
    
    public function removeShortcut($name)
    {
        unset($this->shortcuts[$name]);
        
        return $this;
    }
    
    public function addShortcuts(array $shortcuts)
    {
        $this->shortcuts = array_merge($this->shortcuts, $shortcuts);
        
        return $this;
    }
    
    public function applyShortcuts(array $assets)
    {
        $replaces = array();
        array_walk($this->shortcuts, function ($target, $name) use (&$replaces) { 
            $replaces['+'. $name] = $target;
        });
        
        foreach($assets as $idx => $asset) {
            $assets[$idx] = str_replace(
                array_keys($replaces), 
                array_values($replaces), 
                $asset
            );
        }
        
        return $assets;
    }
}