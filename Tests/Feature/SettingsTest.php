<?php

namespace Modules\PaypalStandard\Tests\Feature;

use Tests\Feature\FeatureTestCase;

class SettingsTest extends FeatureTestCase
{
    public function testItShouldSeePaypalStandardSettingsUpdatePage()
    {
        $this->loginAs()
            ->get(route('settings.module.edit', ['alias' => 'paypal-standard']))
            ->assertOk()
            ->assertSeeText(trans('paypal-standard::general.name'))
            ->assertSeeText(trans('paypal-standard::general.description'));
    }

    public function testItShouldUpdatePaypalStandardSettings()
    {
        $this->loginAs()
            ->patch(route('settings.module.edit', ['alias' => 'paypal-standard']), $this->getRequest())
            ->assertOk();

        $this->assertFlashLevel('success');
    }

    public function getRequest()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'mode' => 'sandbox',
            'transaction' => 'sale',
            'customer' => 1,
            'debug' => 1,
            'order' => 1,
        ];
    }
}
