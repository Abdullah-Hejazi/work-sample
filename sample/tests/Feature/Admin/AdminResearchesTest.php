<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use App\User;
use Carbon\Carbon;
use App\OauthAccessToken;

use App\Role;
use App\Encyclopedia;
use App\AdministrationTitle;
use App\Research;

class AdminResearchesTest extends TestCase{
    use DatabaseMigrations;
    use RefreshDatabase;

    public $admin;

    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);

        Role::factory()->create([
            'id'        =>      1,
            'admin'     =>      1
        ]);

        Role::factory()->create([
            'id'        =>      2,
        ]);


        $this->admin = User::factory()->create([
            'role'  =>  1
        ]);

        Research::factory()->count(3)->create();

        Passport::actingAs($this->admin);
    }

    /** @test **/
    public function admin_can_list_researches(){
        $response = $this->json('GET', '/api/admin/researches');

        $data = json_decode($response->getContent());

        $this->assertEquals(count($data->data), 3);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_retrieve_research_by_id(){
        $response = $this->json('GET', '/api/admin/researches/2');

        $response->assertStatus(200);

        $data = json_decode($response->getContent());

        $this->assertEquals(\App\Research::find(2)->title, $data->data->research->title);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'research'
            ]
        ]);
    }
    
    /** @test **/
    public function admin_get_error_when_retrieving_non_existing_research(){
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');

        $response = $this->json('GET', '/api/admin/researches/55');
    }
    
    /** @test **/
    public function admin_can_edit_research(){
        $response = $this->json('POST', '/api/admin/researches/1/edit', [
            'title'     =>      'test-title',
            'text'      =>      'test-text',
            'normal_text'   =>  'test-text-normal',
            'remove_image'  =>  true
        ]);

        $response->assertStatus(200);

        $data = json_decode($response->getContent());

        $this->assertEquals('test-title', \App\Research::find(1)->title);
        $this->assertEquals('test-text', \App\Research::find(1)->text);
        $this->assertEquals('test-text-normal', \App\Research::find(1)->normal_text);
        $this->assertNull(\App\Research::find(1)->image);

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
    public function admin_can_add_new_research(){
        $response = $this->json('POST', '/api/admin/research/new', [
            'title'     =>      'test-title',
            'text'      =>      'test-text',
            'normal_text'   =>  'test-text-normal'
        ]);

        $response->assertStatus(200);

        $data = json_decode($response->getContent());

        $this->assertEquals('test-title', \App\Research::find(4)->title);
        $this->assertEquals('test-text', \App\Research::find(4)->text);
        $this->assertEquals('test-text-normal', \App\Research::find(4)->normal_text);
        $this->assertNull(\App\Research::find(4)->image);

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
    public function admin_can_delete_research(){
        $response = $this->json('POST', '/api/admin/researches/1/delete');

        $response->assertStatus(200);

        $data = json_decode($response->getContent());

        $this->assertNull(\App\Research::where('id', 1)->first());

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
    
}
