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
use App\AdministrationTitle;
use App\Bundle;
use App\BundleItem;

class AdministrationEncyclopediaSearchTest extends TestCase {
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
            'published'     =>  true,
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
            'encyclopedia_id'   =>  1,
            'published'         =>  false,
            'law_text_normal'   =>  'test'
        ]);
        AdministrationTitle::factory()->create([
            'id'                =>  2,
            'encyclopedia_id'   =>  1,
            'published'         =>  true,
            'law_text_normal'   =>  'lawtextnormal'
        ]);
        AdministrationTitle::factory()->create([
            'id'                =>  6,
            'encyclopedia_id'   =>  1,
            'published'         =>  true,
            'law_judgements_normal'   =>  'lawjudgementsnormal'
        ]);
        AdministrationTitle::factory()->create([
            'id'                =>  7,
            'encyclopedia_id'   =>  1,
            'published'         =>  true,
            'law_explanation_normal'   =>  'lawexplanationnormal'
        ]);

        AdministrationTitle::factory()->create([
            'id'                =>  3,
            'encyclopedia_id'   =>  2,
            'published'         =>  false,
            'is_parent'         =>  true,
            'law_text_normal'   =>  'test'
        ]);
        AdministrationTitle::factory()->create([
            'id'                =>  4,
            'encyclopedia_id'   =>  2,
            'published'         =>  true,
            'law_text_normal'   =>  'test'
        ]);

        Bundle::factory()->create([
            'active'        =>  true,
            'plan_id'   =>  641598
        ]);
        BundleItem::factory()->create([
            'encyclopedia_id'           =>  1,
            'bundle_id'                 =>  1
        ]);
    }

    /** @test **/
    public function user_cannot_search_unpublished_encyclopedias() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('POST', '/api/data/administration/encyclopedias/2/search/law_text');
    }

    /** @test **/
    public function user_cannot_search_when_unsubscribed() {
        $response = $this->json('POST', '/api/data/administration/encyclopedias/1/search/law_text');

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
    public function user_cannot_search_when_no_search_text() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/administration/encyclopedias/1/search/test');


        $response->assertStatus(420);
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
    public function user_cannot_search_when_wrong_search_type() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/administration/encyclopedias/1/search/test', [
            'search'    =>      'test'
        ]);

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
    public function user_can_search_law_text() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/administration/encyclopedias/1/search/law_text', [
            'search'    =>      'law_text_normal'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'payload',
                'encyclopedia'
            ]
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->payload), 1);
    }

    /** @test **/
    public function user_can_search_law_judgements() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/administration/encyclopedias/1/search/law_judgements', [
            'search'    =>      'lawjudgementsnormal'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'payload',
                'encyclopedia'
            ]
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->payload), 1);
    }
    
    /** @test **/
    public function user_can_search_law_explanation() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/administration/encyclopedias/1/search/law_explanation', [
            'search'    =>      'lawexplanationnormal'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'  =>  [
                'payload',
                'encyclopedia'
            ]
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->payload), 1);
    }
}
