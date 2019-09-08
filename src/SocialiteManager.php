<?php

/*
 * This file is part of the overtrue/socialite.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\Socialite;

use Closure;
use InvalidArgumentException;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;


/**
 * Class SocialiteManager.
 */
class SocialiteManager implements FactoryInterface
{
    /**
     * The configuration.
     *
     * @var \Overtrue\Socialite\Config
     */
    protected $config;

    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The initial drivers.
     *
     * @var array
     */
    protected $initialDrivers = [
        'facebook' => 'Facebook',
        'github' => 'GitHub',
        'google' => 'Google',
        'linkedin' => 'Linkedin',
        'weibo' => 'Weibo',
        'qq' => 'QQ',
        'wechat' => 'WeChat',
        'douban' => 'Douban',
        'wework' => 'WeWork',
        'outlook' => 'Outlook',
        'douyin' => 'DouYin',
        'taobao' => 'Taobao',
    ];

    /**
     * The array of created "drivers".
     *
     * @var ProviderInterface[]
     */
    protected $drivers = [];

    /**
     * SocialiteManager constructor.
     *
     * @param array        $config
     * @param Request $request
     * @param Response $response
     */
    public function __construct(array $config, Request $request,Response $response)
    {

        $this->config = new Config($config);

        $this->setRequest($request);
        $this->setResponse($response);
    }

    /**
     * Set config instance.
     *
     * @param \Overtrue\Socialite\Config $config
     *
     * @return $this
     */
    public function config(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get a driver instance.
     *
     * @param string $driver
     *
     * @return ProviderInterface
     */
    public function driver($driver)
    {
        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return  Request
     */
    public function getRequest()
    {
        return $this->request ?: $this->createDefaultRequest();
    }

    /**
     * @param Response $response
     *
     * @return $this
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return  Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Create a new driver instance.
     *
     * @param string $driver
     *
     * @throws \InvalidArgumentException
     *
     * @return ProviderInterface
     */
    protected function createDriver($driver)
    {
        if (isset($this->initialDrivers[$driver])) {
            $provider = $this->initialDrivers[$driver];
            $provider = __NAMESPACE__.'\\Providers\\'.$provider.'Provider';

            return $this->buildProvider($provider, $this->formatConfig($this->config->get($driver)));
        }

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $driver
     *
     * @return ProviderInterface
     */
    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->config);
    }

    /**
     * Create default request instance.
     *
     * @return Request
     */
    protected function createDefaultRequest()
    {

        //$request = Request::createFromGlobals();
        //$session = new Session();

        //$request->setSession($session);

        return $this->request;
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param string   $driver
     * @param \Closure $callback
     *
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Get all of the created "drivers".
     *
     * @return ProviderInterface[]
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Build an OAuth 2 provider instance.
     *
     * @param string $provider
     * @param array  $config
     *
     * @return ProviderInterface
     */
    public function buildProvider($provider, $config)
    {
        return new $provider(
            $this->getRequest(),
            $this->getResponse(),
            $config['client_id'],
            $config['client_secret'],
            $config['redirect']
        );
    }

    /**
     * Format the server configuration.
     *
     * @param array $config
     *
     * @return array
     */
    public function formatConfig(array $config)
    {
        return array_merge([
            'identifier' => $config['client_id'],
            'secret' => $config['client_secret'],
            'callback_uri' => $config['redirect'],
        ], $config);
    }
}
