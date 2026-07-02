<?php

namespace App\Services;

use App\Mail\MagicLinkMail;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthVerificationService
{
    public function __construct(
        private MailService $mailService,
    ) {}

    /**
     * Request a magic link for the given email (login flow).
     * Always succeeds from caller perspective for security when user missing.
     */
    public function requestMagicLink(string $email, bool $forApi = false): void
    {
        $email = strtolower($email);
        $user = User::where('email', $email)->first();

        if (! $user) {
            return;
        }

        $token = $this->storeMagicLinkToken($email);
        $magicLinkUrl = $forApi
            ? url('/api/v1/auth/verify-magic-link?token=' . $token)
            : url('/auth/magic-link/verify?token=' . $token . '&callbackURL=' . urlencode('/'));

        $this->mailService->sendMailable(
            $user->email,
            new MagicLinkMail($magicLinkUrl),
            ['mail_type' => $forApi ? 'magic_link_login_api' : 'magic_link_login_web'],
            false
        );
    }

    /**
     * Send magic link after magic-link signup.
     */
    public function sendMagicLinkForUser(User $user, bool $forApi = false): void
    {
        $token = $this->storeMagicLinkToken($user->email);
        $magicLinkUrl = $forApi
            ? url('/api/v1/auth/verify-magic-link?token=' . $token)
            : url('/auth/magic-link/verify?token=' . $token . '&callbackURL=' . urlencode('/'));

        $this->mailService->sendMailable(
            $user->email,
            new MagicLinkMail($magicLinkUrl),
            [
                'mail_type' => $forApi ? 'magic_link_signup_api' : 'magic_link_signup_web',
                'user_id' => $user->id,
            ],
            false
        );
    }

    /**
     * @return array{user: User, identifier: string}|null
     */
    public function verifyMagicLinkToken(string $token): ?array
    {
        $verifications = DB::table('verifications')
            ->where('identifier', 'like', 'magic_link:%')
            ->where('expires_at', '>', Carbon::now())
            ->get();

        foreach ($verifications as $verification) {
            if (! Hash::check($token, $verification->value)) {
                continue;
            }

            $email = str_replace('magic_link:', '', $verification->identifier);
            $user = User::where('email', $email)->first();

            if (! $user) {
                return null;
            }

            $this->markEmailVerified($user);

            DB::table('verifications')->where('identifier', $verification->identifier)->delete();

            return [
                'user' => $user->fresh(),
                'identifier' => $verification->identifier,
            ];
        }

        return null;
    }

    public function sendEmailVerification(User $user, bool $forApi = false): void
    {
        $token = $this->storeEmailVerificationToken($user);
        $verificationUrl = $forApi
            ? url('/api/v1/auth/verify-email?token=' . $token . '&user_id=' . $user->id)
            : url('/auth/verify-email/' . $user->id . '/' . $token);

        $this->mailService->sendMailable(
            $user->email,
            new VerifyEmailMail($verificationUrl),
            ['mail_type' => $forApi ? 'verify_email_api' : 'verify_email_web', 'user_id' => $user->id],
            false
        );
    }

    public function verifyEmailByUserIdAndHash(int $userId, string $hash): ?User
    {
        $user = User::find($userId);
        if (! $user) {
            return null;
        }

        $identifier = 'email_verify:' . $user->email;
        $verification = DB::table('verifications')
            ->where('identifier', $identifier)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (! $verification || ! Hash::check($hash, $verification->value)) {
            return null;
        }

        $this->markEmailVerified($user);
        DB::table('verifications')->where('identifier', $identifier)->delete();

        return $user->fresh();
    }

    public function verifyEmailByToken(string $token, ?int $userId = null): ?User
    {
        $query = DB::table('verifications')
            ->where('identifier', 'like', 'email_verify:%')
            ->where('expires_at', '>', Carbon::now());

        if ($userId !== null) {
            $user = User::find($userId);
            if (! $user) {
                return null;
            }
            $query->where('identifier', 'email_verify:' . $user->email);
        }

        foreach ($query->get() as $verification) {
            if (! Hash::check($token, $verification->value)) {
                continue;
            }

            $email = str_replace('email_verify:', '', $verification->identifier);
            $user = User::where('email', $email)->first();

            if (! $user) {
                return null;
            }

            $this->markEmailVerified($user);
            DB::table('verifications')->where('identifier', $verification->identifier)->delete();

            return $user->fresh();
        }

        return null;
    }

    /**
     * @return array{success: bool, user?: User, error?: string, status?: int}
     */
    public function changeEmail(User $user, string $newEmail, string $password): array
    {
        $newEmail = strtolower($newEmail);

        if (! Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'error' => __('messages.errors.current_password_incorrect'),
                'status' => 401,
            ];
        }

        if ($newEmail === $user->email) {
            return [
                'success' => false,
                'error' => __('messages.errors.email_same_as_current'),
                'status' => 422,
            ];
        }

        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return [
                'success' => false,
                'error' => __('messages.errors.email_already_taken'),
                'status' => 422,
            ];
        }

        $user->email = $newEmail;
        $user->email_verified_at = null;
        $user->save();

        $this->sendEmailVerification($user, true);

        return [
            'success' => true,
            'user' => $user->fresh(),
        ];
    }

    public function markEmailVerified(User $user): void
    {
        if ($user->email_verified_at === null) {
            $user->email_verified_at = now();
            $user->save();
        }
    }

    private function storeMagicLinkToken(string $email): string
    {
        $token = Str::random(64);
        $identifier = 'magic_link:' . strtolower($email);

        DB::table('verifications')->updateOrInsert(
            ['identifier' => $identifier],
            [
                'value' => Hash::make($token),
                'expires_at' => Carbon::now()->addMinutes(15),
                'created_at' => Carbon::now(),
            ]
        );

        return $token;
    }

    private function storeEmailVerificationToken(User $user): string
    {
        $token = Str::random(64);
        $identifier = 'email_verify:' . $user->email;

        DB::table('verifications')->updateOrInsert(
            ['identifier' => $identifier],
            [
                'value' => Hash::make($token),
                'expires_at' => Carbon::now()->addHours(24),
                'created_at' => Carbon::now(),
            ]
        );

        return $token;
    }
}
