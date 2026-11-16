<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    protected $primaryKey = 'id_user';

    public $timestamps = false;

    protected $guarded = ['id_user'];

    protected $hidden = ['password', 'ga_secret'];

    protected function casts(): array
    {
        return ['st_aktif' => 'boolean', 'deleted' => 'boolean'];
    }

    public function active(): bool
    {
        return $this->st_aktif && ! $this->deleted;
    }

    public function totpSecret(): ?string
    {
        if (! $this->ga_secret) {
            return null;
        }

        return str_starts_with($this->ga_secret, 'encrypted:')
            ? Crypt::decryptString(substr($this->ga_secret, 10))
            : $this->ga_secret;
    }

    public function getRememberTokenName()
    {
        return null;
    }
}
