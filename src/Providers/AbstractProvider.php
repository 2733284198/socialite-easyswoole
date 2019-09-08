<?php

/*
 * This file is part of the overtrue/socialite.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\Socialite\Providers;

use easySwoole\Cache\Cache;
use EasySwoole\Http\Response;
use EasySwoole\HttpClient\HttpClient;
use Overtrue\Socialite\AccessToken;
use Overtrue\Socialite\AccessTokenInterface;
use Overtrue\Socialite\AuthorizeFailedException;
use Overtrue\Socialite\InvalidStateException;
use Overtrue\Socialite\ProviderInterface;
use EasySwoole\Http\Request;

/**
 * Class SessionDriverInterface.
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * AbstractProvider name.
     *
     * @var string
     */
    protected $name;

    /**
     * The HTTP request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * The HTTP response instance.
     *
     * @var Request
     */
    protected $response;

    /**
     * The client ID.
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client secret.
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * @var \Overtrue\Socialite\AccessTokenInterface
     */
    protected $accessToken;

    /**
     * The redirect URL.
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * The custom parameters to be sent with the request.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ',';

    /**
     * The type of the encoding in the query.
     *
     * @var int Can be either PHP_QUERY_RFC3986 or PHP_QUERY_RFC1738
     */
    protected $encodingType = PHP_QUERY_RFC1738;

    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;

    /**
     * The options for guzzle\client.
     *
     * @var array
     */
    protected static $guzzleOptions = ['http_errors' => false];

    /** @var SessionDriver */
    protected $session;

    /**
     * Create a new provider instance.
     *
     * @param Request  $request
     * @param Response $response
     * @param string                                    $clientId
     * @param string                                    $clientSecret
     * @param string|null                               $redirectUrl
     */
    public function __construct(Request $request, Response $response, $clientId, $clientSecret, $redirectUrl = null)
    {
        $this->request = $request;
        $this->response=$response;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUrl = $redirectUrl;

        $sessionManager= new SessionManager('file',$this->request,$this->response);
        $this->session=$sessionManager->session();
        $this->session->start();
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     *
     * @return string
     */
    abstract protected function getAuthUrl($state);

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    abstract protected function getTokenUrl();

    /**
     * Get the raw user for the given access token.
     *
     * @param \Overtrue\Socialite\AccessTokenInterface $token
     *
     * @return array
     */
    abstract protected function getUserByToken(AccessTokenInterface $token);

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array $user
     *
     * @return \Overtrue\Socialite\User
     */
    abstract protected function mapUserToObject(array $user);


    /**
     * Redirect the user of the application to the provider's authentication screen.
     *
     * @param string $redirectUrl
     *
     * @return string
     */
    public function redirect($redirectUrl = null)
    {
        $state = null;

        if (!is_null($redirectUrl)) {
            $this->redirectUrl = $redirectUrl;
        }
        if ($this->usesState()) {
            $state = $this->makeState();
        }

        return $this->response->redirect($this->getAuthUrl($state));
        //return new RedirectResponse($this->getAuthUrl($state));
    }

    /**
     * {@inheritdoc}
     */
    public function user(AccessTokenInterface $token = null)
    {
        if (is_null($token) && $this->hasInvalidState()) {
            throw new InvalidStateException();
        }
        $token = $token ?: $this->getAccessToken($this->getCode());
        $user = $this->getUserByToken($token);

        $user = $this->mapUserToObject($user)->merge(['original' => $user]);

        return $user->setToken($token)->setProviderName($this->getName());
    }

    /**
     * Set redirect url.
     *
     * @param string $redirectUrl
     *
     * @return $this
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Set redirect url.
     *
     * @param string $redirectUrl
     *
     * @return $this
     */
    public function withRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * Return the redirect url.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param \Overtrue\Socialite\AccessTokenInterface $accessToken
     *
     * @return $this
     */
    public function setAccessToken(AccessTokenInterface $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Get the access token for the given code.
     *
     * @param string $code
     *
     * @return \Overtrue\Socialite\AccessTokenInterface
     */
    public function getAccessToken($code)
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }



        $response = $this->getHttpClient()
            ->setUrl($this->getTokenUrl())
            ->post($this->getTokenFields($code),['Accept' => 'application/json']);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * Set the scopes of the requested access.
     *
     * @param array $scopes
     *
     * @return $this
     */
    public function scopes(array $scopes)
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * Set the request instance.
     *
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
     * Get the request instance.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Indicates that the provider should operate as stateless.
     *
     * @return $this
     */
    public function stateless()
    {
        $this->stateless = true;

        return $this;
    }

    /**
     * Set the custom parameters of the request.
     *
     * @param array $parameters
     *
     * @return $this
     */
    public function with(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @throws \ReflectionException
     *
     * @return string
     */
    public function getName()
    {
        if (empty($this->name)) {
            $this->name = strstr((new \ReflectionClass(get_class($this)))->getShortName(), 'AbstractProvider', true);
        }

        return $this->name;
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $url
     * @param string $state
     *
     * @return string
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        return $url.'?'.http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);
    }

    /**
     * Get the GET parameters for the code request.
     *
     * @param string|null $state
     *
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $fields = array_merge([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'response_type' => 'code',
        ], $this->parameters);

        if ($this->usesState()) {
            $fields['state'] = $state;
        }

        return $fields;
    }

    /**
     * Format the given scopes.
     *
     * @param array  $scopes
     * @param string $scopeSeparator
     *
     * @return string
     */
    protected function formatScopes(array $scopes, $scopeSeparator)
    {
        return implode($scopeSeparator, $scopes);
    }

    /**
     * Determine if the current request / session has a mismatching "state".
     *
     * @return bool
     */
    protected function hasInvalidState()
    {
        if ($this->isStateless()) {
            return false;
        }
        $cookie=$this->request->getCookieParams('EsLoginSession');
        if(empty($cookie))
        {
            return false;
        }
        Cache::init();
        $state=Cache::get($cookie);
        return !(strlen($state) > 0);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     *
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
        ];
    }

    /**
     * Get the access token from the token response body.
     *
     * @param \Psr\Http\Message\StreamInterface|array $body
     *
     * @return \Overtrue\Socialite\AccessTokenInterface
     */
    protected function parseAccessToken($body)
    {
        if (!is_array($body)) {
            $body = json_decode($body, true);
        }
        if (empty($body['access_token'])) {
            throw new AuthorizeFailedException('Authorize Failed: '.json_encode($body, JSON_UNESCAPED_UNICODE), $body);
        }

        return new AccessToken($body);
    }

    /**
     * Get the code from the request.
     *
     * @return string
     */
    protected function getCode()
    {
        return $this->request->getQueryParam('code');
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return HttpClient
     */
    protected function getHttpClient()
    {
        //这儿后期加上httpclient的config
        return new  HttpClient();
        //return new Client(self::$guzzleOptions);
    }



    /**
     * Determine if the provider is operating with state.
     *
     * @return bool
     */
    protected function usesState()
    {
        return !$this->stateless;
    }

    /**
     * Determine if the provider is operating as stateless.
     *
     * @return bool
     */
    protected function isStateless()
    {
        return $this->stateless;
    }

    /**
     * Return array item by key.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    protected function arrayItem(array $array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Put state to session storage and return it.
     *
     * @return string|bool
     */
    protected function makeState()
    {
        $state = sha1(uniqid(mt_rand(1, 1000000), true));
        $this->response->setCookie('EsLoginSession',$state,1440);
        Cache::init();
        Cache::set($state,$state);

        return $state;
    }
}
