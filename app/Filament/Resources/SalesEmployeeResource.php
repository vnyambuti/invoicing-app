<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesEmployeeResource\Pages;
use App\Filament\Resources\SalesEmployeeResource\RelationManagers;
use App\Models\SalesEmployee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesEmployeeResource extends Resource
{
    protected static ?string $model = SalesEmployee::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalesEmployees::route('/'),
            'create' => Pages\CreateSalesEmployee::route('/create'),
            'edit'   => Pages\EditSalesEmployee::route('/{record}/edit'),
        ];
    }
}
