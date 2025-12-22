<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthPageController extends Controller
{
    public function __construct(
        private AuthService $authService
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
     * @return \Illuminate\Http\RedirectResponse
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
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput($request->only('email'));
        }

        $user = auth('api')->user();

        // Check if user is banned
        if ($user->banned) {
            auth('api')->logout();
            return back()->withErrors([
                'email' => 'Account is banned. ' . ($user->ban_reason ? 'Reason: ' . $user->ban_reason : ''),
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

        // Redirect based on user role
        $redirectPath = '/';
        if ($user->role === 'admin') {
            $redirectPath = '/admin';
        } elseif ($user->role === 'dealer') {
            $redirectPath = '/dealer';
        }

        return redirect($redirectPath)->withCookies([$refreshCookie, $accessCookie]);
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
            'role' => $request->role ?? 'user',
            'email_verified' => false,
        ]);

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
        $message = 'If that email is in our system, we\'ll send you a password reset link. Check your inbox!';

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

            // TODO: Send email with reset link
            // Mail::to($user)->send(new ResetPasswordMail($resetUrl));
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
                ->with('error', 'Invalid or expired reset token.')
                ->withInput($request->only('email', 'token'));
        }

        // Update user password
        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect('/auth/reset-password')
                ->with('error', 'User not found.')
                ->withInput($request->only('email', 'token'));
        }

        $user->password = $request->password;
        $user->save();

        // Delete the reset token
        DB::table('verifications')->where('identifier', $identifier)->delete();

        return redirect('/auth/login')->with('status', 'Password has been reset successfully. You can now login with your new password.');
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
            return redirect('/auth/login')->with('error', 'Please login to verify your email.');
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user) {
                return redirect('/auth/login')->with('error', 'Please login to verify your email.');
            }

            if ($user->email_verified) {
                return redirect('/')->with('status', 'Email is already verified.');
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

            // TODO: Send verification email
            // Mail::to($user)->send(new VerifyEmailMail($verificationUrl));

            return back()->with('status', 'Verification email sent! Please check your inbox.');
        } catch (JWTException $e) {
            return redirect('/auth/login')->with('error', 'Please login to verify your email.');
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
            return redirect('/auth/login')->with('error', 'Invalid verification link.');
        }

        // Check verification token
        $identifier = 'email_verify:' . $user->email;
        $verification = DB::table('verifications')
            ->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification || !Hash::check($hash, $verification->value)) {
            return redirect('/auth/verify-email')->with('error', 'Invalid or expired verification link.');
        }

        // Mark email as verified
        $user->email_verified = true;
        $user->save();

        // Delete verification token
        DB::table('verifications')->where('identifier', $identifier)->delete();

        return redirect('/')->with('status', 'Email verified successfully!');
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
        $message = 'If that email is in our system, we\'ll send you a magic link. Check your inbox!';

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

            // TODO: Send magic link email
            // Mail::to($user)->send(new MagicLinkMail($magicLinkUrl));
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
            'role' => $request->role ?? 'user',
            'email_verified' => false,
        ]);

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

        // TODO: Send magic link email
        // Mail::to($user)->send(new MagicLinkMail($magicLinkUrl));

        return back()->with('status', 'Magic link sent! Please check your inbox to complete signup.');
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
                ->with('error', 'Invalid or expired magic link.')
                ->withInput($request->only('token', 'callbackURL'));
        }

        // Extract email from identifier (magic_link:email)
        $email = str_replace('magic_link:', '', $verification->identifier);
        
        // Find user by email
        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect('/auth/login')->with('error', 'User not found.');
        }

        // Check if user is banned
        if ($user->banned) {
            return redirect('/auth/login')->with('error', 'Account is banned.');
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

        // Redirect to callback URL or home
        return redirect($callbackURL)->withCookies([$refreshCookie, $accessCookie])
            ->with('status', 'Magic link verified successfully!');
    }

    /**
     * Redirect user based on their role
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectBasedOnRole($user)
    {
        if ($user->role === 'admin') {
            return redirect('/admin');
        } elseif ($user->role === 'dealer') {
            return redirect('/dealer');
        } else {
            return redirect('/');
        }
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
                ->with('status', 'Logged out successfully');
        } catch (\Exception $e) {
            // Clear cookies anyway
            $accessCookie = cookie()->forget('access_token');
            $refreshCookie = cookie()->forget('refresh_token');
            
            return redirect('/')->withCookies([$accessCookie, $refreshCookie])
                ->with('status', 'Logged out successfully');
        }
    }
}

