<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Enums\EventType;
use App\Filament\Resources\ServiceHistoryResource\Pages;
use App\Models\ServiceHistory;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceHistoryResource extends Resource
{
    protected static ?string $model = ServiceHistory::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | UnitEnum | null $navigationGroup = 'Personnel';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->components([
                Select::make('officer_id')
                    ->relationship('officer', 'full_name_en')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('event_type')
                    ->options(collect(EventType::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()]))
                    ->required(),
                TextInput::make('ref_no')->maxLength(100),
                DatePicker::make('effective_date')->required(),
                Textarea::make('description')->rows(3),
                Textarea::make('old_value')->rows(2),
                Textarea::make('new_value')->rows(2),
                Select::make('created_by')->relationship('creator', 'name')->default(fn () => auth()->id())->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('officer.full_name_en')->label('Officer')->searchable()->sortable(),
                TextColumn::make('event_type')->badge()->formatStateUsing(fn (EventType $state) => $state->label()),
                TextColumn::make('ref_no')->searchable()->toggleable(),
                TextColumn::make('description')->limit(60)->toggleable(),
                TextColumn::make('effective_date')->date()->sortable(),
                TextColumn::make('creator.name')->label('Created By')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options(collect(EventType::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])),
            ])
            ->defaultSort('effective_date', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceHistories::route('/'),
            'create' => Pages\CreateServiceHistory::route('/create'),
            'edit' => Pages\EditServiceHistory::route('/{record}/edit'),
        ];
    }
}
