<?php

namespace Tests\Feature\Admin;

use App\Mail\VerificationSuccessEmail;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Passport\Passport;
use App\User;
use Illuminate\Support\Facades\Mail;

use App\Bundle;
use App\Role;

class AdminSingleUserTest extends TestCase{
    use DatabaseMigrations;
    use RefreshDatabase;

    public $admin;
    public $user;

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

        $this->user = User::factory()->create([
            'id'    =>      2
        ]);

        Bundle::factory()->count(2)->create([
            'active'    =>  true
        ]);

        Passport::actingAs($this->admin);
    }

    /** @test **/
    public function admin_can_get_user_data(){
        $response = $this->json('GET', '/api/admin/users/2');

        $data = json_decode($response->getContent());

        $this->assertEquals($data->user->id, 2);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_get_bundles_when_getting_user_data(){
        $response = $this->json('GET', '/api/admin/users/2');

        $data = json_decode($response->getContent())->bundles;

        $this->assertEquals(count($data), 2);
 
        $response->assertStatus(200);
    }
    
    /** @test **/
    public function admin_can_get_demo_when_getting_user_data(){
        User::find(2)->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        User::find(2)->createAsCustomer([
            'trial_ends_at' => now()->addDays(2)
        ]);

        $response = $this->json('GET', '/api/admin/users/2');

        $data = json_decode($response->getContent())->demo;

        $this->assertTrue($data);
 
        $response->assertStatus(200);
    }
    
    /** @test **/
    public function admin_can_get_correct_demo_when_user_doesnt_have(){
        $response = $this->json('GET', '/api/admin/users/2');

        $data = json_decode($response->getContent())->demo;

        $this->assertFalse($data);
    }
    
    /** @test **/
    public function admin_can_get_correct_subscribed_status_when_user_subscribed(){
        User::find(2)->subscriptions()->create([
            'name'          =>      'test',
            'billable_type' =>      'App\\User',
            'paddle_id'     =>      641598,
            'paddle_plan'   =>      641598,
            'paddle_status' =>      'active',
            'quantity'      =>      0,
            'ends_at'       =>      now()->addDays(2)
        ]);

        $response = $this->json('GET', '/api/admin/users/2');

        $data = json_decode($response->getContent())->subscribed;

        $this->assertNotNull($data);
 
        $response->assertStatus(200);
    }
    
    /** @test **/
    public function admin_can_get_correct_subscribed_status_when_user_not_subscribed(){
        $response = $this->json('GET', '/api/admin/users/2');

        $data = json_decode($response->getContent())->subscribed;

        $this->assertNull($data);
 
        $response->assertStatus(200);
    }
    
    /** @test **/
    public function admin_can_edit_all_user_data(){
        $response = $this->json('POST', '/api/admin/users/2/edit', [
            'full_name'     => 'test1',
            'email'         => 'test@gmail.com',
            'phone_number'  => 5112314,
            'country'       => 'Egypt',
            'city'          => 'Cairo',
            'birthday'      => '1997-10-01',
            'job'           => 'testjob'
        ]);

        $response->assertStatus(200);

        $user = User::find(2);

        $this->assertEquals($user->full_name, 'test1');
        $this->assertEquals($user->email, 'test@gmail.com');
        $this->assertEquals($user->phone_number, 5112314);
        $this->assertEquals($user->country, 'Egypt');
        $this->assertEquals($user->job, 'testjob');

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function admin_can_edit_some_user_data(){
        $response = $this->json('POST', '/api/admin/users/2/edit', [
            'full_name'     => 'test5',
            'email'         => 'test2@gmail.com',
            'phone_number'  => 5112324,
            'country'       => 'Egypt1',
            'birthday'      => '1997-10-02'
        ]);

        $response->assertStatus(200);

        $user = User::find(2);

        $this->assertEquals($user->full_name, 'test5');
        $this->assertEquals($user->email, 'test2@gmail.com');
        $this->assertEquals($user->phone_number, 5112324);
        $this->assertEquals($user->country, 'Egypt1');
        $this->assertEquals($user->birthday, '1997-10-02');
        $this->assertEquals($user->job, $this->user->job);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }
    
    /** @test **/
    public function admin_can_change_user_password(){
        $response = $this->json('POST', '/api/admin/users/2/edit/password', [
            'password'                  =>      'test1234',
            'password_confirmation'     =>      'test1234'
        ]);

        $response->assertStatus(200);

        $user = User::find(2);

        $this->assertTrue(\Hash::check('test1234', $user->password));

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function admin_get_error_when_changing_user_password_without_confirmation(){
        $response = $this->json('POST', '/api/admin/users/2/edit/password', [
            'password'                  =>      'test1234'
        ]);

        $response->assertStatus(420);

        $user = User::find(2);

        $this->assertFalse(\Hash::check('test1234', $user->password));

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function admin_can_ban_user(){
        $response = $this->json('POST', '/api/admin/users/2/ban');

        $response->assertStatus(200);

        $user = User::find(2);

        $this->assertTrue((bool)$user->banned);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }

    /** @test **/
    public function admin_can_unban_user(){
        $response = $this->json('POST', '/api/admin/users/2/unban');

        $response->assertStatus(200);

        $user = User::find(2);

        $this->assertFalse((bool)$user->banned);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }
    
    /** @test **/
    public function admin_can_activate_user(){
        Mail::fake();
        $response = $this->json('POST', '/api/admin/users/2/activate');

        $response->assertStatus(200);

        $user = User::find(2);

        $this->assertTrue((bool)$user->active);

        Mail::assertQueued(VerificationSuccessEmail::class);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }
    
    /** @test **/
    public function admin_can_create_new_user_account(){
        $response = $this->json('POST', '/api/admin/users/new', [
            'full_name'             => 'test1',
            'email'                 => 'test@gmail.com',
            'phone_number'          => 5112314,
            'country'               => 'Egypt',
            'city'                  => 'Cairo',
            'birthday'              => '1997-10-01',
            'password'              =>  'test1234',
            'password_confirmation' =>  'test1234',
            'job'                   => 'testjob'
        ]);

        $response->assertStatus(200);

        $user = User::find(3);

        $this->assertEquals($user->full_name, 'test1');
        $this->assertEquals($user->email, 'test@gmail.com');
        $this->assertEquals($user->phone_number, 5112314);
        $this->assertEquals($user->country, 'Egypt');
        $this->assertEquals($user->job, 'testjob');
        $this->assertEquals($user->active, 1);
        $this->assertNotNull($user->email_verification_token);

        $response->assertJsonStructure([
            'status',
            'data'      =>      [
                'message'   =>      [
                    'title',
                    'text',
                    'icon'
                ]
            ]
        ]);
    }
    
}
