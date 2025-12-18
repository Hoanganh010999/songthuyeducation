<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'start_date',
        'end_date',
        'applicable_product_ids',
        'applicable_categories',
        'target_customer_segments',
        'priority',
        'total_usage_limit',
        'total_usage_count',
        'is_active',
        'is_auto_apply',
        'banner_image',
        'banner_url',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'applicable_product_ids' => 'array',
        'applicable_categories' => 'array',
        'target_customer_segments' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_auto_apply' => 'boolean',
    ];

    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function scopeAutoApply($query)
    {
        return $query->valid()->where('is_auto_apply', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Methods
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date > $now || $this->end_date < $now) {
            return false;
        }

        if ($this->total_usage_limit && $this->total_usage_count >= $this->total_usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeAppliedToProduct(Product $product): bool
    {
        if ($this->applicable_product_ids && !in_array($product->id, $this->applicable_product_ids)) {
            return false;
        }

        if ($this->applicable_categories && !in_array($product->category, $this->applicable_categories)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            $discount = $amount * ($this->discount_value / 100);
            
            if ($this->max_discount_amount) {
                $discount = min($discount, $this->max_discount_amount);
            }
            
            return $discount;
        }

        return min($this->discount_value, $amount);
    }

    public function incrementUsage(): void
    {
        $this->increment('total_usage_count');
    }
}

