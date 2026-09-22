<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources;

use App\Domains\Catalog\Filament\Actions\ImportProductsAction;
use App\Domains\Catalog\Filament\Resources\ProductResource\Pages;
use App\Domains\Catalog\Imports\ProductImporter;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
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
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catálogo';

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

                TextInputColumn::make('price')
                    ->label('Precio')
                    ->type('number')
                    ->rules(['required', 'numeric', 'min:0'])
                    ->sortable(),

                TextInputColumn::make('stock')
                    ->label('Stock')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->sortable(),

                TextColumn::make('embedding')
                    ->label('Disponible en el bot')
                    ->state(fn (Product $record): string => $record->embedding ? 'Sí' : 'Actualizando…')
                    ->badge()
                    ->color(fn (Product $record): string => $record->embedding ? 'success' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(fn (): array => Product::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
            ])
            ->headerActions([
                ImportProductsAction::make()
                    ->importer(ProductImporter::class)
                    ->label('Importar catálogo'),
            ])
            ->actions([
                Action::make('resyncWithBot')
                    ->label('Sincronizar con el bot')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn (Product $record) => GenerateProductEmbeddingJob::dispatch($record)),
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
