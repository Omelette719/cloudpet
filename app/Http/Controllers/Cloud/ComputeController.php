<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ComputeService;
use App\Models\ComputeInstance;

class ComputeController extends Controller
{
    protected $service;

    public function __construct(ComputeService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $items = ComputeInstance::where('user_id', $user->id)->orderBy('created_at','desc')->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate(['plan' => 'required|string']);
        $user = $request->user();
        $instance = $this->service->createInstance($user, $request->plan);
        return response()->json($instance, 201);
    }

    public function action(Request $request, $id)
    {
        $request->validate(['action' => 'required|string']);
        $user = $request->user();
        $instance = ComputeInstance::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $this->service->changeStatus($instance, $request->action);
        return response()->json($instance);
    }
}
