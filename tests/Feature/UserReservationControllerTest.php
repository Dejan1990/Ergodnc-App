<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class UserReservationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     */
    public function itListsReservationsThatBelongToTheUser()
    {
        $user = User::factory()->create();
        [$reservation] = Reservation::factory(3)->for($user)->create();

        $image = $reservation->office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        $reservation->office()->update(['featured_image_id' => $image->id]);

        Reservation::factory(3)->create();

        Sanctum::actingAs($user, ['reservations.show']);

        $response = $this->get('/api/reservations');

        $response->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'office']]])
            ->assertJsonPath('data.0.office.featured_image.id', $image->id);
    }

    /**
     * @test
     */
    public function itListsReservationFilteredByDateRange()
    {
        $user = User::factory()->create();

        $fromDate = '2021-03-03';
        $toDate = '2021-04-04';

        // Within the date range
        // ...
        $reservation1 = Reservation::factory()->for($user)->create([
            'start_date' => '2021-03-01',
            'end_date' => '2021-03-15',
        ]);

        $reservation2 = Reservation::factory()->for($user)->create([
            'start_date' => '2021-03-25',
            'end_date' => '2021-04-15',
        ]);

        $reservation3 = Reservation::factory()->for($user)->create([
            'start_date' => '2021-03-25',
            'end_date' => '2021-03-29',
        ]);


        // Within the range but belongs to a different user
        // ...
        Reservation::factory()->create([
            'start_date' => '2021-03-25',
            'end_date' => '2021-03-29',
        ]);

        // Outside the date range
        // ...
        Reservation::factory()->for($user)->create([
            'start_date' => '2021-02-25',
            'end_date' => '2021-03-01',
        ]);

        Reservation::factory()->for($user)->create([
            'start_date' => '2021-05-01',
            'end_date' => '2021-05-01',
        ]);

        Sanctum::actingAs($user, ['reservations.show']);

        $response = $this->getJson('/api/reservations?'.http_build_query([
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]));

        $response->assertJsonCount(3, 'data');

        $this->assertEquals([$reservation1->id, $reservation2->id, $reservation3->id], collect($response->json('data'))->pluck('id')->toArray());
    }

    /**
     * @test
     */
    public function itFiltersResultsByStatus()
    {
        $user = User::factory()->create();

        $reservation = Reservation::factory()->for($user)->create();
        $reservation2 = Reservation::factory()->for($user)->cancelled()->create();

        Sanctum::actingAs($user, ['reservations.show']);

        $response = $this->getJson('/api/reservations?'.http_build_query([
            'status' => Reservation::STATUS_ACTIVE,
        ]));

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservation->id);
    }

    /**
     * @test
     */
    public function itFiltersResultsByOffice()
    {
        $user = User::factory()->create();

        $office = Office::factory()->for($user)->create();

        $reservation1 = Reservation::factory()->for($office)->for($user)->create();
        $reservation2 = Reservation::factory()->for($user)->create();

        Sanctum::actingAs($user, ['reservations.show']);

        $response = $this->getJson('/api/reservations?'.http_build_query([
            'office_id' => $office->id
        ]));

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservation1->id);
    }
}
