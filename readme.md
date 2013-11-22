Simple Facebook PHP SDK Wrapper
==========================
It is a wrapper for Facebook PHP SDK. You can easily write Facebook canvas or tab applications with this useful library.

[![Build Status](https://secure.travis-ci.org/aykutfarsak/simplefacebook.png)](http://travis-ci.org/aykutfarsak/simplefacebook)

Installation
-----
You can use [Composer](http://getcomposer.org/) to install SimpleFacebook.

``` 
{
    "require": {
        "aykut/simplefacebook": "*"
    },
    "minimum-stability": "dev"
}
```

Basic Usage
-----
``` php
<?php 

// create Facebook sdk object
$facebook = new Facebook(array(
    'appId'  => 'APP_ID',
    'secret' => 'APP_SECRET'
));

// redirect and permission config (it is optional)
$config = array(
    'redirect_uri' => 'REDIRECT_URI', // (optional)
    'app_perms'    => 'APP_PERMISSIONS' // (optional) comma-separated list
);

$fb = new SimpleFacebook($facebook, $config);

// Check user login
if (!$fb->isLogged()) {

    printf('<a href="%s" target="_top">Login with Facebook</a>', $fb->getLoginUrl());

} else {
    echo 'Facebook user id: ' . $fb->getId();
}
```

Documentation
-----
 
### Table of Contents ###
* [Extended Access Token](#extended-access-token)  
* [Friends](#friends)
* [Permissions](#permissions) 
* [Request](#request) 
* [Page Tab Application](#page-tab-application) 
* [Publish Open Graph Action](#publish-open-graph-action) 
* [Real-time Updates](#real-time-updates) 
* [Send Notification](#send-notification)
* [Run FQL Query](#run-fql-query) 
* [Check A Page Like](#check-a-page-like)
* [Post to Wall](#post-to-wall) 
* [Create Event](#create-event) 
* [Force User to Login](#force-user-to-login) 
* [Application Access Token](#application-access-token)


### Extended Access Token ###

After [removal of offline_access permission](http://developers.facebook.com/roadmap/offline-access-removal/), you have to make a graph api call for get extended access token.

``` php
<?php 

$token = $fb->getExtendedAccessToken();
echo "Extented access token: {$token} <br>";

// with expire time
$tokenData = $fb->getExtendedAccessToken(true);
$token = $tokenData['access_token'];
$tokenExpires = time() + $tokenData['expires'];

echo "Extented access token: {$token} (Expires at {$tokenExpires})";
```

### Friends ###

``` php
<?php 

$friends = $fb->getFriends();

foreach ($friends as $friend) {
    echo "Name: {$friend["name"]} ID: {$friend['id']} <br>";
}

// or just ids
$friendIds = $fb->getFriendIds();
```

Get user friends who using this application.

``` php
<?php 

$friends = $fb->getAppUserFriends();

foreach ($friends as $friend) {
    echo "Name: {$friend["name"]} Facebook ID: {$friend['uid']} <br>";
}

// or just ids
$friendIds = $fb->getAppUserFriendIds();
```

### Permissions ###

``` php
<?php 

// get given permissions array
$perms = $fb->getGivenPermissions();

// check if a permission given
if ($fb->isPermGiven('read_stream')) {
    // now you can get user's news feed items
    $data = $fb->api('/me/home');
    // ..
}

// check multiple permissions
if ($fb->isPermGiven('read_stream,read_mailbox,user_likes')) {
    // ..
}
```

### Request ###

If you are using [Requests Dialog](http://developers.facebook.com/docs/reference/dialogs/requests/), you must [delete the request](http://developers.facebook.com/docs/requests/#deleting) after it has been accepted. It is the developer's responsibility to clear them.

``` php
<?php 

// get request ids after delete
$requestIds = $fb->getRequestIdsAfterDelete();

if (!empty($requestIds)) {

    foreach ($requestIds as $requestData) {

        $data = explode('_', $requestData);
        $requestId = $data[0];
        $userFbUid = $data[1];
        
        // your turn..
    }
}
```

### Page Tab Application ###

If it is a tab application, you might want user to like page before using app.

``` php
<?php 

if (!$fb->isTabPageLiked()) {
    
    echo 'Like our page before using app';
    
} else {
    // ..
}
```

Get information about tab page.

``` php
<?php 

// ...?sk=app_YOUR_APP_ID&app_data=YOUR_APP_DATA
$appData = $fb->getTabAppData();

// tab page id
$pageId  = $fb->getTabPageId();
```

### Publish Open Graph Action ###

``` php
<?php 

$responseId = $fb->publishOpenGraphAction('YOUR_APP_NAMESPACE', 'ACTION_NAME', array(
    'OBJECT_TYPE' => 'OBJECT_URL'
));
```

### Real-time Updates ###

[Real-time Updates](https://developers.facebook.com/docs/reference/api/realtime/) enable your application to subscribe to changes in data in Facebook.

``` php
<?php 

$object      = 'OBJECT'; // user, permissions or page
$fields      = 'FIELDS'; // friends, checkins, likes etc.
$callbackUrl = 'YOUR_CALLBACK_URL';
$verifyToken = 'YOUR_SECRET_VERIFY_TOKEN';

// subscribe to real-time updates
$fb->subscribe($object, $fields, $callbackUrl, $verifyToken);

// get list of subscripted updates
$subscriptions = $fb->getSubscriptions();

// unsubscribe from all subscripted objects
$fb->unsubscribe();

// unsubscribe from a specific subscripted object
$fb->unsubscribe('user');
```

And this is callback url part.

``` php
<?php 
// returns challenge parameter for subscription verification of your callback url
echo SimpleFacebook::getSubscriptionChallenge($verifyToken);

// get subscripted updates
$updates = SimpleFacebook::getSubscriptedUpdates();

if (!empty($updates)) {
    // there is a update!
}
```

### Send Notification ###

With [Notifications API](https://developers.facebook.com/docs/app_notifications/), you can easily send notification to an application user.

``` php
<?php

$userId   = 'USER_FACEBOOK_ID';
$template = 'NOTIFICATION_TEXT';
$href     = 'RETURN_HREF'; // index.html?gift_id=123

$response = $fb->sendNotification($userId, $template, $href);
```

### Run FQL Query ###

``` php
<?php 

// Get page fan count with running fql query
$query = "SELECT name, fan_count FROM page WHERE page_id = 40796308305";
$data  = $fb->runFQL($query);

echo 'Fan count: ' . $data[0]['fan_count'];
```

### Check A Page Like ###

``` php
<?php 

if ($fb->isLogged() && $fb->isPageLiked('PAGE_ID')) {

    echo "Liked";

} else {
    echo "Didn't like";
}
```

### Post to Wall ###

``` php
<?php 

// Post data
$postData = array(
    'message'     => 'Feed message',
    'picture'     => 'Address of image', 
    'link'        => 'URL',
    'name'        => 'URL title',
    'caption'     => 'Caption (under the URL title)',
    'description' => 'Description',
    'actions'     => array('name' => 'Action name', 'link' => 'Action link')
);

$postId = $fb->postToWall($postData);
```

### Create Event ###

``` php
<?php 

// Event data
$eventData = array(
    'name'        => 'Event Title',
    'description' => 'Event description',
    'start_time'  => gmmktime(22,0,0,7,23,2012),
    'end_time'    => gmmktime(5,0,0,7,24,2012),
    'location'    => 'Event location',
    'privacy'     => 'OPEN'
);

$eventId = $fb->createEvent($eventData);
```

### Force User to Login ###

If you have not a application login page or want user to use application after authenticated, use `forceToLogin` after initialize SimpleFacebook.

### Application Access Token ###

Get your application access token with `getApplicationAccessToken` method. More information about application access token, read [Using App Access Tokens](http://developers.facebook.com/docs/opengraph/using-app-tokens/)


License
-----
(The MIT License)

Copyright (c) 2012 Aykut Farsak (aykutfarsak@gmail.com)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
