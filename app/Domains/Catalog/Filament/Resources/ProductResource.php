<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources;

use App\Domains\Catalog\Actions\GenerateProductEmbeddingAction;
use App\Domains\Catalog\Filament\Resources\ProductResource\Pages;
use App\Domains\Catalog\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            TextInput::make('category')
                ->label('Categoría')
                ->maxLength(255),

            Textarea::make('description')
                ->label('Descripción')
                ->rows(4)
                ->columnSpanFull(),

            TextInput::make('price')
                ->label('Precio')
                ->numeric()
                ->prefix('Gs.')
                ->required(),

            TextInput::make('stock')
                ->label('Stock')
                ->numeric()
                ->default(0)
                ->required(),

            FileUpload::make('image_path')
                ->label('Foto')
                ->image()
                ->disk('public')
                ->directory('products')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('PYG')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable(),

                TextColumn::make('embedding')
                    ->label('Embedding')
                    ->state(fn (Product $record): string => $record->embedding ? 'Generado' : 'Pendiente')
                    ->badge()
                    ->color(fn (Product $record): string => $record->embedding ? 'success' : 'warning'),
            ])
            ->filters([])
            ->actions([
                Action::make('regenerateEmbedding')
                    ->label('Regenerar embedding')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn (Product $record) => app(GenerateProductEmbeddingAction::class)->execute($record)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
