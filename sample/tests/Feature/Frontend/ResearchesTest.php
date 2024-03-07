<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Research;
use NunoMaduro\LaravelMojito\InteractsWithViews;

class ResearchesTest extends TestCase {
    use DatabaseMigrations;
    use RefreshDatabase;
    use InteractsWithViews;

    public function setUp(): void {
        parent::setUp();

        \Artisan::call('passport:install',['-vvv' => true]);

        Research::factory()->count(5)->create();
        Research::factory()->create([
            'image'     =>      'researches/test.jpg'
        ]);
    }

    /** @test **/
    public function test_researches_get_listed_with_correct_links() {
        $response = $this->get('/researches');

        $response->assertStatus(200);
        
        for($i = 0; $i < 6; $i++) {
            $index = ($i * 2) + 1;
            $id = $i+1;
            $response->assertView()->in('#researches')->at('a', $index)->hasLink('/researches/'.$id.'/'.make_slug(Research::orderBy('id')->get()[$i]->title));
        }
    }

    /** @test **/
    public function test_researches_get_listed_with_correct_images() {
        \Storage::disk('public')->put('researches/test.jpg', \File::get(public_path() . '/images/research.jpg'));
        $response = $this->get('/researches');

        $response->assertStatus(200);

        for($i = 0; $i < 6; $i++) {
            $research = Research::orderBy('id')->get()[$i]->image;
            if ($research){
                $response->assertView()->in('#researches')->at('img', $i)->hasAttribute('src', \Storage::url($research));
            } else {
                $response->assertView()->in('#researches')->at('img', $i)->hasAttribute('src', url('images/research.jpg'));
            }
        }
    }

    /** @test **/
    public function test_researches_get_listed_with_correct_titles() {
        $response = $this->get('/researches');

        $response->assertStatus(200);
        
        for($i = 0; $i < 6; $i++) {
            $research = Research::orderBy('id')->get()[$i]->title;
            $response->assertView()->in('#researches')->at('h6 a', $i)->contains($research);
        }
    }
    
    /** @test **/
    public function test_researches_get_listed_with_correct_text() {
        $response = $this->get('/researches');

        $response->assertStatus(200);
        
        for($i = 0; $i < 6; $i++) {
            $research = \Str::limit(Research::orderBy('id')->get()[$i]->normal_text, 150, '...');

            $response->assertView()->in('#researches')->at('small', $i)->contains($research);
        }
    }
    
    /** @test **/
    public function test_research_title_correct() {
        $response = $this->get('/researches/1');

        $response->assertStatus(200);
        
        
        $research = Research::orderBy('id')->where('id', 1)->first();

        $response->assertView()->in('body')->first('h1')->contains($research->title);
    }

    /** @test **/
    public function test_research_text_correct() {
        $response = $this->get('/researches/1');

        $response->assertStatus(200);
        
        
        $research = Research::orderBy('id')->where('id', 1)->first();

        $response->assertView()->in('body')->first('#research_body')->contains($research->text);
    }

    /** @test **/
    public function test_research_meta_image_when_null_correct() {
        $response = $this->get('/researches/1');

        $response->assertStatus(200);

        $response->assertView()->hasMeta(['content' => url('images/research.jpg')]);
    }
    
    /** @test **/
    public function test_research_meta_image_when_not_null_correct() {
        $response = $this->get('/researches/6');

        $response->assertStatus(200);
        
        
        $research = Research::orderBy('id')->where('id', 6)->first();

        $response->assertView()->hasMeta(['content' => url(\Storage::url($research->image))]);
    }
}