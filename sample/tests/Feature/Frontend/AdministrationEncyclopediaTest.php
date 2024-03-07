<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use App\Encyclopedia;
use Carbon\Carbon;
use App\User;
use App\Bundle;
use App\BundleItem;
use App\AdministrationTitle;

class AdministrationEncyclopediaTest extends TestCase {
    use DatabaseMigrations;
    use RefreshDatabase;

    public $user;

        
    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);
        $this->user = User::factory()->create([
            'active'    =>  true
        ]);

        Passport::actingAs($this->user);

        Encyclopedia::factory()->create([
            'id'            =>  1,
            'published'     =>  false,
            'is_parent'     =>  true,
            'parent_id'     =>  NULL
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  2,
            'published'     =>  false,
            'is_parent'     =>  false,
            'parent_id'     =>  1
        ]);

        AdministrationTitle::factory()->create([
            'id'                =>  1,
            'encyclopedia_id'   =>  2,
            'published'         =>  false,
            'is_parent'         =>  true
        ]);
        AdministrationTitle::factory()->create([
            'id'                =>  2,
            'encyclopedia_id'   =>  2,
            'published'         =>  false,
            'is_parent'         =>  false,
            'parent_id'         =>  1
        ]);


        Encyclopedia::factory()->create([
            'id'            =>  3,
            'published'     =>  true,
            'is_parent'     =>  true,
            'parent_id'     =>  NULL
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  4,
            'published'     =>  true,
            'is_parent'     =>  false,
            'parent_id'     =>  1
        ]);

        AdministrationTitle::factory()->create([
            'id'                =>  5,
            'encyclopedia_id'   =>  3,
            'published'         =>  false,
            'is_parent'         =>  true
        ]);
        
        AdministrationTitle::factory()->create([
            'id'                =>  3,
            'encyclopedia_id'   =>  3,
            'published'         =>  true,
            'is_parent'         =>  true
        ]);
        AdministrationTitle::factory()->create([
            'id'                =>  4,
            'encyclopedia_id'   =>  3,
            'published'         =>  true,
            'is_parent'         =>  false,
            'parent_id'         =>  1
        ]);

        
        Bundle::factory()->create([
            'active'        =>  true,
            'plan_id'   =>  641598
        ]);
        BundleItem::factory()->create([
            'encyclopedia_id'           =>  3,
            'bundle_id'                 =>  1
        ]);
    }

    /** @test **/
    public function user_cannot_access_administration_encyclopedia_when_not_published() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $this->json('GET', '/api/data/administration/encyclopedias/1');
    }

    /** @test **/
    public function user_cannot_access_administration_encyclopedia_when_not_subscribed() {
        $response = $this->json('GET', '/api/data/administration/encyclopedias/3');

        $response->assertStatus(450);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'message'      =>      [
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
    public function user_can_access_administration_encyclopedia_when_subscribed_and_published() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('GET', '/api/data/administration/encyclopedias/3');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'payload',
                'encyclopedia'
            ]
        ]);
    }

    /** @test **/
    public function user_cannot_access_title_when_encyclopedia_not_published() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('GET', '/api/data/administration/titles/1/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'payload',
                'parent',
                'encyclopedia'
            ]
        ]);
    }

    /** @test **/
    public function user_cannot_access_title_when_encyclopedia_not_subscribed() {
        $response = $this->json('GET', '/api/data/administration/titles/3/3');

        $response->assertStatus(450);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'message'      =>      [
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
    public function user_cannot_access_title_when_title_not_published() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);
        

        $response = $this->json('GET', '/api/data/administration/titles/3/5');
    }

    /** @test **/
    public function user_can_access_title_when_title_is_published_and_encyclopedia_is_subscribed() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('GET', '/api/data/administration/titles/3/3');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'payload'
            ]
        ]);
    }
}
