<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    
    /** @test */

    public function itListsTags()
    {
        $response = $this->get('/tags');

        //dd($response->json('data'));

        $response->assertOk();
        $this->assertNotNull($response->json('data')[0]['id']);
    }
}
