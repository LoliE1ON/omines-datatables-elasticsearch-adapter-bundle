<?php

declare(strict_types=1);

namespace E1on\OminesDatatablesElasticsearchAdapterBundle\Enum;

enum DataTableSessionEnum: string
{
    case CURRENT_DOCUMENT = 'currentDocument';
    case PREVIOUS_DOCUMENT = 'previousDocument';

    case BETWEEN_DOCUMENT = 'betweenDocument';

    case FROM = 'from';

    public static function getForm(string $name): string
    {
        return sprintf('%s_%s', $name, self::FROM->value);
    }

    public static function getCurrentDocument(string $name): string
    {
        return sprintf('%s_%s', $name, self::CURRENT_DOCUMENT->value);
    }

    public static function getPreviousDocument(string $name): string
    {
        return sprintf('%s_%s', $name, self::PREVIOUS_DOCUMENT->value);
    }

    public static function getBetweenDocument(string $name): string
    {
        return sprintf('%s_%s', $name, self::BETWEEN_DOCUMENT->value);
    }
}
