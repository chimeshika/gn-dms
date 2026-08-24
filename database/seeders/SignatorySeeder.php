<?php

namespace Database\Seeders;

use App\Enums\SignatoryCategory;
use App\Models\District;
use App\Models\DsDivision;
use App\Models\Signatory;
use Illuminate\Database\Seeder;

class SignatorySeeder extends Seeder
{
    public function run(): void
    {
        $this->nationalSignatories();
        $this->districtSignatories();
        $this->divisionalSignatories();
    }

    /**
     * Ministry-level signatories used by the letter generation:
     * - Secretary / officers signing on behalf of the Secretary
     * - The Controlling Officer (Senior Assistant Secretary - GN Administration)
     * - Special recipients for retirements (DG Pensions, Auditor General)
     */
    protected function nationalSignatories(): void
    {
        Signatory::whereNull('district_id')->whereNull('ds_division_id')->delete();

        $national = [
            [SignatoryCategory::Secretary, 'එස්. ආලෝකබණ්ඩාර', 'ලේකම්'],
            [SignatoryCategory::Secretary, 'එමාලි විමලරත්න', 'අතිරේක ලේකම් (ස්වදේශ කටයුතු)'],
            [SignatoryCategory::ControllingOfficer, 'එස්. ආර්. සෙනෙවිරත්න', 'ජ්‍යෙෂ්ඨ සහකාර ලේකම් (ග්‍රාම නිලධාරී පාලන)'],
            [SignatoryCategory::ControllingOfficer, 'ඩබ්ලිව්. ඒ. කුමාරසිංහ', 'සහකාර ලේකම්'],
            [SignatoryCategory::Special, 'ඒ. එම්. ජයසේකර', 'විශ්‍රාම වැටුප් අධ්‍යක්ෂ ජනරාල්'],
            [SignatoryCategory::Special, 'සී. ජේ. පී. ලොකුගේ', 'විගණකාධිපති'],
        ];

        foreach ($national as [$category, $name, $designation]) {
            Signatory::create([
                'category' => $category,
                'designation' => $designation,
                'officer_name' => $name,
                'is_active' => true,
            ]);
        }
    }

    protected function districtSignatories(): void
    {
        $names = [
            'Ampara' => 'W. M. Gunasekara',
            'Anuradhapura' => 'D. M. Jayatissa',
            'Badulla' => 'N. G. Wickramasinghe',
            'Batticaloa' => 'R. Sivakumar',
            'Colombo' => 'P. S. M. Charles',
            'Galle' => 'S. P. de Silva',
            'Gampaha' => 'M. A. P. Herath',
            'Hambantota' => 'K. D. N. Weerasinghe',
            'Jaffna' => 'S. Tharmalingam',
            'Kalutara' => 'A. M. C. Jayasuriya',
            'Kandy' => 'H. M. R. B. Herath',
            'Kegalle' => 'J. A. S. Karunaratne',
            'Kilinochchi' => 'V. Sivanesan',
            'Kurunegala' => 'T. M. R. Tennakoon',
            'Mannar' => 'K. Jeyaraj',
            'Matale' => 'R. M. S. B. Rathnayake',
            'Matara' => 'E. M. S. Ekanayake',
            'Monaragala' => 'H. M. D. K. Bandara',
            'Mullaitivu' => 'P. Yogaraj',
            'Nuwara Eliya' => 'D. M. A. Dissanayake',
            'Polonnaruwa' => 'N. M. A. Kulasekara',
            'Puttalam' => 'W. A. R. Priyanthi',
            'Ratnapura' => 'B. M. N. Wijesinghe',
            'Trincomalee' => 'M. S. M. Rizwan',
            'Vavuniya' => 'T. Sathiyaseelan',
        ];

        foreach ($names as $districtName => $officerName) {
            $district = District::where('name_en', $districtName)->first();

            if (! $district) {
                continue;
            }

            Signatory::updateOrCreate(
                ['category' => SignatoryCategory::District->value, 'district_id' => $district->id],
                [
                    'designation' => 'දිස්ත්‍රික් ලේකම්',
                    'officer_name' => $officerName,
                    'is_active' => true,
                ]
            );
        }
    }

    protected function divisionalSignatories(): void
    {
        $names = [
            'Colombo' => 'R. A. D. N. Bandara',
            'Homagama' => 'L. H. S. Premaratne',
            'Kaduwela' => 'C. M. Wijesundara',
            'Maharagama' => 'D. G. S. Kumarihami',
            'Negombo' => 'T. M. F. Saldin',
            'Gampaha' => 'W. K. Liyanage',
            'Kandy' => 'S. M. K. B. Senanayake',
        ];

        foreach ($names as $dsName => $officerName) {
            $ds = DsDivision::where('name_en', $dsName)->first();

            if (! $ds) {
                continue;
            }

            Signatory::updateOrCreate(
                ['category' => SignatoryCategory::Divisional->value, 'ds_division_id' => $ds->id],
                [
                    'designation' => 'ප්‍රාදේශීය ලේකම්',
                    'officer_name' => $officerName,
                    'is_active' => true,
                ]
            );
        }
    }
}
