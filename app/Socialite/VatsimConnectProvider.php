<?php

namespace App\Socialite;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class VatsimConnectProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['full_name', 'email', 'vatsim_details'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The VATSIM Connect OAuth server base URL.
     *
     * Defaults to the production server; set to the sandbox server
     * (https://auth-dev.vatsim.net) for testing via `setBaseUrl()`.
     */
    protected string $baseUrl = 'https://auth.vatsim.net';

    /**
     * Set the VATSIM Connect OAuth server base URL.
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->baseUrl.'/oauth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return $this->baseUrl.'/oauth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->baseUrl.'/api/user', [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        $data = Arr::get($user, 'data', []);
        $personal = Arr::get($data, 'personal', []);
        $vatsim = Arr::get($data, 'vatsim', []);

        return (new User)->setRaw($data)->map([
            'id' => Arr::get($data, 'cid'),
            'name' => Arr::get($personal, 'name_full'),
            'email' => Arr::get($personal, 'email'),
            'rating' => Arr::get($vatsim, 'rating.id'),
            'area' => Arr::get($vatsim, 'region.id'),
            'division' => Arr::get($vatsim, 'division.id'),
            'subdivision' => Arr::get($vatsim, 'subdivision.id'),
        ]);
    }
}
