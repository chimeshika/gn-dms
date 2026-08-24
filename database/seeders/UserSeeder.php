<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\District;
use App\Models\DsDivision;
use App\Models\GnDivision;
use App\Models\Officer;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->getOutput()->writeln('<info>Seeding demo users...</info>');

        $colombo = District::where('name_en', 'Colombo')->first();
        $homagama = DsDivision::where('name_en', 'Homagama')->where('district_id', $colombo->id)->first();
        $kaduwela = DsDivision::where('name_en', 'Kaduwela')->where('district_id', $colombo->id)->first();

        $admin = $this->createUser(
            'Home Affairs Admin',
            'admin@gn.gov.lk',
            'password',
            UserRole::MainAdmin,
            null,
            null,
        );

        $districtAdmin = $this->createUser(
            'Colombo District Admin',
            'district.admin@gn.gov.lk',
            'password',
            UserRole::DistrictAdmin,
            $colombo->id,
            null,
        );

        $divAdmin = $this->createUser(
            'Homagama Divisional Admin',
            'div.admin@gn.gov.lk',
            'password',
            UserRole::DivisionalAdmin,
            $colombo->id,
            $homagama->id,
        );

        $this->createOfficer(
            '921234567V',
            'Nimal Perera',
            'නිමල් පෙරේරා',
            'நிமல் பெரேரா',
            'nimal@gn.gov.lk',
            $colombo,
            $homagama,
            '11',
            UserStatus::Active,
        );

        $this->createOfficer(
            '911112233V',
            'Kumari Silva',
            'කුමාරි සිල්වා',
            'குமாரி சில்வா',
            'kumari@gn.gov.lk',
            $colombo,
            $kaduwela,
            '17',
            UserStatus::PendingVerification,
        );
    }

    protected function createUser(
        string $name,
        string $email,
        string $password,
        UserRole $role,
        ?int $districtId,
        ?int $dsDivisionId,
    ): User {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'status' => UserStatus::Active,
                'district_id' => $districtId,
                'ds_division_id' => $dsDivisionId,
            ]
        );

        $user->syncRoles([$role->spatieRole()]);

        return $user;
    }

    protected function createOfficer(
        string $nic,
        string $nameEn,
        string $nameSi,
        string $nameTa,
        string $email,
        District $district,
        DsDivision $dsDivision,
        string $gnCode,
        UserStatus $status,
    ): Officer {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $nameEn,
                'password' => 'password',
                'nic_no' => $nic,
                'phone' => '0771234567',
                'role' => UserRole::Officer,
                'status' => $status,
                'district_id' => $district->id,
                'ds_division_id' => $dsDivision->id,
            ]
        );

        $user->syncRoles([UserRole::Officer->spatieRole()]);

        $gn = GnDivision::where('ds_division_id', $dsDivision->id)->where('code', $gnCode)->first();

        return Officer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nic_no' => $nic,
                'full_name_si' => $nameSi,
                'full_name_ta' => $nameTa,
                'full_name_en' => $nameEn,
                'dob' => '1990-05-15',
                'gender' => 'male',
                'medium' => 'sinhala',
                'address_line1' => 'No. 123, Main Street',
                'address_line2' => $dsDivision->name_en,
                'address_line3' => $district->name_en,
                'first_appointment_date' => '2016-08-01',
                'current_grade' => 'grade_iii',
                'current_district_id' => $district->id,
                'current_ds_division_id' => $dsDivision->id,
                'current_gn_division_id' => $gn?->id,
                'confirmation_status' => 'confirmed',
                'confirmation_date' => '2018-08-01',
                'appointment_date' => '2016-08-01',
                'service_status' => 'confirmed',
            ]
        );
    }
}
