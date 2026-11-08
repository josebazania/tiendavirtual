<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_only_shows_published_courses(): void
    {
        $published = Course::create(['title' => 'Curso visible', 'description' => 'Aprende', 'price_cents' => 1200, 'published' => true]);
        $draft = Course::create(['title' => 'Curso privado', 'description' => 'Borrador', 'price_cents' => 1200, 'published' => false]);
        $this->get('/')->assertOk()->assertSee('Curso visible')->assertDontSee('Curso privado');
        $this->get(route('courses.show', $published))->assertOk();
        $this->get(route('courses.show', $draft))->assertNotFound();
    }

    public function test_registration_creates_a_student_without_admin_privileges(): void
    {
        $this->post('/registro', ['name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'is_admin' => 1])->assertRedirect('/mis-cursos');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'is_admin' => false]);
    }

    public function test_only_admin_can_manage_courses_and_lessons(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student)->get('/admin')->assertForbidden();
        $this->actingAs($student)->post('/admin/cursos', ['title' => 'No permitido'])->assertForbidden();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin)->post('/admin/cursos', ['title' => 'Diseño', 'description' => 'Aprende diseño', 'price' => '15.50', 'published' => '1'])->assertRedirect();
        $course = Course::where('title', 'Diseño')->firstOrFail();
        $this->assertSame(1550, $course->price_cents);
        $this->post(route('admin.lessons.store', $course), ['title' => 'Inicio', 'position' => 1, 'video_url' => 'https://evil.example/video'])->assertSessionHasErrors('video_url');
        $this->post(route('admin.lessons.store', $course), ['title' => 'Inicio', 'position' => 1, 'video_url' => 'https://youtu.be/abc'])->assertRedirect();
        $this->assertDatabaseHas('lessons', ['course_id' => $course->id, 'title' => 'Inicio']);
    }

    public function test_classroom_requires_enrollment(): void
    {
        $course = Course::create(['title' => 'Curso', 'description' => 'Contenido', 'price_cents' => 1000, 'published' => true]);
        $course->lessons()->create(['title' => 'Lección secreta', 'content' => 'Contenido privado', 'position' => 1]);
        $student = User::factory()->create();
        $this->get(route('courses.learn', $course))->assertRedirect(route('login'));
        $this->actingAs($student)->get(route('courses.learn', $course))->assertForbidden();
        DB::table('enrollments')->insert(['user_id' => $student->id, 'course_id' => $course->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->get(route('courses.learn', $course))->assertOk()->assertSee('Contenido privado');
    }

    public function test_only_admin_can_delete_a_course_and_its_lessons(): void
    {
        $course = Course::create(['title' => 'Curso', 'description' => 'Contenido', 'price_cents' => 1000, 'published' => true]);
        $lesson = $course->lessons()->create(['title' => 'Lección', 'position' => 1]);
        $student = User::factory()->create();
        $this->actingAs($student)->delete(route('admin.courses.destroy', $course))->assertForbidden();
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->delete(route('admin.courses.destroy', $course))->assertRedirect(route('admin.index'));
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_course_with_orders_or_enrollments_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $student = User::factory()->create();
        $ordered = Course::create(['title' => 'Con pedido', 'description' => 'Contenido', 'price_cents' => 1000, 'published' => true]);
        $enrolled = Course::create(['title' => 'Con inscripción', 'description' => 'Contenido', 'price_cents' => 1000, 'published' => true]);
        DB::table('orders')->insert(['reference' => (string) Str::uuid(), 'user_id' => $student->id, 'course_id' => $ordered->id, 'amount_cents' => 1000, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('enrollments')->insert(['user_id' => $student->id, 'course_id' => $enrolled->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($admin)->delete(route('admin.courses.destroy', $ordered))->assertSessionHasErrors('course');
        $this->delete(route('admin.courses.destroy', $enrolled))->assertSessionHasErrors('course');
        $this->assertDatabaseHas('courses', ['id' => $ordered->id]);
        $this->assertDatabaseHas('courses', ['id' => $enrolled->id]);
    }
}
