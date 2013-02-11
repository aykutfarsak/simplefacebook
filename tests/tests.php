<?php

use Mockery as m;

class SimpleFacebookTestCase extends PHPUnit_Framework_TestCase {

    protected $sdkMock;

    /**
     * @var SimpleFacebook 
     */
    protected $fb;

    /**
     * @var array 
     */
    protected $config;
    
    /**
     * @var array 
     */
    public static $data;

    public function tearDown() {
        m::close();
    }

    public function setUp() {
        $this->sdkMock = m::mock('BaseFacebook');
        $this->sdkMock->shouldIgnoreMissing();

        $this->config = array(
            'redirect_uri' => 'http://localhost',
            'app_perms' => 'email'
        );
        
        // http://graph.facebook.com/aykutfarsak
        $json = '
            {
               "id": "763787986",
               "name": "Aykut Farsak",
               "first_name": "Aykut",
               "last_name": "Farsak",
               "link": "http://www.facebook.com/aykutfarsak",
               "username": "aykutfarsak",
               "gender": "male",
               "locale": "en_US"
            }';
        
        self:$data = json_encode($json, true);
    }

    public function testConstructorWithValidConfigVars() {
        new SimpleFacebook($this->sdkMock, $this->config);
    }

    public function testIsLoggedWithNotLoggedUser() {

        $this->sdkMock->shouldReceive(array(
            'getUser' => 0
        ));

        $fb = new SimpleFacebook($this->sdkMock, $this->config);

        $this->assertFalse($fb->isLogged());
    }

    public function testIsLoggedWithLoggedUser() {

        $this->sdkMock->shouldReceive(array(
            'getUser' => 1
        ));

        $fb = new SimpleFacebook($this->sdkMock, $this->config);

        $this->assertTrue($fb->isLogged());
        $this->assertEquals(1, $fb->getId());
    }
    
    public function testGetLoginUrl() {
        
        $this->sdkMock->shouldReceive(array(
            'getLoginUrl' => 'localhost'
        ));

        $fb = new SimpleFacebook($this->sdkMock, $this->config);

        $this->assertEquals('localhost', $fb->getLoginUrl());
    }
    
    public function testGetUserProfileData() {
        
        $this->sdkMock->shouldReceive('api')->with('/me')->andReturn(self::$data);
        $fb = new SimpleFacebook($this->sdkMock, $this->config);
        
        $this->assertEquals(self::$data, $fb->getUserProfileData());
    }
    
    public function testTabAppMethods() {
        
        // http://developers.facebook.com/blog/post/462/
        $signedRequestJson = '{
            "algorithm":"HMAC-SHA256",
            "expires":1297328400,
            "issued_at":1297322606,
            "oauth_token":"OAUTH_TOKEN",
            "app_data":"any_string_here",
            "page":{
               "id":119132324783475,
               "liked":true,
               "admin":false
            },
            "user":{
               "country":"us",
               "locale":"en_US"
            },
            "user_id":"USER_ID"
        }'; 
        
        $this->sdkMock->shouldReceive('getSignedRequest')->andReturn(json_decode($signedRequestJson, true));
        $fb = new SimpleFacebook($this->sdkMock, $this->config);
        
        $this->assertEquals('119132324783475', $fb->getTabPageId());
        $this->assertEquals('any_string_here', $fb->getTabAppData());
        $this->assertTrue( $fb->isTabPageLiked() );
        $this->assertFalse( $fb->isTabPageAdmin() );
    }
    
    public function testGetGivenPermissions() {
        
        $json = '{
            "data": [
              {
                "installed": 1, 
                "email": 1, 
                "bookmarked": 1
              }
            ]
        }';
        
        $permissions = json_decode($json, true);
        
        $this->sdkMock->shouldReceive('api')->with('/me/permissions')->andReturn($permissions);
        $fb = new SimpleFacebook($this->sdkMock, $this->config);
        
        $expectedPermissions = array("installed","email","bookmarked");
        
        $this->assertEquals($expectedPermissions, $fb->getGivenPermissions());
    }
    
    public function testGetFriends() {
        
        $json = '{
            "data": [
              {
                "name": "Friend A", 
                "id": "1"
              }, 
              {
                "name": "Friend B", 
                "id": "2"
              }
            ]
        }';
        
        $friends = json_decode($json, true);
        
        $this->sdkMock->shouldReceive('api')->with('/me/friends')->andReturn($friends);
        $fb = new SimpleFacebook($this->sdkMock, $this->config);
        
        $expectedFriends = array(
            array('name' => 'Friend A', 'id' => "1"),
            array('name' => 'Friend B', 'id' => "2")
        );
        
        $this->assertEquals($expectedFriends, $fb->getFriends());
        $this->assertEquals(2, count($fb->getFriendIds()));
    }

}