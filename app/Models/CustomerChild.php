<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerChild extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id', // For linking to users table
        'student_code', // For enrollment
        'name',
        'date_of_birth',
        'gender',
        'school',
        'grade',
        'interests',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'metadata' => 'array',
    ];

    /**
     * Relationship: Customer (Phụ huynh)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship: Calendar Events (Polymorphic)
     */
    public function calendarEvents()
    {
        return $this->morphMany(CalendarEvent::class, 'eventable');
    }

    /**
     * Relationship: Placement Test Event
     */
    public function placementTestEvent()
    {
        return $this->morphOne(CalendarEvent::class, 'eventable')
            ->where('category', 'placement_test')
            ->latest();
    }

    /**
     * Relationship: Trial class registrations
     */
    public function trialClasses()
    {
        return $this->morphMany(TrialStudent::class, 'trialable');
    }

    /**
     * Relationship: Active trial registrations
     */
    public function activeTrials()
    {
        return $this->trialClasses()->active();
    }

    /**
     * Relationship: Wallet (ví tiền của con)
     */
    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    /**
     * Relationship: Enrollments (đơn đăng ký khóa học cho con)
     */
    public function enrollments()
    {
        return $this->morphMany(Enrollment::class, 'student');
    }

    /**
     * Accessor: Age
     */
    public function getAgeAttribute()
    {
        if (!$this->date_of_birth) {
            return null;
        }
        return $this->date_of_birth->age;
    }
}
