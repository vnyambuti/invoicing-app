<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Customer Code')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(20),

            Forms\Components\TextInput::make('name')
                ->label('Customer Name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('contact_person')
                ->maxLength(255),

            Forms\Components\TextInput::make('bp_currency')
                ->label('BP Currency')
                ->default('KES')
                ->maxLength(3),

            Forms\Components\TextInput::make('kra_pin')
                ->label('KRA PIN')
                ->maxLength(20),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contact_person')->searchable(),
                Tables\Columns\TextColumn::make('bp_currency')->label('Currency'),
                Tables\Columns\TextColumn::make('kra_pin')->label('KRA PIN'),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
