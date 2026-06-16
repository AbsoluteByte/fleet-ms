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
     *     director_salutation: string,
     *     footer_html: string
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
            'director_salutation' => 'Mr.',
            'footer_html' => e($company->name).', '.e($company->commaSeparatedAddress()).' '.e($company->phone).' | '.e($company->email).'.'
                .($company->company_registration_number
                    ? '<br>'.e($company->name).' is Registered in England and Wales with Company No. '.e($company->company_registration_number)
                    : ''),
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
                'director_salutation' => 'Mr.',
                'footer_html' => 'SAMORE TRADERS LIMITED<br>'
                    .'Company number 08741649<br>'
                    .'337b New Summer Street, Birmingham, England, B19 3RD',
            ],
            'proactive' => [
                'logo_uri' => $this->imageDataUri(public_path('uploads/companies/permission-letters/proactive-logo.png')),
                'signature_uri' => $this->imageDataUri(public_path('uploads/companies/permission-letters/proactive-signature.png')),
                'logo_align' => 'left',
                'date_align' => 'right',
                'policy_label' => 'POLICY NO:',
                'intro_company_short' => 'Proactive hybrid corporate ltd',
                'owned_by_name' => 'PROACTIVE HYBRID CORPORATE LTD',
                'signatory_name' => 'AMNA CHOUDHRY',
                'director_intro_name' => 'Amna Choudhry',
                'director_salutation' => 'Miss',
                'footer_html' => 'PROACTIVE HYBRID CORPORATE LTD<br>'
                    .'Company number 12298619<br>'
                    .'30 Brewery Street, Birmingham, B6 4JB',
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
