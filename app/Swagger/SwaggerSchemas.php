<?php

namespace App\Swagger;

/**
 *   @OA\Schema(
 *     schema="Exam",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Math Exam"),
 *     @OA\Property(property="description", type="string", example="Basic math questions"),
 *     @OA\Property(property="duration", type="integer", example=60),
 *     @OA\Property(property="passing_score", type="integer", example=70),
 *     @OA\Property(property="company_id", type="integer", example=1),
 *     @OA\Property(property="status", type="string", enum={"active","draft","archived"}, example="draft"),
 *     @OA\Property(property="created_by", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="questions", type="array", @OA\Items(ref="#/components/schemas/Question"))
 *   )
 *
 *   @OA\Schema(
 *     schema="Question",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="exam_id", type="integer", example=1),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="question", type="string", example="What is 2+2?"),
 *     @OA\Property(property="type", type="string", enum={"multiple_choice","true_false","descriptive"}, example="multiple_choice"),
 *     @OA\Property(property="options", type="array", @OA\Items(type="string"), example={"3","4","5"}),
 *     @OA\Property(property="correct_answer", type="string", example="4"),
 *     @OA\Property(property="score", type="integer", example=10),
 *     @OA\Property(property="difficulty", type="string", enum={"easy","medium","hard"}, example="easy"),
 *     @OA\Property(property="category", type="string", example="Math"),
 *     @OA\Property(property="created_by", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 *   )
 *
 */
class SwaggerSchemas
{
}
