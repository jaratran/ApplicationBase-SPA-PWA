<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContextController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $capabilities = collect([
            'catalogs.manage' => 'manage-catalogs',
            'parameters.manage' => 'manage-parameters',
            'organization.manage' => 'manage-organization',
            'users.manage' => 'manage-users',
        ])->filter(
            fn (string $gate): bool => $request->user()->can($gate),
        )->keys()->values()->all();

        return response()->json([
            'data' => [
                'can_access_admin' => true,
                'capabilities' => $capabilities,
            ],
        ]);
    }
}
