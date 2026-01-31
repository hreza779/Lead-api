<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * @OA\Post(
     *     path="/api/auth/send-otp",
     *     summary="Send OTP Code",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="09123456789", description="شماره موبایل کاربر")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP Sent Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کد تایید ارسال شد"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="expires_at", type="string", format="date-time"),
     *                 @OA\Property(property="code", type="string", example="1234", description="Only in dev mode"),
     *                 @OA\Property(property="is_registered", type="boolean", example=true, description="آیا کاربر قبلا ثبت نام کرده است")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شماره موبایل معتبر نیست"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too Many Requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="تعداد درخواست‌های شما بیش از حد مجاز است")
     *         )
     *     )
     * )
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل معتبر نیست',
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = $request->phone;

        // Check rate limit
        if (!$this->otpService->checkRateLimit($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفا بعدا تلاش کنید'
            ], 429);
        }

        // Generate OTP
        $otp = $this->otpService->generateOtp($phone);

        // Check if user exists
        $isRegistered = User::where('phone', $phone)->exists();

        return response()->json([
            'success' => true,
            'message' => 'کد تایید ارسال شد',
            'data' => [
                'expires_at' => $otp['expires_at'],
                // TODO: Remove this in production - only for testing
                'code' => $otp['code'],
                'is_registered' => $isRegistered
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/verify-otp",
     *     summary="Verify OTP and Login/Register",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"phone", "code"},
     *                 @OA\Property(property="phone", type="string", example="09123456789"),
     *                 @OA\Property(property="code", type="string", example="1234"),
     *                 @OA\Property(
     *                     property="data",
     *                     type="object",
     *                     description="اطلاعات تکمیلی برای ثبت نام (به صورت آرایه ارسال شود: data[name], data[avatar])",
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Ali Rezaei",
     *                         description="نام کاربر (اختیاری) - ارسال به صورت: data[name]"
     *                     ),
     *                     @OA\Property(
     *                         property="avatar",
     *                         type="string",
     *                         format="binary",
     *                         description="تصویر پروفایل (اختیاری) - ارسال به صورت: data[avatar]"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="ورود موفقیت‌آمیز"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="کد تایید نامعتبر یا منقضی شده است")
     *         )
     *     ),
     *      @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'code' => 'required|string|size:4',
            'data.name' => 'nullable|string|max:255',
            'data.avatar' => 'nullable|image|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'اطلاعات ورودی معتبر نیست',
                'errors' => $validator->errors()
            ], 422);
        }

        $phone = $request->phone;
        $code = $request->code;

        // Verify OTP
        if (!$this->otpService->verifyOtp($phone, $code)) {
            return response()->json([
                'success' => false,
                'message' => 'کد تایید نامعتبر یا منقضی شده است'
            ], 401);
        }

        // Handle Avatar Upload
        $avatarPath = null;
        if ($request->hasFile('data.avatar')) {
            $avatarPath = $request->file('data.avatar')->store('avatars', 'public');
        }

        // Find or create user
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $userData = [
                'name' => $request->input('data.name') ?? 'کاربر ' . substr($phone, -4),
                'role' => 'owner',
                'status' => 'active',
                'phone' => $phone
            ];

            if ($avatarPath) {
                $userData['avatar'] = $avatarPath;
            }

            $user = User::create($userData);
        }

        // Update last login
        $user->update(['last_login' => now()]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ورود موفقیت‌آمیز',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout User",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout Successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="خروج موفقیت‌آمیز")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'خروج موفقیت‌آمیز'
        ]);
    }
}
