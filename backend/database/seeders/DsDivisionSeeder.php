<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\DsDivision;
use Illuminate\Database\Seeder;

class DsDivisionSeeder extends Seeder
{
    /**
     * Curated (representative) DS Divisions per district. Expand freely with
     * the full list of 331 Divisional Secretariats for production.
     *
     * @var array<string, array<int, string>>
     */
    protected array $divisions = [
        'Ampara' => ['Ampara', 'Kalmunai', 'Dehiattakandiya', 'Mahoya', 'Padiyatalawa', 'Akkaraipattu'],
        'Anuradhapura' => ['Anuradhapura', 'Nuwaragam Palatha', 'Mihintale', 'Horowpothana', 'Kahatagasdigiliya', 'Kebithigollewa'],
        'Badulla' => ['Badulla', 'Bandarawela', 'Hali-Ela', 'Mahiyanganaya', 'Passara', 'Welimada'],
        'Batticaloa' => ['Batticaloa', 'Eravur Pattu', 'Kattankudy', 'Koralai Pattu', 'Manmunai South', 'Valaichchenai'],
        'Colombo' => ['Colombo', 'Homagama', 'Kaduwela', 'Maharagama', 'Moratuwa', 'Dehiwala'],
        'Galle' => ['Galle', 'Ambalangoda', 'Elpitiya', 'Hikkaduwa', 'Habaraduwa', 'Akmeemana'],
        'Gampaha' => ['Gampaha', 'Negombo', 'Attanagalla', 'Katana', 'Minuwangoda', 'Wattala'],
        'Hambantota' => ['Hambantota', 'Tangalle', 'Ambalantota', 'Beliatta', 'Tissamaharama', 'Katuwana'],
        'Jaffna' => ['Jaffna', 'Valikamam', 'Point Pedro', 'Nallur', 'Karainagar', 'Chavakacheri'],
        'Kalutara' => ['Kalutara', 'Beruwala', 'Panadura', 'Horana', 'Bulathsinhala', 'Dodangoda'],
        'Kandy' => ['Kandy', 'Gangawata Korale', 'Kundasale', 'Udapalatha', 'Yatinuwara', 'Harispattuwa'],
        'Kegalle' => ['Kegalle', 'Mawanella', 'Rambukkana', 'Warakapola', 'Dehiovita', 'Aranayake'],
        'Kilinochchi' => ['Kilinochchi', 'Karachchi', 'Poonakary', 'Pachchilaipalli'],
        'Kurunegala' => ['Kurunegala', 'Kuliyapitiya', 'Polgahawela', 'Narammala', 'Bingiriya', 'Wariyapola'],
        'Mannar' => ['Mannar', 'Madhu', 'Manthai West'],
        'Matale' => ['Matale', 'Dambulla', 'Rattota', 'Galewela', 'Ukuwela', 'Wilgamuwa'],
        'Matara' => ['Matara', 'Akuressa', 'Weligama', 'Hakmana', 'Devinuwara', 'Kamburupitiya'],
        'Monaragala' => ['Monaragala', 'Bibile', 'Wellawaya', 'Badalkumbura', 'Medagama', 'Sella Kataragama'],
        'Mullaitivu' => ['Mullaitivu', 'Manthai East', 'Welikanda'],
        'Nuwara Eliya' => ['Nuwara Eliya', 'Walapane', 'Kotmale', 'Hanguranketha', 'Ambagamuwa', 'Lindula'],
        'Polonnaruwa' => ['Polonnaruwa', 'Medirigiriya', 'Thamankaduwa', 'Hingurakgoda', 'Lankapura'],
        'Puttalam' => ['Puttalam', 'Chilaw', 'Wenappuwa', 'Kalpitiya', 'Nattandiya', 'Wennappuwa'],
        'Ratnapura' => ['Ratnapura', 'Balangoda', 'Eheliyagoda', 'Nivitigala', 'Kuruwita', 'Pelmadulla'],
        'Trincomalee' => ['Trincomalee', 'Kinniya', 'Kantalai', 'Muthur', 'Kuchchaveli'],
        'Vavuniya' => ['Vavuniya', 'Vavuniya South', 'Vavuniya North', 'Vengalacheddikulam'],
    ];

