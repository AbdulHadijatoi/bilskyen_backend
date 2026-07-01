<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditActorType;
use App\Mail\MagicLinkMail;
use App\Mail\ResetPasswordMail;
use App\Mail\VerifyEmailMail;
use App\Services\AuthService;
use App\Services\MailService;
use App\Services\RolePermissionService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthPageController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private RolePermissionService $rolePermissionService,
        private AuditLogService $auditLogService,
        private MailService $mailService
    ) {}

    /**
     * Get authenticated user from JWT token in cookie
     *
     * @param Request $request
     * @return User|null
     */
    protected function getAuthenticatedUser(Request $request)
    {
        return $this->authService->getAuthenticatedUser($request);
    }

    /**
     * Show the login page
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showLogin(Request $request)
    {
        // Redirect if already authenticated
        if ($user = $this->getAuthenticatedUser($request)) {
            return $this->redirectBasedOnRole($user);
        }
        
        return view('auth.login');
    }

    /**
     * Show the signup page
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showSignup(Request $request)
    {
        // Redirect if already authenticated
        if ($user = $this->getAuthenticatedUser($request)) {
            return $this->redirectBasedOnRole($user);
        }
        
        return view('auth.signup');
    }

    /**
     * Show the forgot password page
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showForgotPassword(Request $request)
    {
        // Redirect if already authenticated
        if ($user = $this->getAuthenticatedUser($request)) {
            return $this->redirectBasedOnRole($user);
        }
        
        return view('auth.forgot-password');
    }

    /**
     * Show the reset password page
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showResetPassword(Request $request)
    {
        // Allow access only if there's a reset token (user resetting password via email link)
        // If authenticated without a token, redirect to home (they should use change password)
        $token = $request->query('token');
        if (!$token && $user = $this->getAuthenticatedUser($request)) {
            return $this->redirectBasedOnRole($user);
        }
        
        $email = $request->query('email');
        $error = session('error');
        
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
            'error' => $error
        ]);
    }

    /**
     * Show the verify email page
     *
     * @return \Illuminate\View\View
     */
    public function showVerifyEmail(Request $request)
    {
        // Check if user is authenticated, but allow access to this page
        // as they might need to verify their email
        $user = $this->getAuthenticatedUser($request);
        
        return view('auth.verify-email', ['user' => $user]);
    }

    /**
     * Show the magic link login page
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showMagicLinkLogin(Request $request)
    {
        // Redirect if already authenticated
        if ($user = $this->getAuthenticatedUser($request)) {
            return $this->redirectBasedOnRole($user);
        }
        
        return view('auth.magic-link.login');
    }

    /**
     * Show the magic link signup page
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showMagicLinkSignup(Request $request)
    {
        // Redirect if already authenticated
        if ($user = $this->getAuthenticatedUser($request)) {
            return $this->redirectBasedOnRole($user);
        }
        
        return view('auth.magic-link.signup');
    }

    /**
     * Show the magic link verify page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showMagicLinkVerify(Request $request)
    {
        // Allow access to magic link verify even if authenticated
        // as they might be verifying a magic link to log in
        $token = $request->query('token');
        $callbackURL = $request->query('callbackURL', '/');
        
        return view('auth.magic-link.verify', [
            'token' => $token,
            'callbackURL' => $callbackURL
        ]);
    }

    /**
     * Handle login form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handleLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|max:128',
        ]);

        // Normalize email to lowercase
        $credentials['email'] = strtolower($credentials['email']);

        // Attempt authentication using JWT
        if (!$token = auth('api')->attempt($credentials)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __('messages.errors.credentials_mismatch'),
                    'errors' => [
                        'email' => [__('messages.errors.credentials_mismatch')],
                    ]
                ], 422);
            }
            return back()->withErrors([
                'email' => __('messages.errors.credentials_mismatch'),
            ])->withInput($request->only('email'));
        }

        $user = auth('api')->user();
        $user->load('roles');

        // Check if user is banned
        if ($user->banned) {
            auth('api')->logout();
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __('messages.errors.account_banned') . ($user->ban_reason ? ' ' . __('messages.errors.ban_reason', ['reason' => $user->ban_reason]) : ''),
                    'errors' => [
                        'email' => [__('messages.errors.account_banned') . ($user->ban_reason ? ' ' . __('messages.errors.ban_reason', ['reason' => $user->ban_reason]) : '')],
                    ]
                ], 403);
            }
            return back()->withErrors([
                'email' => __('messages.errors.account_banned') . ($user->ban_reason ? ' ' . __('messages.errors.ban_reason', ['reason' => $user->ban_reason]) : ''),
            ])->withInput($request->only('email'));
        }

        // Generate refresh token and set cookies
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);
        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            20160, // 14 days
            null,
            null,
            true, // secure
            true, // httpOnly
            false, // raw
            'Strict' // sameSite
        );

        // Set access token in cookie for web sessions
        $accessCookie = cookie(
            'access_token',
            $token,
            config('jwt.ttl', 30), // minutes
            null,
            null,
            true, // secure
            false, // httpOnly (false so JS can access if needed)
            false, // raw
            'Strict' // sameSite
        );

        // If AJAX request, return JSON response
        if ($request->expectsJson() || $request->ajax()) {
            $response = response()->json([
                'message' => __('messages.errors.login_successful'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->map(function($role) {
                        return ['id' => $role->id, 'name' => $role->name];
                    }),
                ]
            ]);
            
            // Set cookies on the response
            $response->cookie($refreshCookie);
            $response->cookie($accessCookie);
            
            return $response;
        }

        // Redirect all users to home page after login
        return redirect('/')->withCookies([$refreshCookie, $accessCookie]);
    }

    /**
     * Handle signup form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleSignup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
            ],
            'confirmPassword' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except('password', 'confirmPassword'));
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'email_verified' => false,
        ]);

        // Assign default role
        $roles = $request->input('roles', ['seller']);
        $this->rolePermissionService->assignRoleToUser($user, $roles);

        // Log audit trail (use SYSTEM actor type since user doesn't exist yet)
        try {
            $userData = $user->toArray();
            // Remove sensitive data
            unset($userData['password']);
            
            $this->auditLogService->log(
                0, // System actor ID
                AuditActorType::SYSTEM,
                'create',
                'User',
                $user->id,
                null,
                $userData,
                $request,
                null,
                null,
                'User registered via web signup form',
                ['user', 'registration', 'web'],
                'info',
                null
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for user registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Generate JWT tokens
        $token = auth('api')->login($user);
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);

        // Set cookies
        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            20160, // 14 days
            null,
            null,
            true,
            true,
            false,
            'Strict'
        );

        $accessCookie = cookie(
            'access_token',
            $token,
            config('jwt.ttl', 30),
            null,
            null,
            true,
            false,
            false,
            'Strict'
        );

        // Redirect to verify email page
        return redirect('/auth/verify-email')->withCookies([$refreshCookie, $accessCookie]);
    }

    /**
     * Handle forgot password form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        // Always return success message for security (don't reveal if email exists)
        $message = __('messages.api.password_reset_link_sent');

        if ($user) {
            // Generate password reset token
            $token = Str::random(64);
            
            // Store token in verifications table with 'password_reset' prefix in identifier
            $identifier = 'password_reset:' . $email;
            DB::table('verifications')->updateOrInsert(
                ['identifier' => $identifier],
                [
                    'value' => Hash::make($token),
                    'expires_at' => Carbon::now()->addHours(1), // Reset links expire in 1 hour
                    'created_at' => Carbon::now(),
                ]
            );

            // Generate reset URL
            $resetUrl = url('/auth/reset-password?token=' . $token . '&email=' . urlencode($email));

            $this->mailService->sendMailable(
                $user->email,
                new ResetPasswordMail($resetUrl),
                ['mail_type' => 'password_reset_web'],
                false
            );
        }

        return back()->with('status', $message);
    }

    /**
     * Handle reset password form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'max:128',
            ],
            'confirmPassword' => 'required|same:password',
        ]);

        $email = strtolower($request->email);
        $token = $request->token;
        $identifier = 'password_reset:' . $email;

        // Check if token exists and is valid
        $resetRecord = DB::table('verifications')
            ->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$resetRecord || !Hash::check($token, $resetRecord->value)) {
            return redirect('/auth/reset-password')
                ->with('error', __('messages.errors.invalid_reset_token'))
                ->withInput($request->only('email', 'token'));
        }

        // Update user password
        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect('/auth/reset-password')
                ->with('error', __('messages.errors.user_not_found'))
                ->withInput($request->only('email', 'token'));
        }

        $user->password = $request->password;
        $user->save();

        // Delete the reset token
        DB::table('verifications')->where('identifier', $identifier)->delete();

        return redirect('/auth/login')->with('status', __('messages.messages.password_reset_successfully'));
    }

    /**
     * Resend verification email
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        // Get authenticated user from JWT token in cookie
        $token = $request->cookie('access_token');
        if (!$token) {
            return redirect('/auth/login')->with('error', __('messages.errors.please_login_verify_email'));
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user) {
                return redirect('/auth/login')->with('error', __('messages.errors.please_login_verify_email'));
            }

            if ($user->email_verified) {
                return redirect('/')->with('status', __('messages.messages.email_already_verified'));
            }

            // Generate verification token
            $verificationToken = Str::random(64);
            
            // Store in verifications table with prefix
            $identifier = 'email_verify:' . $user->email;
            DB::table('verifications')->updateOrInsert(
                ['identifier' => $identifier],
                [
                    'value' => Hash::make($verificationToken),
                    'expires_at' => Carbon::now()->addHours(24),
                    'created_at' => Carbon::now(),
                ]
            );

            // Generate verification URL
            $verificationUrl = url('/auth/verify-email/' . $user->id . '/' . $verificationToken);

            $this->mailService->sendMailable(
                $user->email,
                new VerifyEmailMail($verificationUrl),
                ['mail_type' => 'verify_email_web', 'user_id' => $user->id],
                false
            );

            return back()->with('status', __('messages.messages.verification_email_sent'));
        } catch (JWTException $e) {
            return redirect('/auth/login')->with('error', __('messages.errors.please_login_verify_email'));
        }
    }

    /**
     * Verify email with token
     *
     * @param Request $request
     * @param int $id
     * @param string $hash
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);
        if (!$user) {
            return redirect('/auth/login')->with('error', __('messages.errors.invalid_verification_link'));
        }

        // Check verification token
        $identifier = 'email_verify:' . $user->email;
        $verification = DB::table('verifications')
            ->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification || !Hash::check($hash, $verification->value)) {
            return redirect('/auth/verify-email')->with('error', __('messages.errors.invalid_expired_verification_link'));
        }

        // Capture before state for audit log
        $beforeData = ['email_verified' => $user->email_verified];

        // Mark email as verified
        $user->email_verified = true;
        $user->save();

        // Log audit trail (use SYSTEM actor type as it's automated verification)
        try {
            $afterData = ['email_verified' => $user->email_verified];
            $this->auditLogService->log(
                0, // System actor ID
                AuditActorType::SYSTEM,
                'update',
                'User',
                $user->id,
                $beforeData,
                $afterData,
                $request,
                null,
                null,
                'User email verified',
                ['user', 'email', 'verification', 'web'],
                'info',
                null
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for email verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Delete verification token
        DB::table('verifications')->where('identifier', $identifier)->delete();

        // Redirect all users to home page
        return redirect('/')->with('status', __('messages.messages.email_verified_successfully'));
    }

    /**
     * Handle magic link login form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleMagicLinkLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower($request->email);
        $user = User::where('email', $email)->first();

        // Always return success for security
        $message = __('messages.messages.magic_link_sent');

        if ($user) {
            // Generate magic link token
            $token = Str::random(64);
            
            // Store in verifications table with prefix
            $identifier = 'magic_link:' . $email;
            DB::table('verifications')->updateOrInsert(
                ['identifier' => $identifier],
                [
                    'value' => Hash::make($token),
                    'expires_at' => Carbon::now()->addMinutes(15), // Magic links expire in 15 minutes
                    'created_at' => Carbon::now(),
                ]
            );

            // Generate magic link URL
            $magicLinkUrl = url('/auth/magic-link/verify?token=' . $token . '&callbackURL=' . urlencode('/'));

            $this->mailService->sendMailable(
                $user->email,
                new MagicLinkMail($magicLinkUrl),
                ['mail_type' => 'magic_link_login_web'],
                false
            );
        }

        return back()->with('status', $message);
    }

    /**
     * Handle magic link signup form submission
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleMagicLinkSignup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        // Create user without password
        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Str::random(32), // Temporary random password
            'email_verified' => false,
        ]);

        // Assign default role
        $roles = $request->input('roles', ['seller']);
        $this->rolePermissionService->assignRoleToUser($user, $roles);

        // Log audit trail (use SYSTEM actor type since user doesn't exist yet)
        try {
            $userData = $user->toArray();
            // Remove sensitive data
            unset($userData['password']);
            
            $this->auditLogService->log(
                0, // System actor ID
                AuditActorType::SYSTEM,
                'create',
                'User',
                $user->id,
                null,
                $userData,
                $request,
                null,
                null,
                'User registered via magic link signup',
                ['user', 'registration', 'magic-link', 'web'],
                'info',
                null
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to create audit log for magic link user registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Generate magic link token
        $token = Str::random(64);
        
        // Store in verifications table with prefix
        $identifier = 'magic_link:' . $user->email;
        DB::table('verifications')->updateOrInsert(
            ['identifier' => $identifier],
            [
                'value' => Hash::make($token),
                'expires_at' => Carbon::now()->addMinutes(15),
                'created_at' => Carbon::now(),
            ]
        );

        // Generate magic link URL
        $magicLinkUrl = url('/auth/magic-link/verify?token=' . $token . '&callbackURL=' . urlencode('/'));

        $this->mailService->sendMailable(
            $user->email,
            new MagicLinkMail($magicLinkUrl),
            ['mail_type' => 'magic_link_signup_web', 'user_id' => $user->id],
            false
        );

        return back()->with('status', __('messages.messages.magic_link_sent_signup'));
    }

    /**
     * Handle magic link verification
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleMagicLinkVerify(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        $token = $request->token;
        $callbackURL = $request->input('callbackURL', '/');

        // Find verification records with magic_link prefix
        $verifications = DB::table('verifications')
            ->where('identifier', 'like', 'magic_link:%')
            ->where('expires_at', '>', Carbon::now())
            ->get();

        $verification = null;
        foreach ($verifications as $v) {
            if (Hash::check($token, $v->value)) {
                $verification = $v;
                break;
            }
        }

        if (!$verification) {
            return redirect('/auth/magic-link/verify')
                ->with('error', __('messages.errors.invalid_expired_magic_link'))
                ->withInput($request->only('token', 'callbackURL'));
        }

        // Extract email from identifier (magic_link:email)
        $email = str_replace('magic_link:', '', $verification->identifier);
        
        // Find user by email
        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect('/auth/login')->with('error', __('messages.errors.user_not_found'));
        }

        // Check if user is banned
        if ($user->banned) {
            return redirect('/auth/login')->with('error', __('messages.errors.account_banned'));
        }

        // Generate JWT tokens
        $accessToken = auth('api')->login($user);
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);

        // Mark email as verified if not already
        if (!$user->email_verified) {
            $user->email_verified = true;
            $user->save();
        }

        // Delete verification token
        DB::table('verifications')->where('identifier', $verification->identifier)->delete();

        // Set cookies
        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            20160, // 14 days
            null,
            null,
            true,
            true,
            false,
            'Strict'
        );

        $accessCookie = cookie(
            'access_token',
            $accessToken,
            config('jwt.ttl', 30),
            null,
            null,
            true,
            false,
            false,
            'Strict'
        );

        // Redirect all users to callback URL or home
        return redirect($callbackURL)->withCookies([$refreshCookie, $accessCookie])
            ->with('status', __('messages.messages.magic_link_verified_successfully'));
    }

    /**
     * Redirect user based on their roles
     * All users are redirected to home page
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectBasedOnRole($user)
    {
        // Redirect all users to home page
        return redirect('/');
    }

    /**
     * Handle logout
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        try {
            $token = $request->cookie('access_token');
            if ($token) {
                // Invalidate JWT token
                try {
                    JWTAuth::setToken($token)->invalidate();
                } catch (JWTException $e) {
                    // Token might already be invalid
                }
            }

            // Clear cookies
            $accessCookie = cookie()->forget('access_token');
            $refreshCookie = cookie()->forget('refresh_token');

            return redirect('/')->withCookies([$accessCookie, $refreshCookie])
                ->with('status', __('messages.errors.logged_out_success'));
        } catch (\Exception $e) {
            // Clear cookies anyway
            $accessCookie = cookie()->forget('access_token');
            $refreshCookie = cookie()->forget('refresh_token');
            
            return redirect('/')->withCookies([$accessCookie, $refreshCookie])
                ->with('status', __('messages.errors.logged_out_success'));
        }
    }
}

