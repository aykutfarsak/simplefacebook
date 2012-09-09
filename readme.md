Simple Facebook PHP SDK Wrapper
==========================
It is a wrapper for Facebook PHP SDK. You can easily write Facebook canvas or tab applications with this useful library.

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

$facebookAppConfig = array(
    'app_id'        => 'YOUR_APP_ID',
    'app_secret'    => 'YOUR_APP_SECRET',
    'redirect_uri'  => 'YOUR_REDIRECT_URI',
    'app_perms'     => 'YOUR_PERMISSIONS' // (optional) comma-separated list
);

$simpleFacebook = new SimpleFacebook($facebookAppConfig);

// Check user login
if( ! $simpleFacebook->isLogged() ) {

    printf('<a href="%s" target="_top">Login with Facebook</a>', $simpleFacebook->getLoginUrl());

} else {
    echo 'Facebook user id: ' . $simpleFacebook->getId();
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
* [Post to Wall](#post-to-wall) 
* [Create Event](#create-event) 
* [Force User to Login](#force-user-to-login) 
* [Application Access Token](#application-access-token)


### Extended Access Token ###

After [removal of offline_access permission](http://developers.facebook.com/roadmap/offline-access-removal/), you have to make a graph api call for get extended access token.

``` php
<?php 

$eAC = $simpleFacebook->getExtendedAccessToken();
echo "Extented access token: {$eAC} <br>";

// with expire time
$eACWithExpireTime = $simpleFacebook->getExtendedAccessToken(true);
$eAC = $eACWithExpireTime['access_token'];
$eACExpires = time() + $eACWithExpireTime['expires'];

echo "Extented access token: {$eAC} (Expires at {$eACExpires})";
```

### Friends ###

``` php
<?php 

$friends = $simpleFacebook->getFriends();

foreach ($friends as $friend) {
    echo "Name: {$friend["name"]} ID: {$friend['id']} <br>";
}

// or just ids
$friendIds = $simpleFacebook->getFriendIds();
```

Get user friends who using this application.

``` php
<?php 

$friends = $simpleFacebook->getAppUserFriends();

foreach ($friends as $friend) {
    echo "Name: {$friend["name"]} Facebook ID: {$friend['uid']} <br>";
}

// or just ids
$friendIds = $simpleFacebook->getAppUserFriendIds();
```

### Permissions ###

``` php
<?php 

// get given permissions array
$perms = $simpleFacebook->getGivenPermissions();

// check if a permission given
if ( $simpleFacebook->isPermGiven('read_stream') ) {
    // now you can get user's news feed items
    $data = $simpleFacebook->api('/me/home');
    // ..
}

// check multiple permissions
if ( $simpleFacebook->isPermGiven('read_stream,read_mailbox,user_likes') ) {
    // ..
}
```

### Request ###

If you are using [Requests Dialog](http://developers.facebook.com/docs/reference/dialogs/requests/), you must [delete the request](http://developers.facebook.com/docs/requests/#deleting) after it has been accepted. It is the developer's responsibility to clear them.

``` php
<?php 

// get request ids after delete
$requestIds = $simpleFacebook->getRequestIdsAfterDelete();

if ( ! empty($requestIds) ) {

    foreach ( $requestIds as $requestData ) {

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

if ( ! $simpleFacebook->isTabPageLiked() ) {
    
    echo 'Like our page before using app';
    
} else {
    // ..
}
```

Get information about tab page.

``` php
<?php 

// ...?sk=app_YOUR_APP_ID&app_data=YOUR_APP_DATA
$appData = $simpleFacebook->getTabAppData();

// tab page id
$pageId  = $simpleFacebook->getTabPageId();
```

### Publish Open Graph Action ###

``` php
<?php 

$responseId = $simpleFacebook->publishOpenGraphAction('YOUR_APP_NAMESPACE', 'ACTION_NAME', array(
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
$simpleFacebook->subscribe($object, $fields, $callbackUrl, $verifyToken);

// get list of subscripted updates
$subscriptions = $simpleFacebook->getSubscriptions();

// unsubscribe from all subscripted objects
$simpleFacebook->unsubscribe();

// unsubscribe from a specific subscripted object
$simpleFacebook->unsubscribe('user');
```

And this is callback url part.

``` php
<?php 
// returns challenge parameter for subscription verification of your callback url
echo SimpleFacebook::getSubscriptionChallenge($verifyToken);

// get subscripted updates
$updates = SimpleFacebook::getSubscriptedUpdates();

if ( ! empty($updates) ) {
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

$response = $simpleFacebook->sendNotification($userId, $template, $href);
```

### Run FQL Query ###

``` php
<?php 

// Get page fan count with running fql query
$query = "SELECT name, fan_count FROM page WHERE page_id = 40796308305";
$data  = $simpleFacebook->runFQL($query);

echo 'Fan count: ' . $data[0]['fan_count'];
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

$postId = $simpleFacebook->postToWall($postData);
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

$eventId = $simpleFacebook->createEvent($eventData);
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