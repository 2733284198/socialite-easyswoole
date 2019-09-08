<?php
namespace App\HttpController;
use EasySwoole\Http\AbstractInterface\Controller;

//require_once '../vendor/autoload.php';


class Index extends Controller
{

    function index()
    {
        $config = [
             'wechat' => [
                 'client_id'     => 'xxxx',
                 'client_secret' => 'xxxx',
                 'redirect'      => 'xxxx',
             ],
         ];

         $socialite = new \Overtrue\Socialite\SocialiteManager($config,$this->request(),$this->response());
         $socialite->driver('wechat')->redirect();
        /*
         $config = [
             'qq' => [
                 'client_id'     => 'xxxx',
                 'client_secret' => 'xxxx',
                 'redirect'      => 'xxxx',
             ],
         ];

         $socialite = new \Overtrue\Socialite\SocialiteManager($config,$this->request(),$this->response());
         $socialite->driver('qq')->redirect();

        $config = [
            'weibo' => [
                'client_id'     => 'xxxx',
                'client_secret' => 'xxxx',
                'redirect'      => 'xxxx',
            ],
        ];

        $socialite = new \Overtrue\Socialite\SocialiteManager($config,$this->request(),$this->response());
        $socialite->driver('weibo')->redirect();

        $config = [
            'github' => [
                'client_id'     => 'xxxx',
                'client_secret' => 'xxxx',
                'redirect'      => 'xxxx',
            ],
        ];

        $socialite = new \Overtrue\Socialite\SocialiteManager($config,$this->request(),$this->response());
        $socialite->driver('github')->redirect();*/
    }


    function callback()
    {
        $config = [
            'wechat' => [
                'client_id'     => 'xxxx',
                'client_secret' => 'xxx',
                'redirect'      => 'xxxxxx',
            ],
        ];

        $socialite = new \Overtrue\Socialite\SocialiteManager($config,$this->request(),$this->response());
        $user=$socialite->driver('wechat')->user();
        var_dump($user);
    }

}