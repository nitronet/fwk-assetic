<?php
namespace FwkAssetic\Cache;

use Assetic\Factory\Resource\ResourceInterface;

class CacheResource implements ResourceInterface
{
    /**
     * @var string
     */
    protected $name;
    
    /**
     * Constructor
     * 
     * @param string $entryName The entry name
     * 
     * @return void
     */
    public function __construct($entryName)
    {
        $this->name = $entryName;
    }
    
    /**
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
    
    /**
     * 
     * @return string
     */
    public function getContent()
    {
        return null;
    }
    
    
    /**
     *
     * @param integer $timestamp (not used)
     * 
     * @return boolean
     */
    public function isFresh($timestamp)
    {
        return true;
    }
    
    public function getName()
    {
        return $this->name;
    }
}