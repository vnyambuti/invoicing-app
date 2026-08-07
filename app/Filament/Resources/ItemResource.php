<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemResource\Pages;
use App\Filament\Resources\ItemResource\RelationManagers;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('item_no')
                ->label('Item No.')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(30),

            Forms\Components\TextInput::make('description')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('uom_code')
                ->label('UoM Code')
                ->maxLength(20),

            Forms\Components\TextInput::make('unit_price')
                ->numeric()
                ->step(0.001)
                ->required()
                ->prefix('KES'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_no')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('description')->searchable(),
                Tables\Columns\TextColumn::make('uom_code')->label('UoM'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('KES', divideBy: 1)
                    ->sortable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit'   => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
