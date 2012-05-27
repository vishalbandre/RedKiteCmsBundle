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

namespace AlphaLemon\AlphaLemonCmsBundle\Tests\Unit\Core\Content\Page;

use AlphaLemon\AlphaLemonCmsBundle\Tests\TestCase;
use AlphaLemon\AlphaLemonCmsBundle\Core\Content\Page\AlPageManager;
use AlphaLemon\AlphaLemonCmsBundle\Model\AlLanguage;

use AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General;

/**
 * AlPageManagerTest
 *
 * @author AlphaLemon <webmaster@alphalemon.com>
 */
class AlPageManagerTest extends TestCase
{   
    private $dispatcher;
    private $pageManager;
    private $templateManager;
      
    protected function setUp() 
    {
        parent::setUp();
        
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        
        $this->validator = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Content\Validator\AlParametersValidatorPageManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->templateManager = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Content\Template\AlTemplateManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->pageModel = $this->getMockBuilder('AlphaLemon\AlphaLemonCmsBundle\Core\Model\Propel\AlPageModelPropel')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        
        $this->pageModel->expects($this->any())
            ->method('getModelObjectClassName')
            ->will($this->returnValue('\AlphaLemon\AlphaLemonCmsBundle\Model\AlPage'));
        
        $this->pageManager = new AlPageManager($this->dispatcher, $this->templateManager, $this->pageModel, $this->validator);
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\InvalidParameterTypeException
     */
    public function testSetFailsWhenANotValidPropelObjectIsGiven()
    {
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlBlock');
        
        $this->pageManager->set($page);
    }
    
    public function testSetANullAlPageObject()
    {
        $this->pageManager->set(null);
        $this->assertNull($this->pageManager->get());
    }
    
    public function testSetAlPageObject()
    {
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageManager->set($page);
        $this->assertEquals($page, $this->pageManager->get());
    }
    
    /**
     * @expectedException \AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\EmptyParametersException
     */
    public function testAddFailsWhenAnyParamIsGiven()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
                
        $this->validator->expects($this->once())
            ->method('checkEmptyParams')
            ->will($this->throwException(new General\EmptyParametersException()));
        
        $values = array();
        $this->pageManager->save($values); 
    }
    
    /**
     * @expectedException \AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterExpectedException
     */
    public function testAddFailsWhenAnyExpectedParamIsGiven()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
                
        $this->validator->expects($this->once())
            ->method('checkRequiredParamsExists')
            ->will($this->throwException(new General\ParameterExpectedException()));
                
        $values = array('fake' => 'value');
                
        $this->pageManager->save($values); 
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterIsEmptyException
     */
    public function testAddFailsWhenExpectedPageNameParamIsMissing()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $params = array('TemplateName'      => 'home', 
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $this->pageManager->save($params); 
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterIsEmptyException
     */
    public function testAddFailsWhenExpectedTemplateParamIsMissing()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $params = array('PageName'      => 'fake page', 
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $this->pageManager->save($params); 
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\Page\PageExistsException
     */
    public function testAddFailsWhenTryingToAddPageThatAlreadyExists()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('pageExists')
            ->will($this->returnValue(true));
        
        $params = array('PageName'      => 'fake page', 
                        'TemplateName'      => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $this->pageManager->save($params); 
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\Page\AnyLanguageExistsException
     */
    public function testAddFailsWhenAnyLanguageHasBeenAddedAndTryingToAddPage()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(false));
        
        $params = array('PageName'      => 'fake page', 
                        'TemplateName'      => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $this->pageManager->save($params); 
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testAddBlockThrownAnUnespectedException()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollback');
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
                ->method('save')
                ->will($this->throwException(new \RuntimeException()));
        
        $params = array('PageName'      => 'fake page', 
                        'TemplateName'  => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        $this->pageManager->save($params); 
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testResetHomeThrownAnUnespectedExceptionWhenAdding()
    {
        $this->dispatcher->expects($this->once(1))
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasPages')
            ->will($this->returnValue(true));
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(true));
        
        $homepage = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageModel->expects($this->once())
            ->method('homePage')
            ->will($this->returnValue($homepage));
        
        $this->pageModel->expects($this->once(1))
            ->method('setModelObject')
            ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \RuntimeException()));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $params = array('PageName'      => 'fake page', 
                        'IsHome'        => '1', 
                        'TemplateName'  => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $this->pageManager->save($params); 
    }
        
    public function testAddNewPageFailsBecauseSaveFailsAtLast()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));
        