    /**
     * Sinhala names for the curated DS Divisions (used in official Sinhala letters).
     *
     * @var array<string, string>
     */
    protected array $sinhalaNames = [
        'Ampara' => 'අම්පාර',
        'Kalmunai' => 'කල්මුනායි',
        'Dehiattakandiya' => 'දෙහිඅත්තකණ්ඩිය',
        'Mahoya' => 'මහඔය',
        'Padiyatalawa' => 'පඩියතලාව',
        'Akkaraipattu' => 'අක්කරපත්තුව',
        'Anuradhapura' => 'අනුරාධපුර',
        'Nuwaragam Palatha' => 'නුවරගම් පලාත',
        'Mihintale' => 'මිහින්තලේ',
        'Horowpothana' => 'හොරොව්පොතාන',
        'Kahatagasdigiliya' => 'කහටගස්දිගිලිය',
        'Kebithigollewa' => 'කෙබිතිගොල්ලෑව',
        'Badulla' => 'බදුල්ල',
        'Bandarawela' => 'බණ්ඩාරවෙල',
        'Hali-Ela' => 'හලිඇල',
        'Mahiyanganaya' => 'මහියංගනය',
        'Passara' => 'පස්සර',
        'Welimada' => 'වැලිමඩ',
        'Batticaloa' => 'මඩකලපුව',
        'Eravur Pattu' => 'එරාවූර් පත්තුව',
        'Kattankudy' => 'කාත්තන්කුඩි',
        'Koralai Pattu' => 'කොරලෛ පත්තුව',
        'Manmunai South' => 'මාන්මුනේ දකුණ',
        'Valaichchenai' => 'වාලච්චේන',
        'Colombo' => 'කොළඹ',
        'Homagama' => 'හෝමාගම',
        'Kaduwela' => 'කඩුවෙල',
        'Maharagama' => 'මහරගම',
        'Moratuwa' => 'මොරටුව',
        'Dehiwala' => 'දෙහිවල',
        'Galle' => 'ගාල්ල',
        'Ambalangoda' => 'අම්බලන්ගොඩ',
        'Elpitiya' => 'ඇල්පිටිය',
        'Hikkaduwa' => 'හික්කඩුව',
        'Habaraduwa' => 'හබරාදුව',
        'Akmeemana' => 'අක්මීමන',
        'Gampaha' => 'ගම්පහ',
        'Negombo' => 'මීගමුව',
        'Attanagalla' => 'අත්තනගල්ල',
        'Katana' => 'කටාන',
        'Minuwangoda' => 'මීනුවන්ගොඩ',
        'Wattala' => 'වත්තල',
        'Hambantota' => 'හම්බන්තොට',
        'Tangalle' => 'තංගල්ල',
        'Ambalantota' => 'අම්බලන්තොට',
        'Beliatta' => 'බෙලිඅත්ත',
        'Tissamaharama' => 'තිස්සමහාරාමය',
        'Katuwana' => 'කටුවන',
        'Jaffna' => 'යාපනය',
        'Valikamam' => 'වලිකාමම්',
        'Point Pedro' => 'පේදුරුතුඩුව',
        'Nallur' => 'නල්ලූර්',
        'Karainagar' => 'කරයිනගර්',
        'Chavakacheri' => 'චාවකච්චේරි',
        'Kalutara' => 'කළුතර',
        'Beruwala' => 'බේරුවල',
        'Panadura' => 'පානදුර',
        'Horana' => 'හොරණ',
        'Bulathsinhala' => 'බුලත්සිංහල',
        'Dodangoda' => 'දොඩන්ගොඩ',
        'Kandy' => 'මහනුවර',
        'Gangawata Korale' => 'ගංගාවට කෝරලේ',
        'Kundasale' => 'කුණ්ඩසාලේ',
        'Udapalatha' => 'උඩපලාත',
        'Yatinuwara' => 'යටිනුවර',
        'Harispattuwa' => 'හාරිස්පත්තුව',
        'Kegalle' => 'කෑගල්ල',
        'Mawanella' => 'මාවනැල්ල',
        'Rambukkana' => 'රඹුක්කන',
        'Warakapola' => 'වරකාපොල',
        'Dehiovita' => 'දෙහියොවිට',
        'Aranayake' => 'අරණායක',
        'Kilinochchi' => 'කිලිනොච්චිය',
        'Karachchi' => 'කරච්චි',
        'Poonakary' => 'පූනකරී',
        'Pachchilaipalli' => 'පච්චිලෙයිපල්ලි',
        'Kurunegala' => 'කුරුණෑගල',
        'Kuliyapitiya' => 'කුලියාපිටිය',
        'Polgahawela' => 'පොල්ගහවෙල',
        'Narammala' => 'නාරම්මල',
        'Bingiriya' => 'බිංගිරිය',
        'Wariyapola' => 'වාරියපොල',
        'Mannar' => 'මන්නාරම',
        'Madhu' => 'මධු',
        'Manthai West' => 'මන්තායි බටහිර',
        'Matale' => 'මාතලේ',
        'Dambulla' => 'දඹුල්ල',
        'Rattota' => 'රත්තොට',
        'Galewela' => 'ගලේවෙල',
        'Ukuwela' => 'උකුවෙල',
        'Wilgamuwa' => 'විල්ගමුව',
        'Matara' => 'මාතර',
        'Akuressa' => 'අකුරැස්ස',
        'Weligama' => 'වැලිගම',
        'Hakmana' => 'හක්මන',
        'Devinuwara' => 'දෙවිනුවර',
        'Kamburupitiya' => 'කඹුරුපිටිය',
        'Monaragala' => 'මොණරාගල',
        'Bibile' => 'බිබිල',
        'Wellawaya' => 'වැල්ලවාය',
        'Badalkumbura' => 'බඩල්කුඹුර',
        'Medagama' => 'මැදගම',
        'Sella Kataragama' => 'සෙල්ල කතරගම',
        'Mullaitivu' => 'මුලතිව්',
        'Manthai East' => 'මන්තායි නැගෙනහිර',
        'Welikanda' => 'වැලිකණ්ඩ',
        'Nuwara Eliya' => 'නුවරඑළිය',
        'Walapane' => 'වලපනේ',
        'Kotmale' => 'කොත්මලේ',
        'Hanguranketha' => 'හංගුරන්කෙත',
        'Ambagamuwa' => 'අම්බගමුව',
        'Lindula' => 'ලිඳුල',
        'Polonnaruwa' => 'පොළොන්නරුව',
        'Medirigiriya' => 'මැදිරිගිරිය',
        'Thamankaduwa' => 'තමන්කඩුව',
        'Hingurakgoda' => 'හිඟුරක්ගොඩ',
        'Lankapura' => 'ලංකාපුර',
        'Puttalam' => 'පුත්තලම',
        'Chilaw' => 'හලාවත',
        'Wenappuwa' => 'වැනආප්පුව',
        'Kalpitiya' => 'කල්පිටිය',
        'Nattandiya' => 'නාත්තණ්ඩිය',
        'Wennappuwa' => 'වෙන්නප්පුව',
        'Ratnapura' => 'රත්නපුර',
        'Balangoda' => 'බලංගොඩ',
        'Eheliyagoda' => 'ඇහැලියගොඩ',
        'Nivitigala' => 'නිවිතිගල',
        'Kuruwita' => 'කුරුවිට',
        'Pelmadulla' => 'පැල්මඩුල්ල',
        'Trincomalee' => 'ත්‍රිකුණාමලය',
        'Kinniya' => 'කින්නියා',
        'Kantalai' => 'කන්තලේ',
        'Muthur' => 'මුතූර්',
        'Kuchchaveli' => 'කුච්චවේලි',
        'Vavuniya' => 'වවුනියාව',
        'Vavuniya South' => 'වවුනියාව දකුණ',
        'Vavuniya North' => 'වවුනියාව උතුර',
        'Vengalacheddikulam' => 'වෙන්ගලචෙඩ්ඩිකුලම්',
    ];

    public function run(): void
    {
        foreach ($this->divisions as $districtName => $divisionNames) {
            $district = District::where('name_en', $districtName)->first();

            if (! $district) {
                continue;
            }

            foreach ($divisionNames as $index => $name) {
                DsDivision::updateOrCreate(
                    ['district_id' => $district->id, 'name_en' => $name],
                    [
                        'code' => $district->code.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                        'name_si' => $this->sinhalaNames[$name] ?? null,
                    ]
                );
            }
        }
    }
}
