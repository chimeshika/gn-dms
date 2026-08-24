<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | UnitEnum | null $navigationGroup = 'Administration';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['district', 'dsDivision']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->components([
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('nic_no')->maxLength(15)->unique(ignoreRecord: true),
                TextInput::make('phone')->maxLength(15)->tel(),
                Select::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                    ->required(),
                Select::make('status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(UserStatus::PendingVerification->value)
                    ->required(),
                Select::make('district_id')
                    ->relationship('district', 'name_en')
                    ->searchable()
                    ->preload(),
                Select::make('ds_division_id')
                    ->relationship('dsDivision', 'name_en')
                    ->searchable()
                    ->preload(),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state)),
                DatePicker::make('email_verified_at'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('nic_no')->searchable()->toggleable(),
                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => $state->label()),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (UserStatus $state) => match ($state) {
                        UserStatus::Active => 'success',
                        UserStatus::PendingVerification => 'warning',
                        UserStatus::Inactive => 'danger',
                    })
                    ->formatStateUsing(fn (UserStatus $state) => $state->label()),
                TextColumn::make('district.name_en')->label('District')->toggleable(),
                TextColumn::make('dsDivision.name_en')->label('DS Division')->toggleable(),
                TextColumn::make('created_at')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])),
                SelectFilter::make('status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('toggle_status')
                    ->label(fn (User $record) => $record->status === UserStatus::Active ? 'Deactivate' : 'Activate')
                    ->icon(fn (User $record) => $record->status === UserStatus::Active ? 'heroicon-o-stop' : 'heroicon-o-play')
                    ->visible(fn (User $record) => $record->status !== UserStatus::PendingVerification)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'status' => $record->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
                        ]);

                        Notification::make()->title('User status updated')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
