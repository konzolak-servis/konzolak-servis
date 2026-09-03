<?php

namespace App\Filament\Resources\Fakturas\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PolozkyRelationManager extends RelationManager
{
    protected static string $relationship = 'polozky';

    protected static ?string $title = 'Řádky faktury';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('zarizeni_text')->label('Zařízení')->maxLength(255),
                TextInput::make('popis')->label('Popis práce')->required()->maxLength(255)->columnSpanFull(),
                TextInput::make('mnozstvi')->label('Množství')->numeric()->default(1)->required(),
                TextInput::make('cena')->label('Cena / ks')->numeric()->default(0)->required()->suffix('Kč'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('popis')
            ->columns([
                TextColumn::make('zarizeni_text')->label('Zařízení'),
                TextColumn::make('popis')->label('Popis')->wrap(),
                TextColumn::make('mnozstvi')->label('Množ.')->numeric(),
                TextColumn::make('cena')->label('Cena/ks')->money('CZK'),
                TextColumn::make('cena_celkem')->label('Celkem')->money('CZK')
                    ->summarize(Sum::make()->label('Celkem faktura')->money('CZK')),
            ])
            ->headerActions([
                CreateAction::make()->label('Přidat řádek'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
