<?php

namespace App\Models;

use App\Services\CalendarEventService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerInteraction extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::saved(function ($interaction) {
            $interaction->syncCalendarEvent();
        });

        static::deleted(function ($interaction) {
            $calendarService = app(CalendarEventService::class);
            $calendarService->deleteEvent($interaction);
        });
    }

    protected $fillable = [
        'customer_id',
        'user_id',
        'interaction_type_id',
        'interaction_result_id',
        'notes',
        'interaction_date',
        'next_follow_up',
        'metadata',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
        'next_follow_up' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function interactionType()
    {
        return $this->belongsTo(CustomerInteractionType::class);
    }

    public function interactionResult()
    {
        return $this->belongsTo(CustomerInteractionResult::class);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('interaction_date', 'desc');
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function calendarEvent()
    {
        return $this->morphOne(CalendarEvent::class, 'eventable');
    }

    public function syncCalendarEvent()
    {
        if (!$this->next_follow_up) {
            $calendarService = app(CalendarEventService::class);
            $calendarService->deleteEvent($this);
            return;
        }

        $calendarService = app(CalendarEventService::class);
        
        $customer = $this->customer;
        $interactionType = $this->interactionType;
        
        $calendarService->syncEvent($this, [
            'title' => "Liên hệ lại: {$customer->name}",
            'description' => $this->notes,
            'category' => 'customer_follow_up',
            'start_date' => $this->next_follow_up,
            'end_date' => $this->next_follow_up->copy()->addHour(),
            'is_all_day' => false,
            'status' => 'pending',
            'user_id' => $this->user_id,
            'branch_id' => $customer->branch_id,
            'created_by' => $this->user_id,
            'color' => '#F59E0B',
            'icon' => '📞',
            'metadata' => [
                'customer_id' => $this->customer_id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'interaction_type' => $interactionType->name ?? null,
            ],
        ]);
    }
}
