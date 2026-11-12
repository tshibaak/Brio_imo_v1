<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        return view('landlord.expenses.index', [
            'expenses' => Expense::query()->where('landlord_id', $request->user()->id)
                ->with('property')->latest('spent_at')->paginate(20),
            'properties' => Property::query()->where('landlord_id', $request->user()->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')->where('landlord_id', $request->user()->id)],
            'category' => ['required', 'in:repair,maintenance,utilities,other'],
            'description' => ['required', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999'],
            'currency' => ['required', 'regex:/^[A-Z]{3}$/'],
            'spent_at' => ['required', 'date'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $property = Property::query()->where('landlord_id', $request->user()->id)->findOrFail($data['property_id']);
        $receiptPath = $request->file('receipt')?->store('expense-receipts', 'local');

        Expense::query()->create([
            'landlord_id' => $request->user()->id,
            'property_id' => $property->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'spent_at' => $data['spent_at'],
            'receipt_path' => $receiptPath,
        ]);

        return redirect()->route('expenses.index')->with('success', 'La dépense a été enregistrée.');
    }

    public function receipt(Request $request, Expense $expense): StreamedResponse
    {
        abort_unless($expense->landlord_id === $request->user()->id, 404);
        abort_unless($expense->receipt_path, 404);

        return Storage::disk('local')->download($expense->receipt_path);
    }
}
