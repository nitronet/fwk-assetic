<?php
namespace Nitronet\Fwk\Assetic;

use Fwk\Core\Components\ViewHelper\AbstractViewHelper;
use Fwk\Core\Components\ViewHelper\ViewHelper;
use Assetic\Factory\LazyAssetManager;

class AssetViewHelper extends AbstractViewHelper 
    implements ViewHelper
{
    protected $assetsService;
    protected $urlViewHelper;
    protected $debug = false;
    protected $actionName = 'Asset';
    
    public function __construct($assetsServiceName, $urlViewHelper, 
        $debug = false, $actionName = null
    ) {
        $this->assetsService    = $assetsServiceName;
        $this->urlViewHelper    = $urlViewHelper;
        $this->debug            = $debug;
        $this->actionName       = $actionName;
    }
    
    public function execute(array $arguments)
    {
        $cleanVariable = function($var) {
            if (!is_array($var)) {
                if (strpos($var, ',')) {
                    $var = array_map('trim', explode(',', $var));
                } else {
                    $var = array($var);
                }
            }
            return $var;
        };
        
        $scripts    = (isset($arguments[0]) ? $cleanVariable($arguments[0]) : array());
        $filters    = (isset($arguments[1]) ? $cleanVariable($arguments[1]) : array());
        $name       = (isset($arguments[2]) ? $arguments[2] : null);
        $debug      = (bool)(isset($arguments[3]) ? $arguments[3] : $this->debug);
        $combine    = (bool)(isset($arguments[4]) ? $arguments[4] : !$debug);
        $factory    = $this->getAssetsService()->getFactory();
        $output     = 'images/*';
        
        $scripts    = $this->getAssetsService()->applyShortcuts($scripts);
        
        $first = $scripts[0];
        if (strpos($first, '.js', strlen($first)-3)) {
            $output = 'js/*.js';
        } elseif (strpos($first, '.css', strlen($first)-4)) {
            $output = 'css/*.css';
        } elseif (strpos($first, '.ttf', strlen($first)-4)
                || strpos($first, '.woff', strlen($first)-5)) {
            $output = 'fonts/*.woff';
        } 
        
        if (null === $name) {
            $name = $factory->generateAssetName($scripts, $filters, array(
                'debug'     => $debug,
                'combine'   => $combine,
                'output'    => $output
            ));
        }
        
        $options = array(
            'debug'     => $debug,
            'name'      => $name,
            'combine'   => $combine,
            'output'    => $output
        );
        
        $collection = $factory->createAsset($scripts, $filters, $options);
        $return     = array();
        $url        = $this->getUrlViewHelper();
        $am         = $this->getAssetsService()->getAssetManager();
        
        if ($combine) {
            array_push($return, $url->execute(array(
                $this->actionName, 
                array('asset' => $collection->getTargetPath())
            )));
            
            if ($am instanceof LazyAssetManager) {
                list(,$formulaName,) = explode('/', $collection->getTargetPath());
                $am->setFormula(
                    $formulaName, 
                    array($scripts, $filters, $options)
                );
            }
        } else {
            foreach ($collection as $leaf) {
                array_push($return, $url->execute(array(
                    $this->actionName, 
                    array('asset' => $leaf->getTargetPath())
                )));
                
                if ($am instanceof LazyAssetManager) {
                    list(,$formulaName,) = explode('/', $leaf->getTargetPath());
                    $am->setFormula(
                        $formulaName, 
                        array($leaf, $filters, $options)
                     );
                }
            }
        }
        
        return $return;
    }
    
    /**
     * Returns the Assets Service
     * 
     * @return AssetsService 
     */
    public function getAssetsService()
    {
        return $this->getViewHelperService()
            ->getApplication()
            ->getServices()
            ->get($this->assetsService);
    }
    
    /**
     * Returns the UrlViewHelper
     * 
     * @return ViewHelper
     */
    public function getUrlViewHelper()
    {
        return $this->getViewHelperService()->helper($this->urlViewHelper);
    }
}