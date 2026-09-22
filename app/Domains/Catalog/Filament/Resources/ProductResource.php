<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Resources;

use App\Domains\Catalog\Filament\Actions\ImportProductsAction;
use App\Domains\Catalog\Filament\Resources\ProductResource\Pages;
use App\Domains\Catalog\Imports\ProductImporter;
use App\Domains\Catalog\Jobs\GenerateProductEmbeddingJob;
use App\Domains\Catalog\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
            Grid::make(12)->schema([
                Section::make('Información del Producto')
                    ->description('Datos visibles para el cliente y analizados por la IA del bot.')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del producto')
                            ->placeholder('Ej: Auriculares Bluetooth XT-200')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('category')
                            ->label('Categoría')
                            ->placeholder('Ej: Audio, Calzado, Electrónica')
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Descripción detallada')
                            ->placeholder('Detalles, características, colores o talles. Mientras más descriptivo, más fácil será para la IA encontrarlo.')
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpan(['lg' => 8]),

                Grid::make(1)->schema([
                    Section::make('Precio e Inventario')
                        ->icon('heroicon-m-banknotes')
                        ->schema([
                            TextInput::make('price')
                                ->label('Precio')
                                ->numeric()
                                ->prefix('Gs.')
                                ->placeholder('150000')
                                ->required(),

                            TextInput::make('stock')
                                ->label('Stock disponible')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ]),

                    Section::make('Multimedia')
                        ->icon('heroicon-m-photo')
                        ->schema([
                            FileUpload::make('image_path')
                                ->label('Foto del producto')
                                ->helperText('Esta imagen se enviará al cliente por WhatsApp.')
                                ->image()
                                ->imageEditor()
                                ->disk('public')
                                ->directory('products'),
                        ]),
                ])->columnSpan(['lg' => 4]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(asset('favicon.svg')),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->weight('medium')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): ?string => $record->description ? Str::limit($record->description, 50) : null),

                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state): string => 'Gs. ' . number_format((float) $state, 0, ',', '.'))
                    ->weight('semibold')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? 'Agotado' : "{$state} un.")
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('embedding')
                    ->label('Estado IA')
                    ->state(fn (Product $record): string => $record->embedding ? 'Sincronizado' : 'Pendiente')
                    ->badge()
                    ->icon(fn (Product $record): string => $record->embedding ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-path')
                    ->color(fn (Product $record): string => $record->embedding ? 'success' : 'warning')
                    ->alignCenter(),
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
                    ->label('Importar catálogo')
                    ->color('gray')
                    ->icon('heroicon-m-arrow-up-tray'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('resyncWithBot')
                        ->label('Sincronizar con el bot')
                        ->icon('heroicon-m-arrow-path')
                        ->color('gray')
                        ->action(fn (Product $record) => GenerateProductEmbeddingJob::dispatch($record)),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-horizontal')
                ->tooltip('Acciones'),
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
