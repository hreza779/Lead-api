<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Questions",
 *     description="API Endpoints for managing questions"
 * )
 */
class QuestionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/questions",
     *     summary="Get all questions",
     *     tags={"Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by question type",
     *         @OA\Schema(type="string", enum={"multiple_choice","true_false","descriptive"})
     *     ),
     *     @OA\Parameter(
     *         name="difficulty",
     *         in="query",
     *         description="Filter by difficulty",
     *         @OA\Schema(type="string", enum={"easy","medium","hard"})
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of questions"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Question::with(['exams', 'creator']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/questions",
     *     summary="Create a new question",
     *     tags={"Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question","type","correct_answer","score","difficulty","category"},
     *             @OA\Property(property="question", type="string", example="What is 2+2?"),
     *             @OA\Property(property="type", type="string", enum={"multiple_choice","true_false","descriptive"}, example="multiple_choice"),
     *             @OA\Property(property="options", type="array", @OA\Items(type="string"), example={"3","4","5"}),
     *             @OA\Property(property="correct_answer", type="string", example="4"),
     *             @OA\Property(property="score", type="integer", example=10),
     *             @OA\Property(property="difficulty", type="string", enum={"easy","medium","hard"}, example="easy"),
     *             @OA\Property(property="category", type="string", example="Math")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Question created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|in:multiple_choice,true_false,descriptive',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'correct_answer' => 'required|string',
            'score' => 'required|integer|min:0',
            'difficulty' => 'required|in:easy,medium,hard',
            'category' => 'required|string|max:255',
        ]);

        $validated['created_by'] = auth()->id();

        $question = Question::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت ایجاد شد',
            'data' => $question
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/questions/{question}",
     *     summary="Get a specific question",
     *     tags={"Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question details"
     *     ),
     *     @OA\Response(response=404, description="Question not found")
     * )
     */
    public function show(Question $question)
    {
        return response()->json([
            'success' => true,
            'data' => $question->load(['exams', 'creator'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/questions/{question}",
     *     summary="Update a question",
     *     tags={"Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="question", type="string"),
     *             @OA\Property(property="type", type="string", enum={"multiple_choice","true_false","descriptive"}),
     *             @OA\Property(property="options", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="correct_answer", type="string"),
     *             @OA\Property(property="score", type="integer"),
     *             @OA\Property(property="difficulty", type="string", enum={"easy","medium","hard"}),
     *             @OA\Property(property="category", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Question not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'question' => 'sometimes|required|string',
            'type' => 'sometimes|required|in:multiple_choice,true_false,descriptive',
            'options' => 'nullable|array',
            'options.*' => 'string',
            'correct_answer' => 'sometimes|required|string',
            'score' => 'sometimes|required|integer|min:0',
            'difficulty' => 'sometimes|required|in:easy,medium,hard',
            'category' => 'sometimes|required|string|max:255',
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت بروزرسانی شد',
            'data' => $question
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/questions/{question}",
     *     summary="Delete a question",
     *     tags={"Questions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="question",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Question deleted successfully"),
     *     @OA\Response(response=404, description="Question not found")
     * )
     */
    public function destroy(Question $question)
    {
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'سوال با موفقیت حذف شد'
        ], 204);
    }
}
