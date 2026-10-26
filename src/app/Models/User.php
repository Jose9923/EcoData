<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\UserCreatedNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'document_type',
        'document_number',
        'password',
        'school_id',
        'grade_id',
        'course_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function responsibleWeatherStations()
    {
        return $this->hasMany(WeatherStation::class, 'responsible_user_id');
    }

    public function createdEnvironmentalEvents()
    {
        return $this->hasMany(EnvironmentalEvent::class, 'created_by');
    }

    public function environmentalEventAcknowledgements()
    {
        return $this->hasMany(EnvironmentalEventAcknowledgement::class);
    }

    public function createdFieldDiaryActivities()
    {
        return $this->hasMany(FieldDiaryActivity::class, 'created_by');
    }

    public function fieldDiarySubmissions()
    {
        return $this->hasMany(FieldDiarySubmission::class);
    }

    public function reviewedFieldDiarySubmissions()
    {
        return $this->hasMany(FieldDiarySubmission::class, 'reviewed_by');
    }

    public function sendAccountInvitation(?string $roleName = null): string
    {
        return Password::broker()->sendResetLink(
            ['email' => $this->email, 'is_active' => true],
            function (User $user, string $token) use ($roleName): void {
                $user->notify(new UserCreatedNotification($token, $roleName));
            }
        );
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
