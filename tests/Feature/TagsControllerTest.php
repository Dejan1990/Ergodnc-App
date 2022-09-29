<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagsControllerTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */

    public function itListsTags()
    {
        $response = $this->get('/api/tags');

        //dd($response->json('data'));

        $response->assertOk();
        $this->assertNotNull($response->json('data')[0]['id']);
    }
}
