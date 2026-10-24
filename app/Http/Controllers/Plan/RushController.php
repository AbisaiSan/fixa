<?php

namespace App\Http\Controllers\Plan;

use App\Domain\Plan\RushMode;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fim do modo corrida (J5-B5, PLA-04). Sem corpo: tudo o que conta é
 * conferido no servidor.
 */
class RushController extends Controller
{
    public function store(Request $request, RushMode $rush): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['counted' => $rush->finish($user)]);
    }
}
