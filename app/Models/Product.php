<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'slug',
        'description',
        'type',
        'price',
        'sale_price',
        'duration_months',
        'total_sessions',
        'price_per_session',
        'category',
        'level',
        'target_ages',
        'is_active',
        'is_featured',
        'allow_trial',
        'image',
        'gallery',
        'meta_title',
        'meta_description',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'price_per_session' => 'decimal:2',
        'target_ages' => 'array',
        'gallery' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'allow_trial' => 'boolean',
    ];

    protected $appends = [
        'current_price',
        'discount_percentage',
    ];

    const TYPE_COURSE = 'course';
    const TYPE_PACKAGE = 'package';
    const TYPE_MATERIAL = 'material';
    const TYPE_SERVICE = 'service';

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Accessors
     */
    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->sale_price || $this->price <= 0) {
            return 0;
        }
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Methods
     */
    public static function generateCode(): string
    {
        $prefix = 'PRD';
        $lastProduct = self::where('code', 'like', "{$prefix}%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastProduct) {
            $lastNumber = (int) substr($lastProduct->code, 3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public function calculatePricePerSession(): void
    {
        if ($this->total_sessions && $this->total_sessions > 0) {
            $this->price_per_session = $this->current_price / $this->total_sessions;
        }
    }

    /**
     * Boot
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = self::generateCode();
            }
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            $product->calculatePricePerSession();
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if ($product->isDirty(['price', 'sale_price', 'total_sessions'])) {
                $product->calculatePricePerSession();
            }
        });
    }
}

