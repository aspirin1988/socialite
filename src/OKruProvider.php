<?php

    namespace Aspirin1988\Socialite;

    use Illuminate\Support\Arr;
    use Laravel\Socialite\Two\AbstractProvider;
    use Laravel\Socialite\Two\InvalidStateException;
    use Laravel\Socialite\Two\ProviderInterface;
    use Laravel\Socialite\Two\User;

    class OKruProvider extends AbstractProvider implements ProviderInterface
    {
        /**
         * The separating character for the requested scopes.
         *
         * @var string
         */
        protected $scopeSeparator = ';';

        /**
         * The scopes being requested.
         *
         * @var array
         */
        protected $scopes = [
            'VALUABLE_ACCESS',
            'LONG_ACCESS_TOKEN',
            'GET_EMAIL',
        ];

        /**
         * {@inheritdoc}
         */
        protected function getAuthUrl($state)
        {
            return $this->buildAuthUrlFromBase('https://connect.ok.ru/oauth/authorize', $state);
        }

        /**
         * {@inheritdoc}
         */

        protected function getTokenUrl()
        {
            return 'https://api.ok.ru/oauth/token.do';
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
            $response = $this->getHttpClient()->get('https://api.ok.ru/fb.do?', [
                'query'   => [
                    'application_key' => config('services.okru.client_public'),
                    'format'          => 'json',
                    'method'          => 'users.getCurrentUser',
                    'sig'             => $token['sig'],
                    'access_token'    => $token['token'],
//                    'fields'=>'uid,first_name,last_name,email',
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

            $secret_key = md5(Arr::get($response, 'access_token') . $this->clientSecret);
            $user = $this->mapUserToObject($this->getUserByToken(
                [
                    'token' => Arr::get($response, 'access_token'),
                    'sig'   => md5("application_key=" . config('services.okru.client_public') . "format=jsonmethod=users.getCurrentUser" . $secret_key),
                ]
            ));

            return $user->setToken(Arr::get($response, 'access_token'))
                ->setRefreshToken(Arr::get($response, 'refresh_token'))
                ->setExpiresIn(Arr::get($response, 'expires_in'));
        }
    }