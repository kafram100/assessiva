<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentProfilePictureTest extends TestCase
{
    use RefreshDatabase;

    private function createStudent(): User
    {
        return User::create([
            'name' => 'Bismark Adu',
            'username' => 'bismark' . uniqid(),
            'email' => 'student' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::User,
            'status' => UserStatus::Approved,
            'is_active' => true,
        ]);
    }

    public function test_student_can_upload_profile_picture_via_avatar_endpoint(): void
    {
        Storage::fake('public');

        $student = $this->createStudent();
        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($student)
            ->post(route('student.settings.avatar'), [
                'profile_picture' => $file,
            ]);

        $response->assertRedirect(route('student.settings'));
        $student->refresh();

        $this->assertNotNull($student->profile_picture);
        Storage::disk('public')->assertExists($student->profile_picture);
    }

    public function test_student_can_upload_profile_picture_via_settings_update(): void
    {
        Storage::fake('public');

        $student = $this->createStudent();
        $file = UploadedFile::fake()->image('avatar.png', 200, 200);

        $response = $this->actingAs($student)
            ->post(route('student.settings.update'), [
                'name' => 'Bismark Adu',
                'educational_level' => 'Tertiary',
                'level' => '400',
                'profile_picture' => $file,
            ]);

        $response->assertRedirect(route('student.settings'));
        $student->refresh();

        $this->assertNotNull($student->profile_picture);
        Storage::disk('public')->assertExists($student->profile_picture);
    }

    public function test_profile_picture_url_returns_null_when_file_does_not_exist(): void
    {
        $student = $this->createStudent();
        // Set a path that does not exist on disk
        $student->profile_picture = 'avatars/non_existent_file_12345.jpg';
        $student->save();

        // Must return null instead of a broken URL
        $this->assertNull($student->profile_picture_url);
    }

    public function test_student_can_remove_profile_picture(): void
    {
        Storage::fake('public');

        $student = $this->createStudent();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $this->actingAs($student)->post(route('student.settings.avatar'), [
            'profile_picture' => $file,
        ]);

        $student->refresh();
        $this->assertNotNull($student->profile_picture);

        // Now remove it
        $deleteResponse = $this->actingAs($student)->delete(route('student.settings.avatar.remove'));
        $deleteResponse->assertRedirect(route('student.settings'));

        $student->refresh();
        $this->assertNull($student->profile_picture);
        $this->assertNull($student->profile_picture_url);
    }
}
