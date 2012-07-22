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

License
===============
Copyright (c) 2012 Aykut Farsak

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.