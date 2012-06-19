<?php
/*
 * This file is part of the AlphaLemon CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) AlphaLemon <webmaster@alphalemon.com>
 *
 * For the full copyright and license infpageModelation, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.alphalemon.com
 *
 * @license    GPL LICENSE Version 2.0
 *
 */

namespace AlphaLemon\AlphaLemonCmsBundle\Tests\Functional\Controller;

use AlphaLemon\AlphaLemonCmsBundle\Tests\WebTestCaseFunctional;
use AlphaLemon\AlphaLemonCmsBundle\Core\Model\Propel\AlPageModelPropel;
use AlphaLemon\AlphaLemonCmsBundle\Core\Model\Propel\AlSeoModelPropel;
use AlphaLemon\AlphaLemonCmsBundle\Core\Model\Propel\AlBlockModelPropel;

/**
 * PagesControllerTest
 *
 * @author alphalemon <webmaster@alphalemon.com>
 */
class PagesControllerTest extends WebTestCaseFunctional
{
    private $pageModel;
    private $seoModel;
    private $blockModel;

    protected function setUp()
    {
        parent::setUp();

        $this->pageModel = new AlPageModelPropel();
        $this->seoModel = new AlSeoModelPropel();
        $this->blockModel = new AlBlockModelPropel();
    }

