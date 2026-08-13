<?php

namespace App\Providers;

use App\Models\Setting;
use App\Support\CrossrefConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.public', function ($view) {
            $view->with('siteSettings', [
                'email' => Setting::get('contact_email', 'liceum9zd@yandex.ru'),
                'phone' => Setting::get('contact_phone', '+7 (995) 285-83-21'),
                'phone_raw' => Setting::get('contact_phone_raw', '+79952858321'),
                'vk' => Setting::get('social_vk', 'https://vk.com/public220986216'),
                'telegram' => Setting::get('social_telegram', 'https://t.me/asooaspp'),
                'whatsapp' => Setting::get('social_whatsapp', 'https://api.whatsapp.com/message/5YSFA5VES7O2J1'),
                'rutube' => Setting::get('social_rutube', 'https://rutube.ru/channel/26854854'),
                'print_issn' => Setting::get('journal_issn_print'),
                'electronic_issn' => Setting::get('journal_issn_electronic'),
            ]);
        });

        Blade::directive('purify', function (string $expression) {
            return "<?php
                \$__purifierConfig = \HTMLPurifier_Config::createDefault();
                \$__purifierConfig->set('HTML.Allowed', 'h1,h2,h3,h4,h5,h6,p,br,strong,em,b,i,u,a[href|title|target],ul,ol,li,blockquote,img[src|alt|width|height],table,thead,tbody,tr,th,td,pre,code,hr,span[style],div');
                \$__purifierConfig->set('HTML.TargetBlank', true);
                \$__purifierConfig->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
                echo (new \HTMLPurifier(\$__purifierConfig))->purify({$expression});
            ?>";
        });

        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        CrossrefConfig::warnIfMisconfigured();

        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
