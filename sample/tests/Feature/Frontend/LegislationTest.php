<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Carbon\Carbon;

use App\User;
use App\Bundle;
use App\BundleItem;
use App\Encyclopedia;
use App\LegislationRow;

class LegislationTest extends TestCase {
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
            'published'     =>  true,
            'type'          =>  'legislation'
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  2,
            'published'     =>  false,
            'type'          =>  'legislation'
        ]);

        Encyclopedia::factory()->create([
            'id'            =>  3,
            'published'     =>  true,
            'is_parent'     =>  true,
            'type'          =>  'legislation'
        ]);

        Encyclopedia::factory()->count(2)->create([
            'published'     =>  true,
            'parent_id'     =>  3,
            'type'          =>  'legislation'
        ]);
        Encyclopedia::factory()->count(2)->create([
            'published'     =>  false,
            'parent_id'     =>  3,
            'type'          =>  'legislation'
        ]);

        LegislationRow::factory()->count(10)->create([
            'legislation_id'    =>      1
        ]);

        LegislationRow::factory()->count(10)->create([
            'legislation_id'    =>      1,
            'published'         =>      0
        ]);

        LegislationRow::factory()->create([
            'id'                =>      50,
            'legislation_id'    =>      1,
            'published'         =>      0
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
            'encyclopedia_id'           =>  3,
            'bundle_id'                 =>  1
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

        Passport::actingAs($this->user);
    }
    
    /** @test **/
    public function user_can_access_published_legislations() {
        $response = $this->json('GET', '/api/data/legislations/1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'parent',
                'legislation',
                'data'
            ]
        ]);
    }
    
    /** @test **/
    public function user_cannot_access_unpublished_legislations() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('GET', '/api/data/legislations/2');
    }
    
    /** @test **/
    public function it_returns_correct_published_legislation_rows() {
        $response = $this->json('GET', '/api/data/legislations/1');

        $data = json_decode($response->getContent())->data;

        $response->assertStatus(200);
        $this->assertEquals(count($data->data->data), 10);
    }
    
    /** @test **/
    public function it_returns_correct_child_legislations() {
        $response = $this->json('GET', '/api/data/legislations/3');

        $data = json_decode($response->getContent())->data;

        $response->assertStatus(200);
        $this->assertEquals(count($data->data), 2);
    }

    /** @test **/
    public function user_can_request_specific_legislation_row() {
        $response = $this->json('GET', '/api/data/legislations/row/1');

        $data = json_decode($response->getContent())->data;

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'legislationRow',
                'legislation'
            ]
        ]);
        $this->assertEquals(1, $data->legislationRow->id);
        $this->assertEquals(1, $data->legislation->id);
    }

    /** @test **/
    public function user_get_error_when_legislation_row_doesnt_exist() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('GET', '/api/data/legislations/row/5555');

        $response->assertStatus(404);
    }

    /** @test **/
    public function user_get_error_when_legislation_row_is_unpublished() {
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        $response = $this->json('GET', '/api/data/legislations/row/50');
    }
}