    public function testFormElements()
    {
        $crawler = $this->client->request('GET', 'backend/en/al_showPages');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $crawler->filter('#pages_pageName')->count());
        $this->assertEquals(1, $crawler->filter('#pages_template')->count());
        $this->assertEquals(1, $crawler->filter('#pages_isHome')->count());
        $this->assertEquals(1, $crawler->filter('#pages_isPublished')->count());
        $this->assertEquals(1, $crawler->filter('#seo_permalink')->count());
        $this->assertEquals(1, $crawler->filter('#seo_title')->count());
        $this->assertEquals(1, $crawler->filter('#seo_description')->count());
        $this->assertEquals(1, $crawler->filter('#seo_keywords')->count());
        $this->assertEquals(1, $crawler->filter('#seo_idLanguage')->count());
        $this->assertEquals(1, $crawler->filter('#al_page_saver')->count());
        $this->assertEquals(1, $crawler->filter('#al_pages_list')->count());
        $this->assertEquals(1, $crawler->filter('#al_pages_list .al_element_selector')->count());
        $this->assertEquals(1, $crawler->filter('#al_pages_remover')->count());
        //$this->assertEquals('pages_template', $crawler->filter('select')->attr('id'));//echo $crawler->filter('#pages_template > *')->eq(1)->attr('value');
    }

    public function testAddPageFailsWhenPagenameContainsInvalidPrefix()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        "pageName" => "al_temp");

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($crawler->filter('html:contains("The prefix [ al_ ] is not permitted to avoid conflicts with the application internal routes")')->count() > 0);
    }

    public function testAddPageFailsWhenPageNameParamIsMissing()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'templateName' => "home",
                        'permalink' => "page 1",
                        'title' => 'A title',
                        'description' => 'A description',
                        'keywords' => 'Some keywords');

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('The name to assign to the page cannot be null. Please provide a valid page name to add your page', $crawler->text());
    }

    public function testAddPageFailsWhenTemplateNameParamIsMissing()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageName' => "page1",
                        'permalink' => "page 1",
                        'title' => 'A title',
                        'description' => 'A description',
                        'keywords' => 'Some keywords');

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('The page requires at least a template. Please provide the template name to add your page', $crawler->text());
    }

    public function testAddPage()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageName' => "page1",
                        'templateName' => "home",
                        'permalink' => "page 1",
                        'title' => 'A title',
                        'description' => 'A description',
                        'keywords' => 'Some keywords');

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertRegExp('/Content-Type:  application\/json/s', $response->__toString());

        $json = json_decode($response->getContent(), true);
        $this->assertEquals(3, count($json));
        $this->assertTrue(array_key_exists("key", $json[0]));
        $this->assertEquals("message", $json[0]["key"]);
        $this->assertTrue(array_key_exists("value", $json[0]));
        $this->assertEquals("The page has been successfully saved", $json[0]["value"]);
        $this->assertTrue(array_key_exists("key", $json[1]));
        $this->assertEquals("pages", $json[1]["key"]);
        $this->assertTrue(array_key_exists("value", $json[1]));
        $this->assertRegExp("/\<a[^\>]+ref=\"2\"\>index\<\/a\>/s", $json[1]["value"]);
        $this->assertRegExp("/\<a[^\>]+ref=\"3\"\>page1\<\/a\>/s", $json[1]["value"]);
        $this->assertTrue(array_key_exists("key", $json[2]));
        $this->assertEquals("pages_menu", $json[2]["key"]);
        $this->assertTrue(array_key_exists("value", $json[2]));
        $this->assertRegExp("/\<select[^\>]+id=\"al_pages_navigator\"[^\>]+\>/s", $json[2]["value"]);
        $this->assertRegExp("/\<option[^\>]+rel=\"index\"[^\>]+\>index\<\/option\>/s", $json[2]["value"]);
        $this->assertRegExp("/\<option[^\>]+rel=\"page1\"[^\>]+\>page1\<\/option\>/s", $json[2]["value"]);

        $page = $this->pageModel->fromPk(3);
        $this->assertNotNull($page);
        $this->assertEquals('page1', $page->getPageName());
        $this->assertEquals('home', $page->getTemplateName());
        $this->assertEquals(0, $page->getIsHome());

        $seo = $this->seoModel->fromPageAndLanguage(2, 3);
        $this->assertNotNull($seo);
        $this->assertEquals('page-1', $seo->getPermalink());
        $this->assertEquals('A title', $seo->getMetaTitle());
        $this->assertEquals('A description', $seo->getMetaDescription());
        $this->assertEquals('Some keywords', $seo->getMetaKeywords());

        // Repeated contents have not been added
        $pagesSlots = $this->retrievePageSlots();
        $this->assertEquals(count($pagesSlots), count($this->blockModel->retrieveContents(2, 3, $pagesSlots)));
    }

    public function testPageJustAdded()
    {
        $crawler = $this->client->request('GET', 'backend/en/page1');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $crawler->filter('#block_1')->count());
        $this->assertEquals(1, $crawler->filter('#block_32')->count());
        $this->assertEquals(22, $crawler->filter('.al_editable')->count());
    }

    public function testPageJustAddedSeoAttributes()
    {
        $params = array('pageId' => 3, 'languageId' => 2);
        $crawler = $this->client->request('POST', 'backend/en/al_loadPageAttributes', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals("#pages_pageName", $json[0]["name"]);
        $this->assertEquals("page1", $json[0]["value"]);
        $this->assertEquals("#pages_template", $json[1]["name"]);
        $this->assertEquals("home", $json[1]["value"]);
        $this->assertEquals("#pages_isHome", $json[2]["name"]);
        $this->assertEquals("0", $json[2]["value"]);
        $this->assertEquals("#page_attributes_permalink", $json[3]["name"]);
        $this->assertEquals("page-1", $json[3]["value"]);
        $this->assertEquals("#page_attributes_title", $json[4]["name"]);
        $this->assertEquals("A title", $json[4]["value"]);
        $this->assertEquals("#page_attributes_description", $json[5]["name"]);
        $this->assertEquals("A description", $json[5]["value"]);
        $this->assertEquals("#page_attributes_keywords", $json[6]["name"]);
        $this->assertEquals("Some keywords", $json[6]["value"]);
    }

    public function testAddPageFailsWhenThePageNameAlreadyExists()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageName' => "page1",
                        'templateName' => "home",
                        'permalink' => "page 1",
                        'title' => 'A title',
                        'description' => 'A description',
                        'keywords' => 'Some keywords');

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('The web site already contains the page you are trying to add. Please use another name for that page', $crawler->text());
    }

    public function testAddANewHomePage()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageName' => "page2",
                        'templateName' => "home",
                        'isHome' => '1',
                        'permalink' => "page 2",
                        'title' => 'A title',
                        'description' => 'A description',
                        'keywords' => 'Some keywords');

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(3, count($this->pageModel->activePages()));
        $this->assertEquals(4, $this->pageModel->homePage()->getId());

        // Previous home page has been degraded
        $page = $this->pageModel->fromPk(2);
        $this->assertEquals(0, $page->getIsHome());

        $seo = $this->seoModel->fromPageAndLanguage(2, 4);
        $this->assertNotNull($seo);

        // Repeated contents have not been added
        $pagesSlots = $this->retrievePageSlots();
        $this->assertEquals(count($pagesSlots), count($this->blockModel->retrieveContents(2, 4, $pagesSlots)));
    }

    public function testEditPage()
    {
        // Saves a link that contains the permalink we are going to change
        $block = $this->blockModel->fromPK(14);
        $block->setHtmlContent('<a href="page-2">Go to page 2</a>');
        $block->save();

        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => 4,
                        'pageName' => "page2 edited",
                        'permalink' => "page-2 edited",);

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $page = $this->pageModel->fromPk(4);
        $this->assertEquals('page2-edited', $page->getPageName());

        $page = $this->seoModel->fromPk(3);
        $this->assertEquals('page-2-edited', $page->getPermalink());
    }

    public function testPermalinksHaveBeenChanged()
    {
        $crawler = $this->client->request('GET', 'backend/en/index');
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $link = $crawler->selectLink('Go to page 2')->link();
        $this->assertEquals('http://localhost/backend/en/page-2-edited', $link->getUri());
    }

    public function testChangeThePageTemplate()
    {
        $blocks = $this->blockModel->retrieveContents(2, 4);
        $this->assertEquals(11, count($blocks));

        $blocks = $this->blockModel->retrieveContents(2, 4, 'page_content');
        $this->assertEquals(0, count($blocks));

        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => 4,
                        'templateName' => 'fullpage',);

        $crawler = $this->client->request('POST', 'backend/en/al_savePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $page = $this->pageModel->fromPK(4);
        $this->assertEquals('fullpage', $page->getTemplateName());

        $blocks = $this->blockModel->retrieveContents(2, 4);
        $this->assertEquals(1, count($blocks));

        $blocks = $this->blockModel->retrieveContents(2, 4, 'page_content');
        $this->assertEquals(1, count($blocks));
    }

    public function testDeletePageFailsBecauseAnyPageIdIsGiven()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => 'none',
                        'languageId' => 'none');

        $crawler = $this->client->request('POST', 'backend/en/al_deletePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Any page has been choosen for removing', $crawler->text());
    }

    public function testDeletePageFailsBecauseAnInvalidPageIdIsGiven()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => 999,
                        'languageId' => 'none');

        $crawler = $this->client->request('POST', 'backend/en/al_deletePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Any page has been choosen for removing', $crawler->text());
    }

    public function testDeleteTheHomePageIsForbidden()
    {
        $page = $this->pageModel->homePage();
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => $page->getId(),
                        'languageId' => 'none');

        $crawler = $this->client->request('POST', 'backend/en/al_deletePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('It is not allowed to remove the website\'s home page. Promote another page as the home of your website, then remove this one', $crawler->text());
    }

    public function testDeletePageSeoAttributes()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => 3,
                        'languageId' => 2);

        $crawler = $this->client->request('POST', 'backend/en/al_deletePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(3, count($this->pageModel->activePages()));
        $this->assertRegExp('/Content-Type:  application\/json/s', $response->__toString());

        $seo = $this->seoModel->fromPageAndLanguage(2, 3);
        $this->assertNull($seo);

        $pagesSlots = $this->retrievePageSlots();
        $this->assertEquals(0, count($this->blockModel->retrieveContents(3, 2, $pagesSlots)));
    }

    public function testPageJustDeletedSeoAttributes()
    {
        $params = array('pageId' => 3, 'languageId' => 2);
        $crawler = $this->client->request('POST', 'backend/en/al_loadPageAttributes', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertEquals("#pages_pageName", $json[0]["name"]);
        $this->assertEquals("page1", $json[0]["value"]);
        $this->assertEquals("#pages_template", $json[1]["name"]);
        $this->assertEquals("home", $json[1]["value"]);
        $this->assertEquals("#pages_isHome", $json[2]["name"]);
        $this->assertEquals("0", $json[2]["value"]);
        $this->assertEquals("#page_attributes_permalink", $json[3]["name"]);
        $this->assertEquals("", $json[3]["value"]);
        $this->assertEquals("#page_attributes_title", $json[4]["name"]);
        $this->assertEquals("", $json[4]["value"]);
        $this->assertEquals("#page_attributes_description", $json[5]["name"]);
        $this->assertEquals("", $json[5]["value"]);
        $this->assertEquals("#page_attributes_keywords", $json[6]["name"]);
        $this->assertEquals("", $json[6]["value"]);
    }

    public function testDeletePage()
    {
        $params = array('page' => 'index',
                        'language' => 'en',
                        'pageId' => 2,
                        'languageId' => 'none');

        $crawler = $this->client->request('POST', 'backend/en/al_deletePage', $params);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, count($this->pageModel->activePages()));

        $this->assertRegExp('/Content-Type:  application\/json/s', $response->__toString());

        $json = json_decode($response->getContent(), true);
        $this->assertEquals(3, count($json));
        $this->assertTrue(array_key_exists("key", $json[0]));
        $this->assertEquals("message", $json[0]["key"]);
        $this->assertTrue(array_key_exists("value", $json[0]));
        $this->assertEquals("The page has been successfully removed", $json[0]["value"]);
        $this->assertTrue(array_key_exists("key", $json[1]));
        $this->assertEquals("pages", $json[1]["key"]);
        $this->assertTrue(array_key_exists("value", $json[1]));
        $this->assertRegExp("/\<a[^\>]+ref=\"3\"\>page1\<\/a\>/s", $json[1]["value"]);
        $this->assertRegExp("/\<a[^\>]+ref=\"4\"\>page2-edited\<\/a\>/s", $json[1]["value"]);
        $this->assertTrue(array_key_exists("key", $json[2]));
        $this->assertEquals("pages_menu", $json[2]["key"]);
        $this->assertTrue(array_key_exists("value", $json[2]));
        $this->assertRegExp("/\<select[^\>]+id=\"al_pages_navigator\"[^\>]+\>/s", $json[2]["value"]);
        $this->assertRegExp("/\<option[^\>]+rel=\"page1\"[^\>]+\>page1\<\/option\>/s", $json[2]["value"]);
        $this->assertRegExp("/\<option[^\>]+rel=\"page2-edited\"[^\>]+\>page2-edited\<\/option\>/s", $json[2]["value"]);

        $page = $this->pageModel->fromPk(2);
        $this->assertEquals(1, $page->getToDelete());

        $seo = $this->seoModel->fromPageAndLanguage(2, 2);
        $this->assertNull($seo);

        // Repeated contents have not been added
        $pagesSlots = $this->retrievePageSlots();
        $this->assertEquals(0, count($this->blockModel->retrieveContents(2, 2, $pagesSlots)));
    }

    private function retrievePageSlots()
    {
        $pageTree = $this->client->getContainer()->get('al_page_tree');
        $templateSlots = $pageTree->getTemplateManager()->getTemplateSlots();
        $slots = $templateSlots->toArray();

        return $slots['page'];
    }
}
