<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SellerTokenService
{
    /**
     * Generate encrypted token for seller dashboard
     * 
     * @param User $user
     * @return string
     */
    public function generateToken(User $user): string
    {
        // Create payload with user ID and timestamp
        $payload = [
            'user_id' => $user->id,
            'timestamp' => now()->timestamp,
        ];
        
        // Encrypt the payload
        $encrypted = Crypt::encryptString(json_encode($payload));
        
        // URL-safe base64 encode
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
    }

    /**
     * Validate token and return user if valid
     * 
     * @param string $token
     * @return User|null
     */
    public function validateToken(string $token): ?User
    {
        try {
            // URL-safe base64 decode
            $encrypted = base64_decode(str_replace(['-', '_'], ['+', '/'], $token));
            
            // Decrypt the payload
            $decrypted = Crypt::decryptString($encrypted);
            $payload = json_decode($decrypted, true);
            
            if (!isset($payload['user_id']) || !isset($payload['timestamp'])) {
                return null;
            }
            
            // Check if token is not too old (optional: 30 days expiry)
            $tokenAge = now()->timestamp - $payload['timestamp'];
            $maxAge = 30 * 24 * 60 * 60; // 30 days in seconds
            
            if ($tokenAge > $maxAge) {
                return null;
            }
            
            // Get user
            $user = User::find($payload['user_id']);
            
            // Verify user has seller role
            if (!$user || !$user->hasRole('seller')) {
                return null;
            }
            
            return $user;
        } catch (\Exception $e) {
            Log::warning('Failed to validate seller token', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
            return null;
        }
    }
}
