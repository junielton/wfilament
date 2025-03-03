<?php

namespace App\Filament\Resources;

use App\Exports\StudentsExport;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\Classes;
use App\Models\Section;
use App\Models\Student;
use DeepCopy\Filter\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter as FiltersFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Builder;


class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academic Management';

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('class_id')
                    ->live()
                    ->relationship('class', 'name'),
                Select::make('section_id')
                    ->label('Section')
                    ->options(function (Get $get) {
                        $classId = $get('class_id');

                        if ($classId) {
                            return Section::where('class_id', $classId)
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    }),
                TextInput::make('name')
                    ->autofocus()
                    ->required(),
                TextInput::make('email')
                    ->unique(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email'),
                TextColumn::make('class.name')->label('Class')->badge(),
                TextColumn::make('section.name')->label('Section')->badge(),
            ])
            ->filters([
                FiltersFilter::make('class-section-filter')
                    ->form([
                        Select::make('class_id')
                            ->label('Filter by Class')
                            ->placeholder('Select a class')
                            ->options(
                                Classes::pluck('name', 'id')->toArray(),
                            ),
                        Select::make('section_id')
                            ->label('Filter by Section')
                            ->placeholder('Select a Section')
                            ->options(
                                function (Get $get) {
                                    $classId = $get('class_id');
                                    if ($classId) {
                                        return Section::where('class_id', $classId)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }
                                }

                            ),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['class_id'], function ($query) use ($data) {
                                $query->where('class_id', $data['class_id']);
                            })
                            ->when($data['section_id'], function ($query) use ($data) {
                                $query->where('section_id', $data['section_id']);
                            });
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('export')
                        ->label('Export')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (Collection $records) {
                            return Excel::download(new StudentsExport($records), 'students.xlsx');
                        })

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
