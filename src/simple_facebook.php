<?php
/*
 * (The MIT License)
 * 
 * Copyright (c) 2012 Aykut Farsak (aykutfarsak@gmail.com)
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software 
 * and associated documentation files (the "Software"), to deal in the Software without restriction, 
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, 
 * subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or 
 * substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR 
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * SimpleFacebook
 * 
 * It is a wrapper class for Facebook PHP SDK
 * 
 * @author Aykut Farsak <aykutfarsak@gmail.com>
 * @link https://github.com/aykutfarsak
 */
class SimpleFacebook {

    /**
     * Facebook SDK object
     * @var Facebook 
     */
    protected $sdk;

    /**
     * Application config vars
     * @var array
     */
    protected $config;

    /**
     * Facebook user id
     * @var int
     */
    protected $id;

    /**
     * User profile data
     * @var array
     */
    protected $userProfile;

    /**
     * Redirect URI after login
     * @var string
     */
    protected $redirectUri;

    /**
     * Facebook app login url
     * @var string 
     */
    protected $loginUrl;

    /**
     * Signed request
     * @var array
     */
    protected $signedRequest;
    
    /**
     * Real-time updates subscription url
     * @var string 
     */
    protected $subscriptionUrl;

    /**
     * Constructor
     * 
     * @param BaseFacebook $facebook
     * @param array $config
     * @return void
     */
    function __construct(BaseFacebook $facebook, array $config) {
        $this->sdk = $facebook;
        $this->init($config);
    }
    
    /**
     * Initialize
     * 
     * @param array $config
     * @return void 
     */
    protected function init($config) {
        $this->setConfig($config);
        $this->setId(); // set user facebook id
        $this->setSignedRequest();
        $this->setLoginUrl();
    }

    /**
     * Set Facebook config vars 
     * 
     * These are necessary keys for init SDK:
     *  - app_id
     *  - app_secret
     *  - redirect_uri
     * 
     * Optional keys:
     *  - app_perms
     * 
     * @param array $config
     * @return void
     */
    protected function setConfig($config) {
        if ( ! $this->isConfigVarsValid($config) ) {
            throw new SimpleFacebookException('Missing config vars');
        }
        $this->config = $config;
    }
    
    /**
     * Check if all necessary key exist
     * 
     * @param array $config
     * @return boolean 
     */
    protected function isConfigVarsValid($config) {
        return isset($config['redirect_uri']);
    }

    /**
     * Set default redirect uri after login 
     * 
     * @return void
     */
    protected function setRedirectUri() {
        $this->redirectUri = $this->config['redirect_uri'];
    }

    /**
     * Set user login url
     * 
     * @return void
     */
    protected function setLoginUrl() {

        // set the default login url
        $this->setRedirectUri();

        // add request ids to end of login url
        if ( ! empty($_REQUEST['request_ids']) ) {
            $this->redirectUri .= strpos($this->redirectUri, '?') === false ? '?' : '&';
            $this->redirectUri .= 'request_ids=' . $_REQUEST['request_ids'];
        }

        $this->loginUrl = $this->sdk->getLoginUrl(array(
            'scope' => (isset($this->config['app_perms']) 
                ? $this->config['app_perms'] 
                : ''),
            'redirect_uri' => $this->redirectUri
        ));
    }

    /**
     * Set signed request
     * 
     * @return void
     */
    protected function setSignedRequest() {
        $this->signedRequest = $this->sdk->getSignedRequest();
    }

    /**
     * Set Facebook user id
     * 
     * @return void 
     */
    protected function setId() {
        // user id (0 if user not logged)
        $this->id = $this->sdk->getUser();
    }
    
    /**
     * Set user profile data (array)
     * 
     * @return void
     */
    protected function setUserProfileData() {
        try {
            // call api if profile data is empty
            if ( null === $this->userProfile ) {
                $this->userProfile = $this->sdk->api('/me');
            }
        } catch ( Exception $e ) {
            $this->userProfile = null;
            throw $e;
        }
    }

    /**
     * Check user Facebook online status
     * 
     * @return boolean 
     */
    public function isLogged() {
        return (boolean) $this->id;
    }

    /**
     * Get user Facebook id
     * 
     * @return int 
     */
    public function getId() {
        return $this->isLogged() ? $this->id : 0;
    }

