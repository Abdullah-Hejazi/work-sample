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
use App\Law;
use App\Bundle;
use App\BundleItem;

class EncyclopediaTest extends TestCase{
    use DatabaseMigrations;
    use RefreshDatabase;

    public $user;

    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);
        $this->user = User::factory()->create([
            'active'    =>  true
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  1,
            'published'     =>  true
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  2,
            'published'     =>  false
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  3,
            'published'     =>  true
        ]);
        
        Bundle::factory()->create([
            'active'        =>  true,
            'plan_id'   =>  641598
        ]);
        BundleItem::factory()->create([
            'encyclopedia_id'           =>  1,
            'bundle_id'                 =>  1
        ]);
        BundleItem::factory()->create([
            'encyclopedia_id'           =>  6,
            'bundle_id'                 =>  1
        ]);

        Passport::actingAs($this->user);
    }

    /** @test **/
    public function user_can_access_encyclopedia_when_subscribed_and_published() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/1');

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
    public function user_cannot_access_encyclopedia_when_unpublished() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('GET', '/api/data/encyclopedias/2');
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>  [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_cannot_access_when_unsubscribed() {
        $response = $this->json('GET', '/api/data/encyclopedias/3');

        $response->assertStatus(450);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>  [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_get_error_when_encyclopedia_doesnt_exist() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('GET', '/api/data/encyclopedias/55');
    }

    /** @test **/
    public function encyclopedia_non_parent_lists_correct_laws() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        Law::factory()->count(10)->create([
            'encyclopedia_id'       =>      1
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/1');
        $res = json_decode($response->getContent())->data;

        $response->assertStatus(200);
        $this->assertFalse($res->parent);
        $this->assertEquals(count($res->payload->data), 10);
    }

    /** @test **/
    public function encyclopedia_parent_shows_correct_encyclopedia_children() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        Encyclopedia::factory()->create([
            'id'        =>      6,
            'is_parent' =>      true
        ]);

        Encyclopedia::factory()->count(10)->create([
            'parent_id'       =>      6
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/6');
        $res = json_decode($response->getContent())->data;

        $response->assertStatus(200);
        $this->assertTrue($res->parent);
        $this->assertEquals(count($res->payload), 10);
    }

    /** @test **/
    public function user_can_view_law() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        Law::factory()->create([
            'id'                     =>     1,
            'encyclopedia_id'        =>     1
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/1/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'law',
                'encyclopedia'
            ]
        ]);
    }

    /** @test **/
    public function user_cannot_view_laws_when_unsubscribed_to_encyclopedia() {
        Law::factory()->create([
            'id'                     =>     1,
            'encyclopedia_id'        =>     3
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/3/1');

        $response->assertStatus(450);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message' => [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_cannot_view_law_when_unsubscribed_to_nested_encyclopedia() {
        Encyclopedia::factory()->create([
            'id'                     =>     200,
            'is_parent'              =>     true
        ]);

        Encyclopedia::factory()->create([
            'id'                     =>     201,
            'parent_id'              =>     200
        ]);

        $law = Law::factory()->create([
            'encyclopedia_id'        =>     201
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/201/'.$law->id);

        $response->assertStatus(450);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message' => [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }
    
    /** @test **/
    public function user_cannot_access_law_when_unpublished() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        Law::factory()->create([
            'id'                     =>     1,
            'encyclopedia_id'        =>     1,
            'published'              =>     false
        ]);

        $response = $this->json('GET', '/api/data/encyclopedias/1/1');
    }
}
