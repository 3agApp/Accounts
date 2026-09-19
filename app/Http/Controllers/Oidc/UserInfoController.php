<?php

namespace App\Http\Controllers\Oidc;

use App\Models\User;
use App\Oidc\IdTokenBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns the claims about the signed-in user that the presented access token
 * was granted. Scopes the client never asked for are never disclosed.
 */
class UserInfoController
{
    public function __invoke(Request $request, IdTokenBuilder $idTokens): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $scopes = array_filter(
            ['profile', 'email'],
            fn (string $scope): bool => $user->tokenCan($scope)
        );

        return response()->json([
            'sub' => $user->public_id,
            ...$idTokens->claimsFor($user, $scopes),
        ]);
    }
}
