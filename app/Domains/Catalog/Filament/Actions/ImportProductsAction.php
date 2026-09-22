<?php

declare(strict_types=1);

namespace App\Domains\Catalog\Filament\Actions;

use Filament\Actions\Imports\ImportColumn;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Common\Entity\Row as SpreadsheetRow;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;

/**
 * El importador nativo de Filament solo lee CSV. Como el catálogo se sube en
 * XLSX, esta acción reutiliza el mismo modal/columna-mapping/cola de Filament,
 * pero convierte el XLSX a CSV en memoria antes de que el pipeline lo procese
 * (ver getUploadedFileStream()). Es la única parte no nativa de esta pantalla.
 *
 * Extiende Filament\Tables\Actions\ImportAction (no la genérica
 * Filament\Actions\ImportAction) porque se registra como header action de una
 * Table, que necesita el método table() que solo trae la variante de Tables.
 */
class ImportProductsAction extends ImportAction
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reabrimos el campo de archivo solo para admitir también XLSX
        // (el trait base de Filament lo deja fijo en CSV).
        $this->form(fn (self $action): array => array_merge([
            FileUpload::make('file')
                ->label('Archivo del catálogo')
                ->placeholder('Arrastrá tu archivo .xlsx acá o hacé clic para elegirlo')
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                    'text/csv',
                    'text/plain',
                ])
                ->rules($action->getFileValidationRules())
                ->afterStateUpdated(function (FileUpload $component, Component $livewire, Forms\Set $set, ?TemporaryUploadedFile $state) use ($action) {
                    if (! $state instanceof TemporaryUploadedFile) {
                        return;
                    }

                    $livewire->validateOnly($component->getStatePath());

                    $csvStream = $this->getUploadedFileStream($state);

                    if (! $csvStream) {
                        return;
                    }

                    $csvReader = CsvReader::createFromStream($csvStream);
                    $csvReader->setHeaderOffset(0);
                    $csvColumns = $csvReader->getHeader();

                    $lowercaseCsvColumnValues = array_map(Str::lower(...), $csvColumns);
                    $lowercaseCsvColumnKeys = array_combine($lowercaseCsvColumnValues, $csvColumns);

                    $set('columnMap', array_reduce($action->getImporter()::getColumns(), function (array $carry, ImportColumn $column) use ($lowercaseCsvColumnKeys, $lowercaseCsvColumnValues) {
                        $carry[$column->getName()] = $lowercaseCsvColumnKeys[
                            Arr::first(array_intersect($lowercaseCsvColumnValues, $column->getGuesses()))
                        ] ?? null;

                        return $carry;
                    }, []));
                })
                ->storeFiles(false)
                ->visibility('private')
                ->required()
                ->hiddenLabel(),
            Fieldset::make('Mapeo de columnas')
                ->columns(1)
                ->inlineLabel()
                ->schema(function (Forms\Get $get) use ($action): array {
                    $file = Arr::first((array) ($get('file') ?? []));

                    if (! $file instanceof TemporaryUploadedFile) {
                        return [];
                    }

                    $csvStream = $this->getUploadedFileStream($file);

                    if (! $csvStream) {
                        return [];
                    }

                    $csvReader = CsvReader::createFromStream($csvStream);
                    $csvReader->setHeaderOffset(0);
                    $csvColumns = $csvReader->getHeader();
                    $csvColumnOptions = array_combine($csvColumns, $csvColumns);

                    return array_map(
                        fn (ImportColumn $column) => $column->getSelect()->options($csvColumnOptions),
                        $action->getImporter()::getColumns(),
                    );
                })
                ->statePath('columnMap')
                ->visible(fn (Forms\Get $get): bool => Arr::first((array) ($get('file') ?? [])) instanceof TemporaryUploadedFile),
        ], $action->getImporter()::getOptionsFormComponents()));
    }

    public function getUploadedFileStream(TemporaryUploadedFile $file)
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            return parent::getUploadedFileStream($file);
        }

        $csvPath = tempnam(sys_get_temp_dir(), 'catalog_import_').'.csv';

        $xlsxReader = new XlsxReader;
        $xlsxReader->open($file->getRealPath());

        $csvWriter = new CsvWriter;
        $csvWriter->openToFile($csvPath);

        foreach ($xlsxReader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $csvWriter->addRow(SpreadsheetRow::fromValues($row->toArray()));
            }

            break;
        }

        $csvWriter->close();
        $xlsxReader->close();

        return fopen($csvPath, 'r');
    }

    public function getFileValidationRules(): array
    {
        return ['extensions:xlsx,xls,csv,txt'];
    }
}
