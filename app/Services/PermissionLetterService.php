<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;

class PermissionLetterService
{
    /**
     * @return array{
     *     logo_uri: ?string,
     *     signature_uri: ?string,
     *     logo_align: string,
     *     date_align: string,
     *     policy_label: string,
     *     intro_company_short: string,
     *     owned_by_name: string,
     *     signatory_name: string,
     *     director_intro_name: ?string,
     *     footer_style: string
     * }
     */
    public function resolveLetterMeta(Company $company): array
    {
        $default = [
            'logo_uri' => $this->imageDataUri(
                $company->logo ? public_path('uploads/companies/'.$company->logo) : null
            ),
            'signature_uri' => null,
            'logo_align' => 'center',
            'date_align' => 'left',
            'policy_label' => 'INSURANCE POLICY NO:',
            'intro_company_short' => $company->name,
            'owned_by_name' => strtoupper($company->name),
            'signatory_name' => strtoupper($company->director_name ?? ''),
            'director_intro_name' => null,
            'footer_style' => 'generic',
        ];

        $presets = [
            'samore' => [
                'logo_uri' => $this->imageDataUri(public_path('uploads/companies/permission-letters/samore-logo.png')),
                'signature_uri' => $this->imageDataUri(public_path('uploads/companies/permission-letters/samore-signature.png')),
                'logo_align' => 'right',
                'date_align' => 'left',
                'policy_label' => 'INSURANCE POLICY NO:',
                'intro_company_short' => 'Samore Traders Ltd',
                'owned_by_name' => 'SAMORE TRADERS LTD',
                'signatory_name' => 'JAWAD SAMORE',
                'director_intro_name' => 'Jawad Ahmed Samore',
                'footer_style' => 'samore',
            ],
            'proactive' => [
                'logo_uri' => $this->imageDataUri(public_path('uploads/companies/permission-letters/proactive-logo.png')),
                'signature_uri' => $this->imageDataUri(public_path('uploads/companies/permission-letters/proactive-signature.png')),
                'logo_align' => 'left',
                'date_align' => 'right',
                'policy_label' => 'POLICY NO:',
                'intro_company_short' => 'Proactive hybrid corporate ltd',
                'owned_by_name' => 'PROACTIVE HYBRID CORPORATE LTD',
                'signatory_name' => 'JAWAD SAMORE',
                'director_intro_name' => 'Jawad Samore',
                'footer_style' => 'proactive',
            ],
        ];

        $key = $this->resolveCompanyKey($company);

        return array_merge($default, $key ? ($presets[$key] ?? []) : []);
    }

    private function resolveCompanyKey(Company $company): ?string
    {
        $name = strtolower($company->name);

        if (str_contains($name, 'samore')) {
            return 'samore';
        }

        if (str_contains($name, 'proactive')) {
            return 'proactive';
        }

        return null;
    }

    private function imageDataUri(?string $path): ?string
    {
        if (! $path || ! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
