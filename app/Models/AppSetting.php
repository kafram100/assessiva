<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AppSetting extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = ['key', 'value', 'updated_at'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        if (! self::tableExists()) {
            return $default;
        }

        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        if (! self::tableExists()) {
            return;
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()],
        );
    }

    private static function tableExists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        $exists = Schema::hasTable((new static)->getTable());

        return $exists;
    }
}
