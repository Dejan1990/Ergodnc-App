<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfficeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function itListsAllOfficesInPaginatedWay()
    {
        Office::factory(30)->create();

        $response = $this->get('/api/offices');

        //dd($response->json('data'));

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'title']]]);
    }

    /**
     * @test
     */
    public function itOnlyListsOfficesThatAreNotHiddenAndApproved()
    {
        Office::factory(3)->create();

        Office::factory()->pending()->create();
        Office::factory()->hidden()->create();

        $response = $this->get('/api/offices');
        //dd($response->json('data'));
        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /**
     * @test
     */
    public function itFiltersByUserId()
    {
        Office::factory(3)->create();

        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->get('/api/offices?user_id=' . $host->id);

        $response->assertJsonCount(1, 'data');
        //$this->assertEquals($office->id, $response->json('data')[0]['id']);
        $response->assertJsonPath('data.0.id', $office->id);
    }

    /**
     * @test
     */
    public function itFiltersByVisitorId()
    {
        Office::factory(3)->create();

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        Reservation::factory()->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get('/api/offices?visitor_id=' . $user->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $office->id);
    }

    /**
     * @test
     */
    public function itIncludesImagesTagsAndUser()
    {
        $user = User::factory()->create();

        Office::factory()->for($user)->hasTags(2)->hasImages(1)->create();

        $response = $this->get('/api/offices');
        //dd($response->json('data'));

        $response->assertOk()
            ->assertJsonCount(2, 'data.0.tags')
            ->assertJsonCount(1, 'data.0.images')
            //->assertJsonPath('data.0.user_id', $user->id)
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    /**
     * @test
     */
    public function itReturnsTheNumberOfActiveReservations()
    {
        $office = Office::factory()->create();

        Reservation::factory(7)->for($office)->create();
        Reservation::factory()->for($office)->cancelled()->create();

        $response = $this->get('/api/offices');
        //dd($response->json('data'));
        $response->assertOk()
            ->assertJsonPath('data.0.reservations_count', 7);

        //$this->assertEquals(7, $response->json('data')[0]['reservations_count']);
    }

    /**
     * @test
     */
    public function itOrdersByDistanceWhenCoordinatesAreProvided()
    {
        Office::factory()->create([
            'lat' => '39.74051727562952',
            'lng' => '-8.770375324893696',
            'title' => 'Leiria'
        ]);

        Office::factory()->create([
            'lat' => '39.07753883078113',
            'lng' => '-9.281266331143293',
            'title' => 'Torres Vedras'
        ]);

        $response = $this->get('/api/offices?lat=38.720661384644046&lng=-9.16044783453807');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Torres Vedras')
            ->assertJsonPath('data.1.title', 'Leiria');

        $response = $this->get('/api/offices');

        $response->assertOk()
            ->assertJsonPath('data.0.title', 'Leiria')
            ->assertJsonPath('data.1.title', 'Torres Vedras');
    }

    /**
     * @test
     */
    public function itShowsTheOffice()
    {
        $user = User::factory()->create();

        $office = Office::factory()->for($user)->hasTags(2)->hasImages(3)->create();

        Reservation::factory(3)->for($office)->create();
        Reservation::factory()->for($office)->cancelled()->create();

        $response = $this->get('/api/offices/'.$office->id);

        //dd($response->json('data'));

        $response->assertOk()
            ->assertJsonPath('data.reservations_count', 3)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonCount(3, 'data.images')
            ->assertJsonCount(2, 'data.tags');
    }
    
    /**
     * @test
     */
    public function itCreatesAnOffice()
    {
        $user = User::factory()->createQuietly();
        $tag = Tag::factory()->create();
        $tag2 = Tag::factory()->create();

        //$this->actingAs($user);
        Sanctum::actingAs($user, ['office.create']);

        $response = $this->postJson('/api/offices', [
            'title' => 'Office in Arkansas',
            'description' => 'Description',
            'lat' => '39.74051727562952',
            'lng' => '-8.770375324893696',
            'address_line1' => 'address',
            'price_per_day' => 10_000,
            'monthly_discount' => 5,
            'tags' => [
                $tag->id, $tag2->id
            ]
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Office in Arkansas')
            ->assertJsonPath('data.approval_status', Office::APPROVAL_PENDING)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('offices', [
            'title' => 'Office in Arkansas'
        ]);
    }

    /**
     * @test
     */
    public function itDoesntAllowCreatingIfScopeIsNotProvided()
    {
        $user = User::factory()->createQuietly();

        //$token = $user->createToken('test', []);
        Sanctum::actingAs($user, ['']);

        $response = $this->postJson('/api/offices');

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function itAllowsCreatingIfScopeIsProvided()
    {
        $user = User::factory()->createQuietly();

        Sanctum::actingAs($user, ['office.create']);

        $response = $this->postJson('/api/offices');

        $this->assertNotEquals(403, $response->status());
    }
}