        $params = array('PageName'      => 'fake page', 
                        'TemplateName'  => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $res = $this->pageManager->save($params); 
        $this->assertFalse($res);
    }
        
    public function testAddNewPageFailsBecauseResetHomeFails()
    {
        $this->dispatcher->expects($this->once(1))
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasPages')
            ->will($this->returnValue(true));
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(true));
        
        $homepage = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageModel->expects($this->once())
            ->method('homePage')
            ->will($this->returnValue($homepage));
        
        $this->pageModel->expects($this->once(1))
            ->method('setModelObject')
            ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $params = array('PageName'      => 'fake page', 
                        'IsHome'        => '1', 
                        'TemplateName'  => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $res = $this->pageManager->save($params); 
        $this->assertFalse($res);
    }
    
    public function testAddNewHomePage()
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasPages')
            ->will($this->returnValue(true));
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->exactly(2))
            ->method('save')
            ->will($this->returnValue(true));
        
        $homepage = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageModel->expects($this->once())
            ->method('homePage')
            ->will($this->returnValue($homepage));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('commit');
        
        $this->pageModel->expects($this->never())
            ->method('rollback');
        
        $this->pageModel->expects($this->exactly(2))
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $params = array('PageName'      => 'fake page', 
                        'IsHome'        => '1', 
                        'TemplateName'  => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $res = $this->pageManager->save($params); 
        $this->assertTrue($res);
    }
    
    public function testAddNewPage()
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasLanguages')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('commit');
        
        $this->pageModel->expects($this->never())
            ->method('rollback');
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $params = array('PageName'      => 'fake page', 
                        'TemplateName'  => 'home',
                        'Permalink'     => 'this is a website fake page',
                        'Title'         => 'page title',
                        'Description'   => 'page description',
                        'Keywords'      => '');
        
        $res = $this->pageManager->save($params); 
        $this->assertTrue($res);
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\EmptyParametersException
     */
    public function testEditFailsWhenAnyParamIsGiven()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('checkEmptyParams')
            ->will($this->throwException(new General\EmptyParametersException()));
        
        $this->pageModel->expects($this->never())
            ->method('save');
        
        $params = array();
        $this->pageManager->save($params); 
    }
    
    public function testEditFailsBecauseSaveFailsAtLast()
    {
        $this->dispatcher->expects($this->once(1))
            ->method('dispatch');        
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));
        
        $page->expects($this->any())
            ->method('getPageName')
            ->will($this->returnValue('fake-page'));
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $params = array('PageName' => 'fake page');
        $this->pageManager->set($page);
        $res = $this->pageManager->save($params); 
        $this->assertFalse($res);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testEditBlockThrownAnUnespectedException()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollback');
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
                ->method('save')
                ->will($this->throwException(new \RuntimeException()));
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->any())
                ->method('getId')
                ->will($this->returnValue(2));
        
        $params = array('PageName' => 'fake page');
        $this->pageManager->set($page);    
        $this->pageManager->save($params); 
    }
    
    public function testEditPageName()
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');        
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));
        
        $page->expects($this->any())
            ->method('getPageName')
            ->will($this->returnValue('fake-page'));
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('commit');
        
        $this->pageModel->expects($this->never())
            ->method('rollback');
        
        $params = array('PageName' => 'fake page');
        $this->pageManager->set($page);
        $res = $this->pageManager->save($params); 
        $this->assertTrue($res);
        $this->assertEquals('fake-page', $this->pageManager->get()->getPageName());
    }
    
    public function testEditHomePageBecauseResetHomeFails()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasPages')
            ->with(1)
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(false));
        
        $homepage = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageModel->expects($this->once())
            ->method('homePage')
            ->will($this->returnValue($homepage));
        
        $this->pageModel->expects($this->once())
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));
        
        $params = array('IsHome' => 1);
        $this->pageManager->set($page);
        $res = $this->pageManager->save($params); 
        $this->assertFalse($res);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testResetHomeThrownAnUnespectedExceptionWhenEditing()
    {
        $this->dispatcher->expects($this->once(1))
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasPages')
            ->will($this->returnValue(true));
        
        $homepage = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageModel->expects($this->once())
            ->method('homePage')
            ->will($this->returnValue($homepage));
        
        $this->pageModel->expects($this->once(1))
            ->method('setModelObject')
            ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->throwException(new \RuntimeException()));
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));
        
        $params = array('IsHome' => 1);
        $this->pageManager->set($page);
        $this->pageManager->save($params); 
    }
    
    public function testEditHomePage()
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');
        
        $this->validator->expects($this->once())
            ->method('hasPages')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->exactly(2))
            ->method('save')
            ->will($this->returnValue(true));
        
        $homepage = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $this->pageModel->expects($this->once())
            ->method('homePage')
            ->will($this->returnValue($homepage));
        
        $this->pageModel->expects($this->exactly(2))
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('commit');
        
        $this->pageModel->expects($this->never())
            ->method('rollback');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));
        
        $params = array('IsHome' => 1);
        $this->pageManager->set($page);
        $res = $this->pageManager->save($params); 
        $this->assertTrue($res);
    }
    
    public function testEditTemplate()
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');
                
        $this->pageModel->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once(2))
                ->method('setModelObject')
                ->will($this->returnSelf());
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('commit');
        
        $this->pageModel->expects($this->never())
            ->method('rollback');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(2));
        
        $page->expects($this->once())
            ->method('getTemplateName');
        
        $params = array('TemplateName' => 'new');
        $this->pageManager->set($page);
        $res = $this->pageManager->save($params); 
        $this->assertTrue($res);
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\General\ParameterIsEmptyException
     */
    public function testDeleteFailsWhenTheManagedPageIsNull()
    {
        $this->dispatcher->expects($this->never())
            ->method('dispatch');
        
        $this->pageManager->set(null);
        $this->pageManager->delete(); 
    }
    
    /**
     * @expectedException AlphaLemon\AlphaLemonCmsBundle\Core\Exception\Content\Page\RemoveHomePageException
     */
    public function testDeleteFailsWhenTryingToRemoveTheHomePage()
    {
        $this->dispatcher->expects($this->never())
            ->method('dispatch');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
                ->method('getIsHome')
                ->will($this->returnValue(1));
        
        $this->pageManager->set($page);      
        $this->pageManager->delete(); 
    }
    
    public function testDeleteFailsBecauseSaveFailsAtLast()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
                ->method('delete')
                ->will($this->returnValue(false));
        
        $this->pageModel->expects($this->once())
            ->method('rollBack');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
                ->method('getIsHome')
                ->will($this->returnValue(0));
        
        $this->pageManager->set($page);   
        $res = $this->pageManager->delete(); 
        $this->assertFalse($res);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testDeleteBlockThrownAnUnespectedException()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch');
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
            ->method('rollback');
        
        $this->pageModel->expects($this->once())
                ->method('delete')
                ->will($this->throwException(new \RuntimeException()));
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
                ->method('getIsHome')
                ->will($this->returnValue(0));
        
        $this->pageManager->set($page);    
        $this->pageManager->delete(); 
    }
    
    public function testDelete()
    {
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch');
        
        $this->pageModel->expects($this->once())
            ->method('startTransaction');
        
        $this->pageModel->expects($this->once())
                ->method('delete')
                ->will($this->returnValue(true));
        
        $this->pageModel->expects($this->once())
            ->method('commit');
        
        $this->pageModel->expects($this->never())
            ->method('rollback');
        
        $page = $this->getMock('AlphaLemon\AlphaLemonCmsBundle\Model\AlPage');
        $page->expects($this->once())
                ->method('getIsHome')
                ->will($this->returnValue(0));
        
        $this->pageManager->set($page);    
        $res = $this->pageManager->delete(); 
        $this->assertTrue($res);
    }
}