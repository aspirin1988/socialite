<?php

    namespace Aspirin1988\Socialite;

    use Illuminate\Support\Arr;
    use Laravel\Socialite\Two\AbstractProvider;
    use Laravel\Socialite\Two\InvalidStateException;
    use Laravel\Socialite\Two\ProviderInterface;
    use Laravel\Socialite\Two\User;

    class VkontakteProvider extends AbstractProvider implements ProviderInterface
    {
        public $authUrl = 'https://oauth.vk.com/';
        public $apiUrl = 'https://api.vk.com/method/';
        protected $email = null;

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
        protected $scopes = [
            4194304,
        ];

        /**
         * @var array
         */
        protected $scopesUser = [
            'sex',
            'bdate',
            'city',
            'country',
            'home_town',
            'photo_200',
            'domain',
            'has_mobile',
            'site',
            'nickname',
            'timezone',
            'screen_name',
        ];

        /**
         * @param $email
         *
         * @return string
         */
        public function setEmail(string $email)
        {
            $this->email = $email;

            return $this;
        }

        /**
         * @return null
         */
        public function getEmail()
        {
            return $this->email;
        }

        /**
         * {@inheritdoc}
         */
        protected function getAuthUrl($state)
        {
            return $this->buildAuthUrlFromBase('https://oauth.vk.com/authorize', $state);
        }

        /**
         * {@inheritdoc}
         */

        protected function getTokenUrl()
        {
            return 'https://oauth.vk.com/access_token';
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
            $response = $this->getHttpClient()->get('https://api.vk.com/method/users.get?', [
                'query'   => [
                    'access_token' => $token['token'],
                    'user_ids'     => $token['user_id'],
                    'fields'       => implode(',', $this->scopesUser),
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
            $user = $user['response'][0];

            return (new User)->setRaw($user)->map(
                [
                    'id'         => Arr::get($user, 'uid'),
                    'first_name' => Arr::get($user, 'first_name'),
                    'last_name'  => Arr::get($user, 'last_name'),
                    'nickname'   => Arr::get($user, 'domain'),
                    'name'       => Arr::get($user, 'last_name') . ' ' . Arr::get($user, 'first_name'),
                    'email'      => $this->getEmail(),
                    'avatar'     => Arr::get($user, 'photo_200'),
                    'birthday'     => Arr::get($user, 'bdate'),
                ]
            );
        }

        public function user()
        {
            if ($this->hasInvalidState()) {
                throw new InvalidStateException();
            }

            $response = $this->getAccessTokenResponse($this->getCode());

            $this->setEmail(Arr::get($response, 'email'));

            $user = $this->mapUserToObject($this->getUserByToken(
                ['token' => Arr::get($response, 'access_token'), 'user_id' => Arr::get($response, 'user_id')]
            ));

            return $user->setToken(Arr::get($response, 'access_token'))
                ->setRefreshToken(Arr::get($response, 'refresh_token'))
                ->setExpiresIn(Arr::get($response, 'expires_in'));
        }
    }