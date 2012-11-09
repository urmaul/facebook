# Facebook API Wrapper for Yii

Yii Facebook Api Wrapper Component

## Install

First of all you chould separately install Facebook SDK. You can find it here: [https://github.com/facebook/facebook-php-sdk](https://github.com/facebook/facebook-php-sdk).
SDK should be copied into "protected/vendors/facebook" directory, but it may vary.

Component config:

```php
'facebook' => array(
    'class' => 'ext.facebook.FacebookComponent',
    'sdkLocation' => 'application.vendors.facebook.src.facebook',

    'appId'  => '111111111111111',
    'secret' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',

    'messages' => array(
        'invite' => array(
            'message' => "Go sign up!",
            'picture' => '/images/logo.png',
            'link'    => array('site/index', 'from' => '[userId]'),
            'name'    => 'MyProject',
            'caption' => 'Join us',
        ),
    ),
),
```