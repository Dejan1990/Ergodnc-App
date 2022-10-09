<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Office;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class OfficeImageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function itUploadsAnImageAndStoresItUnderTheOffice()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->post("/api/offices/{$office->id}/images", [
            'image' => UploadedFile::fake()->image('image.jpg')
        ]);

        $response->assertCreated();
        //$response->assertStatus(201);

        Storage::disk('public')->assertExists(
            $response->json('data.path')
        );
    }

    /**
     * @test
     */
    public function itDeletesAnImage()
    {
        Storage::disk('public')->put('/office_image.jpg', 'empty');

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->images()->create([
            'path' => 'image.jpg'
        ]);

        $image = $office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        Sanctum::actingAs($user, ['office.delete']);

        $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");

        $response->assertOk();

        $this->assertModelMissing($image);

        Storage::disk('public')->assertMissing('office_image.jpg');
    }

    /**
     * @test
     */
    public function itDoesntDeleteImageThatBelongsToAnotherResource()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();
        $office2 = Office::factory()->for($user)->create();

        $image = $office2->images()->create([
            'path' => 'office_image.jpg'
        ]);

        Sanctum::actingAs($user, ['office.delete']);

        $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(['image' => 'Cannot delete this image.']);
    }

    /**
     * @test
     */
    public function itDoesntDeleteTheOnlyImage()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $image = $office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        Sanctum::actingAs($user, ['office.delete']);

        $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(['image' => 'Cannot delete the only image.']);
    }

    /**
     * @test
     */
    public function itDoesntDeleteTheFeaturedImage()
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->images()->create([
            'path' => 'image.jpg'
        ]);

        $image = $office->images()->create([
            'path' => 'office_image.jpg'
        ]);

        $office->update(['featured_image_id' => $image->id]);

        Sanctum::actingAs($user, ['office.delete']);

        $response = $this->deleteJson("/api/offices/{$office->id}/images/{$image->id}");

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors(['image' => 'Cannot delete the featured image.']);
    }
}
