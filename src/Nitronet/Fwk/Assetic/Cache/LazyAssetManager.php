<?php
namespace Nitronet\Fwk\Assetic\Cache;

use Assetic\Factory\LazyAssetManager as LazyAssetManagerBase;
use Assetic\Factory\AssetFactory;
use Assetic\Cache\CacheInterface;

class LazyAssetManager extends LazyAssetManagerBase
{
    /**
     * Cache Manager
     * 
     * @var CacheInterface
     */
    protected $cache;
    
    public function __construct(AssetFactory $factory, $loaders = array(), 
        CacheInterface $cache = null
    ) {
        parent::__construct($factory, $loaders);
        
        $this->cache = $cache;
        $this->addResource(new CacheResource('assetic'), 'cache');
    }
    
    /**
     *
     * @param string $name
     * @param array  $formula 
     * 
     * @return void
     */
    public function setFormula($name, array $formula)
    {
        $formulas = ($this->getCache()->has('assetic') ? 
            unserialize($this->getCache()->get('assetic')) :
            array()
        );
        
        if (!is_array($formulas)) {
            $formulas = array();
        }
        
        if (array_key_exists($name, $formulas)) {
            return;
        }
        
        $formulas[$name] = $this->makeFormulaSerializable($formula);
        
        $this->getCache()->set('assetic', serialize($formulas));
        
        return parent::setFormula($name, $formula);
    }
    
    protected function makeFormulaSerializable(array $formula)
    {
        if ($formula[0] instanceof \Assetic\Asset\AssetInterface) {
            $formula[0]->clearFilters();
        }
        
        return $formula;
    }
    
    /**
     * Returns Cache Manager
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
     * @param CacheInterface $cache Cache Manager Instance
     * 
     * @return LazyAssetManager 
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        
        return $this;
    }
}