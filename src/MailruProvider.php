<?php

    namespace Aspirin1988\Socialite;

    use Illuminate\Support\Arr;
    use Laravel\Socialite\Two\AbstractProvider;
    use Laravel\Socialite\Two\InvalidStateException;
    use Laravel\Socialite\Two\ProviderInterface;
    use Laravel\Socialite\Two\User;

    class MailruProvider extends AbstractProvider implements ProviderInterface
    {
        /**
         * The separating character for the requested scopes.
         *
         * @var string
         */
        protected $scopeSeparator = ' ';

        /**
         * The scopes being requested.
         *
         * @var array
         */
        protected $scopes = [];

        /**
         * {@inheritdoc}
         */
        protected function getAuthUrl($state)
        {
            return $this->buildAuthUrlFromBase('https://connect.mail.ru/oauth/authorize', $state);
        }

        /**
         * {@inheritdoc}
         */

        protected function getTokenUrl()
        {
            return 'https://connect.mail.ru/oauth/token';
        }

        /**
         * Get the POST fields for the token request.
         *
         * @param  string $code
         * @return array
         */
        protected function getTokenFields($code)
        {
            return array_add(
                parent::getTokenFields($code), 'grant_type', 'authorization_code'
            );
        }

        /**
         * {@inheritdoc}
         */
        protected function getUserByToken($token)
        {
            $response = $this->getHttpClient()->get('http://www.appsmail.ru/platform/api?', [
                'query'   => [
                    'method'      => 'users.getInfo',
                    'secure'      => '1',
                    'app_id'      => $this->clientId,
                    'session_key' => $token['token'],
                    'sig'         => $token['sign'],

                ],
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token['token'],
                ],
            ]);

            return json_decode($response->getBody(), true);
        }

        /**
         * {@inheritdoc}
         */
        protected function mapUserToObject(array $user)
        {
            $user = $user[0];

            return (new User)->setRaw($user)->map(
                [
                    'id'         => Arr::get($user, 'uid'),
                    'first_name' => Arr::get($user, 'first_name'),
                    'last_name'  => Arr::get($user, 'last_name'),
                    'nickname'   => Arr::get($user, 'last_name') . ' ' . Arr::get($user, 'first_name'),
                    'email'      => Arr::get($user, 'email'),
                    'name'       => Arr::get($user, 'last_name') . ' ' . Arr::get($user, 'first_name'),
                    'avatar'     => Arr::get($user, 'pic_190'),
                    'birthday'     => Arr::get($user, 'birthday'),
                ]
            );
        }

        public function user()
        {

            if ($this->hasInvalidState()) {
                throw new InvalidStateException();
            }

            $response = $this->getAccessTokenResponse($this->getCode());

            $user = $this->mapUserToObject($this->getUserByToken(
                [
                    'token' => Arr::get($response, 'access_token'),
                    'sign'  => md5("app_id={$this->clientId}method=users.getInfosecure=1session_key=" . Arr::get($response, 'access_token') . "{$this->clientSecret}"),
                ]
            ));

            return $user->setToken(Arr::get($response, 'access_token'))
                ->setRefreshToken(Arr::get($response, 'refresh_token'))
                ->setExpiresIn(Arr::get($response, 'expires_in'));
        }
    }