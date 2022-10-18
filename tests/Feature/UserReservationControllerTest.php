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

        $reservations = Reservation::factory()->for($user)->createMany([
            [
                'start_date' => '2021-03-01',
                'end_date' => '2021-03-15',
            ],
            [
                'start_date' => '2021-03-25',
                'end_date' => '2021-04-15',
            ],
            [
                'start_date' => '2021-03-25',
                'end_date' => '2021-03-29',
            ],
            [
                'start_date' => '2021-03-01',
                'end_date' => '2021-04-15',
            ],
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

        $response->assertJsonCount(4, 'data');

        $this->assertEquals($reservations->pluck('id')->toArray(), collect($response->json('data'))->pluck('id')->toArray());
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

    /**
     * @test
     */
    public function itMakesReservations()
    {
        $user = User::factory()->create();

        $office = Office::factory()->create([
            'price_per_day' => 1_000,
            'monthly_discount' => 10,
        ]);

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            //'start_date' => now()->addDays(1),
            //'end_date' => now()->addDays(41),
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(40),
        ]);

        $response->assertCreated();

        $response->assertJsonPath('data.price', 36000)
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.office_id', $office->id)
            ->assertJsonPath('data.status', Reservation::STATUS_ACTIVE);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnNonExistingOffice()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => 1000,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(40)
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['office_id' => 'Invalid office_id']);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnOfficeThatBelongsToTheUser()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(41),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'office_id' => 'You cannot make a reservation on your own office'
            ]);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationLessThan2Days()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'end_date' => 'The end date must be a date after start date.'
            ]);
    }

    /**
     * @test
     */
    public function itMakeReservationFor2Days()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
        ]);

        $response->assertCreated();
    }

    /**
     * @test
     */
    public function itCannotMakeReservationThatsConflicting()
    {
        $user = User::factory()->create();

        $fromDate = now()->addDays(2)->toDateString();
        $toDate = now()->addDays(15)->toDateString();

        $office = Office::factory()->create();

        Reservation::factory()->for($office)->create([
            'start_date' => now()->addDays(2),
            'end_date' => $toDate,
        ]);

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            'start_date' => $fromDate,
            'end_date' => $toDate,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'office_id' => 'You cannot make a reservation during this time'
            ]);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnOfficeThatIsPendingOrHidden()
    {
        $user = User::factory()->create();
        $office = Office::factory()->pending()->create();
        $office2 = Office::factory()->hidden()->create();

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(41),
        ]);

        $response2 = $this->postJson('/api/reservations', [
            'office_id' => $office2->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(41),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden office']);

        $response2->assertUnprocessable()
            ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden office']);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnSameDay()
    {
        $user = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($user, ['reservations.make']);

        $response = $this->postJson('/api/reservations', [
            'office_id' => $office->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'start_date' => 'The start date must be a date after today.'
            ]);
    }
}
