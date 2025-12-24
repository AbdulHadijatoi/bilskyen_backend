<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthLoginController;
use App\Http\Controllers\Auth\AuthRegisterController;
use App\Http\Controllers\Auth\AuthPasswordController;
use App\Http\Controllers\Auth\AuthSessionController;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Auth Controller Facade
 * Delegates to internal controllers for better code organization
 * Routes remain unchanged for backward compatibility
 */
class AuthController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService,
        private AuthLoginController $loginController,
        private AuthRegisterController $registerController,
        private AuthPasswordController $passwordController,
        private AuthSessionController $sessionController
    ) {}

    /**
     * Register - delegate to AuthRegisterController
     */
    public function register(Request $request): JsonResponse
    {
        return $this->registerController->register($request);
    }

    /**
     * Login - delegate to AuthLoginController
     */
    public function login(Request $request): JsonResponse
    {
        return $this->loginController->login($request);
    }

    /**
     * Refresh - delegate to AuthLoginController
     */
    public function refresh(Request $request): JsonResponse
    {
        return $this->loginController->refresh($request);
    }

    /**
     * Logout - delegate to AuthLoginController
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->loginController->logout($request);
    }

    /**
     * Me - delegate to AuthLoginController
     */
    public function me(Request $request): JsonResponse
    {
        return $this->loginController->me($request);
    }

    /**
     * Sign out - delegate to AuthSessionController
     */
    public function signOut(Request $request): JsonResponse
    {
        return $this->sessionController->signOut($request);
    }

    /**
     * Get session - delegate to AuthSessionController
     */
    public function getSession(Request $request): JsonResponse
    {
        return $this->sessionController->getSession($request);
    }

    /**
     * Revoke session - delegate to AuthSessionController
     */
    public function revokeSession(Request $request): JsonResponse
    {
        return $this->sessionController->revokeSession($request);
    }

    /**
     * Update user - delegate to AuthSessionController
     */
    public function updateUser(Request $request): JsonResponse
    {
        return $this->sessionController->updateUser($request);
    }

    /**
     * Change password - delegate to AuthPasswordController
     */
    public function changePassword(Request $request): JsonResponse
    {
        return $this->passwordController->changePassword($request);
    }
}
