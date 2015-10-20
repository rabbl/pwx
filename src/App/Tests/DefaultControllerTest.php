<?php

namespace AppBundle\Tests\Controller;

use Silex\WebTestCase;
use Symfony\Component\DomCrawler\Link;

class DefaultControllerTest extends WebTestCase
{

    protected $credentials = array(
            'userName' => 'nameOfUser',
            'password' => 'passwordOfUser',
            'comment' => 'commentOfUser',
            'period' => 3600
    );

    public function createApplication()
    {
        $app = require __DIR__.'/../../../app/app.php';

        $app['debug'] = true;

        unset($app['exception_handler']);

        return $app;
    }

    /**
     * Test credential creation
     *
     * @return void
     */
    public function testIndex()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());

        // Check if all form elements are available
        $this->assertEquals(1, $crawler->filter('input[name="userName"]')->count());
        $this->assertEquals(1, $crawler->filter('input[name="password"]')->count());
        $this->assertEquals(1, $crawler->filter('textarea[name="comment"]')->count());
        $this->assertEquals(1, $crawler->filter('select[name="period"]')->count());
        $this->assertEquals(1, $crawler->filter('button[type="submit"]')->count());
    }

    /**
     * Test redirect after credential creation
     *
     * @return void
     */
    public function testSaveCredentialsAndRedirectToSharePage()
    {
        // Load index page
        $client = $this->createClient();
        $crawler = $client->request('GET', '/');

        // Save credentials
        $form = $crawler->filter('#submitCredentialsForm')->form();
        $form->setValues($this->credentials);
        $client->submit($form);

        // Follow redirect to share link page
        $crawler = $client->followRedirect();
        $this->assertTrue($client->getResponse()->isOk());

        return $crawler->filter('#passwordlink')->link();
    }

    /**
     * Test credential viewing
     *
     * @param Symfony\Component\DomCrawler\Link $link Link to test
     *
     * @depends testSaveCredentialsAndRedirectToSharePage
     */
    public function testViewCredentials($link)
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', $link->getUri());

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals($this->credentials['userName'], $crawler->filter('#userName > span')->text());
        $this->assertEquals($this->credentials['password'], $crawler->filter('#password > span')->text());
        $this->assertEquals($this->credentials['comment'], trim($crawler->filter('#comment')->text()));
    }

    /**
     * Test credential deletion
     *
     * @param Symfony\Component\DomCrawler\Link $link Link to test
     *
     * @depends testSaveCredentialsAndRedirectToSharePage
     */
    public function testDeleteCredentials($link)
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', $link->getUri());

        // Delete credential
        $form = $crawler->filter('#deleteCredentialsForm')->form();
        $client->submit($form);
        $client->followRedirect();
        $this->assertTrue($client->getResponse()->isOk());

        // Test if redirects to '/'
        $this->assertEquals('/', $client->getRequest()->getRequestUri());

        // Visit the link page again and see of the entry is deleted
        $client = $this->createClient();
        $crawler = $client->request('GET', $link->getUri());
        $this->assertEquals(1, $crawler->filter('#credentialsExpired')->count());
    }
}
