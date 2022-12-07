<?php

namespace Tests\Controller\Rest;

use App\Controller\Rest\UserGroupsAPIController;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class UserGroupsAPIControllerTest extends TestCase
{

  const API_BASE_URL = 'http://stanzadelcittadino.localtest.me/comune-di-bugliano';

  protected $token = null;

  protected $itemId = null;

  // Todo: creare una classe di utility per metodi generali
  private function getJwtToken()
  {

    if ($this->token) {
      return $this->token;
    }

    $client = new Client();
    $headers = ['Content-Type' => 'application/json'];

    $data = array(
      'username' => 'admin',
      'password' => 'admin'
    );

    $request = new Request(
      'POST',
      self::API_BASE_URL . '/api/auth',
      $headers,
      json_encode($data)
    );

    $response = $client->send($request);
    $responseData = json_decode($response->getBody()->getContents(), true);
    $this->token = $responseData['token'];
    return $responseData['token'];
  }

  public function testPostUserGroupAction()
  {

    $token = $this->getJwtToken();
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $token,
      'Content-Type' => 'application/json'
    ];

    $data = array(
      'name' => 'test' . date('Y-m-d H:i:s')
    );

    $request = new Request(
      'POST',
      self::API_BASE_URL . '/api/user-groups',
      $headers,
      json_encode($data)
    );

    $response = $client->send($request);
    $this->assertEquals(201, $response->getStatusCode());
    $responseData = json_decode($response->getBody()->getContents(), true);
    $this->assertArrayHasKey('id', $responseData);

    return $responseData['id'];
  }

  /**
   * @return void
   * @depends testPostUserGroupAction
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testPutUserGroupAction($id)
  {

    $token = $this->getJwtToken();
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $token,
      'Content-Type' => 'application/json'
    ];

    $name = 'test' . date('Y-m-d H:i:s');

    $data = array(
      'name' => $name
    );

    $request = new Request(
      'PUT',
      self::API_BASE_URL . '/api/user-groups/' . $id,
      $headers,
      json_encode($data)
    );

    $response = $client->send($request);
    $this->assertEquals(200, $response->getStatusCode());
    $responseData = json_decode($response->getBody()->getContents(), true);
    $this->assertTrue($responseData['name'] === $name);
  }

}
