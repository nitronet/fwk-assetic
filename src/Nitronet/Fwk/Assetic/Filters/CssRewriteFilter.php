<?php
namespace Nitronet\Fwk\Assetic\Filters;

use Assetic\Filter\BaseCssFilter;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\HashableInterface;
use Nitronet\Fwk\Assetic\AssetViewHelper;

class CssRewriteFilter extends BaseCssFilter 
    implements HashableInterface
{
    /**
     *
     * @var AssetViewHelper
     */
    protected $viewHelper;
    
    public function __construct(AssetViewHelper $viewHelper)
    {
        $this->viewHelper = $viewHelper;
    }
    
    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $sourceBase = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();
        $targetPath = $asset->getTargetPath();

        if (null === $sourcePath || null === $targetPath || $sourcePath == $targetPath) {
            return;
        }

        // learn how to get from the target back to the source
        if (false !== strpos($sourceBase, '://')) {
            list($scheme, $url) = explode('://', $sourceBase.'/'.$sourcePath, 2);
            list($host, $path) = explode('/', $url, 2);

            $host = $scheme.'://'.$host.'/';
            $path = false === strpos($path, '/') ? '' : dirname($path);
            $path .= '/';
        } else {
            // assume source and target are on the same host
            $host = '';

            // pop entries off the target until it fits in the source
            if ('.' == dirname($sourcePath)) {
                $path = str_repeat('../', substr_count($targetPath, '/'));
            } elseif ('.' == $targetDir = dirname($targetPath)) {
                $path = dirname($sourcePath).'/';
            } else {
                $path = '';
                while (0 !== strpos($sourcePath, $targetDir)) {
                    if (false !== $pos = strrpos($targetDir, '/')) {
                        $targetDir = substr($targetDir, 0, $pos);
                        $path .= '../';
                    } else {
                        $targetDir = '';
                        $path .= '../';
                        break;
                    }
                }
                $path .= ltrim(substr(dirname($sourcePath).'/', strlen($targetDir)), '/');
            }
        }

        $root = $asset->getSourceRoot();
        $viewHelper =  $this->viewHelper;
        
        $content = $this->filterReferences($asset->getContent(), function($matches) use ($host, $path, $root, $viewHelper) {
            if (false !== strpos($matches['url'], '://') || 0 === strpos($matches['url'], '//') || 0 === strpos($matches['url'], 'data:')) {
                // absolute or protocol-relative or data uri
                return $matches[0];
            }

            if ('/' == $matches['url'][0]) {
                // root relative
                return str_replace($matches['url'], $host.$matches['url'], $matches[0]);
            }

            // document relative
            $url = $matches['url'];
            if (substr($url, 0, 2) === './') {
                $final = $root . substr($url, 1);
            } elseif (substr($url, 0, 3) === '../') {
                $final = dirname($root) . substr($url, 2);
            } 
            
            if (!isset($final)) {
                $final = $root . DIRECTORY_SEPARATOR . $url;
            }

            $dieze = null;
            if (strpos($final, '?') !== false) {
                list($final, $get) = explode('?', $final);
            } else {
                $get = null;
                if (strpos($final, '#') !== false) {
                    list($final, $dieze) = explode('#', $final);
                }
            }

            if (!is_file($final)) {
                return $matches[0];
            }
            
            $src = $viewHelper->execute(array(
                array($final),
                array(),
                null,
                false
            ));
            
            return str_replace($url, $src[0] . (!empty($get) ? '?'. $get : null) . (!empty($dieze) ? '#'. $dieze : null), $matches[0]);
        });

        $asset->setContent($content);
    }
    
    public function getViewHelper()
    {
        return $this->viewHelper;
    }

    public function setViewHelper(AssetViewHelper $viewHelper) 
    {
        $this->viewHelper = $viewHelper;
    }
    
    public function hash() {
        return 'cssRewrite';
    }
}
