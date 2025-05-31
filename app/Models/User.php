<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get or create Wix customer for this user
     *
     * This method tries to find a Wix customer by the user's email. If not found, it creates a new customer in Wix Contacts using the user's name, email, and phone.
     * @return array|null Wix customer data or null on failure
     */
    public function getWixCustomer()
    {
        $email = $this->email;
        $customer = \App\Http\Controllers\WixApiController::findCustomerByEmail($email);
        if ($customer) {
            return $customer;
        }
        // Split the user's name into first and last name
        $nameParts = explode(' ', $this->name ?? '');
        $first = $nameParts[0] ?? '';
        $last = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';
        $data = [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $this->email,
            'phone' => $this->phone ?? '',
        ];
        return \App\Http\Controllers\WixApiController::createCustomer($data);
    }
}
