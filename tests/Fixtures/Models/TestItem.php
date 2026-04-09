<?php

declare(strict_types=1);

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

class TestItem extends Model
{
    protected $fillable = ['name'];

    protected function casts(): array
    {
        return [
            'name' => 'string',
        ];
    }
}
