<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminRecieveReportNotification;

use App\User;
use App\Encyclopedia;
use Carbon\Carbon;
use App\Law;
use App\Bundle;
use App\BundleItem;

class ReportTest extends TestCase {
    use DatabaseMigrations;
    use RefreshDatabase;

    public $user;

    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);
        $this->user = User::factory()->create([
            'active'    =>  true
        ]);

        User::factory()->create([
            'id'        =>  2,
            'employee'    =>  true,
            'role'          =>  1
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

        Passport::actingAs($this->user);
    }

    /** @test **/
    public function user_can_report() {
        Notification::fake();
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('POST', '/api/data/report', [
            'title'     =>      'test title',
            'text'      =>      'test text',
            'type'      =>      'suggestion'
        ]);

        Notification::assertSentTo(
            [User::find(2)], AdminRecieveReportNotification::class
        );

        $response->assertStatus(200);
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

        $this->assertDatabaseHas('reports', [
            'title' => 'test title',
            'text'      =>      'test text',
            'type'      =>      'suggestion'
        ]);
    }

    /** @test **/
    public function user_get_error_when_missing_data() {
        Notification::fake();
        $this->user->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $this->expectException('Illuminate\Validation\ValidationException');
        $response = $this->json('POST', '/api/data/report', [
            'title'     =>      'test title',
            'text'      =>      'test text'
        ]);
    }

    /** @test **/
    public function user_can_report_law_when_subscribed() {
        Notification::fake();
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
            'id'                    =>      1,
            'encyclopedia_id'       =>      1
        ]);

        $response = $this->json('POST', '/api/data/report/1/1', [
            'title'     =>      'test title',
            'text'      =>      'test text',
            'type'      =>      'suggestion'
        ]);

        Notification::assertSentTo(
            [User::find(2)], AdminRecieveReportNotification::class
        );

        $response->assertStatus(200);
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
        $this->assertDatabaseHas('reports', [
            'title'                 =>      'test title',
            'text'                  =>      'test text',
            'type'                  =>      'suggestion',
            'law_id'                =>      1,
            'encyclopedia_id'       =>      1,
            'user_id'               =>      $this->user->id
        ]);
    }

    /** @test **/
    public function user_cannot_report_law_when_unsubscribed() {
        Notification::fake();
        Law::factory()->create([
            'id'                    =>      1,
            'encyclopedia_id'       =>      3
        ]);

        $response = $this->json('POST', '/api/data/report/3/1', [
            'title'     =>      'test title',
            'text'      =>      'test text',
            'type'      =>      'suggestion'
        ]);

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
    public function user_cannot_report_law_when_encyclopedia_unpublished() {
        Notification::fake();
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        Law::factory()->create([
            'id'                    =>      1,
            'encyclopedia_id'       =>      2
        ]);

        $response = $this->json('POST', '/api/data/report/2/1', [
            'title'     =>      'test title',
            'text'      =>      'test text',
            'type'      =>      'suggestion'
        ]);
    }

    /** @test **/
    public function user_cannot_report_law_when_law_unpublished() {
        Notification::fake();
        $this->expectException('Illuminate\Database\Eloquent\ModelNotFoundException');
        Law::factory()->create([
            'id'                    =>      1,
            'encyclopedia_id'       =>      1,
            'published'             =>      false
        ]);

        $response = $this->json('POST', '/api/data/report/1/1', [
            'title'     =>      'test title',
            'text'      =>      'test text',
            'type'      =>      'suggestion'
        ]);
    }
}