    /**
     * Get login url
     * 
     * @return string 
     */
    public function getLoginUrl() {
        return $this->loginUrl;
    }

    /**
     * Get user all profile data
     * 
     * @return array 
     */
    public function getUserProfileData() {
        $this->setUserProfileData();
        return $this->userProfile;
    }
    
    /**
     * Get Facebook page ID of tab application
     * 
     * @return int 
     */
    public function getTabPageId() {
        return isset($this->signedRequest['page']['id']) 
            ? $this->signedRequest['page']['id'] 
            : 0;
    }
    
    /**
     * Get tab page app_data if set
     * 
     * @return string 
     */
    public function getTabAppData() {
        return isset($this->signedRequest['app_data']) 
                && !empty($this->signedRequest['app_data']) 
            ? $this->signedRequest['app_data'] 
            : false;
    }
    
    /**
     * Get user all permissions given to the application
     * 
     * @return array
     */
    public function getGivenPermissions() {
        
        $data = $this->sdk->api('/me/permissions');

        if ( empty($data) ) {
            return array();
        }

        return array_keys($data['data'][0]);
    }

    /**
     * Get user friends (id-name array or just ids)
     * 
     * @param boolean $justIds
     * @return array 
     */
    public function getFriends($justIds = false) {
        
        $friendList = $this->sdk->api('/me/friends');

        if ( !isset($friendList['data'][0]) ) {
            return array();
        }

        if ( !$justIds ) {
            return $friendList['data'];
        }
        
        return array_map(function($friend) {
            
            return $friend['id'];
            
        }, $friendList['data']);
    }

    /**
     * Get user friend ids
     * 
     * @return array 
     */
    public function getFriendIds() {
        return $this->getFriends(true);
    }

    /**
     * Get user friends (uid-name array) who use this application
     * 
     * @param boolean $justIds
     * @return array
     */
    public function getAppUserFriends($justIds = false) {

        $values = $justIds ? 'uid' : 'uid,name';
        
        $query  = 'SELECT ' . $values . ' FROM user 
                   WHERE uid IN(
                        SELECT uid2 FROM friend 
                        WHERE uid1 = me()) 
                   AND is_app_user = "true"';
        
        $data   = $this->runFQL($query);

        if ( !$justIds || empty($data) ) {
            return $data;
        }
        
        return array_map(function($d) {
            
            return $d['uid'];
            
        }, $data);
    }

    /**
     * Get user friend ids who use this application
     * 
     * @return array 
     */
    public function getAppUserFriendIds() {
        return $this->getAppUserFriends(true);
    }

    /**
     * Delete request and return deleted request ids
     * 
     * @return array
     */
    public function getRequestIdsAfterDelete() {
        
        if ( ! $this->isLogged() ) {
            throw new SimpleFacebookException('This action is only for logged users');
        }

        if ( empty($_REQUEST['request_ids']) ) {
            return array();
        }

        $requestIds = explode(',', $_REQUEST['request_ids']);
        $deletedIds = array();

        foreach ( $requestIds as $requestId ) {

            $fullRequestId = $requestId . '_' . $this->getId();
            $deleteSuccess = $this->sdk->api("/$fullRequestId", 'DELETE');

            if ( $deleteSuccess ) {
                $deletedIds[] = $fullRequestId;
            }
        }

        return $deletedIds;
    }
    
    /**
     * Get application access_token
     * 
     * @return string|boolean
     */
    public function getApplicationAccessToken() {
        
        $params = array(
            'client_id'     => $this->sdk->getAppId(),
            'client_secret' => $this->sdk->getAppSecret(),
            'grant_type'    => 'client_credentials'
        );

        $resp = self::getFromUrl('https://graph.facebook.com/oauth/access_token', $params);

        if ( empty($resp) ) {
            return false;
        }

        parse_str($resp, $data);
        return isset($data['access_token']) ? $data['access_token'] : false;
    }
    
