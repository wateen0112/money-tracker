<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TransactionController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $user = $request->user();

        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20);

        return TransactionResource::collection($transactions);
    }

    public function store(TransactionStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $data['user_id'] = $user->id;
        $data['occurred_at'] = Carbon::parse($data['occurred_at'])->utc();

        $transaction = Transaction::create($data);

        return response()->json(new TransactionResource($transaction), 201);
    }

    public function update(TransactionUpdateRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $transaction = $user->transactions()->whereKey($id)->firstOrFail();

        $data = $request->validated();

        if (isset($data['occurred_at'])) {
            $data['occurred_at'] = Carbon::parse($data['occurred_at'])->utc();
        }

        $transaction->update($data);

        return response()->json(new TransactionResource($transaction->refresh()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $transaction = $user->transactions()->whereKey($id)->firstOrFail();

        $transaction->delete();

        return response()->json(null, 204);
    }
}

