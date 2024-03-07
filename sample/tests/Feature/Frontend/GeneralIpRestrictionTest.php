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
use App\Bundle;
use App\BundleItem;
use App\Models\IpRestriction;
use App\Law;

class GeneralIpRestrictionTest extends TestCase{
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

        Passport::actingAs($this->user);
    }

    
    /** @test **/
    public function user_cannot_access_encyclopedia_when_not_subscribed_and_when_not_ip_allowed() {
        $response = $this->withHeaders([
            'REMOTE_ADDR' => '50.50.50.50',
        ])->json('GET', '/api/data/encyclopedias/1');

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
    public function user_can_access_encyclopedia_when_not_subscribed_but_in_allowed_ip() {
        IpRestriction::factory(1)->create([
            'ip'    =>  '50.50.50.50',
            'general'   =>  true
        ]);

        $response = $this->withHeaders([
            'REMOTE_ADDR' => '50.50.50.50',
        ])->json('GET', '/api/data/encyclopedias/1');

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
    public function user_can_access_law_when_not_subscribed_but_in_allowed_ip() {
        IpRestriction::factory(1)->create([
            'ip'    =>  '50.50.50.50',
            'general'   =>  true
        ]);

        $response = $this->withHeaders([
            'REMOTE_ADDR' => '50.50.50.50',
        ])->json('GET', '/api/data/encyclopedias/1');

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
}
