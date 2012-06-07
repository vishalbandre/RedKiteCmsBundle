<?php
/*
 * This file is part of the AlphaLemon CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 * 
 * @license    GPL LICENSE Version 2.0
 * 
 */

namespace AlphaLemon\AlphaLemonCmsBundle\Core\Content\PageContentsContainer; 

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use AlphaLemon\AlphaLemonCmsBundle\Core\Model\Entities\BlockModelInterface;
use AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General;

/**
 * AlPageContentsContainer is the object responsible to manage the contents on a web page.
 * 
 * 
 * Providing the page id and language id, it retrieves the contents and arrange them 
 * into an array which keys are the name of slot where the contents live.
 * 
 * @author alphalemon <webmaster@alphalemon.com>
 */
class AlPageContentsContainer implements AlPageContentsContainerInterface
{
    protected $idPage = null;
    protected $idLanguage = null;
    protected $blockModel;
    protected $dispatcher;
    protected $blocks = array(); 
    
    /**
     * Constructor
     * 
     * @param EventDispatcherInterface $dispatcher
     * @param BlockModelInterface $blockModel 
     */
    public function __construct(EventDispatcherInterface $dispatcher, BlockModelInterface $blockModel)
    {
        $this->dispatcher = $dispatcher;
        $this->blockModel = $blockModel;
    }
    
    /**
     * The id of the page to retrieve
     * 
     * @param int $v
     * @return \AlphaLemon\AlphaLemonCmsBundle\Core\Content\PageContentsContainer\AlPageContentsContainer
     * @throws General\InvalidParameterTypeException 
     */
    public function setIdPage($v)
    {
        if (!is_numeric($v)) {
            throw new General\InvalidParameterTypeException("The page id must be a numeric value");
        }
        
        $this->idPage = $v;
        
        return $this;
    }
    
    /**
     * The id of the language to retrieve
     * 
     * @param type $v
     * @return \AlphaLemon\AlphaLemonCmsBundle\Core\Content\PageContentsContainer\AlPageContentsContainer
     * @throws General\InvalidParameterTypeException 
     */
    public function setIdLanguage($v)
    {
        if (!is_numeric($v)) {
            throw new General\InvalidParameterTypeException("The language id must be a numeric value");
        }
        
        $this->idLanguage = $v;
        
        return $this;
    }
    
    /**
     * Returns the current page id
     * 
     * @return int 
     */
    public function getIdPage()
    {
        return $this->idPage;
    }
    
    /**
     * Returns the current language id
     * 
     * @return int 
     */
    public function getIdLanguage()
    {
        return $this->idLanguage;
    }

    /**
     * Return all the page's blocks
     * 
     * @return array 
     */
    public function getBlocks()
    {
        return $this->blocks;
    }
    
    /**
     * Return all blocks placed on the given slot name
     * 
     * @return array 
     */
    public function getSlotBlocks($slotName)
    {
        return (array_key_exists($slotName, $this->blocks)) ? $this->blocks[$slotName] : array();
    }
    
    /**
     * Refreshes the blocks
     * 
     * @return \AlphaLemon\AlphaLemonCmsBundle\Core\Content\PageContentsContainer\AlPageContentsContainer 
     */
    public function refresh()
    {
        $this->setUpBlocks();
        
        return $this;
    }
          
    /**
     * Retrieves from the database the contents and arranges them by slots
     * 
     * @return array
     */
    protected function setUpBlocks()
    {
        if (null === $this->idLanguage) {
            throw new General\ParameterIsEmptyException("Contents cannot be retrieved because the id language has not been set");
        }
        
        if (null === $this->idPage) {
            throw new General\ParameterIsEmptyException("Contents cannot be retrieved because the id page has not been set");
        }
        
        $this->blocks = array();
        
        $alBlocks = $this->blockModel->retrieveContents(array(1, $this->idLanguage), array(1, $this->idPage));
        foreach ($alBlocks as $alBlock) {
            $this->blocks[$alBlock->getSlotName()][] = $alBlock; 
        }
    }
}