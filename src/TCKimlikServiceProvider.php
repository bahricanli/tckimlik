<?php
namespace BahriCanli;

use Validator;
use Illuminate\Support\ServiceProvider;
use BahriCanli\TcKimlik;

class TCKimlikServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/tckimlik.php' => config_path('tckimlik.php'),
        ], 'tckimlik-config');

        $this->bootValidator();
    }

    protected function bootValidator() {
        Validator::extend('tckimlik', function ($attribute, $value, $parameters, $validator) {
            return TcKimlik::verify($value);
        });

        Validator::replacer('tckimlik', function ($message, $attribute, $rule, $parameters) {
            if ($message=="validation.tckimlik") {
                return "Belirtilen T.C. Kimlik Numarası doğrulanamadı.";
            }
            return $message;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tckimlik.php', 'tckimlik');
    }
}
