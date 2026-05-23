<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Professional;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private function professional(Request $request): Professional
    {
        return Professional::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function index(Request $request)
    {
        $professional = $this->professional($request);

        $query = Expense::where('professional_id', $professional->id)->latest('expense_date');

        if ($request->filled('from')) {
            $query->where('expense_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->where('expense_date', '<=', $request->to);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return ExpenseResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $professional = $this->professional($request);

        $validated = $request->validate([
            'category'     => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0',
            'description'  => 'nullable|string|max:500',
            'expense_date' => 'required|date',
            'receipt_url'  => 'nullable|url',
        ]);

        $expense = Expense::create([
            'professional_id' => $professional->id,
            ...$validated,
        ]);

        return (new ExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function update(Request $request, $id)
    {
        $professional = $this->professional($request);
        $expense = Expense::where('professional_id', $professional->id)->findOrFail($id);

        $validated = $request->validate([
            'category'     => 'sometimes|string|max:100',
            'amount'       => 'sometimes|numeric|min:0',
            'description'  => 'nullable|string|max:500',
            'expense_date' => 'sometimes|date',
            'receipt_url'  => 'nullable|url',
        ]);

        $expense->update($validated);

        return new ExpenseResource($expense);
    }

    public function destroy(Request $request, $id)
    {
        $professional = $this->professional($request);
        $expense = Expense::where('professional_id', $professional->id)->findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted.']);
    }

    /** Summary: total expenses grouped by category for a date range. */
    public function summary(Request $request)
    {
        $professional = $this->professional($request);

        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $rows = Expense::where('professional_id', $professional->id)
            ->whereBetween('expense_date', [$from, $to])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $grandTotal = $rows->sum('total');

        return response()->json([
            'from'        => $from,
            'to'          => $to,
            'grand_total' => (float) $grandTotal,
            'by_category' => $rows->map(fn ($r) => [
                'category' => $r->category,
                'total'    => (float) $r->total,
            ]),
        ]);
    }
}
