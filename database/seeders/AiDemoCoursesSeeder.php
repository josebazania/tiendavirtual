<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiDemoCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'title' => 'Introducción a la inteligencia artificial (demo)',
                'description' => 'Conoce los conceptos básicos de la inteligencia artificial, sus aplicaciones y sus límites mediante ejemplos sencillos. Curso de demostración para probar la tienda.',
                'price_cents' => 3900,
                'lessons' => [
                    ['¿Qué es la inteligencia artificial?', 'La inteligencia artificial reúne técnicas que permiten a los sistemas realizar tareas como clasificar información, reconocer patrones o generar contenido. Ejercicio: identifica una aplicación de IA que uses a diario.'],
                    ['Aprendizaje automático y datos', 'El aprendizaje automático utiliza ejemplos para ajustar modelos que identifican patrones. La calidad y representatividad de los datos influyen en los resultados.'],
                    ['Uso responsable', 'Comprueba la información generada, protege los datos personales y considera posibles errores o sesgos antes de tomar decisiones.'],
                ],
            ],
            [
                'title' => 'Escritura de instrucciones para IA generativa (demo)',
                'description' => 'Practica cómo formular instrucciones claras, aportar contexto y evaluar respuestas de herramientas de IA generativa. Curso de demostración para probar la tienda.',
                'price_cents' => 5900,
                'lessons' => [
                    ['Define el objetivo', 'Describe la tarea, la audiencia y el resultado deseado. Por ejemplo: redacta un correo breve para invitar a clientes a un taller.'],
                    ['Añade contexto y restricciones', 'Proporciona información relevante, tono, extensión y formato. No incluyas información privada que no debas compartir.'],
                    ['Revisa y mejora', 'Comprueba hechos y claridad; después ajusta las instrucciones y compara los resultados. Una respuesta convincente puede contener errores.'],
                ],
            ],
            [
                'title' => 'IA para productividad y trabajo diario (demo)',
                'description' => 'Explora usos prácticos de la IA para organizar ideas, resumir información y preparar borradores, con revisión humana en cada paso. Curso de demostración para probar la tienda.',
                'price_cents' => 7900,
                'lessons' => [
                    ['Organiza tus tareas', 'Pide a una herramienta que agrupe una lista de tareas por prioridad. Revisa sus sugerencias y decide qué hacer primero.'],
                    ['Resume y compara', 'Practica con un texto público: solicita un resumen y una lista de ideas principales. Contrasta siempre la respuesta con el documento original.'],
                    ['Crea un borrador', 'Genera un primer borrador de una propuesta y edítalo para adecuarlo al contexto, verificar sus datos y añadir tu criterio.'],
                ],
            ],
        ];

        DB::transaction(function () use ($courses): void {
            foreach ($courses as $data) {
                $course = Course::firstOrCreate(['title' => $data['title']], [
                    'description' => $data['description'],
                    'price_cents' => $data['price_cents'],
                    'published' => true,
                ]);

                if (! $course->wasRecentlyCreated) {
                    continue;
                }

                foreach ($data['lessons'] as $index => [$title, $content]) {
                    $course->lessons()->create(['title' => $title, 'content' => $content, 'position' => $index + 1]);
                }
            }
        });
    }
}
