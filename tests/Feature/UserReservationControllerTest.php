<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function itListsReservationsThatBelongToTheUser()
    {
        $user = User::factory()->create();
        $reservation = Reservation::factory()->for($user)->create();

        $image = $reservation->office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        $reservation->office()->update(['featured_image_id' => $image->id]);

        Reservation::factory(2)->for($user)->create();
        Reservation::factory(3)->create();

        Sanctum::actingAs($user, ['reservations.show']);

        $response = $this->get('/api/reservations');

        $response->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'office']]])
            ->assertJsonPath('data.0.office.featured_image.id', $image->id);
    }
}
