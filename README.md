<h1 align="center"> Socialite</h1>
<p align="center">
<a href="https://travis-ci.org/overtrue/socialite"><img src="https://travis-ci.org/overtrue/socialite.svg?branch=master" alt="Build Status"></a>
<a href="https://packagist.org/packages/overtrue/socialite"><img src="https://poser.pugx.org/overtrue/socialite/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/overtrue/socialite"><img src="https://poser.pugx.org/overtrue/socialite/v/unstable.svg" alt="Latest Unstable Version"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/socialite/build-status/master"><img src="https://scrutinizer-ci.com/g/overtrue/socialite/badges/build.png?b=master" alt="Build Status"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/socialite/?branch=master"><img src="https://scrutinizer-ci.com/g/overtrue/socialite/badges/quality-score.png?b=master" alt="Scrutinizer Code Quality"></a>
<a href="https://scrutinizer-ci.com/g/overtrue/socialite/?branch=master"><img src="https://scrutinizer-ci.com/g/overtrue/socialite/badges/coverage.png?b=master" alt="Code Coverage"></a>
<a href="https://packagist.org/packages/overtrue/socialite"><img src="https://poser.pugx.org/overtrue/socialite/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/overtrue/socialite"><img src="https://poser.pugx.org/overtrue/socialite/license" alt="License"></a>
</p>


<p align="center">基于 <a href="https://github.com/overtrue/socialite">overtrue/socialite</a>改造的,适用于easyswoole的第三方登录组件，现已支持wechat,qq,weibo,github,facebook</p>

# 依赖

```
PHP >= 7.0
swoole >=4.4.0
```
# 安装

```shell
$ composer require "xbing2002/socialite" "1.0"
```

# 使用说明


`authorize.php`:

```php
<?php

use Overtrue\Socialite\SocialiteManager;

$config = [
    'wechat' => [
        'client_id'     => 'your-app-id',
        'client_secret' => 'your-app-secret',
        'redirect'      => 'http://localhost/socialite/callback.php',
    ],
];

$socialite = new SocialiteManager($config);

$socialite->driver('wechat')->redirect();

```

`callback.php`:

```php
<?php

use Overtrue\Socialite\SocialiteManager;

$config = [
    'wechat' => [
        'client_id' => 'your-app-id',
        'client_secret' => 'your-app-secret',
        'redirect' => 'http://localhost/socialite/callback.php',
    ],
];

$socialite = new SocialiteManager($config);

$user = $socialite->driver('wechat')->user();

$user->getId();        // openid
$user->getNickname();  // "昵称"
$user->getName();      // "昵称"
$user->getAvatar();     // 头像
$user->getProviderName(); // WeChat
...
```

### 配置项

现在支持:

`facebook`, `github`, `weibo`,  `qq`, `wechat`.

每一个登录平台的配置都是一样的，只需要配置: `client_id`, `client_secret`, `redirect`.

例子:
```
...
  'weibo' => [
    'client_id'     => 'your-app-id',
    'client_secret' => 'your-app-secret',
    'redirect'      => 'http://localhost/socialite/callback.php',
  ],
...
```

### Scope

有些登录平台可以在跳转之前设置Scope:

```php
$response = $socialite->driver('github')
                ->scopes(['scope1', 'scope2'])->redirect();

```
> WeChat scopes:
- `snsapi_base`, `snsapi_userinfo` - 用于公众号登录.
- `snsapi_login` - 用户web登录.

### 跳转链接

当然你也可以动态设置跳转链接:

```php
$socialite->redirect($url);
// or
$socialite->withRedirectUrl($url)->redirect();
// or
$socialite->setRedirectUrl($url)->redirect();
```


### 自定义参数

如果存在一些自定义参数，请用with方法

```php
$response = $socialite->driver('google')
                    ->with(['hd' => 'example.com'])->redirect();
```

### User interface

#### Standard user api:

```php

$user = $socialite->driver('weibo')->user();
```

```json
{
  "id": 1472352,
  "nickname": "overtrue",
  "name": "安正超",
  "email": "anzhengchao@gmail.com",
  "avatar": "https://avatars.githubusercontent.com/u/1472352?v=3",
  "original": {
    "login": "overtrue",
    "id": 1472352,
    "avatar_url": "https://avatars.githubusercontent.com/u/1472352?v=3",
    "gravatar_id": "",
    "url": "https://api.github.com/users/overtrue",
    "html_url": "https://github.com/overtrue",
    ...
  },
  "token": {
    "access_token": "5b1dc56d64fffbd052359f032716cc4e0a1cb9a0",
    "token_type": "bearer",
    "scope": "user:email"
  }
}
```

你可以通过数组方式获取用户属性:

```php
$user['id'];        // 1472352
$user['nickname'];  // "overtrue"
$user['name'];      // "安正超"
$user['email'];     // "anzhengchao@gmail.com"
...
```

或者通过对象方式获取:

```php
$user->getId();
$user->getNickname();
$user->getName();
$user->getEmail();
$user->getAvatar();
$user->getOriginal();
$user->getToken();// or $user->getAccessToken()
$user->getProviderName(); // GitHub/Google/Facebook...
```

#### 你也可以直接获取各登录平台的原始返回数据

`$user->getOriginal()` 

#### 获取access token 对象

`$user->getToken()`
`$user->getAccessToken()`
`$user['token']` 


### 可以通过access token 获取用户信息

```php
$accessToken = new AccessToken(['access_token' => $accessToken]);
$user = $socialite->user($accessToken);
```

# License

MIT
