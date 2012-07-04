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

namespace AlphaLemon\AlphaLemonCmsBundle\Tests\Unit\Core\Content\Slot\Repeated\Converter;

use AlphaLemon\AlphaLemonCmsBundle\Tests\TestCase;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\AlSlotManager;
use AlphaLemon\AlphaLemonCmsBundle\Model\AlPage;
use AlphaLemon\AlphaLemonCmsBundle\Model\AlLanguage;
use AlphaLemon\AlphaLemonCmsBundle\Core\Repository\AlBlockQuery;
use AlphaLemon\AlphaLemonCmsBundle\Tests\tools\AlphaLemonDataPopulator;
use AlphaLemon\ThemeEngineBundle\Core\TemplateSlots\AlSlot;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\Factory\AlSlotsConverterFactory;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Slot\Repeated\Converter\AlSlotConverterToPage;

/**
 * AlSlotsConverterToPageTest
 *
 * @author AlphaLemon <webmaster@alphalemon.com>
 */
class AlSlotsConverterToPageTest extends TestCase 
{    
    protected function setUp() 
    {
        parent::setUp();
        
        $this->pageContents = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Content\PageBlocks\AlPageBlocks')
                           ->disableOriginalConstructor()
                            ->getMock();
        
        
        
        $this->languageRepository = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Propel\AlLanguageRepositoryPropel')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->pageRepository = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Propel\AlPageRepositoryPropel')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->blockRepository = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Repository\Propel\AlBlockRepositoryPropel')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->blockRepository->expects($this->any())
            ->method('getModelObjectClassName')
            ->will($this->returnValue('\AlphaLemon\AlphaLemonCmsBundle\Model\AlBlock'));
        
        $this->blockRepository->expects($this->any())
            ->method('setModelObject')
            ->will($this->returnSelf());
    }
    
    public function testConvertReturnsNullWhenAnyBlockExists()
    {
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array()));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertNull($converter->convert());
    }
    
    public function testConvertReturnsNullWhenAnyLanguageExists()
    {
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->once())
            ->method('rollback');
        
        $this->blockRepository->expects($this->once())
            ->method('save');
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertNull($converter->convert());
    }
    
    public function testConvertFailsOnAnEmptySlotWhenDbSavingFails()
    {
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->once())
            ->method('rollback');
        
        $this->blockRepository->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertFalse($converter->convert());
    }
    
    public function testConvertFailsWhenExistingBlocksRemovingFails()
    {
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->once())
            ->method('rollback');
        
        $this->blockRepository->expects($this->never())
            ->method('save');
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(false));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertFalse($converter->convert());
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testConvertFailsWhenAnUnespectedExceptionIsThrowsWhenRemovingBlocks()
    {
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('rollback');
        
        $this->blockRepository->expects($this->never())
            ->method('save');
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new \RuntimeException));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertFalse($converter->convert());
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testConvertFailsWhenAnUnespectedExceptionIsThrowsWhenSavingNewBlocks()
    {
        $block = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->once())
            ->method('commit');
        
        $this->blockRepository->expects($this->once())
            ->method('rollback');
        
        $this->blockRepository->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \RuntimeException));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    public function testSingleBlockSlotWhenSinglePageAndLanguageHasBeenConverted()
    {
        $block = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');
        
        $this->blockRepository->expects($this->never())
            ->method('rollback');
        
        $this->blockRepository->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    public function testMoreBlockSlotWhenSingleLanguageHasBeenConverted()
    {
        $block = $this->setUpBlock();
        $block1 = $this->setUpBlock();
        $block2 = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block, $block1, $block2)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');
        
        $this->blockRepository->expects($this->never())
            ->method('rollback');
        
        $this->blockRepository->expects($this->exactly(3))
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    public function testSingleBlockSlotWhenMorePagesAndSingleLanguageHasBeenConverted()
    {
        $block = $this->setUpBlock();
        $block1 = $this->setUpBlock();
        $block2 = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block, $block1, $block2)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2), $this->setUpPage(3))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');
        
        $this->blockRepository->expects($this->never())
            ->method('rollback');
        
        $this->blockRepository->expects($this->exactly(6))
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    public function testSingleBlockSlotWhenMoreLanguagesHasBeenConverted()
    {
        $block = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2), $this->setUpLanguage(3))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');
        
        $this->blockRepository->expects($this->never())
            ->method('rollback');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    public function testMoreBlockSlotWhenMoreLanguagesHasBeenConverted()
    {
        $block = $this->setUpBlock();
        $block1 = $this->setUpBlock();
        $block2 = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block, $block1, $block2)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2),$this->setUpLanguage(3))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');
        
        $this->blockRepository->expects($this->never())
            ->method('rollback');
        
        $this->blockRepository->expects($this->exactly(6))
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    public function testSingleBlockSlotWhenMorePagesAndLanguagesHasBeenConverted()
    {
        $block = $this->setUpBlock();
        $block1 = $this->setUpBlock();
        $block2 = $this->setUpBlock();
        $this->pageContents->expects($this->once())
            ->method('getSlotBlocks')
            ->will($this->returnValue(array($block, $block1, $block2)));
        
        $this->languageRepository->expects($this->once())
            ->method('activeLanguages')
            ->will($this->returnValue(array($this->setUpLanguage(2), $this->setUpLanguage(3))));
        
        $this->pageRepository->expects($this->once())
            ->method('activePages')
            ->will($this->returnValue(array($this->setUpPage(2), $this->setUpPage(3))));
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('startTransaction');
        
        $this->blockRepository->expects($this->exactly(2))
            ->method('commit');
        
        $this->blockRepository->expects($this->never())
            ->method('rollback');
        
        $this->blockRepository->expects($this->exactly(12))
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->blockRepository->expects($this->any())
            ->method('retrieveContentsBySlotName')
            ->will($this->returnValue(array($this->setUpBlock())));
        
        $this->blockRepository->expects($this->once())
            ->method('delete')
            ->will($this->returnValue(true));
        
        $slot = new AlSlot('test', array('repeated' => 'page'));
        $converter = new AlSlotConverterToPage($slot, $this->pageContents, $this->languageRepository, $this->pageRepository, $this->blockRepository);
        $this->assertTrue($converter->convert());
    }
    
    private function setUpBlock()
    {
        $block = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlBlock');
        $block->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue(array("Id" => 2, "ClassName" => "Text")));
        
        return $block;
    }
    
    private function setUpLanguage($id)
    {
        $language = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlLanguage');        
        $language->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        
        return $language;
    }
    
    private function setUpPage($id)
    {
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');        
        $page->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        
        return $page;
    }
}