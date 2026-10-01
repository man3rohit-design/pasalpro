<?php

declare(strict_types=1);

namespace App\Filament\Resources\LoginHistories\Pages;

use App\Filament\Resources\LoginHistories\LoginHistoryResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLoginHistories extends ManageRecords
{
    protected static string $resource = LoginHistoryResource::class;
}
