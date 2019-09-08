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

use Exception;
use Overtrue\Socialite\AccessTokenInterface;
use Overtrue\Socialite\ProviderInterface;
use Overtrue\Socialite\User;

/**
 * Class GitHubProvider.
 */
class GitHubProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['user:email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://github.com/login/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://github.com/login/oauth/access_token';
    }

    public function getAccessToken($code)
    {
        $response =$this->getHttpClient()
            ->setUrl($this->getTokenUrl())
            ->setQuery($this->getTokenFields($code))
            ->get(['Accept' => 'application/json']);

        return $this->parseAccessToken($response->getBody());
    }

    protected function getTokenFields($code)
    {
        return array_filter([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken(AccessTokenInterface $token)
    {
        $userUrl = 'https://api.github.com/user?access_token='.$token->getToken();
        $response = $this->getHttpClient()->setUrl($userUrl)->get($this->getRequestOptions());


        $user = json_decode($response->getBody(), true);

        if (in_array('user:email', $this->scopes)) {
            $user['email'] = $this->getEmailByToken($token);
        }

        return $user;
    }

    /**
     * Get the email for the given access token.
     *
     * @param string $token
     *
     * @return string|null
     */
    protected function getEmailByToken($token)
    {
        $emailsUrl = 'https://api.github.com/user/emails?access_token='.$token->getToken();

        try {
            $response = $this->getHttpClient()->setUrl($emailsUrl)->get($this->getRequestOptions());

        } catch (Exception $e) {
            return;
        }

        foreach (json_decode($response->getBody(), true) as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return new User([
            'id' => $this->arrayItem($user, 'id'),
            'username' => $this->arrayItem($user, 'login'),
            'nickname' => $this->arrayItem($user, 'login'),
            'name' => $this->arrayItem($user, 'name'),
            'email' => $this->arrayItem($user, 'email'),
            'avatar' => $this->arrayItem($user, 'avatar_url'),
        ]);
    }

    /**
     * Get the default options for an HTTP request.
     *
     * @return array
     */
    protected function getRequestOptions()
    {
        return [
                'Accept' => 'application/vnd.github.v3+json',
        ];
    }
}
