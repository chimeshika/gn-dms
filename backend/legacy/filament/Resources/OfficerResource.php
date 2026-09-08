<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Enums\ConfirmationStatus;
use App\Enums\Gender;
use App\Enums\Medium;
use App\Enums\OfficerGrade;
use App\Enums\ServiceStatus;
use App\Enums\UserStatus;
use App\Filament\Resources\OfficerResource\Pages;
use App\Models\Officer;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficerResource extends Resource
{
    protected static ?string $model = Officer::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';

    protected static string | UnitEnum | null $navigationGroup = 'Personnel';

    /**
     * Scope records to the authenticated user's jurisdiction.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['district', 'dsDivision', 'gnDivision', 'user']);

        $user = auth()->user();

        if ($user?->isDivisionalAdmin() && $user->ds_division_id) {
            $query->where('current_ds_division_id', $user->ds_division_id);
        } elseif ($user?->isDistrictAdmin() && $user->district_id) {
            $query->where('current_district_id', $user->district_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->components([
                TextInput::make('nic_no')->required()->maxLength(15)->disabled(fn (string $operation) => $operation === 'edit'),
                TextInput::make('full_name_en')->required()->maxLength(150),
                TextInput::make('full_name_si')->maxLength(150),
                TextInput::make('full_name_ta')->maxLength(150),
                DatePicker::make('dob')->required(),
                DatePicker::make('first_appointment_date'),
                Select::make('gender')->options(collect(Gender::cases())->mapWithKeys(fn ($g) => [$g->value => $g->label()]))->required(),
                Select::make('medium')->options(collect(Medium::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()]))->required(),
                Select::make('current_grade')
                    ->options(collect(OfficerGrade::cases())->mapWithKeys(fn ($g) => [$g->value => $g->label()]))
                    ->required(),
                TextInput::make('address_line1')->maxLength(255),
                TextInput::make('address_line2')->maxLength(255),
                TextInput::make('address_line3')->maxLength(255),
                Select::make('current_district_id')
                    ->label('Current District')
                    ->relationship('district', 'name_en')
                    ->searchable()
                    ->preload(),
                Select::make('current_ds_division_id')
                    ->label('Current DS Division')
                    ->relationship('dsDivision', 'name_en')
                    ->searchable()
                    ->preload(),
                Select::make('current_gn_division_id')
                    ->label('Current GN Division')
                    ->relationship('gnDivision', 'name_en')
                    ->searchable()
                    ->preload(),
                Select::make('service_status')
                    ->options(collect(ServiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
                Select::make('confirmation_status')
                    ->options(collect(ConfirmationStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
                DatePicker::make('confirmation_date'),
                DatePicker::make('appointment_date'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nic_no')->searchable()->sortable(),
                TextColumn::make('full_name_en')->label('Name (English)')->searchable()->sortable(),
                TextColumn::make('current_grade')->label('Grade')->formatStateUsing(fn ($state) => $state instanceof \App\Enums\OfficerGrade? $state->label(): (\App\Enums\OfficerGrade::tryFrom($state)?->label() ?? $state ?? '-')),
                TextColumn::make('dsDivision.name_en')->label('DS Division')->searchable(),
                TextColumn::make('district.name_en')->label('District')->searchable(),
                TextColumn::make('user.status')
                    ->label('Account Status')
                    ->badge()
                    ->color(fn (UserStatus $state) => match ($state) {
                        UserStatus::Active => 'success',
                        UserStatus::PendingVerification => 'warning',
                        UserStatus::Inactive => 'danger',
                    })
                    ->formatStateUsing(fn (UserStatus $state) => $state->label()),
                TextColumn::make('service_status')
    ->badge()
    ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\ServiceStatus 
        ? $state->label() 
        : (\App\Enums\ServiceStatus::tryFrom($state)?->label() ?? $state ?? '-')),
            ])
            ->filters([
                SelectFilter::make('service_status')
                    ->options(collect(ServiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('current_ds_division_id')
                    ->label('DS Division')
                    ->relationship('dsDivision', 'name_en'),
                SelectFilter::make('user_status')
                    ->label('Account Status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return;
                        }

                        $query->whereHas('user', fn (Builder $q) => $q->where('status', $value));
                    }),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Approve & Activate')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Officer $record) => $record->user?->status === UserStatus::PendingVerification)
                    ->requiresConfirmation()
                    ->action(function (Officer $record) {
                        $record->user?->update(['status' => UserStatus::Active]);
                        $record->user?->markEmailAsVerified();

                        Notification::make()->title('Officer verified and activated')->success()->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfficers::route('/'),
            'create' => Pages\CreateOfficer::route('/create'),
            'edit' => Pages\EditOfficer::route('/{record}/edit'),
        ];
    }
}
