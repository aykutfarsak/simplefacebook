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

    /**
     * @expectedException SimpleFacebookException
     */
    public function testConstructorWithoutRedirect_uriConfigVar() {
        $config = array();
        new SimpleFacebook($this->sdkMock, $config);
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

}