    /**
     * Get extended (2 month) access token using exist token
     * 
     * @param boolean $withExpireTime
     * @return mixed
     */
    public function getExtendedAccessToken($withExpireTime = false) {
        
        $params = array(
            'client_id'         => $this->sdk->getAppId(),
            'client_secret'     => $this->sdk->getAppSecret(),
            'grant_type'        => 'fb_exchange_token',
            'fb_exchange_token' => $this->getAccessToken()
        );

        $resp = self::getFromUrl('https://graph.facebook.com/oauth/access_token', $params);

        if ( empty($resp) ) {
            return false;
        }

        parse_str($resp, $data);
        
        return isset($data['access_token']) 
                ? 
                    $withExpireTime 
                    ? $data 
                    : $data['access_token'] 
                : false;
    }
    
    /**
     * Get data from url with cURL
     * 
     * @param string $url
     * @param array  $params
     * @param string $customMethod
     * @return string 
     */
    public static function getFromUrl($url, $params = null, $customMethod = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'facebook-php-3.1');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ( is_array($params) ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        if ( null !== $customMethod ) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customMethod);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * Check user if tab page liked or not
     * 
     * @return boolean 
     */
    public function isTabPageLiked() {
        return isset($this->signedRequest['page']['liked']) 
                ? $this->signedRequest['page']['liked'] 
                : false;
    }

    /**
     * Check user if Facebook page liked before
     * 
     * @param int $pageId
     * @return boolean
     */
    public function isPageLiked($pageId) {
        
        $like = $this->runFQL(
            sprintf("SELECT uid FROM page_fan WHERE page_id=%s AND uid=%s", $pageId, $this->getId())
        );
        
        return $like != false && isset($like[0]);
    }
    
    /**
     * Check user if admin of the page
     * 
     * @return boolean 
     */
    public function isTabPageAdmin() {
        return isset($this->signedRequest['page']['admin']) 
                ? $this->signedRequest['page']['admin'] 
                : false;
    }

    /**
     * Check if user give perm(s) to the application
     * 
     * @param string|array $perms
     * @return boolean 
     */
    public function isPermGiven($perms) {
        
        if ( ! is_array($perms) ) {
            if ( strpos($perms, ',') !== false ) {
                $perms = explode(',', $perms);
            } else {
                $perms = array($perms);
            }
        }

        $info = $this->runFQL('SELECT ' . implode(',', $perms) . ' FROM permissions WHERE uid = me()');

        if ( empty($info) ) {
            return false;
        }

        foreach ( $info[0] as $v ) {
            if ( $v == 0 ) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Check if application is bookmarked
     * 
     * @return boolean
     */
    public function isBookmarked() {
        return $this->isPermGiven('bookmarked');
    }

    /**
     * Post a message to user wall
     * 
     * Params: 
     *      message     : Feed message
     *      picture     : Address of image
     *      link        : URL
     *      name        : URL title
     *      caption     : Description under the URL title
     *      description : Description
     *      actions     : array('name' => '', 'link' => '')
     * 
     * @param array $params
     * @return int|boolean
     */
    public function postToWall(array $params) {
        $resp = $this->sdk->api('/me/feed', 'POST', $params);
        return isset($resp['id']) ? $resp['id'] : false;
    }

    /**
     * Running FQL
     * 
     * @param string $query
     * @return array 
     */
    public function runFQL($query) {
        
        $param = array(
            'method' => 'fql.query',
            'query'  => $query
        );

        return $this->sdk->api($param);
    }

    /**
     * Redirect user with javascript
     * 
     * @param string $url 
     */
    public static function redirectWithJavascript($url) {
        echo "<script type='text/javascript'>top.location.href = '" . $url . "';</script>";
        exit;
    }
    
    /**
     * Force not logged user to login url 
     * 
     * @return mixed
     */
    public function forceToLogin() {
        if ( !$this->isLogged() ) {
            self::redirectWithJavascript($this->getLoginUrl());
        }
    }
    
    /**
     * Create a event
     * 
     * Params: 
     *      name        : Event title
     *      description : Description
     *      start_time  : Start date (unixtimestamp)
     *      end_time    : End dateURL (unixtimestamp)
     *      location    : Location
     *      privacy     : Privacy info ('OPEN', 'CLOSED', 'SECRET')
     * 
     * @param array $params
     * @return int|boolean
     */
    public function createEvent(array $eventData) {
        $resp = $this->sdk->api("/me/events", "POST", $eventData);
        return ( $resp && !empty($resp) && isset($resp['id']) ) ? $resp['id'] : false;
    }

    /**
     * Publish a open graph action
     * 
     * @param string $appNamespace  Your application namespace
     * @param string $action        Action name
     * @param array $objectData     An object data for your action ( array('object' => 'objectUrl') )
     * @return int|boolean
     */
    public function publishOpenGraphAction($appNamespace, $action, $objectData) {
        $resp = $this->sdk->api("/me/{$appNamespace}:{$action}", 'POST', $objectData);
        return isset($resp['id']) ? $resp['id'] : false;
    }
    
    /**
     * Subscribe to real-time updates
     * 
     * @param string $object
     * @param string $fields
     * @param string $callbackUrl
     * @param string $verifyToken
     * @return mixed 
     */
    public function subscribe($object, $fields, $callbackUrl, $verifyToken) {

        $params = array(
            'object'       => $object,
            'fields'       => $fields,
            'callback_url' => $callbackUrl,
            'verify_token' => $verifyToken,
        );

        $response = self::getFromUrl($this->getSubscriptionUrl(), $params);
        
        return ($response && $response != 'null') ? $response : true;
    }
    
    /**
     * Get list of real-time updates
     * 
     * @return array 
     */
    public function getSubscriptions() {

        $response = self::getFromUrl($this->getSubscriptionUrl());
        
        if ( empty($response) ) {
            return false;
        }
        
        $data = json_decode($response, true);
        
        return isset($data['data']) ? $data['data'] : $data;
    }
    
    /**
     * Unsubscribe to real-time updates
     * 
     * @param type $object
     * @return mixed 
     */
    public function unsubscribe($object = null) {
        
        $params = array();
        if ( null !== $object ) {
            $params = array('object' => $object);
        }
        
        $response = self::getFromUrl($this->getSubscriptionUrl(), $params, 'DELETE');
        
        return ($response && $response != 'null') ? $response : true;
    }
    
    /**
     * Get subscription verification string
     *  
     * @param string $verifyToken
     * @return mixed 
     */
    public static function getSubscriptionChallenge($verifyToken) {
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['hub_mode']) && 
            $_GET['hub_mode'] == 'subscribe' && isset($_GET['hub_verify_token']) && 
            $_GET['hub_verify_token'] == $verifyToken) {
            return $_GET['hub_challenge'];
        } else {
            return false;
        }
    }
    
