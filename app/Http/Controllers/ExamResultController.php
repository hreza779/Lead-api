<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\Exam;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Exam Results",
 *     description="API Endpoints for managing exam results"
 * )
 */
class ExamResultController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exam-results",
     *     summary="Get all exam results",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="exam_set_id",
     *         in="query",
     *         description="Filter by exam set ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="manager_id",
     *         in="query",
     *         description="Filter by manager ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"in_progress","completed","passed","failed"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of exam results"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = ExamResult::with(['examSet', 'exam', 'manager']);

        if ($request->has('exam_set_id')) {
            $query->where('exam_set_id', $request->exam_set_id);
        }

        if ($request->has('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/exam-results",
     *     summary="Start an exam (create result record)",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"exam_set_id","exam_id","manager_id"},
     *             @OA\Property(property="exam_set_id", type="integer", example=1),
     *             @OA\Property(property="exam_id", type="integer", example=1),
     *             @OA\Property(property="manager_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exam started successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'exam_set_id' => 'required|exists:exam_sets,id',
            'exam_id' => 'required|exists:exams,id',
            'manager_id' => 'required|exists:managers,id',
        ]);

        // Check if already started
        $existing = ExamResult::where('exam_set_id', $validated['exam_set_id'])
            ->where('exam_id', $validated['exam_id'])
            ->where('manager_id', $validated['manager_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'این آزمون قبلاً شروع شده است',
                'data' => $existing
            ], 409);
        }

        $result = ExamResult::create([
            'exam_set_id' => $validated['exam_set_id'],
            'exam_id' => $validated['exam_id'],
            'manager_id' => $validated['manager_id'],
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'آزمون شروع شد',
            'data' => $result->load(['exam.questions', 'manager'])
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/exam-results/{result}",
     *     summary="Get exam result details",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="result",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Result details"
     *     )
     * )
     */
    public function show(ExamResult $result)
    {
        return response()->json([
            'success' => true,
            'data' => $result->load(['examSet', 'exam.questions', 'manager'])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/exam-results/{result}",
     *     summary="Save answers (in progress)",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="result",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="object",
     *                 example={"1":"A","2":"B","3":"C"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answers saved"
     *     )
     * )
     */
    public function update(Request $request, ExamResult $result)
    {
        if ($result->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'نمی‌توانید نتیجه تکمیل شده را ویرایش کنید'
            ], 403);
        }

        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        $result->update([
            'answers' => $validated['answers'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'پاسخ‌ها ذخیره شدند',
            'data' => $result
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/exam-results/{result}/submit",
     *     summary="Submit final answers and calculate score",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="result",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"answers"},
     *             @OA\Property(
     *                 property="answers",
     *                 type="object",
     *                 example={"1":"A","2":"B","3":"C"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exam submitted successfully"
     *     )
     * )
     */
    public function submit(Request $request, ExamResult $result)
    {
        if ($result->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'این آزمون قبلاً ثبت شده است'
            ], 409);
        }

        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        // Load exam with questions
        $exam = Exam::with('questions')->find($result->exam_id);

        // Calculate score
        $totalScore = 0;
        $earnedScore = 0;

        foreach ($exam->questions as $question) {
            $totalScore += $question->score;

            // Check if answer is correct
            $questionId = $question->id;
            if (isset($validated['answers'][$questionId])) {
                $userAnswer = $validated['answers'][$questionId];
                if ($userAnswer == $question->correct_answer) {
                    $earnedScore += $question->score;
                }
            }
        }

        $percentage = $totalScore > 0 ? ($earnedScore / $totalScore) * 100 : 0;
        $status = $percentage >= $exam->passing_score ? 'passed' : 'failed';

        // Calculate time spent
        $timeSpent = now()->diffInMinutes($result->started_at);

        $result->update([
            'answers' => $validated['answers'],
            'score' => $earnedScore,
            'total_score' => $totalScore,
            'percentage' => round($percentage, 2),
            'status' => $status,
            'completed_at' => now(),
            'time_spent' => $timeSpent,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'آزمون با موفقیت ثبت شد',
            'data' => [
                'result' => $result,
                'summary' => [
                    'score' => $earnedScore,
                    'total_score' => $totalScore,
                    'percentage' => round($percentage, 2),
                    'status' => $status,
                    'passed' => $status === 'passed',
                    'time_spent_minutes' => $timeSpent,
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/exam-results/{result}/report",
     *     summary="Get detailed result report",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="result",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detailed result report"
     *     )
     * )
     */
    public function report(ExamResult $result)
    {
        if ($result->status === 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'آزمون هنوز تکمیل نشده است'
            ], 400);
        }

        $exam = Exam::with('questions')->find($result->exam_id);
        $answers = $result->answers ?? [];

        $questionResults = [];
        foreach ($exam->questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;
            $isCorrect = $userAnswer == $question->correct_answer;

            $questionResults[] = [
                'question_id' => $question->id,
                'question' => $question->question,
                'user_answer' => $userAnswer,
                'correct_answer' => $question->correct_answer,
                'is_correct' => $isCorrect,
                'score' => $isCorrect ? $question->score : 0,
                'max_score' => $question->score,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'result' => $result,
                'exam' => $exam->only(['title', 'duration', 'passing_score']),
                'manager' => $result->manager,
                'summary' => [
                    'score' => $result->score,
                    'total_score' => $result->total_score,
                    'percentage' => $result->percentage,
                    'status' => $result->status,
                    'passed' => $result->status === 'passed',
                    'time_spent_minutes' => $result->time_spent,
                    'started_at' => $result->started_at,
                    'completed_at' => $result->completed_at,
                ],
                'questions' => $questionResults,
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exam-results/{result}",
     *     summary="Delete exam result",
     *     tags={"Exam Results"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="result",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Result deleted successfully")
     * )
     */
    public function destroy(ExamResult $result)
    {
        $result->delete();

        return response()->json([
            'success' => true,
            'message' => 'نتیجه آزمون حذف شد'
        ], 204);
    }
}
