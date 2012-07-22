Simple Facebook PHP SDK Wrapper
==========================
It is a wrapper for Facebook PHP SDK. You can easily write Facebook canvas or tab applications with this useful library.

Basic Usage
-----

    $facebookAppConfig = array(
        'app_id'        => 'YOUR_APP_ID',
        'app_secret'    => 'YOUR_APP_SECRET',
        'redirect_uri'  => 'YOUR_REDIRECT_URI',
        'app_perms'     => 'YOUR_PERMISSIONS_WITH_COMMA'
    );

    $fb = new SimpleFacebook($facebookAppConfig);

    // Check user login
    if( ! $fb->isLogged() ) {

        printf('<a href="%s" target="_top">Login with Facebook</a>', $fb->getLoginUrl());

    } else {
        echo 'Facebook user id: ' . $fb->getId();
    }