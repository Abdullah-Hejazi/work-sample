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

class EncyclopediaSearchTest extends TestCase {
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
        Law::factory()->count(10)->create([
            'encyclopedia_id'               =>  1,
            'published'                     =>  true,
            'law_number'                    =>  '555',
            'law_text'                      =>  'test text',
            'law_note'                      =>  'test note',
            'law_judgements'                =>  'test judgements',
            'law_explanation'               =>  'test explanation',
            'law_jurisprudence'             =>  'test jurisprudence',
            'law_text_normal'               =>  'test text',
            'law_note_normal'               =>  'test note',
            'law_judgements_normal'         =>  'test judgements',
            'law_explanation_normal'        =>  'test explanation',
            'law_jurisprudence_normal'      =>  'test jurisprudence'
        ]);


        Encyclopedia::factory()->create([
            'id'            =>  2,
            'published'     =>  false
        ]);
        Law::factory()->count(10)->create([
            'encyclopedia_id'               =>  2,
            'published'                     =>  true,
            'law_number'                    =>  '555',
            'law_text'                      =>  'test text',
            'law_note'                      =>  'test note',
            'law_judgements'                =>  'test judgements',
            'law_explanation'               =>  'test explanation',
            'law_jurisprudence'             =>  'test jurisprudence',
            'law_text_normal'               =>  'test text',
            'law_note_normal'               =>  'test note',
            'law_judgements_normal'         =>  'test judgements',
            'law_explanation_normal'        =>  'test explanation',
            'law_jurisprudence_normal'      =>  'test jurisprudence'
        ]);


        Encyclopedia::factory()->create([
            'id'            =>  3,
            'published'     =>  true
        ]);
        Law::factory()->count(10)->create([
            'encyclopedia_id'               =>  3,
            'published'                     =>  true,
            'law_number'                    =>  '555',
            'law_text'                      =>  'test text',
            'law_note'                      =>  'test note',
            'law_judgements'                =>  'test judgements',
            'law_explanation'               =>  'test explanation',
            'law_jurisprudence'             =>  'test jurisprudence',
            'law_text_normal'               =>  'test text',
            'law_note_normal'               =>  'test note',
            'law_judgements_normal'         =>  'test judgements',
            'law_explanation_normal'        =>  'test explanation',
            'law_jurisprudence_normal'      =>  'test jurisprudence'
        ]);
        
        Bundle::factory()->create([
            'active'        =>  true,
            'plan_id'   =>  641598
        ]);
        BundleItem::factory()->create([
            'encyclopedia_id'           =>  1,
            'bundle_id'                 =>  1
        ]);

        Passport::actingAs($this->user);
    }

    /** @test **/
    public function user_can_search_published_encyclopedias() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);
        

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }

    /** @test **/
    public function user_cannot_search_unpublished_encyclopedias() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedias' =>      '[2]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(2)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result), 0);
    }
    
    /** @test **/
    public function user_get_results_only_if_subscribed() {
        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedias' =>      '[1, 3]'
        ]);

        $response->assertStatus(520);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'
            ]
        ]);
    }
    
    /** @test **/
    public function user_get_results_when_searching_law_number() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      '555',
            'field'         =>      'law_number',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }
    
    /** @test **/
    public function user_get_results_when_searching_law_text_normal() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }
    
    /** @test **/
    public function user_get_results_when_searching_law_note_normal() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test note',
            'field'         =>      'law_note_normal',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }
    
    /** @test **/
    public function user_get_results_when_searching_law_judgements_normal() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test judgements',
            'field'         =>      'law_judgements_normal',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }
    
    /** @test **/
    public function user_get_results_when_searching_law_explanation_normal() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test explanation',
            'field'         =>      'law_explanation_normal',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }
    
    /** @test **/
    public function user_get_results_when_searching_law_jurisprudence_normal() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search', [
            'search'        =>      'test jurisprudence',
            'field'         =>      'law_jurisprudence_normal',
            'encyclopedias' =>      '[1]'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'result'
            ]
        ]);

        $encyclopedia = Encyclopedia::find(1)->name;
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data->result->$encyclopedia->data), 10);
    }
    
    /** @test **/
    public function user_can_get_search_page_when_published() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search/1', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedia'  =>      1
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'encid',
            'count',
            'data'
        ]);

        $data = json_decode($response->getContent());

        $this->assertEquals($data->count, 10);
        $this->assertEquals($data->encid, 1);
        $this->assertEquals(count($data->data), 10);
    }
    
    /** @test **/
    public function user_cannot_get_search_page_when_unpublished() {
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/search/1', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedia'  =>      2
        ]);

        $response->assertStatus(520);

        $response->assertJsonStructure([
            'status',
            'data'          =>      [
                'message'   =>  [
                    'title',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function user_cannot_get_search_page_when_unsubscribed() {
        $response = $this->json('POST', '/api/data/search/1', [
            'search'        =>      'test text',
            'field'         =>      'law_text_normal',
            'encyclopedia'  =>      3
        ]);

        $response->assertStatus(520);

        $response->assertJsonStructure([
            'status',
            'data'          =>      [
                'message'   =>  [
                    'title',
                    'icon'
                ]
            ]
        ]);
    }
}
