<?php

namespace Modules\Sorties\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSortieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorisable par défaut car le middleware auth:sanctum bloque en amont
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:VENTE,DECES,PERTE',
            'date_sortie' => 'required|date|before_or_equal:today',
            'prix' => 'nullable|required_if:type,VENTE|numeric|min:0',
            'acheteur' => 'nullable|string|max:150',
            'cause' => 'nullable|required_if:type,DECES|string|max:255',
            'circonstance' => 'nullable|required_if:type,PERTE|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de sortie est obligatoire',
            'type.in' => 'Le type doit être VENTE, DECES ou PERTE',
            'date_sortie.required' => 'La date de sortie est obligatoire',
            'date_sortie.date' => 'La date doit être valide',
            'date_sortie.before_or_equal' => 'La date ne peut pas être dans le futur',
            'prix.required_if' => 'Le prix est obligatoire pour une vente',
            'prix.numeric' => 'Le prix doit être un nombre valide',
            'prix.min' => 'Le prix ne peut pas être négatif',
            'acheteur.max' => 'Le nom de l\'acheteur ne doit pas dépasser 150 caractères',
            'cause.required_if' => 'La cause est obligatoire pour un décès',
            'cause.max' => 'La cause ne doit pas dépasser 255 caractères',
            'circonstance.required_if' => 'La circonstance est obligatoire pour une perte',
            'circonstance.max' => 'La circonstance ne doit pas dépasser 255 caractères',
            'notes.max' => 'Les notes ne doivent pas dépasser 1000 caractères',
        ];
    }
}
