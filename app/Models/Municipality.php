<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipality extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'type',
        'district',
        'zip_code',
        'location_description',
        'latitude',
        'longitude',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Direct link to view location on Google Maps.
     *
     * @return Attribute<?string, never>
     */
    protected function mapUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if ($this->latitude !== null && $this->longitude !== null) {
                    return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
                }

                if (! empty($this->location_description)) {
                    $encoded = urlencode($this->location_description);

                    return "https://www.google.com/maps/search/?api=1&query={$encoded}";
                }

                return null;
            }
        );
    }

    /**
     * Scope a query to only include active municipalities.
     *
     * @param  Builder<Municipality>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by district.
     *
     * @param  Builder<Municipality>  $query
     */
    public function scopeDistrict(Builder $query, ?string $district): void
    {
        if (! empty($district) && $district !== 'all') {
            $query->where('district', $district);
        }
    }

    /**
     * Scope a query to search municipalities.
     *
     * @param  Builder<Municipality>  $query
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('zip_code', 'like', "%{$search}%")
                    ->orWhere('location_description', 'like', "%{$search}%");
            });
        }
    }
}
