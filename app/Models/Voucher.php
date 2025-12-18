<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_per_customer',
        'usage_count',
        'start_date',
        'end_date',
        'applicable_product_ids',
        'applicable_categories',
        'applicable_customer_ids',
        'is_active',
        'is_auto_apply',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'applicable_product_ids' => 'array',
        'applicable_categories' => 'array',
        'applicable_customer_ids' => 'array',
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

    public function usages()
    {
        return $this->hasMany(VoucherUsage::class);
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
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            });
    }

    public function scopeAutoApply($query)
    {
        return $query->valid()->where('is_auto_apply', true);
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

        if ($this->start_date && $this->start_date > $now) {
            return false;
        }

        if ($this->end_date && $this->end_date < $now) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(Customer $customer): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check usage per customer
        $customerUsageCount = $this->usages()
            ->where('customer_id', $customer->id)
            ->count();

        if ($customerUsageCount >= $this->usage_per_customer) {
            return false;
        }

        // Check if voucher is for specific customers
        if ($this->applicable_customer_ids && !in_array($customer->id, $this->applicable_customer_ids)) {
            return false;
        }

        return true;
    }

    public function canBeAppliedToProduct(Product $product): bool
    {
        // Check if product is in applicable list
        if ($this->applicable_product_ids && !in_array($product->id, $this->applicable_product_ids)) {
            return false;
        }

        // Check if product category is in applicable list
        if ($this->applicable_categories && !in_array($product->category, $this->applicable_categories)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            $discount = $amount * ($this->value / 100);
            
            if ($this->max_discount_amount) {
                $discount = min($discount, $this->max_discount_amount);
            }
            
            return $discount;
        }

        // Fixed amount
        return min($this->value, $amount);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}

