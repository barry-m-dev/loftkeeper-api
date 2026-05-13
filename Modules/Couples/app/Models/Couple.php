<?php

namespace Modules\Couples\Models;

use App\Core\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Couple extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'code',
        'male_id',
        'femelle_id',
        'cage_id',
        'date_formation',
        'date_rupture',
        'statut',
        'user_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_formation' => 'date',
        'date_rupture' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot function pour générer UUID automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($couple) {
            if (empty($couple->uuid)) {
                $couple->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relation : Un couple a un mâle (pigeon)
     */
    public function male()
    {
        return $this->belongsTo(\Modules\Pigeons\Models\Pigeon::class, 'male_id');
    }

    /**
     * Relation : Un couple a une femelle (pigeon)
     */
    public function femelle()
    {
        return $this->belongsTo(\Modules\Pigeons\Models\Pigeon::class, 'femelle_id');
    }

    /**
     * Relation : Un couple peut être dans une cage
     */
    public function cage()
    {
        return $this->belongsTo(\Modules\Cages\Models\Cage::class);
    }

    /**
     * Relation : Un couple appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class);
    }

    /**
     * Relation : Un couple peut avoir plusieurs reproductions
     */
    public function reproductions()
    {
        return $this->hasMany(\Modules\Reproductions\Models\Reproduction::class);
    }

    /**
     * Scope : Couples actifs uniquement
     */
    public function scopeActifs($query)
    {
        return $query->where('statut', 'ACTIF');
    }

    /**
     * Scope : Couples rompus uniquement
     */
    public function scopeRompus($query)
    {
        return $query->where('statut', 'ROMPU');
    }

    /**
     * Scope : Couples avec cage
     */
    public function scopeAvecCage($query)
    {
        return $query->whereNotNull('cage_id');
    }

    /**
     * Scope : Couples sans cage
     */
    public function scopeSansCage($query)
    {
        return $query->whereNull('cage_id');
    }
}
