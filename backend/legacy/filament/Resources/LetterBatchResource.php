<?php

namespace App\Filament\Resources;

use BackedEnum;
use UnitEnum;
use App\Enums\DocumentType;
use App\Enums\LetterStatus;
use App\Filament\Resources\LetterBatchResource\Pages;
use App\Models\LetterBatch;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LetterBatchResource extends Resource
{
    protected static ?string $model = LetterBatch::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static string | UnitEnum | null $navigationGroup = 'Correspondence';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->components([
                TextInput::make('name')->required()->maxLength(150),
                Select::make('document_type')
                    ->options(collect(DocumentType::cases())->mapWithKeys(fn ($d) => [$d->value => $d->label()]))
                    ->required(),
                TextInput::make('my_ref_no')->maxLength(100),
                TextInput::make('cabinet_app_no')->maxLength(100),
                DatePicker::make('cabinet_app_date'),
                DatePicker::make('exam_date'),
                DatePicker::make('probation_effective_date'),
                DatePicker::make('training_complete_date'),
                DatePicker::make('letter_date'),
                Select::make('status')
                    ->options(collect(LetterStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->default(LetterStatus::Draft->value ?? 'draft')
                    ->required(),
                Select::make('created_by')->relationship('creator', 'name')->default(fn () => auth()->id())->disabled(),
            ]),
            RichEditor::make('content_template')
                ->label('Letter Template (use placeholders such as {officer_name}, {nic_no}, {letter_date})')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('document_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof DocumentType 
                        ? $state->label() 
                        : (DocumentType::tryFrom($state)?->label() ?? $state ?? '-')),
                TextColumn::make('letters_count')->label('Letters')->counts('letters')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof LetterStatus 
                        ? $state->label() 
                        : (LetterStatus::tryFrom($state)?->label() ?? $state ?? '-')),
                TextColumn::make('letter_date')->date()->sortable(),
                TextColumn::make('creator.name')->label('Created By')->toggleable(),
                TextColumn::make('created_at')->dateTime('Y-m-d')->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options(collect(DocumentType::cases())->mapWithKeys(fn ($d) => [$d->value => $d->label()])),
                SelectFilter::make('status')
                    ->options(collect(LetterStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Manage Letters')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(fn (LetterBatch $record) => route('letters.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterBatches::route('/'),
            'create' => Pages\CreateLetterBatch::route('/create'),
            'edit' => Pages\EditLetterBatch::route('/{record}/edit'),
        ];
    }
}
