<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Filament\Resources\SignatoryResource\Pages;
use App\Enums\SignatoryCategory;
use App\Models\Signatory;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SignatoryResource extends Resource
{
    protected static ?string $model = Signatory::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | UnitEnum | null $navigationGroup = 'Administration';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->components([
                Select::make('category')
                    ->options(collect(SignatoryCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->default(SignatoryCategory::Other->value),
                TextInput::make('designation')->required()->maxLength(150),
                TextInput::make('officer_name')->required()->maxLength(150),
                Select::make('district_id')
                    ->label('District (jurisdiction)')
                    ->relationship('district', 'name_en')
                    ->searchable()
                    ->preload(),
                Select::make('ds_division_id')
                    ->label('DS Division (jurisdiction)')
                    ->relationship('dsDivision', 'name_en')
                    ->searchable()
                    ->preload(),
                FileUpload::make('digital_signature_path')
                    ->label('Digital Signature Image')
                    ->image()
                    ->directory('signatures')
                    ->imageEditor(),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')->label('Role')->formatStateUsing(fn ($state) => $state instanceof SignatoryCategory ? $state->label() : $state),
                TextColumn::make('designation')->searchable()->sortable(),
                TextColumn::make('officer_name')->searchable()->sortable(),
                TextColumn::make('jurisdiction_label')->label('Jurisdiction'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Role')->options(collect(SignatoryCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                SelectFilter::make('district_id')->label('District')->relationship('district', 'name_en'),
                SelectFilter::make('is_active')->options([true => 'Active', false => 'Inactive']),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignatories::route('/'),
            'create' => Pages\CreateSignatory::route('/create'),
            'edit' => Pages\EditSignatory::route('/{record}/edit'),
        ];
    }
}
