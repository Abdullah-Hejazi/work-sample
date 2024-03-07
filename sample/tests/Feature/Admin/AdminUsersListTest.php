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

use App\Bundle;
use App\Role;
use App\Models\AlrayaCoupon;

class AdminUsersListTest extends TestCase{
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

        $this->admin = User::factory()->count(2)->create([
            'banned'  =>  true
        ]);


        $this->admin = User::factory()->create([
            'employee'  =>  true,
            'role'  =>  1
        ]);

        Passport::actingAs($this->admin);

        User::factory()->count(3)->create([
            'role'      =>      2,
            'employee'  =>      true
        ]);

        for($i = 1; $i <= 6; $i++) {
            User::find($i)->subscriptions()->create([
                'name'          =>      'test',
                'billable_type' =>      'App\\User',
                'paddle_id'     =>      641598 + $i,
                'paddle_plan'   =>      641598,
                'paddle_status' =>      'active',
                'quantity'      =>      0,
                'ends_at'       =>      now()->addDays(2),
                'created_at'    =>      now()->subDays(1)
            ]);
        }

        User::limit(3)->update([
            'created_at'        =>      '2020-01-01'
        ]);

        User::factory()->count(10)->create();
        User::factory()->count(10)->create([
            'active'    =>      1
        ]);

        AlrayaCoupon::factory()->create([
            'activator_id'  =>  1,
            'activated_at'  =>  now()
        ]);
    }

    /** @test **/
    public function admin_can_index_users(){
        $response = $this->json('GET', '/api/admin/list/users');

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 10);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_index_users_pagination(){
        $response = $this->json('GET', '/api/admin/list/users?page=2');

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 10);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_search_users_by_email(){
        $response = $this->json('POST', '/api/admin/list/users/search', [
            'search'        =>      $this->admin->email,
            'option'        =>      'email'
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 1);
        $this->assertEquals($this->admin->id, $data[0]->id);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_search_users_by_full_name(){
        $response = $this->json('POST', '/api/admin/list/users/search', [
            'search'        =>      $this->admin->full_name,
            'option'        =>      'full_name'
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 1);
        $this->assertEquals($this->admin->id, $data[0]->id);
 
        $response->assertStatus(200);
    }
    
    /** @test **/
    public function admin_can_search_users_by_phonenumber(){
        $response = $this->json('POST', '/api/admin/list/users/search', [
            'search'        =>      $this->admin->phone_number,
            'option'        =>      'phone_number'
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 1);
        $this->assertEquals($this->admin->id, $data[0]->id);
 
        $response->assertStatus(200);
    }
     
    /** @test **/
    public function admin_cannot_search_users_when_search_empty(){
        $this->expectException('Illuminate\Validation\ValidationException');

        $response = $this->json('POST', '/api/admin/list/users/search', [
            'option'        =>      'phone_number'
        ]);
    }

    /** @test **/
    public function admin_cannot_search_users_when_option_is_wrong(){
        $response = $this->json('POST', '/api/admin/list/users/search', [
            'search'        =>      $this->admin->email,
            'option'        =>      'dwadwa'
        ]);
 
        $response->assertStatus(450);
    }

    /** @test **/
    public function admin_can_filter_users_by_active(){
        $response = $this->json('POST', '/api/admin/list/users/filter', [
            'active'        =>      1,
            'employee'      =>      0,
            'banned'        =>      0,
            'non_employees' =>      0,
            'alrayacoupons' =>      0
        ]);
 
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 10);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_filter_users_by_employee(){
        $response = $this->json('POST', '/api/admin/list/users/filter', [
            'active'        =>      0,
            'employee'      =>      1,
            'banned'        =>      0,
            'non_employees' =>      0,
            'alrayacoupons' =>      0
        ]);
 
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 4);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_filter_users_by_banned(){
        $response = $this->json('POST', '/api/admin/list/users/filter', [
            'active'        =>      0,
            'employee'      =>      0,
            'banned'        =>      1,
            'non_employees' =>      0,
            'alrayacoupons' =>      0
        ]);
 
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 2);
 
        $response->assertStatus(200);
    }
    
    /** @test **/
    public function admin_can_filter_users_by_non_employees(){
        $response = $this->json('POST', '/api/admin/list/users/filter?page=3', [
            'active'        =>      0,
            'employee'      =>      0,
            'banned'        =>      0,
            'non_employees' =>      1,
            'alrayacoupons' =>      0
        ]);
 
        $data = json_decode($response->getContent())->data;

        //last page of pagination will return 3, cuz they're 23 non employees
        $this->assertEquals(count($data), 2);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_filter_users_by_alrayacoupon_users(){
        $response = $this->json('POST', '/api/admin/list/users/filter', [
            'active'        =>      0,
            'employee'      =>      0,
            'banned'        =>      0,
            'non_employees' =>      0,
            'alrayacoupons' =>      1
        ]);
 
        $data = json_decode($response->getContent())->data;

        //last page of pagination will return 3, cuz they're 23 non employees
        $this->assertEquals(count($data), 1);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_get_list_of_subscribers(){
        $response = $this->json('GET', '/api/admin/list/users/subscribers');
 
        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 6);
 
        $response->assertStatus(200);
    }

    /** @test **/
    public function admin_can_filter_users_by_join_date_interval(){
        $response = $this->json('POST', '/api/admin/list/users/interval', [
            'start'        =>      '2019-01-01',
            'end'          =>      '2020-12-12'
        ]);

        $data = json_decode($response->getContent())->data;

        $this->assertEquals(count($data), 3);
 
        $response->assertStatus(200);
    }
}
