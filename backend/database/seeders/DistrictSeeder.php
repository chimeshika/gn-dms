<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            ['01', 'Ampara', 'අම්පාර', 'அம்பாறை'],
            ['02', 'Anuradhapura', 'අනුරාධපුර', 'அனுராதபுரம்'],
            ['03', 'Badulla', 'බදුල්ල', 'பதுளை'],
            ['04', 'Batticaloa', 'මඩකලපුව', 'மட்டக்களப்பு'],
            ['05', 'Colombo', 'කොළඹ', 'கொழும்பு'],
            ['06', 'Galle', 'ගාල්ල', 'காலி'],
            ['07', 'Gampaha', 'ගම්පහ', 'கம்பஹா'],
            ['08', 'Hambantota', 'හම්බන්තොට', 'அம்பாந்தோட்டை'],
            ['09', 'Jaffna', 'යාපනය', 'யாழ்ப்பாணம்'],
            ['10', 'Kalutara', 'කළුතර', 'களுத்துறை'],
            ['11', 'Kandy', 'මහනුවර', 'கண்டி'],
            ['12', 'Kegalle', 'කෑගල්ල', 'கேகாலை'],
            ['13', 'Kilinochchi', 'කිලිනොච්චිය', 'கிளிநொச்சி'],
            ['14', 'Kurunegala', 'කුරුණෑගල', 'குருணாகல்'],
            ['15', 'Mannar', 'මන්නාරම', 'மன்னார்'],
            ['16', 'Matale', 'මාතලේ', 'மாத்தளை'],
            ['17', 'Matara', 'මාතර', 'மாத்தறை'],
            ['18', 'Monaragala', 'මොණරාගල', 'மொணராகலை'],
            ['19', 'Mullaitivu', 'මුලතිව්', 'முல்லைத்தீவு'],
            ['20', 'Nuwara Eliya', 'නුවරඑළිය', 'நுவரெலியா'],
            ['21', 'Polonnaruwa', 'පොළොන්නරුව', 'பொலன்னறுவை'],
            ['22', 'Puttalam', 'පුත්තලම', 'புத்தளம்'],
            ['23', 'Ratnapura', 'රත්නපුර', 'இரத்தினபுரி'],
            ['24', 'Trincomalee', 'ත්රිකුණාමලය', 'திருகோணமலை'],
            ['25', 'Vavuniya', 'වවුනියාව', 'வவுனியா'],
        ];

        foreach ($districts as [$code, $en, $si, $ta]) {
            District::updateOrCreate(
                ['code' => $code],
                ['name_en' => $en, 'name_si' => $si, 'name_ta' => $ta]
            );
        }
    }
}
