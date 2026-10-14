<?php

// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:150'],
            'courriel' => ['required', 'email', 'max:190'],
            'organisation' => ['nullable', 'string', 'max:190'],
            'sujet' => ['required', 'string', 'max:190'],
            'heures' => ['nullable', 'integer', 'min:1', 'max:500'],
            'niveau' => ['nullable', 'in:debutant,intermediaire,avance'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consentement' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 150 caractères.',
            'courriel.required' => 'Le courriel est obligatoire.',
            'courriel.email' => 'Veuillez fournir une adresse courriel valide.',
            'courriel.max' => 'Le courriel ne peut pas dépasser 190 caractères.',
            'sujet.required' => 'Le sujet de la formation souhaitée est obligatoire.',
            'sujet.max' => 'Le sujet ne peut pas dépasser 190 caractères.',
            'heures.integer' => 'La durée doit être un nombre entier d\'heures.',
            'heures.min' => 'La durée minimale est d\'une heure.',
            'heures.max' => 'La durée maximale est de 500 heures.',
            'niveau.in' => 'Le niveau doit être débutant, intermédiaire ou avancé.',
            'participants.integer' => 'Le nombre de participants doit être un nombre entier.',
            'participants.min' => 'Il faut au moins un participant.',
            'participants.max' => 'Le nombre maximal de participants est 1000.',
            'message.required' => 'Veuillez décrire votre besoin.',
            'message.min' => 'Votre message doit contenir au moins 10 caractères.',
            'message.max' => 'Votre message ne peut pas dépasser 5000 caractères.',
            'consentement.required' => 'Vous devez accepter que nous utilisions vos coordonnées pour vous répondre.',
            'consentement.accepted' => 'Vous devez accepter que nous utilisions vos coordonnées pour vous répondre.',
        ];
    }
}
