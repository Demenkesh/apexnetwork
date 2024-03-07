<?php

namespace App\Http\Services\Auth;

use App\Jobs\SendMailJob;
use App\Mail\Passwordreset;
use App\Models\User;
use App\Models\UserUnverified;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthServices
{
    public function sendResetLinkEmail(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'email' => 'required|max:191',
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
                    'status' => false, // 404
                    'message' => 'User not found',
                ], Response::HTTP_FORBIDDEN);
            }

            $tokens = Str::random(64);

            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $tokens,
                'created_at' => Carbon::now(),
            ]);

            $password_Url = URL::temporarySignedRoute('password-reset', now()->addMinute(10), ['email' => $user->email, 'token' => $tokens], false);
            $password_Url = $request->getHost() . $password_Url;
            SendMailJob::dispatch($user->email, Passwordreset::class, $password_Url);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Password reset link sent successfully to your email',
                'link'=>  $password_Url
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|confirmed|min:8',
                'token' => 'required',
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
                    'status' => false, // 404
                    'message' => 'Password rest failed. Try again!',
                ], Response::HTTP_FORBIDDEN);
            }

            $check_token = DB::table('password_reset_tokens')->where([
                'email' => $request->email,
                'token' => $request->token,
            ])->first();

            if (!$check_token) {
                return response()->json([
                    'status' => false, // 401
                    'message' => 'Password reset failed. Try again!',
                ], Response::HTTP_FORBIDDEN);
            }
            $token = Str::random(10);
            User::where('email', $request->email)->update([
                'password' => Hash::make($request->password),
                'remember_token' => $token,
            ]);
            DB::table('password_reset_tokens')->where([
                'email' => $request->email,
            ])->delete();

            DB::commit();
            return response()->json([
                'status' => true, // 200
                'message' => 'Password changed successfully',
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false, // 500
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function verifyEmail(Request $request)
    {
        try {
            $email = $request->input('email');
            $signature = $request->input('signature');

            $user = User::where('email', $email)
                ->where('email_signature', $signature)
                ->first();

            $minutes = Carbon::now()->diffInMinutes($user?->created_at);

            // The link should expire after 10 mins
            if (!$user || $minutes > 10) {
                return response()->json([
                    'status' => false, // 401
                    'message' => 'Link expired , login again for a new link  ',
                ], Response::HTTP_FORBIDDEN);
            }

            // TODO (what's next if the user doesn't verify the email for some reasons eg: internet issues,....)

            if (!$user->email_verified_at) {
                $user->forceFill([
                    'email_verified_at' => now()
                ])->save();
            }
            if ($user->role_as === '1') {
                $token = $user->createToken($request->email . '_AdminToken')->plainTextToken;
            }else{
                $token = $user->createToken($request->email . '_UserToken')->plainTextToken;
            }

           
            return response()->json([
                'status' => true, // 200
                'access_token' => $token,
                'message' => 'Email verified',
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false, // 500
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
