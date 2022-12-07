<?php

namespace Tests\Controller\Rest;

use App\Controller\Rest\UserGroupsAPIController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class UserGroupsAPIControllerTest extends TestCase
{

  const API_BASE_URL = 'http://stanzadelcittadino.localtest.me/comune-di-bugliano';

  protected $token = null;

  protected function setUp()
  {
    $this->getJwtToken();
    parent::setUp();
  }


  // Todo: creare una classe di utility per metodi generali
  private function getJwtToken()
  {

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
  }

  /**
   * @return mixed
   * @throws GuzzleException
   */
  public function testPostUserGroupAction()
  {
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Content-Type' => 'application/json'
    ];

    $data = [
      'name' => 'test' . date('Y-m-d H:i:s'),
      'short_description' => 'short description',
      'main_function' => 'main function',
      'core_contact_point' => [
        'name' => 'test',
        'pec' => 'pec@sdc.it',
        'email' => 'email@sdc.it',
        'phone_number' => '33312312323',
      ]
    ];

    $request = new Request(
      'POST',
      self::API_BASE_URL . '/api/user-groups',
      $headers,
      json_encode($data)
    );

    $response = $client->send($request);
    $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    $responseData = json_decode($response->getBody()->getContents(), true);
    $this->assertArrayHasKey('id', $responseData);

    return $responseData['id'];
  }

  /**
   * @return void
   * @depends testPostUserGroupAction
   * @throws GuzzleException
   */
  public function testPutUserGroupAction($id)
  {

    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Content-Type' => 'application/json'
    ];

    $data = [
      'name' => 'test' . date('Y-m-d H:i:s')
    ];

    $request = new Request(
      'PUT',
      self::API_BASE_URL . '/api/user-groups/' . $id,
      $headers,
      json_encode($data)
    );

    $response = $client->send($request);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

  /**
   * @return void
   * @depends testPostUserGroupAction
   * @throws GuzzleException
   */
  public function testPatchUserGroupAction($id)
  {
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Content-Type' => 'application/json'
    ];

    $data = [
      'name' => 'test' . date('Y-m-d H:i:s')
    ];

    $request = new Request(
      'PATCH',
      self::API_BASE_URL . '/api/user-groups/' . $id,
      $headers,
      json_encode($data)
    );

    $response = $client->send($request);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

  /**
   * @return void
   * @throws GuzzleException
   */
  public function testGetUserGroupsAction()
  {
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Content-Type' => 'application/json'
    ];

    $request = new Request(
      'GET',
      self::API_BASE_URL . '/api/user-groups',
      $headers
    );

    $response = $client->send($request);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
  }

  /**
   * @param $id
   * @depends testPostUserGroupAction
   * @return void
   * @throws GuzzleException
   */
  public function testGetUserGroupAction($id)
  {
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Content-Type' => 'application/json'
    ];

    $request = new Request(
      'GET',
      self::API_BASE_URL . '/api/user-groups/' . $id,
      $headers
    );

    $response = $client->send($request);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    $responseData = json_decode($response->getBody()->getContents(), true);
    $this->assertEquals($id, $responseData['id']);
  }

  /**
   * @param $id
   * @depends testPostUserGroupAction
   * @return void
   * @throws GuzzleException
   */
  public function testDeleteUserGroupAction($id)
  {
    $client = new Client();
    $headers = [
      'Authorization' => 'Bearer ' . $this->token,
      'Content-Type' => 'application/json'
    ];

    $request = new Request(
      'DELETE',
      self::API_BASE_URL . '/api/user-groups/' . $id,
      $headers
    );

    $response = $client->send($request);
    $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
  }

}
