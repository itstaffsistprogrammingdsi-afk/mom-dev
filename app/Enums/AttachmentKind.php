<?php

namespace App\Enums;

enum AttachmentKind: string
{
    case File = 'file';
    case Link = 'link';
    case Recording = 'recording';

    public function label(): string
    {
        return match ($this) {
            self::File => 'Berkas',
            self::Link => 'Tautan',
            self::Recording => 'Rekaman',
        };
    }
}
