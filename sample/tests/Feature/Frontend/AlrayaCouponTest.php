<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use App\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ChangeMailEmail;
use App\Bundle;
use App\Models\AlrayaCoupon;

class AlrayaCouponTest extends TestCase{
    use DatabaseMigrations;
    use RefreshDatabase;

    public $user;

    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);

        $this->user = User::factory()
        ->create([
            'active'    =>  true
        ]);


        Passport::actingAs($this->user);
    }

    /** @test **/
    public function user_can_apply_coupon() {
        AlrayaCoupon::factory()->create([
            'code'      =>      'HELLO'
        ]);

        Bundle::factory()->create([
            'price'     =>  100
        ]);

        $response = $this->json('POST', '/api/data/alrayacoupon', [
            'code'      =>      'HELLO'
        ]);

        $response->assertStatus(200);

        $this->assertEquals(1, $this->user->subscriptions()->active()->count());

        $coupon = AlrayaCoupon::first();

        $this->assertNotEquals($coupon->activated_at, NULL);
        $this->assertEquals($coupon->activator_id, $this->user->id);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'       =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_get_error_when_coupon_doesnt_exist() {
        $response = $this->json('POST', '/api/data/alrayacoupon', [
            'code'      =>      'HELLO'
        ]);

        $response->assertStatus(450);

        $this->assertEquals(0, $this->user->subscriptions()->active()->count());

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'       =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_get_error_when_coupon_number_is_wrong() {
        AlrayaCoupon::factory()->create([
            'code'      =>      'HELLO'
        ]);

        Bundle::factory()->create([
            'price'     =>  100
        ]);

        $response = $this->json('POST', '/api/data/alrayacoupon', [
            'code'      =>      'WRONG'
        ]);

        $response->assertStatus(450);

        $this->assertEquals(0, $this->user->subscriptions()->active()->count());

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'       =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_get_error_when_bundle_doesnt_exist() {
        AlrayaCoupon::factory()->create([
            'code'      =>      'HELLO'
        ]);

        $response = $this->json('POST', '/api/data/alrayacoupon', [
            'code'      =>      'WRONG'
        ]);

        $response->assertStatus(450);

        $this->assertEquals(0, $this->user->subscriptions()->active()->count());

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'       =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_get_error_when_coupon_is_used() {
        AlrayaCoupon::factory()->create([
            'code'      =>      'HELLO',
            'activated_at'    =>  now()
        ]);

        Bundle::factory()->create([
            'price'     =>  100
        ]);

        $response = $this->json('POST', '/api/data/alrayacoupon', [
            'code'      =>      'HELLO'
        ]);

        $response->assertStatus(450);

        $this->assertEquals(0, $this->user->subscriptions()->active()->count());

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'       =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_get_error_when_coupon_has_already_ended() {
        AlrayaCoupon::factory()->create([
            'code'      =>      'HELLO',
            'end_date'    =>  now()->subDays(1)
        ]);

        Bundle::factory()->create([
            'price'     =>  100
        ]);

        $response = $this->json('POST', '/api/data/alrayacoupon', [
            'code'      =>      'HELLO'
        ]);

        $response->assertStatus(450);

        $this->assertEquals(0, $this->user->subscriptions()->active()->count());

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'       =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }
}
