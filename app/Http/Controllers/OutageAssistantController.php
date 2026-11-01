<?php

namespace App\Http\Controllers;

use App\Services\OutageAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutageAssistantController extends Controller
{
    public function index()
    {
        return view('ai.assistant');
    }

    public function ask(Request $request, OutageAssistantService $assistant): JsonResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'min:3', 'max:300']]);

        return response()->json([
            'answer' => $assistant->ask($request->user(), $data['question']),
        ]);
    }
}