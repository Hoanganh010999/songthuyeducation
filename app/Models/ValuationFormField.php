<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValuationFormField extends Model
{
    protected $fillable = [
        'valuation_form_id', 'field_type', 'field_label', 'field_title', 'field_description',
        'field_config', 'field_options', 'sort_order', 'is_required'
    ];

    protected $casts = [
        'field_config' => 'array',
        'field_options' => 'array',
        'is_required' => 'boolean',
    ];

    public function valuationForm(): BelongsTo
    {
        return $this->belongsTo(ValuationForm::class);
    }

    public function scopeForForm($query, $formId)
    {
        return $query->where('valuation_form_id', $formId)->orderBy('sort_order');
    }
}
