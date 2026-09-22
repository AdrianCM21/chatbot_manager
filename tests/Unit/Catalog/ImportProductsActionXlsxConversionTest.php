<?php

declare(strict_types=1);

use App\Domains\Catalog\Filament\Actions\ImportProductsAction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Filament trae un solo pipeline de import, pensado para CSV. Como el
 * catálogo se sube en XLSX, ImportProductsAction::getUploadedFileStream()
 * convierte el XLSX a CSV en memoria antes de que ese pipeline lo procese.
 * Este test verifica esa conversión de forma aislada, sin pasar por el
 * modal de Livewire.
 *
 * Requiere la extensión ext-zip (OpenSpout la necesita para leer/escribir XLSX).
 */
beforeEach(function () {
    Storage::fake(FileUploadConfiguration::disk());
});

it('convierte el xlsx a un csv con las mismas columnas y filas', function () {
    $file = fakeTemporaryUploadedFile('catalog-sample.xlsx', 'catalog-sample.xlsx');

    $stream = ImportProductsAction::make()->getUploadedFileStream($file);

    expect($stream)->not->toBeFalse();

    $csv = CsvReader::createFromStream($stream);
    $csv->setHeaderOffset(0);

    expect($csv->getHeader())->toBe(['Producto', 'Rubro', 'Valor', 'Existencia', 'Detalle']);

    $records = [...$csv->getRecords()];

    expect($records)->toHaveCount(4)
        ->and($records[0]['Producto'])->toBe('Auriculares Bluetooth XT-200')
        ->and($records[0]['Valor'])->toBe('185000')
        ->and($records[2]['Producto'])->toBe('')
        ->and($records[3]['Valor'])->toBe('no-es-un-numero');
});

it('deja pasar un csv normal sin tocarlo', function () {
    $disk = FileUploadConfiguration::disk();
    $meta = str_replace('/', '_', base64_encode('catalog.csv'));
    $physicalName = Str::random(20).'-meta'.$meta.'-.csv';

    // OJO: createFromLivewire() ya antepone FileUploadConfiguration::directory()
    // al nombre que le pasemos, así que acá hay que guardar el archivo en esa
    // ruta completa pero pasarle a createFromLivewire() solo el nombre pelado.
    Storage::disk($disk)->put(
        FileUploadConfiguration::path($physicalName),
        "Producto,Rubro,Valor,Existencia,Detalle\nMouse,Electrónica,65000,50,Mouse inalámbrico\n"
    );

    $file = TemporaryUploadedFile::createFromLivewire($physicalName);

    $stream = ImportProductsAction::make()->getUploadedFileStream($file);

    $csv = CsvReader::createFromStream($stream);
    $csv->setHeaderOffset(0);

    expect($csv->getHeader())->toBe(['Producto', 'Rubro', 'Valor', 'Existencia', 'Detalle']);
});
