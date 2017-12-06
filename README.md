# Авторизация через социальные сети
    VK | Mail.ru | Ok.ru

Установка
-------
composer require aspirin1988/socialite

Затем добавьте в:
    /app/Providers/AppServiceProvider.php
    
    в фунцию boot(){
    
        ..//..//..
        $this->bootVkontakte();
        $this->bootMailru();
        $this->bootOKru();
    }
    
Добавляем ниже эти функции:
    
    private function bootVkontakte()
        {
            $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
            $socialite->extend(
                'vkontakte',
                function ($app) use ($socialite) {
                    $config = $app['config']['services.vkontakte'];
                    return $socialite->buildProvider(VkontakteProvider::class, $config);
                }
            );
        }
    
        private function bootMailRu()
        {
            $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
            $socialite->extend(
                'mailru',
                function ($app) use ($socialite) {
                    $config = $app['config']['services.mailru'];
                    return $socialite->buildProvider(MailruProvider::class, $config);
                }
            );
        }
    
        private function bootOKru()
        {
            $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
            $socialite->extend(
                'okru',
                function ($app) use ($socialite) {
                    $config = $app['config']['services.okru'];
                    return $socialite->buildProvider(OKruProvider::class, $config);
                }
            );
        }
        
А так же в /config/services.php добавляем соответствующие секции:
    
    'vkontakte' => [
            'client_id' => APP_ID,
            'client_secret' => APP_SECRET,
            'redirect' => APP_REDIREDT_URL,
        ],
    
        'mailru' => [
            'client_id' => APP_ID,
            'client_secret' => APP_SECRET,
            'redirect' => APP_REDIREDT_URL,
        ],
    
        'okru' => [
            'client_id' => APP_ID,
            'client_public' => APP_PUBLIcK_KEY,
            'client_secret' => APP_SECRET,
            'redirect' => 'APP_REDIREDT_URL,
        ],

Использование
-------
Пример вызова: 
    
    /*Вызов метода авторизации через VK*/
    public function authVkontakte()
    {
       return Socialite::driver('vkontakte')->redirect();
    }
    
    /*Получение и вывод данных пользователя VK*/
    public function authVkontakteCallback()
    {
        $user = Socialite::driver('vkontakte')->user();
        return response()->json($user);
    }
