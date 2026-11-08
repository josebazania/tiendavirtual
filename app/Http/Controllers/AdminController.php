<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function checkAdmin(Request $request): void
    {
        abort_unless($request->user()->is_admin, 403);
    }

    public function index(Request $request): View
    {
        $this->checkAdmin($request);

        return view('admin.index', ['courses' => Course::latest()->get(), 'orders' => Order::with(['course', 'user'])->latest()->limit(100)->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->checkAdmin($request);
        $course = Course::create($this->courseData($request));

        return redirect()->route('admin.courses.edit', $course)->with('success', 'Curso creado.');
    }

    public function edit(Request $request, Course $course): View
    {
        $this->checkAdmin($request);

        return view('admin.edit', ['course' => $course->load('lessons')]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->checkAdmin($request);
        $course->update($this->courseData($request));

        return back()->with('success', 'Curso actualizado.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->checkAdmin($request);

        if (DB::table('orders')->where('course_id', $course->id)->exists() || DB::table('enrollments')->where('course_id', $course->id)->exists()) {
            return back()->withErrors(['course' => 'No se puede eliminar un curso con pedidos o estudiantes inscritos. Puedes despublicarlo desde Editar.']);
        }

        try {
            $course->delete();
        } catch (QueryException $exception) {
            if (! in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                throw $exception;
            }

            return back()->withErrors(['course' => 'No se puede eliminar un curso con pedidos o estudiantes inscritos. Puedes despublicarlo desde Editar.']);
        }

        return redirect()->route('admin.index')->with('success', 'Curso y lecciones eliminados.');
    }

    private function courseData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
        ]);

        return ['title' => $data['title'], 'description' => $data['description'], 'price_cents' => (int) round((float) $data['price'] * 100), 'published' => $request->boolean('published')];
    }

    public function storeLesson(Request $request, Course $course): RedirectResponse
    {
        $this->checkAdmin($request);
        $course->lessons()->create($this->lessonData($request));

        return back()->with('success', 'Lección creada.');
    }

    public function updateLesson(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->checkAdmin($request);
        abort_unless($lesson->course_id === $course->id, 404);
        $lesson->update($this->lessonData($request));

        return back()->with('success', 'Lección actualizada.');
    }

    public function destroyLesson(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->checkAdmin($request);
        abort_unless($lesson->course_id === $course->id, 404);
        $lesson->delete();

        return back()->with('success', 'Lección eliminada.');
    }

    private function lessonData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:50000'],
            'position' => ['required', 'integer', 'min:0', 'max:999999'],
            'video_url' => ['nullable', 'url', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                if ($scheme !== 'https' || ! in_array($host, ['youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
                    $fail('Usa un enlace HTTPS de YouTube o Vimeo.');
                }
            }],
        ]);
    }
}
