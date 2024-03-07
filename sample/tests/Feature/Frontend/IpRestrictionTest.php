<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use App\Encyclopedia;
use Carbon\Carbon;
use App\User;
use App\Legislation;
use App\LegislationRow;
use App\Models\IpRestriction;

class IpRestrictionTest extends TestCase{
    use DatabaseMigrations;
    use RefreshDatabase;

    public $user;

    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);
        $this->user = User::factory()->create([
            'active'    =>  true,
            'ip_restricted' => true
        ]);

        $this->user->ips()->create([
            'ip'    =>  '50.50.50.50'
        ]);


        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $this->user2 = User::factory()->create([
            'active'    =>  true
        ]);
    }

    /** @test **/
    public function user_cannot_login_when_he_has_wrong_ip() {
        $response = $this->json('POST', '/api/auth/login', [
            'email'                     =>      $this->user->email,
            'password'                  =>      'password'
        ]);

        $response->assertStatus(450);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon',
                    'button'    =>  [
                            'text',
                            'closeModal'
                    ]
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_can_login_when_he_has_the_correct_ip() {
        $response = $this->withHeaders([
            'REMOTE_ADDR' => '50.50.50.50',
        ])->json('POST', '/api/auth/login', [
            'email'                     =>      $this->user->email,
            'password'                  =>      'password'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon',
                    'button'    =>  [
                            'text',
                            'closeModal'
                    ]
                ],
                'token'
            ]
        ]);
    }

    /** @test **/
    public function non_ip_resitricted_users_can_login_directly() {
        $response = $this->withHeaders([
            'REMOTE_ADDR' => '60.60.60.60',
        ])->json('POST', '/api/auth/login', [
            'email'                     =>      $this->user2->email,
            'password'                  =>      'password'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon',
                    'button'    =>  [
                            'text',
                            'closeModal'
                    ]
                ],
                'token'
            ]
        ]);
    }
}
