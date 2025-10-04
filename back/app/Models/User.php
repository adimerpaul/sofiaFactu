<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'active',
        'agencia',
        'role',
        'avatar',
        'celular',
    ];
    protected $appends = ['color'];
    public function getColorAttribute(){
        $roles = [
            'Admin' => 'red',
            'Usuario' => 'green',
        ];
        return $roles[$this->role] ?? 'blue';
    }
    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
