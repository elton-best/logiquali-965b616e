<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterEnterpriseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enterprise_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:enterprises,email',
            'registration_number' => 'required|string|unique:enterprises,registration_number',
            'address' => 'required|string',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'field' => 'required|string|max:255',
            'documents' => 'nullable|array',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'enterprise_name.required' => 'Le nom de l\'entreprise est requis',
            'email.required' => 'L\'email est requis',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'registration_number.required' => 'Le numéro d\'enregistrement est requis',
            'registration_number.unique' => 'Ce numéro d\'enregistrement existe déjà',
            'address.required' => 'L\'adresse est requise',
            'field.required' => 'Le domaine d\'activité est requis',
            'username.required' => 'Le nom d\'utilisateur est requis',
            'username.unique' => 'Ce nom d\'utilisateur est déjà utilisé',
            'password.required' => 'Le mot de passe est requis',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
            'documents.*.mimes' => 'Les documents doivent être au format PDF, JPG, JPEG ou PNG',
            'documents.*.max' => 'Chaque document ne doit pas dépasser 5 Mo',
        ];
    }
}
