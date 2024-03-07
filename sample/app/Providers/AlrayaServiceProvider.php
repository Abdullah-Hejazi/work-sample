<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AlrayaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {
        if($this->app->runningUnitTests()) {
            $this->runningTests();
        } else {
            $this->runningProduction();
        }
    }

    public function runningTests() {
        $this->app->bind(
            'App\Repositories\LawRepository\ILawRepository',
            'App\Repositories\LawRepository\MysqlLawRepository'
        );

        $this->app->bind(
            'App\Repositories\EmailValidation\EmailValidation',
            'App\Repositories\EmailValidation\MockValidator'
        );
    }

    public function runningProduction() {
        $this->app->bind(
            'App\Repositories\LawRepository\ILawRepository',
            'App\Repositories\LawRepository\ElasticSearchLawRepository'
        );

        $this->app->bind(
            'App\Repositories\EmailValidation\EmailValidation',
            'App\Repositories\EmailValidation\ZeroBounceValidator'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        \Laravel\Paddle\Subscription::resolveRelationUsing('bundle', function ($subscription) {
            return $subscription->hasOne(\App\Bundle::class, 'plan_id', 'paddle_plan');
        });
    }
}
