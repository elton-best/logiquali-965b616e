<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class EnterpriseConfigurationController extends Controller
{
    /**
     * Mettre à jour le branding
     */
    public function updateBranding(Request $request, Enterprise $enterprise): JsonResponse
    {
        $this->authorize('update', $enterprise);

        $validated = $request->validate([
            'slogan' => 'nullable|string|max:255',
            'brand_colors' => 'nullable|array',
            'brand_colors.primary' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'brand_colors.secondary' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'brand_colors.accent' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'header_font' => 'nullable|string|in:Arial,Helvetica,Times New Roman,Courier,Verdana',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($enterprise->logo_path) {
                Storage::disk('public')->delete($enterprise->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $enterprise->update($validated);

        return response()->json($enterprise);
    }

    /**
     * Mettre à jour les informations légales
     */
    public function updateLegalInfo(Request $request, Enterprise $enterprise): JsonResponse
    {
        $this->authorize('update', $enterprise);

        $country = $request->input('country', $enterprise->country);
        $profile = config("legal_profiles.{$country}", config('legal_profiles.DEFAULT'));

        $rules = [
            'country' => 'nullable|string|max:10',
            'legal_form' => 'nullable|string|max:50',
            'share_capital' => 'nullable|string|max:100',
            'legal_profile_config' => 'nullable|array',
            'rccm_number' => 'nullable|string|max:100',
            'ifu_number' => 'nullable|string|max:100',
            'cnss_number' => 'nullable|string|max:100',
            'fodefca_number' => 'nullable|string|max:100',
            'siret' => 'nullable|string|max:100',
            'rcs' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:100',
            'ape_code' => 'nullable|string|max:100',
            'trade_register_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:100',
        ];

        foreach ($profile['fields'] ?? [] as $field) {
            $rules[$field] = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules);

        $enterprise->update($validated);

        return response()->json($enterprise);
    }

    /**
     * Mettre à jour les coordonnées
     */
    public function updateContact(Request $request, Enterprise $enterprise): JsonResponse
    {
        $this->authorize('update', $enterprise);

        $validated = $request->validate([
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone_primary' => 'nullable|string|max:50',
            'phone_secondary' => 'nullable|string|max:50',
            'phone_types' => 'nullable|array',
            'email_general' => 'nullable|email|max:255',
            'email_support' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'social_media' => 'nullable|array',
        ]);

        $enterprise->update($validated);

        return response()->json($enterprise);
    }

    /**
     * Mettre à jour la configuration des documents
     */
    public function updateDocumentConfig(Request $request, Enterprise $enterprise): JsonResponse
    {
        $this->authorize('update', $enterprise);

        $validated = $request->validate([
            'header_footer_config' => 'required|array',
            'header_footer_config.header' => 'required|array',
            'header_footer_config.footer' => 'required|array',
            'header_footer_config.document_types' => 'nullable|array',
        ]);

        $enterprise->update($validated);

        return response()->json($enterprise);
    }

    /**
     * Obtenir la configuration complète
     */
    public function show(Enterprise $enterprise): JsonResponse
    {
        $this->authorize('view', $enterprise);

        $resolvedRccm = $enterprise->rccm_number ?: $enterprise->registration_number;
        $resolvedAddress = $enterprise->address_line_1 ?: $enterprise->address;
        $resolvedPhone = $enterprise->phone_primary ?: $enterprise->phone;
        $resolvedEmail = $enterprise->email_general ?: $enterprise->email;

        return response()->json([
            'enterprise' => [
                'id' => $enterprise->id,
                'name' => $enterprise->name,
                'sigle' => $enterprise->sigle,
                'codification_mode' => $enterprise->codification_mode,
                'email' => $resolvedEmail,
                'phone' => $resolvedPhone,
                'address' => $resolvedAddress,
                'city' => $enterprise->city,
                'country' => $enterprise->country,
                'field' => $enterprise->field,
                'domaine_activite_set' => (bool) $enterprise->domaine_activite_set,
                'logo_path' => $enterprise->logo_path,
            ],
            'branding' => [
                'slogan' => $enterprise->slogan,
                'brand_colors' => $enterprise->getBrandColors(),
                'header_font' => $enterprise->header_font,
                'logo_path' => $enterprise->logo_path,
            ],
            'legal' => $enterprise->getLegalInfo(),
            'legal_raw' => [
                'country' => $enterprise->country,
                'rccm_number' => $resolvedRccm,
                'ifu_number' => $enterprise->ifu_number,
                'cnss_number' => $enterprise->cnss_number,
                'fodefca_number' => $enterprise->fodefca_number,
                'siret' => $enterprise->siret,
                'rcs' => $enterprise->rcs,
                'vat_number' => $enterprise->vat_number,
                'ape_code' => $enterprise->ape_code,
                'legal_form' => $enterprise->legal_form,
                'share_capital' => $enterprise->share_capital,
                'trade_register_number' => $enterprise->trade_register_number,
                'tax_id' => $enterprise->tax_id,
            ],
            'contact' => [
                'address_line_1' => $resolvedAddress,
                'address_line_2' => $enterprise->address_line_2,
                'postal_code' => $enterprise->postal_code,
                'city' => $enterprise->city,
                'country' => $enterprise->country,
                'phone_primary' => $resolvedPhone,
                'phone_secondary' => $enterprise->phone_secondary,
                'email_general' => $resolvedEmail,
                'email_support' => $enterprise->email_support,
                'website' => $enterprise->website,
                'social_media' => $enterprise->social_media,
            ],
            'document_config' => $enterprise->header_footer_config,
            'certifications' => $enterprise->activeCertifications()->with('certification')->get(),
        ]);
    }
}
