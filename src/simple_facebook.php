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
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR 
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

class SimpleFacebookException extends Exception {}

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
    protected $_sdk;

    /**
     * Application config vars
     * @var array
     */
    protected $_config;

    /**
     * Facebook user id
     * @var int
     */
    protected $_id;

    /**
     * User profile data
     * @var array
     */
    protected $_userProfile;

    /**
     * Redirect URI after login
     * @var string
     */
    protected $_redirectUri;

    /**
     * Facebook app login url
     * @var string 
     */
    protected $_loginUrl;

    /**
     * Signed request
     * @var array
     */
    protected $_signedRequest;

    /**
     * Constructor
     * 
     * @param array $config
     * @return void
     */
    function __construct(array $config) {

        // Facebook PHP SDK dependency
        if ( ! class_exists('Facebook') ) {
            throw new SimpleFacebookException(__CLASS__ . ' dependency to Facebook PHP SDK');
        }

        $this->init($config);
    }
    
    /**
     * Initialize
     * 
     * @return void 
     */
    protected function init($config) {
        
        // config vars
        $this->setConfig($config);
        
        $this->initFacebookSdk();
        
        // set user facebook id (0 if user not logged)
        $this->setId();
        
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
     * @return void
     */
    protected function setConfig($config) {
        if ( ! $this->isConfigVarsValid($config) ) {
            throw new SimpleFacebookException('Missing config vars');
        }
        $this->_config = $config;
    }
    
    /**
     * Check if all necessary key exist
     * 
     * @param array $config
     * @return boolean 
     */
    protected function isConfigVarsValid($config) {
        return isset($config['app_id']) && isset($config['app_secret']) && isset($config['redirect_uri']);
    }

    /**
     * Init Facebook PHP SDK
     * You can continue to use SDK callable methods
     * 
     * @return void 
     */
    protected function initFacebookSdk() {
        $this->_sdk = new Facebook(array(
            'appId'  => $this->_config['app_id'],
            'secret' => $this->_config['app_secret']
        ));
    }

    /**
     * Set default redirect uri ($this->_redirectUri) after login 
     * 
     * @return void
     */
    protected function setRedirectUri() {
        $this->_redirectUri = $this->_config['redirect_uri'];
    }

    /**
     * Set user login url
     * 
     * @return void
     */
    protected function setLoginUrl() {

        // set the default login url
        $this->setRedirectUri();

        if ( empty($this->_redirectUri) ) {
            throw new SimpleFacebookException('Missing login url');
        }

        // add request ids to end of login url
        if ( ! empty($_REQUEST['request_ids']) ) {
            $this->_redirectUri .= strpos($this->_redirectUri, '?') === false ? '?' : '&';
            $this->_redirectUri .= 'request_ids=' . $_REQUEST['request_ids'];
        }

        $this->_loginUrl = $this->_sdk->getLoginUrl(array(
            'scope'         => (isset($this->_config['app_perms']) ? $this->_config['app_perms'] : ''),
            'redirect_uri'  => $this->_redirectUri
        ));
    }

    /**
     * Set signed request
     * 
     * @return void
     */
    protected function setSignedRequest() {
        $this->_signedRequest = $this->_sdk->getSignedRequest();
    }

    /**
     * Set Facebook user id
     * 
     * @return void 
     */
    protected function setId() {
        // user id (0 if user not logged)
        $this->_id = $this->_sdk->getUser();
    }
    
    /**
     * Set user profile data (array)
     * 
     * @return void
     */
    protected function setUserProfileData() {
        try {
            // call api if profile data is empty
            if ( null === $this->_userProfile ) {
                $this->_userProfile = $this->_sdk->api('/me');
            }
        } catch ( Exception $e ) {
            $this->_userProfile = null;
            throw $e;
        }
    }

    /**
     * Check user Facebook online status
     * 
     * @return boolean 
     */
    public function isLogged() {
        return (boolean) $this->_id;
    }

    /**
     * Get user Facebook id
     * 
     * @return int 
     */
    public function getId() {
        return $this->isLogged() ? $this->_id : 0;
    }

    /**
     * Get login url
     * 
     * @return string 
     */
    public function getLoginUrl() {
        return $this->_loginUrl;
    }

    /**
     * Get user all profile data
     * 
     * @return array 
     */
    public function getUserProfileData() {
        $this->setUserProfileData();
        return $this->_userProfile;
    }
    
    /**
     * Get Facebook page ID of tab application
     * 
     * @return int 
     */
    public function getTabPageId() {
        return isset($this->_signedRequest['page']['id']) ? $this->_signedRequest['page']['id'] : 0;
    }
    
    /**
     * Get tab page app_data if set
     * 
     * @return string 
     */
    public function getTabAppData() {
        return isset($this->_signedRequest['app_data']) && ! empty($this->_signedRequest['app_data']) ? $this->_signedRequest['app_data'] : false;
    }
    
    /**
     * Get user all permissions given to the application
     * 
     * @return array
     */
    public function getGivenPermissions() {
        
        $data = $this->_sdk->api('/me/permissions');

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
        
        $friendList = $this->_sdk->api('/me/friends');

        if ( !isset($friendList['data'][0]) ) {
            return array();
        }

        if ( $justIds ) {

            $friends = array();

            foreach ( $friendList['data'] as $friend ) {
                $friends[] = $friend['id'];
            }

            return $friends;
        } else {
            return $friendList['data'];
        }
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
        $query = 'SELECT ' . $values . ' FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = "true"';
        $data = $this->runFQL($query);

        if ( !$justIds || empty($data) ) {
            return $data;
        }

        $ids = array();
        foreach ( $data as $d ) {
            $ids[] = $d['uid'];
        }

        return $ids;
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

            $fullRequestId = $requestId . '_' . $this->_id;

            $deleteSuccess = $this->_sdk->api("/$fullRequestId", 'DELETE');

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
            'client_id'     => $this->_config['app_id'],
            'client_secret' => $this->_config['app_secret'],
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
            'client_id'         => $this->_config['app_id'],
            'client_secret'     => $this->_config['app_secret'],
            'grant_type'        => 'fb_exchange_token',
            'fb_exchange_token' => $this->getAccessToken()
        );

        $resp = self::getFromUrl('https://graph.facebook.com/oauth/access_token', $params);

        if ( empty($resp) ) {
            return false;
        }

        parse_str($resp, $data);

        return isset($data['access_token']) ? $withExpireTime ? $data : $data['access_token'] : false;
    }
    
    /**
     * Get data from url with cURL
     * 
     * @param type $url
     * @param type $params
     * @return string 
     */
    public static function getFromUrl($url, $params = null) {
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
        return isset($this->_signedRequest['page']['liked']) ? $this->_signedRequest['page']['liked'] : false;
    }

    /**
     * Check user if Facebook page liked before
     * 
     * @param int $pageId
     * @return boolean
     */
    public function isPageLiked($pageId) {
        $like = $this->runFQL('SELECT uid FROM page_fan WHERE page_id="' . $pageId . '" and uid="' . $this->_id . '"');
        return $like != false && isset($like[0]);
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
        $resp = $this->_sdk->api('/me/feed', 'POST', $params);
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

        return $this->_sdk->api($param);
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
        $resp = $this->_sdk->api("/me/events", "POST", $eventData);
        return ( $resp && !empty($resp) && isset($resp['id']) ) ? $resp['id'] : false;
    }

    /**
     * Publish a open graph action
     * 
     * @param string $appNamespace  Your application namespace
     * @param string $action        Action name
     * @param array $objectData     An object data for your action ( array('object' => 'objectUrl') ). Object page source must contain open graph tags-
     * @return int|boolean
     */
    public function publishOpenGraphAction($appNamespace, $action, $objectData) {
        $resp = $this->_sdk->api("/me/{$appNamespace}:{$action}", 'POST', $objectData);
        return isset($resp['id']) ? $resp['id'] : false;
    }
    
    /**
     * Magix call method
     */
    public function __call($name, $arguments) {

        // you can continue to use SDK callable methods
        if ( method_exists($this->_sdk, $name) && is_callable(array($this->_sdk, $name)) ) {
            return call_user_func_array(array($this->_sdk, $name), $arguments);
        }

        if ( strpos($name, 'get') === 0 ) {

            $property = strtolower(substr($name, 3));

            if ( method_exists($this, $property) ) {
                return $this->$property($arguments);
            }
            
            $this->setUserProfileData();

            if ( isset($this->_userProfile[$property]) ) {
                return $this->_userProfile[$property];
            }

            if ( isset($this->_sdk->$property) ) {
                return $this->_sdk->$property;
            }
        }

        throw new SimpleFacebookException("There is no method named '" . $name . "'");
    }

}