    /**
     * Get real-time updates posted by Facebook
     * 
     * @return array 
     */
    public static function getSubscriptedUpdates() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return json_decode(file_get_contents("php://input"), true);
        }
    }
    
    /**
     * Get real-time updates subscription url
     * 
     * @return string 
     */
    protected function getSubscriptionUrl() {
        
        if ( null !== $this->subscriptionUrl ) {
            return $this->subscriptionUrl;
        }
        
        $url = sprintf("https://graph.facebook.com/%s/subscriptions?access_token=%s", 
               $this->sdk->getAppId(), $this->getApplicationAccessToken());
        
        $this->subscriptionUrl = $url;
        return $url;
    }
    
    /**
     * Send notification to an application user
     * 
     * @param int $userId
     * @param string $template
     * @param string $href
     * @return mixed
     */
    public function sendNotification($userId, $template, $href) {
        $params = array(
            'access_token' => $this->getApplicationAccessToken(),
            'template'     => $template,
            'href'         => $href
        );

        $response = self::getFromUrl('https://graph.facebook.com/'.$userId.'/notifications', $params);
        return strpos($response, 'error') !== false ? $response : true;
    }
    
    /**
     * Magix call method
     */
    public function __call($name, $arguments) {

        // you can continue to use SDK callable methods
        if ( method_exists($this->sdk, $name) && is_callable(array($this->sdk, $name)) ) {
            return call_user_func_array(array($this->sdk, $name), $arguments);
        }

        if ( strpos($name, 'get') === 0 ) {

            $property = strtolower(substr($name, 3));

            if ( method_exists($this, $property) ) {
                return $this->$property($arguments);
            }
            
            $this->setUserProfileData();

            if ( isset($this->userProfile[$property]) ) {
                return $this->userProfile[$property];
            }

            if ( isset($this->sdk->$property) ) {
                return $this->sdk->$property;
            }
        }

        throw new SimpleFacebookException("There is no method named '" . $name . "'");
    }

}

class SimpleFacebookException extends Exception {}