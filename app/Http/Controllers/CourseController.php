<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        return view('courses.index', ['courses' => Course::where('published', true)->latest()->get()]);
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($course->published, 404);

        $enrolled = $request->user() && DB::table('enrollments')->where('user_id', $request->user()->id)->where('course_id', $course->id)->exists();

        return view('courses.show', compact('course', 'enrolled'));
    }

    public function mine(Request $request): View
    {
        $ids = DB::table('enrollments')->where('user_id', $request->user()->id)->pluck('course_id');

        return view('courses.mine', ['courses' => Course::whereIn('id', $ids)->get()]);
    }

    public function learn(Request $request, Course $course): View
    {
        abort_unless(DB::table('enrollments')->where('user_id', $request->user()->id)->where('course_id', $course->id)->exists(), 403);

        return view('courses.learn', ['course' => $course->load('lessons')]);
    }
}
