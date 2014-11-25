<?php
namespace Nitronet\Fwk\Assetic\Cache;

use Assetic\Factory\Loader\FormulaLoaderInterface;
use Assetic\Factory\Resource\ResourceInterface;
use Assetic\Cache\CacheInterface;
use Assetic\FilterManager;

class CacheFormulaLoader implements FormulaLoaderInterface
{
    /**
     * Cache Manager
     * @var CacheInterface
     */
    protected $cache;
    
    /**
     * The Filter Manager
     * @var FilterManager
     */
    protected $filterManager;
    
    /**
     * Constructor
     * 
     * @param CacheInterface     $cache         Cache Manager
     * @param null|FilterManager $filterManager Assetic's Filter Manager
     * 
     * @return void
     */
    public function __construct(CacheInterface $cache, 
        FilterManager $filterManager = null
    ) {
        $this->cache = $cache;
        $this->filterManager = $filterManager;
    }
    
    /**
     * Loads a cached resource
     * 
     * @param ResourceInterface $resource
     * 
     * @return array 
     */
    public function load(ResourceInterface $resource)
    {
        if (!$resource instanceof CacheResource) {
            return array();
        }
        
        if (!$this->cache->has($resource->getName())) {
            return array();
        }
        
        $formulas = unserialize($this->cache->get($resource->getName()));
        foreach ($formulas as $idx => $formula) {
            $formulas[$idx] = $this->restoreFormulaFilters($formula);
        }
        
        return $formulas;
    }
    
    protected function restoreFormulaFilters(array $formula)
    {
        if (!$this->filterManager instanceof FilterManager) {
            return $formula;
        }
        
        if (!$formula[0] instanceof \Assetic\Asset\FileAsset) {
            return $formula;
        }
        
        if (!isset($formula[1]) || !is_array($formula[1]) 
            || !count($formula[1])
        ) {
            return $formula;
        }
        
        $filters = array();
        foreach ($formula[1] as $filterName) {
             $filters[$filterName] = $this->filterManager->get($filterName);
        }
        
        $assetClass = get_class($formula[0]);
        $new = new $assetClass(
            $formula[0]->getSourceRoot() . DIRECTORY_SEPARATOR . $formula[0]->getSourcePath(),
            $filters, 
            $formula[0]->getSourceRoot(), 
            $formula[0]->getSourcePath(), 
            $formula[0]->getVars()
        );
        
        $new->setTargetPath($formula[0]->getTargetPath());
        $formula[0] = $new;
        
        return $formula;
    }
}