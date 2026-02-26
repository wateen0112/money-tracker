<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    public function dateRange(): array
    {
        $from = $this->filled('from')
            ? Carbon::parse($this->input('from'))->startOfDay()->utc()
            : Carbon::now('UTC')->subMonth()->startOfDay();

        $to = $this->filled('to')
            ? Carbon::parse($this->input('to'))->endOfDay()->utc()
            : Carbon::now('UTC')->endOfDay();

        return [$from, $to];
    }
}

