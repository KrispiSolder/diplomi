<?php

namespace App\Socialite;

use SocialiteProviders\Yandex\Provider as YandexBase;

/**
 * У Яндекса scope в query перечисляются через пробел, у Socialite по умолчанию — запятая.
 */
class YandexOAuthProvider extends YandexBase
{
    protected $scopeSeparator = ' ';
}
