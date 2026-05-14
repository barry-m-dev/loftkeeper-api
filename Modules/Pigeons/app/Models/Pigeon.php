<?php

namespace Modules\Pigeons\Models;

use App\Core\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Pigeon extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'bague',
        'bague_physique',
        'nom',
        'photo',
        'sexe',
        'date_naissance',
        'race',
        'couleur',
        'statut',
        'pere_id',
        'mere_id',
        'cage_id',
        'user_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_naissance' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['age', 'is_disponible', 'statut_disponibilite'];

    /**
     * Boot function pour générer UUID automatiquement
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pigeon) {
            if (empty($pigeon->uuid)) {
                $pigeon->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Accesseur : Calculer le statut de disponibilité dynamique du pigeon
     * 
     * Logique :
     * - Si cage_id existe → EN_CAGE
     * - Si dans un couple actif → EN_COUPLE
     * - Sinon → DISPONIBLE
     * 
     * Note : Ceci est différent de la colonne 'statut' (ACTIF/VENDU/MORT/PERDU)
     * 
     * @return string
     */
    public function getStatutDisponibiliteAttribute(): string
    {
        // Si le pigeon est dans une cage
        if (!is_null($this->cage_id)) {
            return 'EN_CAGE';
        }

        // Si le pigeon est dans un couple actif
        if ($this->hasCouple()) {
            return 'EN_COUPLE';
        }

        // Sinon, le pigeon est disponible
        return 'DISPONIBLE';
    }

    /**
     * Relation : Un pigeon appartient à un utilisateur
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Users\Models\User::class);
    }

    /**
     * Relation : Un pigeon peut être dans une cage
     */
    public function cage()
    {
        return $this->belongsTo(\Modules\Cages\Models\Cage::class);
    }

    /**
     * Relation : Un pigeon a un père (auto-relation)
     * withoutGlobalScopes() pour voir les pères même s'ils sont d'un autre user ou sortis
     */
    public function pere()
    {
        return $this->belongsTo(Pigeon::class, 'pere_id')->withoutGlobalScopes();
    }

    /**
     * Relation : Un pigeon a une mère (auto-relation)
     * withoutGlobalScopes() pour voir les mères même si elles sont d'un autre user ou sorties
     */
    public function mere()
    {
        return $this->belongsTo(Pigeon::class, 'mere_id')->withoutGlobalScopes();
    }

    /**
     * Relation : Un pigeon peut avoir des enfants
     * withoutGlobalScopes() pour voir tous les enfants
     */
    public function enfants()
    {
        return $this->hasMany(Pigeon::class, 'pere_id')
            ->orWhere('mere_id', $this->id)
            ->withoutGlobalScopes();
    }

    /**
     * Relation : Un pigeon peut avoir une sortie
     */
    public function sortie()
    {
        return $this->hasOne(\Modules\Sorties\Models\Sortie::class);
    }

    /**
     * Scope : Pigeons actifs uniquement
     */
    public function scopeActifs(Builder $query): void
    {
        $query->where('statut', 'ACTIF');
    }

    /**
     * Scope : Pigeons mâles uniquement
     */
    public function scopeMales(Builder $query): void
    {
        $query->where('sexe', 'MALE');
    }

    /**
     * Scope : Pigeons femelles uniquement
     */
    public function scopeFemelles(Builder $query): void
    {
        $query->where('sexe', 'FEMELLE');
    }

    /**
     * Scope : Pigeons disponibles (actifs et sans couple)
     */
    public function scopeDisponibles(Builder $query): void
    {
        $query->where('statut', 'ACTIF')
            ->whereDoesntHave('couplesMale')
            ->whereDoesntHave('couplesFemelle');
    }

    /**
     * Relation : Couples où ce pigeon est le mâle
     */
    public function couplesMale()
    {
        return $this->hasMany(\Modules\Couples\Models\Couple::class, 'male_id')
            ->where('statut', 'ACTIF');
    }

    /**
     * Relation : Couples où ce pigeon est la femelle
     */
    public function couplesFemelle()
    {
        return $this->hasMany(\Modules\Couples\Models\Couple::class, 'femelle_id')
            ->where('statut', 'ACTIF');
    }

    /**
     * Accesseur : URL complète de la photo du pigeon
     * Retourne l'URL de la photo si elle existe, sinon une image par défaut
     */
    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? \Storage::url($this->photo)
            : asset('images/default-pigeon.png');
    }

    /**
     * Accesseur : Calculer l'âge du pigeon en années
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }

        return $this->date_naissance->diffInYears(now());
    }

    /**
     * Accesseur : Vérifier si le pigeon est disponible pour un couple
     */
    public function getIsDisponibleAttribute(): bool
    {
        if ($this->statut !== 'ACTIF') {
            return false;
        }

        // Vérifier si le pigeon n'est pas déjà dans un couple actif
        $hasActiveCouple = $this->couplesMale()->exists() || $this->couplesFemelle()->exists();

        return !$hasActiveCouple;
    }

    // ==================== MÉTHODES HELPER ====================

    /**
     * Vérifier si le pigeon est actif
     */
    public function isActif(): bool
    {
        return $this->statut === 'ACTIF';
    }

    /**
     * Vérifier si le pigeon est dans une cage
     */
    public function hasCage(): bool
    {
        return !is_null($this->cage_id);
    }

    /**
     * Vérifier si le pigeon est dans un couple actif
     */
    public function hasCouple(): bool
    {
        return $this->couplesMale()->exists() || $this->couplesFemelle()->exists();
    }

    /**
     * Vérifier si le pigeon a des enfants
     */
    public function hasEnfants(): bool
    {
        return $this->enfants()->exists();
    }
}
