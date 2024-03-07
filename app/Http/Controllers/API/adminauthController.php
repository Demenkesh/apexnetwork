<?php

namespace App\Http\Controllers\API;

/**
 * @OA\Info(
 *    title="User Management System API",
 *    version="1.0.0",
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Demo API Server"
 * )
 * components:
 *     securitySchemes:
 *         bearerAuth: {}  # Define an empty security scheme for bearerAuth
 *
 * security:
 *   - bearerAuth: []   # Apply bearerAuth to endpoints that require JWT authentication
 */

use App\Models\User;
use App\Jobs\SendMailJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Mail\EmailVerification;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use App\Http\Services\Auth\AuthServices;
use Illuminate\Support\Facades\Validator;

class adminauthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/",
     *     summary="Get API Version",
     *     tags={"General"},
     *     @OA\Response(
     *         response=200,
     *         description="API version information",
     *         @OA\JsonContent(
     *             @OA\Property(property="APEX NETWORK", type="string", example="1.0.0"),
     *         ),
     *     ),
     * )
     */
    public function home()
    {
        return [
            'APEX NETWORK' => '1.0',
        ];
    }

    // register controller
    /**
     *
     * @OA\Post(
     *     path="/api/v1/auth/admin/register",
     *     summary="Register a new admin",
     *         tags={"ADMIN AUTHENTICATIONS"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"first_name", "last_name", "email", "password"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="first_name", type="string"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="email_verification", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *             ),
     *         ),
     *     ),
     *    @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="validation_error", type="object"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="error", type="string", example="An error occurred "),
     *         ),
     *         ),
     *     ),
     * )
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|max:191',
                'last_name' => 'required|max:191',
                'email' => 'required|email|unique:users,email|max:191', // Corrected
                'password' => 'required|min:8',
            ]);


            if ($validator->stopOnFirstFailure()->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->all()[0],
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = new User();
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->role_as = '1'; //admin
            $user->password = Hash::make($request->password);

            // Send the email verification notification
            $verification_url = URL::temporarySignedRoute(
                'verify-email',
                now()->addMinute(10),
                ['email' => $request->email],
                false // Set this to false to get the URL without the domain
            );
            // Parse the URL to get its components
            $urlComponents = parse_url($verification_url);
            // Parse the query string to get its parameters
            parse_str($urlComponents['query'], $queryParams);

            // Get the 'signature' parameter
            $signature = $queryParams['signature'];
            $user->email_signature = $signature;

            // Prepend the FRONTEND_URL to the generated URL
            $verification_url = Config::get('app.frontend_url') . '/api/v1/auth/admin/email/verify' . '?' . $urlComponents['query'];

            SendMailJob::dispatch($request->email, EmailVerification::class, $verification_url);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Successfully! ,  Please verify your mail',
                'data' => [
                    'user' => $user,
                ],
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // login controller
    /**
     * @OA\Post(
     *     path="/api/v1/auth/admin/login",
     *     tags={"ADMIN AUTHENTICATIONS"},
     *     summary="Login admin",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret123"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="access_token", type="string"),
     *             ),
     *             @OA\Property(property="message", type="string", example="Successfully logged in."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="message", type="string", example="Unauthorized"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="message", type="string", example="User not found or password is incorrect"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="message", type="string", example="Validation error"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Internal Server Error"),
     *         ),
     *     ),
     * )
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|max:191',
                'password' => 'required|min:8',
            ]);

            if ($validator->stopOnFirstFailure()->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->all()[0],
                ], Response::HTTP_BAD_REQUEST);
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found!'
                ], Response::HTTP_FORBIDDEN);
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Password is incorrect!',
                ], Response::HTTP_FORBIDDEN);

                if (!$user->email_verified_at) {

                    // Send the email verification notification
                    $verification_url = URL::temporarySignedRoute(
                        'verify-email',
                        now()->addMinute(10),
                        ['email' => $request->email],
                        false // Set this to false to get the URL without the domain
                    );
                    // Parse the URL to get its components
                    $urlComponents = parse_url($verification_url);
                    // Parse the query string to get its parameters
                    parse_str($urlComponents['query'], $queryParams);

                    // Get the 'signature' parameter
                    $signature = $queryParams['signature'];
                    $user->email_signature = $signature;

                    // Prepend the FRONTEND_URL to the generated URL
                    $verification_url = Config::get('app.frontend_url') . '/api/v1/auth/admin/email/verify' . '?' . $urlComponents['query'];

                    SendMailJob::dispatch($request->email, EmailVerification::class, $verification_url);

                    return response()->json([
                        'status' => false,
                        'message' => 'Email not verified! Check your email to continue!!',
                    ], Response::HTTP_FORBIDDEN);
                }
            } else {
                if ($user->role_as === '1') {

                    $token = $user->createToken($request->email . '_AdminToken')->plainTextToken;
                    return response()->json([
                        'status' => true,
                        'data' => [
                            'username' => $user->first_name,
                            'access_token' => $token,
                        ],
                        "message" => 'successfully logged user!',
                    ], Response::HTTP_OK); //200


                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Access Denied! as you are not as admin!!',
                    ], Response::HTTP_FORBIDDEN);
                };
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // logout controller
    /**
     * @OA\Post(
     *     path="/api/v1/auth/admin/logout",
     *     tags={"ADMIN AUTHENTICATIONS"},
     *     summary="Logout admin",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Logout successfully"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=401),
     *             @OA\Property(property="error", type="string", example="Unauthorized"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="error", type="string", example="User not found"),
     *         ),
     *     ),
     * )
     */
    public function logout(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->user()->tokens()->delete();
        return response()->json([
            'status' => true,
            "message" => 'Successfully logged out user!'
        ], Response::HTTP_CREATED);
    }


    // sendResetLinkEmail controller
    /**
     * @OA\Post(
     *     path="/api/v1/auth/admin/sendResetLinkEmail",
     *     summary="Send password reset link to admin's email",
     *     tags={"ADMIN AUTHENTICATIONS"},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="User's email",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Password reset link sent successfully to your email"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="error", type="string", example="User not found"),
     *         ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="message", type="string", example="Validation failed"),
     *                 @OA\Property(property="validation_errors", type="object"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function sendResetLinkEmail(Request $request)
    {
        return (new AuthServices)->sendResetLinkEmail($request);
    }

    // reset controller
    /**
     * @OA\Post(
     *     path="/api/v1/auth/admin/password/reset",
     *     summary="Reset user's password",
     *     tags={"ADMIN AUTHENTICATIONS"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email", "password", "token"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123", description="The new password."),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123", description="Confirm the new password."),
     *             @OA\Property(property="token", type="string", example="reset-token", description="The password reset token."),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Password changed successfully"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="error", type="string", example="Invalid request"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=401),
     *             @OA\Property(property="error", type="string", example="Invalid Token"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="error", type="string", example="User not found"),
     *         ),
     *     ),
     * )
     */
    public function reset(Request $request)
    {
        return (new AuthServices)->resetPassword($request);
    }

    public function show(Request $request)
    {
        return (new AuthServices)->verifyEmail($request);
    }
}
