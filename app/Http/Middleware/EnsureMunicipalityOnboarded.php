<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que o município do prefeito completou o onboarding
 * antes de liberar acesso às funcionalidades principais.
 */
class EnsureMunicipalityOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->municipality) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Seu município ainda não foi configurado. Entre em contato com o consultor.']);
        }

        if (!$user->municipality->isOnboarded()) {
            return response()->view('mayor.onboarding-pending', [
                'status' => $user->municipality->onboarding_status,
            ], 403);
        }

        return $next($request);
    }
}
