<?php

namespace Tests\Feature;

use App\Models\Tag;
use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Notifications\OfficePendingApproval;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class OfficeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

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
    public function itListsOfficesIncludingHiddenAndUnApprovedIfFilteringForTheCurrentLoggedInUser()
    {
        $user = User::factory()->create();

        Office::factory(3)->for($user)->create();

        Office::factory()->hidden()->for($user)->create();
        Office::factory()->pending()->for($user)->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->get('/api/offices?user_id='.$user->id);

        $response->assertOk()
            ->assertJsonCount(5, 'data');
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

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $office->id);
    }

    /**
     * @test
     */
    public function itFiltersByVisitorId()
    {
        Office::factory(3)->create();

        $user = User::factory()->create();

        $office = Office::factory()->create();
        Reservation::factory()->for(Office::factory())->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get(
            '/api/offices?visitor_id='.$user->id
        );
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

        Reservation::factory(3)->for($office)->create();
        Reservation::factory()->for($office)->cancelled()->create();

        $response = $this->get('/api/offices');

        $response->assertOk()
            ->assertJsonPath('data.0.reservations_count', 3);
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
        $office = Office::factory()->for($user)->hasTags(3)->hasImages(2)->create();

        Reservation::factory()->for($office)->create();
        Reservation::factory()->for($office)->cancelled()->create();

        $response = $this->get('/api/offices/'.$office->id);

        $response->assertOk()
            ->assertJsonPath('data.reservations_count', 1)
            ->assertJsonCount(3, 'data.tags')
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.user.id', $user->id);
    }
    
    /**
     * @test
     */
    public function itCreatesAnOffice()
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $user = User::factory()->create();
        $tags = Tag::factory(2)->create();

        //$this->actingAs($user);
        Sanctum::actingAs($user, ['office.create']);

        $response = $this->postJson('/api/offices', Office::factory()->raw([
            'tags' => $tags->pluck('id')->toArray()
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.approval_status', Office::APPROVAL_PENDING)
            ->assertJsonPath('data.reservations_count', 0)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonCount(2, 'data.tags');

            $this->assertDatabaseHas('offices', [
                'id' => $response->json('data.id')
            ]);

        Notification::assertSentTo($admin, OfficePendingApproval::class);
    }

    /**
     * @test
     */
    public function itDoesntAllowCreatingIfScopeIsNotProvided()
    {
        $user = User::factory()->create();

        //$token = $user->createToken('test', []);
        Sanctum::actingAs($user, []);

        $response = $this->postJson('/api/offices');

        $response->assertForbidden();
        //$response->assertStatus(403);
    }

    /**
     * @test
     */
    public function itAllowsCreatingIfScopeIsProvided()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['office.create']);

        $response = $this->postJson('/api/offices');

        //$this->assertNotEquals(403, $response->status());
        $this->assertFalse($response->isForbidden());
    }

    /**
     * @test
     */
    public function itUpdatesAnOffice()
    {
        $user = User::factory()->create();
        $tags = Tag::factory(3)->create();
        $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tags);

        $anotherTag = Tag::factory()->create();

        Sanctum::actingAs($user, ['office.update']);

        $response = $this->putJson('/api/offices/' . $office->id, Office::factory()->raw([
            'title' => 'Another title',
            'tags' => [$tags[0]->id, $anotherTag->id]
        ]));

        //dd($response->json('data'));

        $response->assertOk()
            ->assertJsonCount(2, 'data.tags')
            ->assertJsonPath('data.title', 'Another title')
            ->assertJsonPath('data.tags.0.id', $tags[0]->id)
            ->assertJsonPath('data.tags.1.id', $anotherTag->id);
    }

    /**
     * @test
     */
    public function itDoesntUpdateOfficeThatDoesntBelongToUser()
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $office = Office::factory()->for($anotherUser)->create();

        Sanctum::actingAs($user, ['office.update']);

        $response = $this->putJson('/api/offices/' . $office->id, Office::factory()->raw());

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function itMarksTheOfficeAsPendingIfDirty()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Notification::fake();

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/offices/' . $office->id, [
            'lat' => 40.74051727562952
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('offices', [
            'id' => $office->id,
            'approval_status' => Office::APPROVAL_PENDING,
        ]);

        Notification::assertSentTo($admin, OfficePendingApproval::class);
    }

    /**
     * @test
     */
    public function itCanDeleteOffices()
    {
        Storage::put('/office_image.jpg', 'empty');
        
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $image = $office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        Sanctum::actingAs($user, ['office.delete']);

        $response = $this->deleteJson('/api/offices/' . $office->id);

        $response->assertOk();

        $this->assertSoftDeleted($office);
        $this->assertModelMissing($image);
        Storage::assertMissing('office_image.jpg');
    }

    /**
     * @test
     */
    public function itCannotDeleteAnOfficeThatHasReservations()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        Reservation::factory(3)->for($office)->create();

        Sanctum::actingAs($user, ['office.delete']);

        $response = $this->deleteJson('/api/offices/'.$office->id);

        $response->assertUnprocessable();

        /*$this->assertDatabaseHas('offices', [
            'id' => $office->id,
            'deleted_at' => null
        ]);*/

        $this->assertNotSoftDeleted($office);
    }

    /**
     * @test
     */
    public function itUpdatedTheFeaturedImageOfAnOffice()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $image = $office->images()->create([
            'path' => 'image.jpg'
        ]);

        Sanctum::actingAs($user, ['office.update']);

        $response = $this->putJson('/api/offices/'.$office->id, [
            'featured_image_id' => $image->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.featured_image_id', $image->id);
    }

    /**
     * @test
     */
    public function itDoesntUpdateFeaturedImageThatBelongsToAnotherOffice()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();
        $office2 = Office::factory()->for($user)->create();

        $image = $office2->images()->create([
            'path' => 'image.jpg'
        ]);

        Sanctum::actingAs($user, ['office.update']);

        $response = $this->putJson('/api/offices/'.$office->id, [
            'featured_image_id' => $image->id,
        ]);

        $response->assertUnprocessable()
            ->assertInvalid('featured_image_id');
    }
}
