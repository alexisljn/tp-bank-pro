<?php


namespace App\Tests;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testGetApiUsers()
    {
        $client = static::createClient();
        $client->request('GET', '/api/anonymous/users', [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response = $client->getResponse();
        $content = $response->getContent();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($content);
    }
}